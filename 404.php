<?php
require_once __DIR__ . '/app/bootstrap.php';

http_response_code(404);
$titulo  = 'Página no encontrada';
$noindex = true;
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<section class="mx-auto max-w-xl px-4 py-24 text-center">
  <p class="text-7xl font-extrabold tracking-tight text-brand-500">404</p>
  <h1 class="mt-4 text-2xl font-extrabold">No encontramos esta página</h1>
  <p class="mt-2 text-ink-500">Puede que el enlace haya cambiado o que el vehículo ya se haya vendido.</p>
  <div class="mt-7 flex flex-wrap justify-center gap-2">
    <a href="<?= url('') ?>" class="rounded-xl bg-ink-900 px-6 py-3 font-bold text-white">Ir al inicio</a>
    <a href="<?= url('autos.php') ?>" class="rounded-xl border border-ink-200 px-6 py-3 font-bold text-ink-800 hover:bg-ink-50">Ver vehículos</a>
  </div>
</section>
<?php include __DIR__ . '/partials/footer.php';
