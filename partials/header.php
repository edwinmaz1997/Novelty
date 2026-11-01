<?php
$nav_activo = $nav_activo ?? '';
$links = [
    'inicio'    => ['Inicio', url('')],
    'vehiculos' => ['Vehículos', url('autos.php')],
    'servicios' => ['Servicios', url('#servicios')],
    'marcas'    => ['Marcas', url('#marcas')],
    'contacto'  => ['Contacto', url('#contacto')],
];
?>
<header class="sticky top-0 z-40 border-b border-ink-100/80 bg-white/85 backdrop-blur supports-[backdrop-filter]:bg-white/70">
  <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6">
    <a href="<?= url('') ?>" class="flex shrink-0 items-center" aria-label="<?= e(negocio()) ?>">
      <?php $ico = img_ajuste('icono', 'assets/img/icono.png'); ?>
      <?php if ($ico): ?><img src="<?= e($ico) ?>" alt="<?= e(negocio()) ?>" class="h-9 w-auto sm:hidden"><?php endif; ?>
      <span class="<?= $ico ? 'hidden sm:block' : '' ?>"><?= logo_html('claro', 'h-6 lg:h-7 w-auto') ?></span>
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
      <button type="button" id="btn-menu" class="md:hidden grid h-10 w-10 place-items-center rounded-xl text-ink-700 hover:bg-ink-50" aria-label="Menú"><?= icon('menu') ?></button>
    </div>
  </div>
  <div id="menu-movil" class="md:hidden hidden border-t border-ink-100 bg-white">
    <nav class="mx-auto max-w-7xl px-4 py-3 grid gap-1">
      <?php foreach ($links as $k => $l): ?>
        <a href="<?= e($l[1]) ?>" class="rounded-lg px-3 py-2.5 font-medium <?= $nav_activo === $k ? 'bg-ink-50' : 'text-ink-600' ?>"><?= e($l[0]) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>
