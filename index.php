<?php
require __DIR__ . '/app/bootstrap.php';

$destacados = vehiculos_buscar(['destacado' => 1], true, 1, 10)['items'];
if (count($destacados) < 3) {
    $destacados = vehiculos_buscar(['orden' => 'relevantes'], true, 1, 10)['items'];
}
$recientes = vehiculos_buscar(['orden' => 'recientes'], true, 1, 8);
$total     = $recientes['total'];
$marcas    = facetas_marcas();
$anios     = facetas_anios();
$procs     = facetas_procedencia();

$titulo     = negocio() . ' · ' . ajuste('eslogan', 'Vehículos seminuevos con confianza');
$nav_activo = 'inicio';
$usa_swiper = true;
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$atajos = ['Pick-up', 'SUV', '4x4', 'Automático', 'Diésel', 'Único dueño'];
?>

<!-- HERO -->
<section class="relative overflow-hidden bg-ink-950 hero-grid">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 pt-14 pb-20 sm:pt-20 sm:pb-28">
    <div class="max-w-3xl">
      <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-ink-200">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-400"></span><?= num($total) ?> vehículos disponibles hoy
      </span>
      <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-6xl">
        Tu próximo carro,<br><span class="text-brand-400">sin complicaciones.</span>
      </h1>
      <p class="mt-5 max-w-xl text-base text-ink-300 sm:text-lg">Seminuevos de agencia y rodados, revisados y con facilidades de pago. Escribe lo que buscas o filtra por marca y año.</p>
    </div>

    <form action="<?= url('autos.php') ?>" method="get" class="mt-9 rounded-2xl bg-white p-2 shadow-2xl shadow-black/30 sm:p-2.5">
      <div class="grid gap-2 lg:grid-cols-[1.6fr_1fr_1fr_1fr_auto]">
        <label class="relative flex items-center">
          <span class="pointer-events-none absolute left-4 text-ink-400"><?= icon('search') ?></span>
          <input type="search" name="q" placeholder="Busca: BMW rojo, Hilux 4x4, diésel…" autocomplete="off"
                 class="h-14 w-full rounded-xl border-0 bg-ink-50 pl-12 pr-4 text-[15px] font-medium placeholder:text-ink-400 focus:bg-white focus:ring-2 focus:ring-brand-500">
        </label>
        <select name="marca" class="select h-14 rounded-xl border-0 bg-ink-50 px-4 text-[15px] font-medium focus:ring-2 focus:ring-brand-500">
          <option value="">Todas las marcas</option>
          <?php foreach ($marcas as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['nombre']) ?> (<?= (int)$m['total'] ?>)</option><?php endforeach; ?>
        </select>
        <select name="procedencia" class="select h-14 rounded-xl border-0 bg-ink-50 px-4 text-[15px] font-medium focus:ring-2 focus:ring-brand-500">
          <option value="">Agencia o rodado</option>
          <?php foreach (PROCEDENCIAS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?> (<?= (int)$procs[$k] ?>)</option><?php endforeach; ?>
        </select>
        <select name="anio_min" class="select h-14 rounded-xl border-0 bg-ink-50 px-4 text-[15px] font-medium focus:ring-2 focus:ring-brand-500">
          <option value="">Año desde</option>
          <?php foreach ($anios as $a): ?><option value="<?= $a ?>"><?= $a ?></option><?php endforeach; ?>
        </select>
        <button class="inline-flex h-14 items-center justify-center gap-2 rounded-xl bg-brand-500 px-7 text-[15px] font-bold text-white hover:bg-brand-600 transition">
          <?= icon('search', 'w-5 h-5') ?>Buscar
        </button>
      </div>
    </form>
    <div class="mt-5 flex flex-wrap items-center gap-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-ink-400 mr-1">Populares</span>
      <?php foreach ($atajos as $a): ?>
        <a href="<?= url('autos.php?q=' . urlencode($a)) ?>" class="rounded-full border border-white/10 bg-white/5 px-3.5 py-1.5 text-sm font-medium text-ink-200 hover:border-white/30 hover:text-white transition"><?= e($a) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DESTACADOS -->
<?php if ($destacados): ?>
<section class="mx-auto max-w-7xl px-4 sm:px-6 pt-16">
  <div class="flex items-end justify-between gap-4">
    <div>
      <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Selección</p>
      <h2 class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl">Vehículos destacados</h2>
    </div>
    <div class="flex items-center gap-2">
      <button class="dest-prev grid h-11 w-11 place-items-center rounded-full border border-ink-200 text-ink-700 hover:bg-ink-50" aria-label="Anterior"><?= icon('chev-l') ?></button>
      <button class="dest-next grid h-11 w-11 place-items-center rounded-full border border-ink-200 text-ink-700 hover:bg-ink-50" aria-label="Siguiente"><?= icon('chev-r') ?></button>
    </div>
  </div>
  <div class="swiper destacados mt-7 !overflow-visible sm:!overflow-hidden">
    <div class="swiper-wrapper">
      <?php foreach ($destacados as $v): ?>
        <div class="swiper-slide !h-auto"><?php include __DIR__ . '/partials/card.php'; ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- MARCAS -->
<?php if ($marcas): ?>
<section class="mx-auto max-w-7xl px-4 sm:px-6 pt-16">
  <h2 class="text-lg font-bold tracking-tight">Explora por marca</h2>
  <div class="mt-4 flex gap-2 overflow-x-auto no-scrollbar pb-1">
    <?php foreach ($marcas as $m): ?>
      <a href="<?= url('autos.php?marca=' . (int)$m['id']) ?>" class="group shrink-0 inline-flex items-center gap-2 rounded-xl border border-ink-100 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 hover:border-ink-300 hover:text-ink-900 transition">
        <?= e($m['nombre']) ?><span class="rounded-md bg-ink-50 px-1.5 py-0.5 text-xs text-ink-500 group-hover:bg-brand-50 group-hover:text-brand-700"><?= (int)$m['total'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- RECIENTES -->
<section class="mx-auto max-w-7xl px-4 sm:px-6 pt-16">
  <div class="flex items-end justify-between gap-4">
    <div>
      <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Nuevo en el predio</p>
      <h2 class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl">Recién ingresados</h2>
    </div>
    <a href="<?= url('autos.php?orden=recientes') ?>" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-ink-700 hover:text-brand-600">Ver todos <?= icon('arrow-r', 'w-4 h-4') ?></a>
  </div>
  <?php if ($recientes['items']): ?>
  <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ($recientes['items'] as $v) { include __DIR__ . '/partials/card.php'; } ?>
  </div>
  <?php else: ?>
  <div class="mt-7 rounded-2xl border border-dashed border-ink-200 p-12 text-center text-ink-500">Pronto publicaremos vehículos aquí.</div>
  <?php endif; ?>
  <div class="mt-10 text-center">
    <a href="<?= url('autos.php') ?>" class="inline-flex items-center gap-2 rounded-xl bg-ink-900 px-7 py-3.5 text-[15px] font-bold text-white hover:bg-ink-700 transition">
      Ver los <?= num($total) ?> vehículos <?= icon('arrow-r', 'w-4 h-4') ?>
    </a>
  </div>
</section>

<!-- CONSIGNACIÓN -->
<section id="consigna" class="mx-auto max-w-7xl px-4 sm:px-6 pt-24 scroll-mt-20">
  <div class="overflow-hidden rounded-3xl bg-ink-50 ring-1 ring-ink-100">
    <div class="grid gap-10 p-8 sm:p-12 lg:grid-cols-[1fr_1.3fr] lg:items-center">
      <div>
        <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">¿Quieres vender?</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Deja tu carro a consignación</h2>
        <p class="mt-4 text-ink-600">Nosotros lo fotografiamos, lo publicamos y atendemos a los compradores. Tú solo esperas la venta.</p>
        <?php if (wa_numero()): ?>
        <a href="<?= e(wa_link('Hola, quiero dejar mi carro a consignación.')) ?>" target="_blank" rel="noopener" class="mt-7 inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 font-bold text-white hover:bg-brand-600 transition">
          <?= icon('message', 'w-5 h-5') ?>Quiero consignar
        </a>
        <?php endif; ?>
      </div>
      <ol class="grid gap-4 sm:grid-cols-3">
        <?php
        $pasos = [
            ['car', 'Trae tu carro', 'Lo revisamos y acordamos contigo el precio de venta.'],
            ['camera', 'Fotos y publicación', 'Tomamos entre 20 y 30 fotos y lo publicamos aquí.'],
            ['handshake', 'Lo vendemos', 'Damos facilidades de pago al comprador y te avisamos.'],
        ];
        foreach ($pasos as $i => $p): ?>
          <li class="rounded-2xl bg-white p-5 ring-1 ring-ink-100">
            <div class="flex items-center justify-between">
              <span class="grid h-11 w-11 place-items-center rounded-xl bg-ink-900 text-white"><?= icon($p[0]) ?></span>
              <span class="text-3xl font-extrabold text-ink-100"><?= $i + 1 ?></span>
            </div>
            <h3 class="mt-4 font-bold"><?= e($p[1]) ?></h3>
            <p class="mt-1 text-sm text-ink-500"><?= e($p[2]) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>

<?php
$scripts = ['https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js'];
$script_inline = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (!document.querySelector('.destacados')) return;
  new Swiper('.destacados', {
    slidesPerView: 1.12, spaceBetween: 16, grabCursor: true,
    navigation: { nextEl: '.dest-next', prevEl: '.dest-prev' },
    autoplay: { delay: 5000, disableOnInteraction: true, pauseOnMouseEnter: true },
    breakpoints: { 640: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 24 }, 1280: { slidesPerView: 4, spaceBetween: 24 } }
  });
});
</script>
HTML;
include __DIR__ . '/partials/footer.php';
