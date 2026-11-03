<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_once ROOT . '/app/imagen.php';

$u = usuario();
if (!$u) {
    json_out(['ok' => false, 'error' => 'Tu sesión expiró. Vuelve a ingresar.'], 401);
}
$accion = g('accion');

try {
    switch ($accion) {
        case 'modelos':
            json_out(['ok' => true, 'modelos' => q_all('SELECT id, nombre FROM modelos WHERE marca_id = ? ORDER BY nombre', [gi('marca')])]);
            break;
    }

    if (!es_post() || !csrf_valido()) {
        json_out(['ok' => false, 'error' => 'Solicitud no válida. Recarga la página.'], 419);
    }

    switch ($accion) {
        case 'crear_marca':
            $nombre = mb_substr(p('nombre'), 0, 60);
            if ($nombre === '') {
                throw new RuntimeException('Escribe el nombre de la marca.');
            }
            $id = (int)q_val('SELECT id FROM marcas WHERE nombre = ?', [$nombre]);
            if (!$id) {
                q('INSERT INTO marcas (nombre, slug) VALUES (?, ?)', [$nombre, slugify($nombre)]);
                $id = (int)db()->lastInsertId();
            }
            json_out(['ok' => true, 'id' => $id, 'nombre' => $nombre]);
            break;

        case 'crear_modelo':
            $marca = (int)p('marca_id');
            $nombre = mb_substr(p('nombre'), 0, 80);
            if (!$marca || $nombre === '') {
                throw new RuntimeException('Elige la marca y escribe el modelo.');
            }
            $id = (int)q_val('SELECT id FROM modelos WHERE marca_id = ? AND nombre = ?', [$marca, $nombre]);
            if (!$id) {
                q('INSERT INTO modelos (marca_id, nombre) VALUES (?, ?)', [$marca, $nombre]);
                $id = (int)db()->lastInsertId();
            }
            json_out(['ok' => true, 'id' => $id, 'nombre' => $nombre]);
            break;

        case 'crear_opcion':
            $tipo = p('tipo');
            if (!isset(TIPOS_OPCION[$tipo])) {
                throw new RuntimeException('Tipo no válido.');
            }
            $nombre = mb_substr(trim(preg_replace('/\s+/', ' ', p('nombre'))), 0, 40);
            if ($nombre === '') {
                throw new RuntimeException('Escribe el nombre.');
            }
            $existe = q_val('SELECT nombre FROM opciones WHERE tipo = ? AND nombre = ?', [$tipo, $nombre]);
            if (!$existe) {
                q('INSERT INTO opciones (tipo, nombre, orden) VALUES (?, ?, 100)', [$tipo, $nombre]);
            }
            json_out(['ok' => true, 'nombre' => $existe ?: $nombre]);
            break;

        case 'crear_cliente':
            if ($u['rol'] !== 'admin') {
                throw new RuntimeException('Sin permiso.');
            }
            $nombre = mb_substr(p('nombre'), 0, 150);
            if ($nombre === '') {
                throw new RuntimeException('Escribe el nombre del cliente.');
            }
            q('INSERT INTO clientes (nombre, telefono, dpi) VALUES (?, ?, ?)', [$nombre, p('telefono') ?: null, p('dpi') ?: null]);
            json_out(['ok' => true, 'id' => (int)db()->lastInsertId(), 'nombre' => $nombre, 'telefono' => p('telefono')]);
            break;

        case 'subir_foto':
            $vid = (int)p('vehiculo_id');
            $v = vehiculo($vid);
            if (!$v) {
                throw new RuntimeException('Vehículo no encontrado.');
            }
            if ($v['estado'] === 'vendido' && $u['rol'] !== 'admin') {
                throw new RuntimeException('El vehículo ya fue vendido.');
            }
            if ((int)q_val('SELECT COUNT(*) FROM vehiculo_fotos WHERE vehiculo_id = ?', [$vid]) >= MAX_FOTOS) {
                throw new RuntimeException('Ya tiene el máximo de ' . MAX_FOTOS . ' fotos.');
            }
            $f = $_FILES['foto'] ?? null;
            if (!$f || !is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
                $code = is_array($f) ? (int)($f['error'] ?? 0) : 0;
                throw new RuntimeException($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE
                    ? 'La foto es demasiado pesada para el servidor.' : 'No se recibió la foto.');
            }
            $archivo = imagen_guardar($f['tmp_name'], $vid);
            $orden = (int)q_val('SELECT COALESCE(MAX(orden), -1) + 1 FROM vehiculo_fotos WHERE vehiculo_id = ?', [$vid]);
            q('INSERT INTO vehiculo_fotos (vehiculo_id, archivo, orden) VALUES (?, ?, ?)', [$vid, $archivo, $orden]);
            json_out(['ok' => true, 'foto' => ['id' => (int)db()->lastInsertId(), 'url' => foto_url($archivo, 'sm')]]);
            break;

        case 'ordenar_fotos':
            $vid = (int)p('vehiculo_id');
            $ids = array_map('intval', explode(',', p('ids')));
            $st = db()->prepare('UPDATE vehiculo_fotos SET orden = ? WHERE id = ? AND vehiculo_id = ?');
            foreach ($ids as $i => $fid) {
                $st->execute([$i, $fid, $vid]);
            }
            json_out(['ok' => true]);
            break;

        case 'eliminar_foto':
            $foto = q_one('SELECT f.*, v.estado FROM vehiculo_fotos f JOIN vehiculos v ON v.id = f.vehiculo_id WHERE f.id = ?', [(int)p('id')]);
            if (!$foto) {
                throw new RuntimeException('La foto no existe.');
            }
            q('DELETE FROM vehiculo_fotos WHERE id = ?', [$foto['id']]);
            imagen_borrar($foto['archivo']);
            json_out(['ok' => true]);
            break;
    }
    json_out(['ok' => false, 'error' => 'Acción desconocida.'], 400);
} catch (RuntimeException $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    error_log('[novelty] ' . $e->getMessage());
    json_out(['ok' => false, 'error' => 'Error del servidor. Intenta de nuevo.'], 500);
}
