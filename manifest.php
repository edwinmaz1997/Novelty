<?php
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');
$color = ajuste('color_principal', '#005A50');
echo json_encode([
    'name'             => negocio(),
    'short_name'       => mb_substr(negocio(), 0, 15),
    'description'      => ajuste('marcas_subtitulo', ajuste('eslogan')),
    'start_url'        => url(''),
    'scope'            => url(''),
    'display'          => 'browser',
    'background_color' => '#ffffff',
    'theme_color'      => $color,
    'icons'            => [
        ['src' => icono_url('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => icono_url('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => icono_url('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
