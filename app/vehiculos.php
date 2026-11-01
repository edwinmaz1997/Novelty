<?php
/**
 * Acceso a datos de vehículos: búsqueda, filtros y utilidades.
 */
const VEH_SELECT = "SELECT v.*, ma.nombre AS marca, ma.slug AS marca_slug, mo.nombre AS modelo,
    (SELECT f.archivo FROM vehiculo_fotos f WHERE f.vehiculo_id = v.id ORDER BY f.orden, f.id LIMIT 1) AS portada,
    (SELECT COUNT(*) FROM vehiculo_fotos f2 WHERE f2.vehiculo_id = v.id) AS num_fotos
  FROM vehiculos v
  JOIN marcas ma ON ma.id = v.marca_id
  JOIN modelos mo ON mo.id = v.modelo_id";

const ESTADOS_PUBLICOS = "('disponible','reservado')";

const STOPWORDS = ['de', 'del', 'el', 'la', 'los', 'las', 'en', 'y', 'con', 'color', 'un', 'una', 'auto', 'autos',
    'carro', 'carros', 'vehiculo', 'vehiculos', 'para', 'por', 'q'];

/** Limpia los filtros recibidos por GET. */
function vehiculos_filtros(array $s): array
{
    $f = [];
    $f['q'] = (isset($s['q']) && is_string($s['q'])) ? trim(mb_substr($s['q'], 0, 100)) : '';
    foreach (['marca', 'modelo', 'anio_min', 'anio_max', 'km_min', 'km_max', 'precio_min', 'precio_max', 'destacado'] as $k) {
        $v = $s[$k] ?? null;
        $f[$k] = (is_string($v) || is_int($v)) && $v !== '' && is_numeric($v) ? (int)$v : null;
    }
    $proc = $s['procedencia'] ?? '';
    $f['procedencia'] = (is_string($proc) && isset(PROCEDENCIAS[$proc])) ? $proc : '';
    $ord = $s['orden'] ?? '';
    $f['orden'] = (is_string($ord) && (isset(ORDENES[$ord]) || $ord === 'dias_desc')) ? $ord : 'relevantes';
    $est = $s['estado'] ?? '';
    $f['estado'] = is_string($est) ? $est : '';
    $ti = $s['tipo_ingreso'] ?? '';
    $f['tipo_ingreso'] = (is_string($ti) && isset(TIPOS_INGRESO[$ti])) ? $ti : '';
    $f['sin_fotos'] = !empty($s['sin_fotos']);
    return $f;
}

/** Divide la búsqueda libre en palabras normalizadas. */
function tokens_busqueda(string $q): array
{
    $q = norm(str_replace(',', ' ', $q));
    if ($q === '') {
        return [];
    }
    $out = [];
    foreach (explode(' ', $q) as $t) {
        $t = trim($t, '.-');
        if ($t === '' || in_array($t, STOPWORDS, true)) {
            continue;
        }
        $out[$t] = true;
    }
    return array_slice(array_keys($out), 0, 8);
}

/** Raíz simple para plurales/género: rojos→roj, blancas→blanc. */
function raiz_token(string $t): ?string
{
    if (!preg_match('/^[a-z]{4,}$/', $t)) {
        return null;
    }
    $r = preg_replace('/s$/', '', $t);
    $r = preg_replace('/(es|[ao])$/', '', $r);
    return (strlen($r) >= 3 && $r !== $t) ? $r : null;
}

function vehiculos_where(array $f, bool $publico): array
{
    $w = [];
    $p = [];
    if ($publico) {
        $w[] = 'v.estado IN ' . ESTADOS_PUBLICOS;
    } else {
        switch ($f['estado']) {
            case '':
            case 'predio':
                $w[] = "v.estado IN ('disponible','reservado','oculto')";
                break;
            case 'todos':
                break;
            default:
                if (isset(ESTADOS[$f['estado']])) {
                    $w[] = 'v.estado = ?';
                    $p[] = $f['estado'];
                }
        }
        if (!empty($f['sin_fotos'])) {
            $w[] = 'NOT EXISTS (SELECT 1 FROM vehiculo_fotos fx WHERE fx.vehiculo_id = v.id)';
        }
        if ($f['tipo_ingreso'] !== '') {
            $w[] = 'v.tipo_ingreso = ?';
            $p[] = $f['tipo_ingreso'];
        }
    }
    $map = [
        'marca'      => 'v.marca_id = ?',
        'modelo'     => 'v.modelo_id = ?',
        'anio_min'   => 'v.anio >= ?',
        'anio_max'   => 'v.anio <= ?',
        'km_min'     => 'v.kilometraje >= ?',
        'km_max'     => 'v.kilometraje <= ?',
        'precio_min' => 'v.precio_publicado >= ?',
        'precio_max' => 'v.precio_publicado <= ?',
    ];
    foreach ($map as $k => $sql) {
        if ($f[$k] !== null && $f[$k] !== 0) {
            $w[] = $sql;
            $p[] = $f[$k];
        }
    }
    if ($f['procedencia'] !== '') {
        $w[] = 'v.procedencia = ?';
        $p[] = $f['procedencia'];
    }
    if (!empty($f['destacado'])) {
        $w[] = 'v.destacado = 1';
    }
    foreach (tokens_busqueda($f['q']) as $t) {
        $r = raiz_token($t);
        if ($r) {
            $w[] = '(v.buscar LIKE ? OR v.buscar LIKE ?)';
            $p[] = '%' . $t . '%';
            $p[] = '%' . $r . '%';
        } else {
            $w[] = 'v.buscar LIKE ?';
            $p[] = '%' . $t . '%';
        }
    }
    return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
}

function orden_sql(string $o): string
{
    switch ($o) {
        case 'recientes':
            return 'v.fecha_ingreso DESC, v.id DESC';
        case 'precio_asc':
            return 'v.precio_publicado ASC, v.id DESC';
        case 'precio_desc':
            return 'v.precio_publicado DESC, v.id DESC';
        case 'km_asc':
            return 'v.kilometraje ASC, v.id DESC';
        case 'anio_desc':
            return 'v.anio DESC, v.id DESC';
        case 'dias_desc':
            return 'v.fecha_ingreso ASC, v.id ASC';
        default:
            return "v.destacado DESC, (v.estado = 'reservado') ASC, v.fecha_ingreso DESC, v.id DESC";
    }
}

function vehiculos_buscar(array $f, bool $publico, int $page = 1, int $per = 12): array
{
    $f = array_merge(vehiculos_filtros([]), $f);
    list($where, $p) = vehiculos_where($f, $publico);
    $total = (int)q_val("SELECT COUNT(*) FROM vehiculos v $where", $p);
    $page = max(1, $page);
    $off = ($page - 1) * $per;
    $items = q_all(VEH_SELECT . " $where ORDER BY " . orden_sql($f['orden']) . ' LIMIT ' . (int)$per . ' OFFSET ' . (int)$off, $p);
    return [
        'items'    => $items,
        'total'    => $total,
        'page'     => $page,
        'per'      => $per,
        'has_more' => ($off + count($items)) < $total,
    ];
}

function vehiculo(int $id, bool $publico = false): ?array
{
    $sql = VEH_SELECT . ' WHERE v.id = ?' . ($publico ? ' AND v.estado IN ' . ESTADOS_PUBLICOS : '');
    return q_one($sql, [$id]);
}

function vehiculo_fotos(int $id): array
{
    return q_all('SELECT id, archivo, orden FROM vehiculo_fotos WHERE vehiculo_id = ? ORDER BY orden, id', [$id]);
}

function codigo_vehiculo(int $id): string
{
    return 'NV-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

function vehiculo_titulo(array $v): string
{
    return trim($v['marca'] . ' ' . $v['modelo']);
}

function vehiculo_url(array $v): string
{
    return url('auto/' . $v['id'] . '-' . slugify($v['marca'] . ' ' . $v['modelo'] . ' ' . $v['anio']));
}

function vehiculo_wa_mensaje(array $v): string
{
    return 'Hola, me interesa el ' . vehiculo_titulo($v) . ' ' . $v['anio'] . ' (código ' . $v['codigo'] . '): ' . abs_url('auto/' . $v['id']);
}

function dias_predio(array $v): int
{
    $fin = !empty($v['fecha_venta']) ? strtotime($v['fecha_venta']) : strtotime(date('Y-m-d'));
    return max(0, (int)floor(($fin - strtotime($v['fecha_ingreso'])) / 86400));
}

/** Ganancia real (vendido) o esperada (en predio). */
function vehiculo_ganancia(array $v): ?float
{
    $precio = $v['estado'] === 'vendido' ? $v['precio_venta_final'] : $v['precio_publicado'];
    if ($precio === null) {
        return null;
    }
    return (float)$precio - (float)($v['precio_propietario'] ?? 0) - (float)($v['gastos'] ?? 0);
}

/** Texto plano que alimenta la búsqueda general (incluye sinónimos). */
function vehiculo_texto_busqueda(array $v): string
{
    $partes = [$v['marca'], $v['modelo'], $v['version'], $v['anio'], $v['color'], $v['codigo'], $v['motor'], $v['carroceria'], $v['traccion']];
    $partes[] = str_replace(',', ' ', (string)$v['etiquetas']);
    $sin = [
        'agencia'    => 'agencia de agencia',
        'rodado'     => 'rodado importado',
        'automatica' => 'automatica automatico',
        'manual'     => 'manual mecanico estandar',
        'diesel'     => 'diesel',
        'gasolina'   => 'gasolina',
        'hibrido'    => 'hibrido',
        'electrico'  => 'electrico',
        'SUV'        => 'camioneta suv',
        'Pick-up'    => 'pickup picop palangana',
        'Coupé'      => 'deportivo',
        'Microbús'   => 'microbus bus',
        'Van'        => 'panel',
        '4x4'        => '4wd doble traccion',
        'AWD'        => '4x4 doble traccion',
    ];
    foreach (['procedencia', 'transmision', 'combustible', 'carroceria', 'traccion'] as $k) {
        if (!empty($v[$k]) && isset($sin[$v[$k]])) {
            $partes[] = $sin[$v[$k]];
        }
    }
    $partes = array_filter($partes, function ($x) {
        return $x !== null && $x !== '';
    });
    return norm(implode(' ', $partes));
}

function vehiculo_actualizar_buscar(int $id): void
{
    $v = vehiculo($id);
    if ($v) {
        q('UPDATE vehiculos SET buscar = ? WHERE id = ?', [vehiculo_texto_busqueda($v), $id]);
    }
}

/* ---------- Facetas públicas ---------- */
function facetas_marcas(): array
{
    return q_all('SELECT ma.id, ma.nombre, ma.slug, COUNT(v.id) AS total
        FROM marcas ma JOIN vehiculos v ON v.marca_id = ma.id AND v.estado IN ' . ESTADOS_PUBLICOS . '
        GROUP BY ma.id, ma.nombre, ma.slug ORDER BY ma.nombre');
}

function facetas_modelos(int $marca): array
{
    return q_all('SELECT mo.id, mo.nombre, COUNT(v.id) AS total
        FROM modelos mo JOIN vehiculos v ON v.modelo_id = mo.id AND v.estado IN ' . ESTADOS_PUBLICOS . '
        WHERE mo.marca_id = ? GROUP BY mo.id, mo.nombre ORDER BY mo.nombre', [$marca]);
}

function facetas_anios(): array
{
    $r = q_one('SELECT MIN(anio) mi, MAX(anio) ma FROM vehiculos WHERE estado IN ' . ESTADOS_PUBLICOS);
    $max = (int)($r['ma'] ?? 0) ?: (int)date('Y') + 1;
    $min = (int)($r['mi'] ?? 0) ?: $max - 20;
    return range($max, $min);
}

function facetas_procedencia(): array
{
    $out = ['agencia' => 0, 'rodado' => 0];
    foreach (q_all('SELECT procedencia, COUNT(*) t FROM vehiculos WHERE estado IN ' . ESTADOS_PUBLICOS . ' GROUP BY procedencia') as $r) {
        $out[$r['procedencia']] = (int)$r['t'];
    }
    return $out;
}

/* ---------- Etiquetas visuales ---------- */
function badge_procedencia(string $p, string $extra = ''): string
{
    $cls = $p === 'agencia'
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
        : 'bg-sky-50 text-sky-700 ring-sky-600/20';
    return '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide ring-1 ring-inset ' . $cls . ' ' . $extra . '">' . e(PROCEDENCIAS[$p] ?? $p) . '</span>';
}

function badge_estado(string $s): string
{
    $map = [
        'disponible' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'reservado'  => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'oculto'     => 'bg-ink-100 text-ink-600 ring-ink-500/20',
        'vendido'    => 'bg-brand-50 text-brand-700 ring-brand-600/20',
    ];
    return '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ' . ($map[$s] ?? '') . '">' . e(ESTADOS[$s] ?? $s) . '</span>';
}

function badge_dias(int $d): string
{
    if ($d <= 30) {
        $c = 'bg-emerald-50 text-emerald-700';
    } elseif ($d <= 60) {
        $c = 'bg-amber-50 text-amber-700';
    } elseif ($d <= 90) {
        $c = 'bg-orange-100 text-orange-700';
    } else {
        $c = 'bg-red-50 text-red-700';
    }
    return '<span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold tabular-nums ' . $c . '">' . $d . ' d</span>';
}

/** Borra vehículo y su carpeta de fotos. */
function vehiculo_eliminar(int $id): void
{
    q('DELETE FROM vehiculos WHERE id = ?', [$id]);
    $dir = ROOT . '/uploads/vehiculos/' . $id;
    if (is_dir($dir)) {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }
}

/* ---------- Etiquetas ---------- */
function etiquetas_lista(?string $txt): array
{
    $out = [];
    foreach (explode(',', (string)$txt) as $t) {
        $t = trim(preg_replace('/\s+/', ' ', $t));
        if ($t !== '' && !in_array(mb_strtolower($t), array_map('mb_strtolower', $out), true)) {
            $out[] = mb_substr($t, 0, 60);
        }
    }
    return $out;
}

function etiquetas_catalogo(): array
{
    try {
        return q_all('SELECT * FROM etiquetas ORDER BY orden, nombre');
    } catch (Throwable $e) {
        return [];
    }
}

/** Registra en el catálogo las etiquetas nuevas que se escribieron a mano. */
function etiquetas_registrar(array $lista): void
{
    $st = db()->prepare('INSERT IGNORE INTO etiquetas (nombre, orden) VALUES (?, 100)');
    foreach ($lista as $t) {
        $st->execute([$t]);
    }
}

/** Renombra (o quita si $nueva es null) una etiqueta en todos los vehículos. Devuelve cuántos cambió. */
function etiquetas_reemplazar_en_vehiculos(string $vieja, ?string $nueva): int
{
    $n = 0;
    $vk = mb_strtolower($vieja);
    foreach (q_all('SELECT id, etiquetas FROM vehiculos WHERE etiquetas LIKE ?', ['%' . $vieja . '%']) as $r) {
        $lista = etiquetas_lista($r['etiquetas']);
        $cambio = false;
        foreach ($lista as $i => $t) {
            if (mb_strtolower($t) === $vk) {
                $cambio = true;
                if ($nueva === null) {
                    unset($lista[$i]);
                } else {
                    $lista[$i] = $nueva;
                }
            }
        }
        if ($cambio) {
            q('UPDATE vehiculos SET etiquetas = ? WHERE id = ?', [implode(', ', etiquetas_lista(implode(',', $lista))), $r['id']]);
            vehiculo_actualizar_buscar((int)$r['id']);
            $n++;
        }
    }
    return $n;
}

function etiqueta_uso(string $t): int
{
    $n = 0;
    $tk = mb_strtolower($t);
    foreach (q_all('SELECT etiquetas FROM vehiculos WHERE etiquetas LIKE ?', ['%' . $t . '%']) as $r) {
        foreach (etiquetas_lista($r['etiquetas']) as $x) {
            if (mb_strtolower($x) === $tk) {
                $n++;
                break;
            }
        }
    }
    return $n;
}
