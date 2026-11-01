<?php
$nav_activo = $nav_activo ?? '';
$links = [
    'inicio'    => ['Inicio', url(''), 'home'],
    'vehiculos' => ['Vehículos', url('autos.php'), 'car'],
    'servicios' => ['Servicios', url('#servicios'), 'handshake'],
    'marcas'    => ['Marcas', url('#marcas'), 'star'],
    'contacto'  => ['Contacto', url('#contacto'), 'phone'],
];
?>
<header class="sticky top-0 z-40 border-b border-ink-100/80 bg-white/85 backdrop-blur supports-[backdrop-filter]:bg-white/70">
  <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6">
    <a href="<?= url('') ?>" class="flex shrink-0 items-center" aria-label="<?= e(negocio()) ?>">
      <?= logo_html('claro', 'h-[18px] min-[380px]:h-5 sm:h-6 lg:h-7 w-auto max-w-[56vw]') ?>
    </a>
    <nav class="hidden md:flex items-center gap-1 text-[14px] font-medium">
      <?php foreach ($links as $k => $l): ?>
        <a href="<?= e($l[1]) ?>" class="rounded-lg px-3 py-2 transition <?= $nav_activo === $k ? 'text-ink-900 bg-ink-50' : 'text-ink-500 hover:text-ink-900 hover:bg-ink-50' ?>"><?= e($l[0]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="ml-auto flex items-center gap-2">
      <a href="<?= url('autos.php') ?>" class="hidden sm:inline-flex md:hidden items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-ink-700 hover:bg-ink-50"><?= icon('search', 'w-4 h-4') ?>Buscar</a>
      <?php if (wa_numero()): ?>
      <a href="<?= e(wa_link('Hola, quisiera información sobre sus vehículos.')) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-3.5 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition">
        <?= icon('message', 'w-4 h-4') ?><span class="hidden sm:inline">Escríbenos</span>
      </a>
      <?php endif; ?>
      <button type="button" id="btn-menu" class="hamburguesa md:hidden relative grid h-10 w-10 place-items-center rounded-xl text-ink-800 hover:bg-ink-50" aria-label="Abrir menú" aria-expanded="false" aria-controls="menu-movil">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
  <div id="menu-movil" class="menu-movil md:hidden" aria-hidden="true">
    <nav class="mx-auto flex h-full max-w-7xl flex-col px-5 pb-8 pt-6">
      <p class="menu-item text-[11px] font-bold uppercase tracking-[.2em] text-ink-400" style="--i:0">Menú</p>
      <ul class="mt-3 divide-y divide-ink-100">
        <?php $i = 1; foreach ($links as $k => $l): ?>
          <li class="menu-item" style="--i:<?= $i++ ?>">
            <a href="<?= e($l[1]) ?>" data-cierra-menu class="group flex items-center gap-4 py-4">
              <span class="grid h-11 w-11 place-items-center rounded-2xl transition <?= $nav_activo === $k ? 'bg-brand-500 text-white' : 'bg-ink-50 text-brand-600 group-active:bg-brand-500 group-active:text-white' ?>"><?= icon($l[2], 'w-5 h-5') ?></span>
              <span class="flex-1 text-2xl font-extrabold tracking-tight <?= $nav_activo === $k ? 'text-brand-600' : 'text-ink-900' ?>"><?= e($l[0]) ?></span>
              <span class="text-ink-300 transition group-active:translate-x-1 group-active:text-brand-500"><?= icon('arrow-r', 'w-5 h-5') ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="menu-item mt-auto grid gap-2 pt-8" style="--i:<?= $i ?>">
        <?php if (wa_numero()): ?>
          <a href="<?= e(wa_link('Hola, quisiera información sobre sus vehículos.')) ?>" target="_blank" rel="noopener" class="inline-flex h-14 items-center justify-center gap-2 rounded-2xl bg-[#25D366] font-bold text-white"><?= icon('message') ?>Escríbenos por WhatsApp</a>
        <?php endif; ?>
        <?php if (ajuste('telefono')): ?>
          <a href="<?= e(tel_link()) ?>" class="inline-flex h-14 items-center justify-center gap-2 rounded-2xl border border-ink-200 font-bold text-ink-800"><?= icon('phone', 'w-5 h-5') ?>Llamar · <?= e(ajuste('telefono')) ?></a>
        <?php endif; ?>
        <?php if (ajuste('direccion')): ?>
          <button type="button" data-abrir-mapa class="inline-flex h-14 items-center justify-center gap-2 rounded-2xl border border-ink-200 font-bold text-ink-800"><?= icon('pin', 'w-5 h-5') ?>Cómo llegar</button>
        <?php endif; ?>
        <?php if (ajuste('horario')): ?><p class="mt-2 px-4 text-center text-xs leading-relaxed text-ink-400"><?= e(ajuste('horario')) ?></p><?php endif; ?>
      </div>
    </nav>
  </div>
</header>
