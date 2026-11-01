<?php
/* ---------- Catálogos fijos ---------- */
const PROCEDENCIAS  = ['agencia' => 'Agencia', 'rodado' => 'Rodado'];
const TRANSMISIONES = ['automatica' => 'Automática', 'manual' => 'Manual'];
const COMBUSTIBLES  = ['gasolina' => 'Gasolina', 'diesel' => 'Diésel', 'hibrido' => 'Híbrido', 'electrico' => 'Eléctrico'];
const CARROCERIAS   = ['Sedán', 'Hatchback', 'SUV', 'Pick-up', 'Coupé', 'Convertible', 'Van', 'Microbús', 'Camión'];
const TRACCIONES    = ['4x2', '4x4', 'AWD'];
const ESTADOS       = ['disponible' => 'Disponible', 'reservado' => 'Reservado', 'oculto' => 'Oculto', 'vendido' => 'Vendido'];
const TIPOS_INGRESO = ['consignacion' => 'Consignación', 'compra' => 'Compra'];
const COLORES       = ['Blanco', 'Negro', 'Gris', 'Plata', 'Rojo', 'Azul', 'Verde', 'Beige', 'Café', 'Dorado', 'Vino', 'Celeste', 'Naranja', 'Amarillo'];
const ETIQUETAS_SUGERIDAS = ['Único dueño', 'Full extras', 'Servicios al día', 'Llantas nuevas', 'Cámara de reversa', 'Asientos de cuero', 'Sunroof', 'Pantalla', 'Bluetooth', 'Recién importado', 'Papeles al día', 'Financiamiento'];
const ORDENES = [
    'relevantes'  => 'Más relevantes',
    'recientes'   => 'Recién ingresados',
    'precio_asc'  => 'Precio: menor a mayor',
    'precio_desc' => 'Precio: mayor a menor',
    'km_asc'      => 'Menor kilometraje',
    'anio_desc'   => 'Año más reciente',
];
const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const MAX_FOTOS = 30;

/* ---------- Salida ---------- */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function es_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

/** Ruta base del proyecto ('' si está en la raíz del dominio). */
function base_path(): string
{
    static $b = null;
    if ($b !== null) {
        return $b;
    }
    if (!empty($GLOBALS['config']['base_path'])) {
        return $b = rtrim($GLOBALS['config']['base_path'], '/');
    }
    $root   = str_replace('\\', '/', (string)realpath(ROOT));
    $script = str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $sn     = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $b = '';
    if ($root !== '' && $script !== '' && strpos($script, $root) === 0) {
        $rel = substr($script, strlen($root));
        if ($rel !== '' && substr($sn, -strlen($rel)) === $rel) {
            $b = rtrim(substr($sn, 0, -strlen($rel)), '/');
        }
    }
    return $b;
}

function url(string $p = ''): string
{
    return base_path() . '/' . ltrim($p, '/');
}

function abs_url(string $p = ''): string
{
    return (es_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($p);
}

function asset(string $p): string
{
    $f = ROOT . '/' . ltrim($p, '/');
    return url($p) . '?v=' . (is_file($f) ? filemtime($f) : APP_VERSION);
}

function redirect(string $p): void
{
    header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p)));
    exit;
}

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- Ajustes del negocio ---------- */
function ajustes(bool $refrescar = false): array
{
    static $c = null;
    if ($c === null || $refrescar) {
        $c = [];
        try {
            foreach (db()->query('SELECT clave, valor FROM ajustes') as $r) {
                $c[$r['clave']] = (string)$r['valor'];
            }
        } catch (Throwable $e) {
        }
    }
    return $c;
}

function ajuste(string $k, string $d = ''): string
{
    $a = ajustes();
    return (isset($a[$k]) && $a[$k] !== '') ? $a[$k] : $d;
}

function negocio(): string
{
    return ajuste('negocio_nombre', 'Novelty Autos');
}

/* ---------- Formatos ---------- */
function money($n, int $dec = 0): string
{
    if ($n === null || $n === '') {
        return '—';
    }
    $n = (float)$n;
    $s = ajuste('moneda', 'Q');
    return ($n < 0 ? '-' : '') . $s . ' ' . number_format(abs($n), $dec, '.', ',');
}

function km($n): string
{
    return number_format((int)$n, 0, '.', ',') . ' km';
}

function num($n): string
{
    return number_format((float)$n, 0, '.', ',');
}

function fecha($d): string
{
    if (!$d) {
        return '—';
    }
    return date('d/m/Y', strtotime($d));
}

/** Convierte "125,000.50" / "Q 125 000" a float o null. */
function a_numero($v): ?float
{
    if ($v === null || is_array($v)) {
        return null;
    }
    $v = preg_replace('/[^0-9.\-]/', '', (string)$v);
    if ($v === '' || !is_numeric($v)) {
        return null;
    }
    return (float)$v;
}

/** Texto en minúsculas, sin tildes, solo [a-z0-9 -.] */
function norm(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'â' => 'a', 'ê' => 'e', 'ô' => 'o',
    ]);
    $s = preg_replace('/[^a-z0-9\s\-\.]/', ' ', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

function slugify(string $s): string
{
    $s = preg_replace('/[^a-z0-9]+/', '-', norm($s));
    return trim($s, '-');
}

/* ---------- Peticiones ---------- */
function p(string $k, string $d = ''): string
{
    $v = $_POST[$k] ?? $d;
    return is_string($v) ? trim($v) : $d;
}

function g(string $k, string $d = ''): string
{
    $v = $_GET[$k] ?? $d;
    return is_string($v) ? trim($v) : $d;
}

function gi(string $k, int $d = 0): int
{
    $v = $_GET[$k] ?? null;
    return (is_string($v) && is_numeric($v)) ? (int)$v : $d;
}

function es_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------- CSRF / flash ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function csrf_check(): void
{
    if (!csrf_valido()) {
        http_response_code(419);
        exit('La sesión expiró. Regresa y recarga la página.');
    }
}

function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = [$tipo, $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Enlaces ---------- */
function wa_numero(): string
{
    return preg_replace('/\D/', '', ajuste('whatsapp', ''));
}

function wa_link(string $msg = ''): string
{
    return 'https://wa.me/' . wa_numero() . ($msg !== '' ? '?text=' . rawurlencode($msg) : '');
}

function tel_link(): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', ajuste('telefono', ajuste('whatsapp', '')));
}

function foto_url(?string $archivo, string $tam = 'md'): string
{
    if (!$archivo) {
        return url('assets/img/sin-foto.svg');
    }
    if ($tam === 'sm') {
        $archivo = preg_replace('/\.jpg$/', '_sm.jpg', $archivo);
    }
    return url('uploads/vehiculos/' . $archivo);
}
