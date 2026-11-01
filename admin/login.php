<?php
require dirname(__DIR__) . '/app/bootstrap.php';

if (usuario()) {
    redirect('admin/');
}
$error = '';
if (es_post()) {
    csrf_check();
    if (intentar_login(p('usuario'), (string)($_POST['password'] ?? ''))) {
        redirect('admin/');
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Ingresar · <?= e(negocio()) ?></title>
  <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tw.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-ink-950 hero-grid font-sans text-ink-900 antialiased">
  <div class="flex min-h-screen items-center justify-center px-4">
    <div class="w-full max-w-sm">
      <div class="mb-8 text-center">
        <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white text-2xl font-extrabold text-ink-900">N</span>
        <h1 class="mt-4 text-2xl font-extrabold text-white"><?= e(negocio()) ?></h1>
        <p class="text-sm text-ink-400">Panel de administración</p>
      </div>
      <form method="post" class="rounded-2xl bg-white p-6 shadow-2xl shadow-black/40">
        <?= csrf_field() ?>
        <?php if ($error): ?><div class="mb-4 rounded-xl bg-red-50 p-3 text-sm font-medium text-red-700"><?= e($error) ?></div><?php endif; ?>
        <label class="block text-sm font-semibold">Usuario
          <input name="usuario" value="<?= e(p('usuario')) ?>" autocomplete="username" required autofocus class="mt-1.5 block h-12 w-full rounded-xl border-ink-200 focus:border-brand-500 focus:ring-brand-500">
        </label>
        <label class="mt-4 block text-sm font-semibold">Contraseña
          <input type="password" name="password" autocomplete="current-password" required class="mt-1.5 block h-12 w-full rounded-xl border-ink-200 focus:border-brand-500 focus:ring-brand-500">
        </label>
        <button class="mt-6 h-12 w-full rounded-xl bg-brand-500 font-bold text-white hover:bg-brand-600">Ingresar</button>
      </form>
      <p class="mt-6 text-center text-sm"><a href="<?= url('') ?>" class="text-ink-400 hover:text-white">← Volver al sitio</a></p>
    </div>
  </div>
</body>
</html>
