<?php
require __DIR__ . '/app/bootstrap.php';

$destacados = vehiculos_buscar(['destacado' => 1], true, 1, 10)['items'];
if (count($destacados) < 3) {
    $destacados = vehiculos_buscar(['orden' => 'relevantes'], true, 1, 10)['items'];
}
$recientes = vehiculos_buscar(['orden' => 'recientes'], true, 1, 8);
$total     = $recientes['total'];
$marcas    = facetas_marcas();
$logos     = ajuste('mostrar_marcas', '1') === '1'
    ? q_all("SELECT m.id, m.nombre, m.logo, COUNT(v.id) AS total FROM marcas m
        LEFT JOIN vehiculos v ON v.marca_id = m.id AND v.estado IN " . ESTADOS_PUBLICOS . "
        WHERE m.en_portada = 1 AND m.logo IS NOT NULL AND m.logo <> ''
        GROUP BY m.id, m.nombre, m.logo, m.orden ORDER BY m.orden, m.nombre")
    : [];
$heroImg   = img_ajuste('hero_imagen');
$anios     = facetas_anios();
$procs     = facetas_procedencia();

$titulo     = negocio() . ' · ' . ajuste('eslogan', 'Una nueva experiencia');
$nav_activo = 'inicio';
$usa_swiper = true;
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$atajos = ['Pick-up', 'SUV', '4x4', 'Automático', 'Diésel', 'Único dueño'];
?>

<!-- HERO -->
<section class="relative overflow-hidden bg-brand-950 <?= $heroImg ? 'hero-img' : 'hero-grid' ?>"<?= $heroImg ? ' style="background-image:url(\'' . e($heroImg) . '\')"' : '' ?>>
  <div class="relative mx-auto max-w-7xl px-4 sm:px-6 pt-14 pb-20 sm:pt-24 sm:pb-32">
    <div class="max-w-3xl">
      <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-white/80">
        <span class="h-1.5 w-1.5 rounded-full bg-brand-300"></span><?= num($total) ?> vehículos disponibles hoy
      </span>
      <h1 class="mt-5 text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-6xl">
        <?= e(ajuste('hero_titulo', 'Una nueva')) ?><?php if (ajuste('hero_titulo_color')): ?><br><span class="text-brand-300"><?= e(ajuste('hero_titulo_color')) ?></span><?php endif; ?>
      </h1>
      <?php if (ajuste('hero_subtitulo')): ?><p class="mt-5 max-w-xl text-base text-white/70 sm:text-lg"><?= e(ajuste('hero_subtitulo')) ?></p><?php endif; ?>
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
      <span class="text-xs font-semibold uppercase tracking-wider text-white/50 mr-1">Populares</span>
      <?php foreach ($atajos as $a): ?>
        <a href="<?= url('autos.php?q=' . urlencode($a)) ?>" class="rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-sm font-medium text-white/80 hover:border-white/40 hover:text-white transition"><?= e($a) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DESTACADOS -->
<?php if ($destacados): ?>
<section class="mx-auto max-w-7xl overflow-hidden px-4 sm:px-6 pt-16 pb-2">
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
<?php if ($logos): ?>
<section id="marcas" class="mt-20 scroll-mt-16 bg-brand-950 hero-grid">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20">
    <div class="mx-auto max-w-2xl text-center">
      <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl"><?= e(ajuste('marcas_titulo', 'Nuestras Marcas')) ?></h2>
      <?php if (ajuste('marcas_subtitulo')): ?><p class="mt-3 text-white/70"><?= e(ajuste('marcas_subtitulo')) ?></p><?php endif; ?>
    </div>
    <div class="relative mt-12">
      <div class="swiper marcas">
        <div class="swiper-wrapper">
          <?php foreach ($logos as $m): ?>
            <div class="swiper-slide">
              <a href="<?= url('autos.php?marca=' . (int)$m['id']) ?>" class="group relative flex h-28 items-center justify-center rounded-2xl border border-white/10 bg-white/[.03] px-6 transition hover:border-white/30 hover:bg-white/[.07] sm:h-32" title="<?= e($m['nombre']) ?>">
                <img src="<?= e(url($m['logo'])) ?>" alt="<?= e($m['nombre']) ?>" loading="lazy" class="max-h-14 max-w-[70%] object-contain opacity-80 transition group-hover:scale-105 group-hover:opacity-100 sm:max-h-16">
                <?php if ((int)$m['total']): ?><span class="absolute bottom-2.5 text-[11px] font-semibold text-white/0 transition group-hover:text-white/70"><?= (int)$m['total'] ?> disponible<?= (int)$m['total'] === 1 ? '' : 's' ?></span><?php endif; ?>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="mt-8 flex items-center justify-center gap-3">
        <button class="marcas-prev grid h-11 w-11 place-items-center rounded-full border border-white/15 text-white/80 hover:border-white/40 hover:text-white" aria-label="Anterior"><?= icon('chev-l') ?></button>
        <div class="marcas-pag !static flex !w-auto items-center gap-1.5"></div>
        <button class="marcas-next grid h-11 w-11 place-items-center rounded-full border border-white/15 text-white/80 hover:border-white/40 hover:text-white" aria-label="Siguiente"><?= icon('chev-r') ?></button>
      </div>
    </div>
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
    <?php foreach ($recientes['items'] as $i => $v): ?>
      <div class="<?= $i >= 4 ? 'hidden sm:block' : '' ?>"><?php include __DIR__ . '/partials/card.php'; ?></div>
    <?php endforeach; ?>
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

<!-- SERVICIOS -->
<section id="servicios" class="mx-auto max-w-7xl scroll-mt-20 px-4 pt-24 sm:px-6">
  <div class="grid gap-10 lg:grid-cols-[1fr_2fr] lg:items-end">
    <div>
      <p class="text-xs font-bold uppercase tracking-[.18em] text-brand-600">Servicios</p>
      <h2 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl"><?= e(ajuste('servicios_titulo', 'Te acompañamos en cada paso')) ?></h2>
    </div>
    <div class="flex flex-wrap gap-2 lg:justify-end">
      <?php if (wa_numero()): ?>
        <a href="<?= e(wa_link('Hola, quiero vender / consignar mi vehículo.')) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-3 text-sm font-bold text-white hover:bg-brand-600 transition"><?= icon('message', 'w-4 h-4') ?>Quiero vender mi carro</a>
      <?php endif; ?>
      <a href="<?= url('autos.php') ?>" class="inline-flex items-center gap-2 rounded-xl border border-ink-200 px-5 py-3 text-sm font-bold text-ink-800 hover:bg-ink-50 transition">Ver inventario <?= icon('arrow-r', 'w-4 h-4') ?></a>
    </div>
  </div>
  <ol class="mt-10 grid gap-5 md:grid-cols-3">
    <?php
    $iconos = [1 => 'tag', 2 => 'cash', 3 => 'shield'];
    for ($i = 1; $i <= 3; $i++):
        $t = ajuste('servicio' . $i . '_titulo');
        if ($t === '') {
            continue;
        } ?>
      <li class="group relative overflow-hidden rounded-3xl bg-ink-50 p-7 ring-1 ring-ink-100 transition hover:bg-white hover:shadow-soft">
        <div class="flex items-start justify-between">
          <span class="text-5xl font-extrabold tracking-tight text-brand-500/90 tabular-nums"><?= sprintf('%02d', $i) ?></span>
          <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-brand-600 ring-1 ring-ink-100 transition group-hover:bg-brand-500 group-hover:text-white"><?= icon($iconos[$i], 'w-6 h-6') ?></span>
        </div>
        <h3 class="mt-6 text-xl font-bold"><?= e($t) ?></h3>
        <p class="mt-2 leading-relaxed text-ink-600"><?= e(ajuste('servicio' . $i . '_texto')) ?></p>
      </li>
    <?php endfor; ?>
  </ol>
</section>

<?php
$scripts = ['https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js'];
$script_inline = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (document.querySelector('.marcas')) {
    var n = document.querySelectorAll('.marcas .swiper-slide').length;
    new Swiper('.marcas', {
      slidesPerView: 2.2, spaceBetween: 12, grabCursor: true, loop: n > 6, speed: 600,
      autoplay: { delay: 2600, disableOnInteraction: false, pauseOnMouseEnter: true },
      navigation: { nextEl: '.marcas-next', prevEl: '.marcas-prev' },
      pagination: { el: '.marcas-pag', clickable: true, bulletClass: 'marca-punto', bulletActiveClass: 'activo' },
      breakpoints: { 640: { slidesPerView: 3, spaceBetween: 16 }, 1024: { slidesPerView: 5, spaceBetween: 16 }, 1280: { slidesPerView: 6, spaceBetween: 16 } }
    });
  }
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
