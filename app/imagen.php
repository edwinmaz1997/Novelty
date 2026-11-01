<?php
/**
 * Guarda una foto subida: valida, corrige orientación y genera
 * versión grande (1600px) y miniatura (560px) en JPG.
 * Devuelve la ruta relativa "ID/archivo.jpg".
 */
function imagen_guardar(string $tmp, int $vehiculoId): string
{
    if (!extension_loaded('gd')) {
        throw new RuntimeException('El servidor no tiene la extensión GD activa.');
    }
    @ini_set('memory_limit', '256M');
    $info = @getimagesize($tmp);
    if (!$info) {
        throw new RuntimeException('El archivo no es una imagen válida.');
    }
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $img = @imagecreatefromjpeg($tmp);
            break;
        case IMAGETYPE_PNG:
            $img = @imagecreatefrompng($tmp);
            break;
        case IMAGETYPE_WEBP:
            $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false;
            break;
        default:
            $img = false;
    }
    if (!$img) {
        throw new RuntimeException('Formato no soportado. Usa JPG, PNG o WEBP.');
    }

    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($tmp);
        $o = (int)($exif['Orientation'] ?? 1);
        $rot = [3 => 180, 6 => -90, 8 => 90];
        if (isset($rot[$o])) {
            $r = imagerotate($img, $rot[$o], 0);
            if ($r) {
                imagedestroy($img);
                $img = $r;
            }
        }
    }

    $dir = ROOT . '/uploads/vehiculos/' . $vehiculoId;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta de fotos.');
    }
    $nombre = bin2hex(random_bytes(6)) . '.jpg';
    imagen_redimensionar($img, $dir . '/' . $nombre, 1600, 82);
    imagen_redimensionar($img, $dir . '/' . str_replace('.jpg', '_sm.jpg', $nombre), 560, 76);
    imagedestroy($img);
    return $vehiculoId . '/' . $nombre;
}

function imagen_redimensionar($img, string $destino, int $max, int $calidad): void
{
    $w = imagesx($img);
    $h = imagesy($img);
    $s = min(1, $max / max($w, $h));
    $nw = (int)round($w * $s);
    $nh = (int)round($h * $s);
    $out = imagecreatetruecolor($nw, $nh);
    $blanco = imagecolorallocate($out, 255, 255, 255);
    imagefill($out, 0, 0, $blanco);
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imageinterlace($out, true);
    if (!imagejpeg($out, $destino, $calidad)) {
        imagedestroy($out);
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    imagedestroy($out);
}

function imagen_borrar(string $archivo): void
{
    $base = ROOT . '/uploads/vehiculos/' . $archivo;
    @unlink($base);
    @unlink(preg_replace('/\.jpg$/', '_sm.jpg', $base));
}
