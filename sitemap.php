<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$hoy = date('Y-m-d');
$ultimo = q_val('SELECT DATE(MAX(COALESCE(actualizado_en, creado_en))) FROM vehiculos WHERE estado IN ' . ESTADOS_PUBLICOS) ?: $hoy;
$urls = [
    ['', $ultimo, 'daily', '1.0'],
    ['autos.php', $ultimo, 'daily', '0.9'],
    ['servicios.php', $hoy, 'monthly', '0.6'],
    ['marcas.php', $ultimo, 'weekly', '0.6'],
    ['contacto.php', $hoy, 'monthly', '0.7'],
];
if ((int)q_val('SELECT COUNT(*) FROM vehiculos v WHERE v.estado IN ' . ESTADOS_PUBLICOS . ' AND ' . OFERTA_SQL)) {
    $urls[] = ['ofertas.php', $ultimo, 'daily', '0.8'];
}
foreach (q_all('SELECT m.slug, DATE(MAX(COALESCE(v.actualizado_en, v.creado_en))) f FROM marcas m
    JOIN vehiculos v ON v.marca_id = m.id AND v.estado IN ' . ESTADOS_PUBLICOS . ' GROUP BY m.slug') as $m) {
    $urls[] = ['autos/marca/' . $m['slug'] . '/', $m['f'] ?: $hoy, 'weekly', '0.7'];
}
$vehiculos = q_all(VEH_SELECT . ' WHERE v.estado IN ' . ESTADOS_PUBLICOS . ' ORDER BY v.id DESC');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
foreach ($urls as $u) {
    echo '  <url><loc>' . e(abs_url($u[0])) . '</loc><lastmod>' . e($u[1]) . '</lastmod><changefreq>' . $u[2] . '</changefreq><priority>' . $u[3] . "</priority></url>\n";
}
foreach ($vehiculos as $v) {
    $f = substr($v['actualizado_en'] ?: $v['creado_en'], 0, 10);
    echo '  <url><loc>' . e(vehiculo_url_abs($v)) . '</loc><lastmod>' . e($f) . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority>';
    if ($v['portada']) {
        echo '<image:image><image:loc>' . e(abs_url('uploads/vehiculos/' . $v['portada'])) . '</image:loc><image:title>' . e(vehiculo_titulo($v) . ' ' . $v['anio']) . '</image:title></image:image>';
    }
    echo "</url>\n";
}
echo "</urlset>\n";
