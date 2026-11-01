<?php
/** @var string $titulo */
$descripcion = $descripcion ?? (negocio() . ' — ' . ajuste('eslogan', 'Vehículos seminuevos con confianza') . '. Encuentra tu próximo carro por marca, modelo, año y kilometraje.');
$og_imagen   = $og_imagen ?? null;
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($titulo) ?></title>
  <meta name="description" content="<?= e($descripcion) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($titulo) ?>">
  <meta property="og:description" content="<?= e($descripcion) ?>">
  <?php if ($og_imagen): ?><meta property="og:image" content="<?= e($og_imagen) ?>"><?php endif; ?>
  <meta name="theme-color" content="#0B1220">
  <link rel="stylesheet" href="<?= asset('assets/css/tw.css') ?>">
  <?php if (!empty($usa_swiper)): ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <?php endif; ?>
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <?= head_identidad() ?>
</head>
<body class="<?= e($body_class ?? 'bg-white') ?> font-sans text-ink-900 antialiased">
