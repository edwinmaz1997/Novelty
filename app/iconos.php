<?php
/**
 * Íconos para navegadores y celulares (favicon.ico, Apple, Android).
 * - Los de fábrica viven en la raíz del sitio (favicon.ico, apple-touch-icon.png, icon-192.png, icon-512.png).
 * - Si en Ajustes se sube otro ícono, se generan en uploads/sitio/iconos/ y el .htaccess los sirve
 *   en las mismas direcciones, así iPhone y Android los encuentran aunque no lean las etiquetas <link>.
 */

const ICONOS_DIR = 'uploads/sitio/iconos';

function icono_cargar(string $ruta)
{
    $f = ROOT . '/' . ltrim($ruta, '/');
    $info = @getimagesize($f);
    if (!$info) {
        return null;
    }
    switch ($info[2]) {
        case IMAGETYPE_PNG:
            $i = @imagecreatefrompng($f);
            break;
        case IMAGETYPE_JPEG:
            $i = @imagecreatefromjpeg($f);
            break;
        case IMAGETYPE_WEBP:
            $i = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f) : null;
            break;
        default:
            $i = null;
    }
    return $i ?: null;
}

/** Lienzo cuadrado con la imagen centrada. $fondo null = transparente. $escala = % del lado. */
function icono_lienzo($src, int $lado, float $escala, ?array $fondo)
{
    $o = imagecreatetruecolor($lado, $lado);
    imagesavealpha($o, true);
    if ($fondo === null) {
        imagealphablending($o, false);
        imagefill($o, 0, 0, imagecolorallocatealpha($o, 0, 0, 0, 127));
        imagealphablending($o, true);
    } else {
        imagefill($o, 0, 0, imagecolorallocate($o, $fondo[0], $fondo[1], $fondo[2]));
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $max = $lado * $escala;
    $s = min($max / $w, $max / $h);
    $nw = (int)round($w * $s);
    $nh = (int)round($h * $s);
    imagecopyresampled($o, $src, (int)(($lado - $nw) / 2), (int)(($lado - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
    return $o;
}

function icono_png($img): string
{
    ob_start();
    imagepng($img, null, 9);
    return (string)ob_get_clean();
}

/** Archivo .ico con PNG embebidos (16, 32, 48). */
function icono_ico(array $pngs): string
{
    $n = count($pngs);
    $cab = pack('vvv', 0, 1, $n);
    $dir = '';
    $datos = '';
    $offset = 6 + 16 * $n;
    foreach ($pngs as $lado => $png) {
        $dir .= pack('CCCCvvVV', $lado >= 256 ? 0 : $lado, $lado >= 256 ? 0 : $lado, 0, 0, 1, 32, strlen($png), $offset);
        $datos .= $png;
        $offset += strlen($png);
    }
    return $cab . $dir . $datos;
}

/**
 * Genera todos los íconos.
 * @param string $chico  imagen para favicon (16–48 px), normalmente el favicon
 * @param string $grande imagen para Apple/Android, normalmente el ícono
 * @param string $destino carpeta relativa al proyecto ('' = raíz)
 */
function iconos_generar(string $chico, string $grande, string $destino): bool
{
    if (!extension_loaded('gd')) {
        return false;
    }
    $sc = icono_cargar($chico);
    $sg = icono_cargar($grande) ?: $sc;
    $sc = $sc ?: $sg;
    if (!$sc) {
        return false;
    }
    $dir = rtrim(ROOT . '/' . trim($destino, '/'), '/');
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return false;
    }
    $blanco = [255, 255, 255];

    $pngs = [];
    foreach ([16, 32, 48] as $l) {
        $im = icono_lienzo($sc, $l, 1.0, null);
        $pngs[$l] = icono_png($im);
        imagedestroy($im);
    }
    file_put_contents($dir . '/favicon.ico', icono_ico($pngs));
    file_put_contents($dir . '/favicon-32.png', $pngs[32]);

    // iPhone / iPad: 180x180, fondo sólido (iOS pone negro si es transparente)
    $im = icono_lienzo($sg, 180, .72, $blanco);
    imagepng($im, $dir . '/apple-touch-icon.png', 9);
    imagedestroy($im);

    // Android / Chrome (también "maskable": margen de seguridad)
    foreach ([192, 512] as $l) {
        $im = icono_lienzo($sg, $l, .62, $blanco);
        imagepng($im, $dir . '/icon-' . $l . '.png', 9);
        imagedestroy($im);
    }
    return true;
}

/** Ruta pública de un ícono: el personalizado si existe, si no el de fábrica en la raíz. */
function icono_url(string $archivo): string
{
    $custom = ROOT . '/' . ICONOS_DIR . '/' . $archivo;
    if (is_file($custom)) {
        return url(ICONOS_DIR . '/' . $archivo) . '?v=' . filemtime($custom);
    }
    $f = ROOT . '/' . $archivo;
    return url($archivo) . (is_file($f) ? '?v=' . filemtime($f) : '');
}

/** Regenera (o borra) los íconos personalizados según lo guardado en Ajustes. */
function iconos_actualizar_desde_ajustes(): void
{
    $fav = ajuste('favicon', 'assets/img/favicon.png');
    $ico = ajuste('icono', 'assets/img/icono.png');
    $deFabrica = strpos($fav, 'uploads/') !== 0 && strpos($ico, 'uploads/') !== 0;
    $dir = ROOT . '/' . ICONOS_DIR;
    if ($deFabrica) {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        return;
    }
    iconos_generar($fav ?: $ico, $ico ?: $fav, ICONOS_DIR);
}
