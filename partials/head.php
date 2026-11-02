<?php
/**
 * Variables opcionales de cada página:
 * $titulo, $descripcion, $canonical (ruta relativa, ej. 'autos.php'), $og_imagen (URL absoluta),
 * $og_tipo, $noindex (bool), $jsonld (array de bloques schema.org)
 */
$titulo      = seo_titulo($titulo ?? negocio());
$descripcion = seo_texto($descripcion ?? (negocio() . ': venta, consignación y compra de vehículos seminuevos de agencia e importados en ' . ciudad() . '. Busca por marca, modelo, año y kilometraje.'));
$og_imagen   = $og_imagen ?? seo_imagen_defecto();
$canon       = isset($canonical) ? abs_url($canonical) : null;
$jsonld      = $jsonld ?? [];
?><!doctype html>
<html lang="es-GT">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <script>
    /* Al recargar, empezar arriba (el navegador restauraba una posición vieja mientras la portada aún se acomodaba).
       Al usar "atrás/adelante" se conserva la posición normal. */
    (function () {
      try {
        var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
        var recarga = nav ? nav.type === 'reload' : (performance.navigation && performance.navigation.type === 1);
        if (recarga && !location.hash && 'scrollRestoration' in history) {
          history.scrollRestoration = 'manual';
          var arriba = function () { window.scrollTo({ top: 0, left: 0, behavior: 'instant' }); };
          arriba();
          document.addEventListener('DOMContentLoaded', arriba);
          window.addEventListener('load', function () { arriba(); setTimeout(arriba, 60); });
          // Al salir de la página se vuelve a 'auto' para que "atrás" sí recuerde la posición
          window.addEventListener('pagehide', function () { history.scrollRestoration = 'auto'; });
        }
      } catch (e) {}
    })();
  </script>
  <title><?= e($titulo) ?></title>
  <meta name="description" content="<?= e($descripcion) ?>">
  <?php if (!empty($noindex)): ?>
  <meta name="robots" content="noindex, follow">
  <?php else: ?>
  <meta name="robots" content="index, follow, max-image-preview:large">
  <?php endif; ?>
  <?php if ($canon): ?><link rel="canonical" href="<?= e($canon) ?>"><?php endif; ?>
  <meta property="og:site_name" content="<?= e(negocio()) ?>">
  <meta property="og:locale" content="es_GT">
  <meta property="og:type" content="<?= e($og_tipo ?? 'website') ?>">
  <meta property="og:title" content="<?= e($titulo) ?>">
  <meta property="og:description" content="<?= e($descripcion) ?>">
  <?php if ($canon): ?><meta property="og:url" content="<?= e($canon) ?>"><?php endif; ?>
  <meta property="og:image" content="<?= e($og_imagen) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($titulo) ?>">
  <meta name="twitter:description" content="<?= e($descripcion) ?>">
  <meta name="twitter:image" content="<?= e($og_imagen) ?>">
  <meta name="color-scheme" content="light">
  <?php if (!empty($precargar_imagen)): ?><link rel="preload" as="image" href="<?= e($precargar_imagen) ?>" fetchpriority="high"><?php endif; ?>
  <meta name="theme-color" content="<?= e(ajuste('color_principal', '#005A50')) ?>">
  <?php if (ajuste('google_verificacion')): ?><meta name="google-site-verification" content="<?= e(ajuste('google_verificacion')) ?>"><?php endif; ?>
  <link rel="stylesheet" href="<?= asset('assets/css/tw.css') ?>">
  <?php if (!empty($usa_swiper)): ?>
  <link rel="stylesheet" href="<?= asset('assets/vendor/swiper-bundle.min.css') ?>">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <?= head_identidad() ?>
  <style>/* Transición suave entre páginas del sitio en lugar del parpadeo blanco */@view-transition{navigation:auto}::view-transition-old(root),::view-transition-new(root){animation-duration:.18s}</style>
  <?= jsonld(ld_negocio()) ?>
  <?php foreach ($jsonld as $bloque): ?><?= jsonld($bloque) ?><?php endforeach; ?>
</head>
<body class="<?= e($body_class ?? 'bg-white') ?> font-sans text-ink-900 antialiased">
