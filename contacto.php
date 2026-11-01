<?php
require __DIR__ . '/app/bootstrap.php';

$titulo      = 'Contacto, ubicación y horario';
$descripcion = 'Visítanos en ' . (ajuste('direccion') ?: ciudad()) . '. Teléfono ' . ajuste('telefono') . '. ' . ajuste('horario') . '. Escríbenos por WhatsApp.';
$canonical   = 'contacto.php';
$jsonld      = [ld_migas([['Inicio', abs_url('')], ['Contacto', abs_url('contacto.php')]])];
$nav_activo  = 'contacto';
$body_class  = 'bg-ink-50/60';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$coords = mapa_destino();
$mapaEmbed = 'https://maps.google.com/maps?q=' . rawurlencode($coords !== '' ? $coords : negocio() . ', ' . ajuste('direccion') . ', Guatemala') . '&z=16&output=embed';
?>
<section class="border-b border-ink-100 bg-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-10">
    <nav class="text-xs font-medium text-ink-400" aria-label="Migas de pan"><a href="<?= url('') ?>" class="hover:text-ink-700">Inicio</a> <span class="mx-1">/</span> Contacto</nav>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Contacto</h1>
    <p class="mt-2 max-w-2xl text-ink-500">Visítanos, llámanos o escríbenos. Te ayudamos a comprar, vender o consignar tu vehículo.</p>
  </div>
</section>

<div class="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[400px_minmax(0,1fr)]">
  <div class="space-y-4">
    <?php if (wa_numero()): ?>
    <a href="<?= e(wa_link('Hola, quisiera información sobre sus vehículos.')) ?>" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-2xl bg-[#25D366] p-5 text-white transition hover:brightness-95">
      <span class="grid h-12 w-12 place-items-center rounded-xl bg-white/20"><?= icon('whatsapp', 'w-6 h-6') ?></span>
      <span><span class="block text-lg font-bold">WhatsApp</span><span class="text-sm text-white/85">Respuesta rápida en horario de atención</span></span>
    </a>
    <?php endif; ?>
    <div class="divide-y divide-ink-100 rounded-2xl bg-white ring-1 ring-ink-100">
      <?php if (ajuste('telefono')): ?>
      <a href="<?= e(tel_link()) ?>" class="flex items-center gap-4 p-5 hover:bg-ink-50">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600"><?= icon('phone') ?></span>
        <span><span class="block text-xs font-semibold uppercase tracking-wider text-ink-400">Teléfono</span><span class="text-lg font-bold"><?= e(ajuste('telefono')) ?></span></span>
      </a>
      <?php endif; ?>
      <?php if (ajuste('email')): ?>
      <a href="mailto:<?= e(ajuste('email')) ?>" class="flex items-center gap-4 p-5 hover:bg-ink-50">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600"><?= icon('message') ?></span>
        <span class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-wider text-ink-400">Correo</span><span class="block truncate font-bold"><?= e(ajuste('email')) ?></span></span>
      </a>
      <?php endif; ?>
      <?php if (ajuste('direccion')): ?>
      <button type="button" data-abrir-mapa class="flex w-full items-center gap-4 p-5 text-left hover:bg-ink-50">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><?= icon('pin') ?></span>
        <span><span class="block text-xs font-semibold uppercase tracking-wider text-ink-400">Dirección</span><address class="not-italic font-bold"><?= e(ajuste('direccion')) ?></address><span class="text-sm font-semibold text-brand-600">Cómo llegar (Google Maps o Waze) →</span></span>
      </button>
      <?php endif; ?>
      <?php if (ajuste('horario')): ?>
      <div class="flex items-center gap-4 p-5">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><?= icon('clock') ?></span>
        <span><span class="block text-xs font-semibold uppercase tracking-wider text-ink-400">Horario</span><span class="font-semibold"><?= e(ajuste('horario')) ?></span></span>
      </div>
      <?php endif; ?>
    </div>
    <?php $redes = array_filter(['Facebook' => ajuste('facebook'), 'Instagram' => ajuste('instagram'), 'TikTok' => ajuste('tiktok')]); if ($redes): ?>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($redes as $n => $u): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold ring-1 ring-ink-200 hover:ring-ink-400"><?= e($n) ?></a><?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="min-h-[360px] overflow-hidden rounded-2xl bg-ink-100 ring-1 ring-ink-100">
    <iframe src="<?= e($mapaEmbed) ?>" title="Ubicación de <?= e(negocio()) ?>" class="h-full min-h-[360px] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
