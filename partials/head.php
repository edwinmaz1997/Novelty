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
  <meta name="theme-color" content="<?= e(ajuste('color_principal', '#005A50')) ?>">
  <?php if (ajuste('google_verificacion')): ?><meta name="google-site-verification" content="<?= e(ajuste('google_verificacion')) ?>"><?php endif; ?>
  <link rel="stylesheet" href="<?= asset('assets/css/tw.css') ?>">
  <?php if (!empty($usa_swiper)): ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <?= head_identidad() ?>
  <?= jsonld(ld_negocio()) ?>
  <?php foreach ($jsonld as $bloque): ?><?= jsonld($bloque) ?><?php endforeach; ?>
</head>
<body class="<?= e($body_class ?? 'bg-white') ?> font-sans text-ink-900 antialiased">
