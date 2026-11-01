<?php
require __DIR__ . '/app/bootstrap.php';

$id = gi('id');
$v  = $id ? vehiculo($id, true) : null;

if (!$v) {
    http_response_code(404);
    $titulo = 'Vehículo no disponible · ' . negocio();
    include __DIR__ . '/partials/head.php';
    include __DIR__ . '/partials/header.php';
    ?>
    <section class="mx-auto max-w-xl px-4 py-24 text-center">
      <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-ink-50 text-ink-400"><?= icon('car', 'w-8 h-8') ?></span>
      <h1 class="mt-5 text-2xl font-extrabold">Este vehículo ya no está disponible</h1>
      <p class="mt-2 text-ink-500">Puede que ya se haya vendido. Mira otras opciones similares en el inventario.</p>
      <a href="<?= url('autos.php') ?>" class="mt-6 inline-flex rounded-xl bg-ink-900 px-6 py-3 font-bold text-white">Ver vehículos</a>
    </section>
    <?php
    include __DIR__ . '/partials/footer.php';
    exit;
}

if (empty($_SESSION['visto'][$id])) {
    $_SESSION['visto'][$id] = 1;
    q('UPDATE vehiculos SET vistas = vistas + 1 WHERE id = ?', [$id]);
}

$fotos = vehiculo_fotos($id);
$similares = q_all(VEH_SELECT . ' WHERE v.estado IN ' . ESTADOS_PUBLICOS . ' AND v.id <> ? AND (v.marca_id = ? OR v.carroceria = ?)
    ORDER BY (v.marca_id = ?) DESC, ABS(v.precio_publicado - ?) ASC LIMIT 4', [$id, $v['marca_id'], (string)$v['carroceria'], $v['marca_id'], $v['precio_publicado']]);

$nombre    = vehiculo_titulo($v);
$titulo    = $nombre . ' ' . $v['anio'] . ' · ' . money($v['precio_publicado']) . ' · ' . negocio();
$descripcion = $nombre . ' ' . ($v['version'] ?: '') . ' ' . $v['anio'] . ', ' . km($v['kilometraje']) . ', ' . (PROCEDENCIAS[$v['procedencia']] ?? '') . '. ' . money($v['precio_publicado']) . '.';
$og_imagen = $fotos ? abs_url('uploads/vehiculos/' . $fotos[0]['archivo']) : null;
$nav_activo = 'vehiculos';
$usa_swiper = true;
$ocultar_wa_flotante = true;
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';

$specs = array_filter([
    ['calendar', 'Año', $v['anio']],
    ['gauge', 'Kilometraje', km($v['kilometraje'])],
    ['shield', 'Procedencia', PROCEDENCIAS[$v['procedencia']] ?? ''],
    ['gear', 'Transmisión', $v['transmision'] ? TRANSMISIONES[$v['transmision']] : ''],
    ['fuel', 'Combustible', $v['combustible'] ? COMBUSTIBLES[$v['combustible']] : ''],
    ['engine', 'Motor', $v['motor']],
    ['wheel', 'Tracción', $v['traccion']],
    ['car', 'Carrocería', $v['carroceria']],
    ['palette', 'Color', $v['color']],
    ['door', 'Puertas', $v['puertas']],
    ['users', 'Pasajeros', $v['pasajeros']],
    ['hash', 'Código', $v['codigo']],
], function ($s) {
    return $s[2] !== null && $s[2] !== '';
});
$etiquetas = array_filter(array_map('trim', explode(',', (string)$v['etiquetas'])));
$msg = vehiculo_wa_mensaje($v);
?>

<div class="mx-auto max-w-7xl px-4 sm:px-6 pt-6 pb-28 lg:pb-10">
  <nav class="flex flex-wrap items-center gap-1 text-xs font-medium text-ink-400">
    <a href="<?= url('') ?>" class="hover:text-ink-700">Inicio</a><span>/</span>
    <a href="<?= url('autos.php') ?>" class="hover:text-ink-700">Vehículos</a><span>/</span>
    <a href="<?= url('autos.php?marca=' . (int)$v['marca_id']) ?>" class="hover:text-ink-700"><?= e($v['marca']) ?></a><span>/</span>
    <span class="text-ink-600"><?= e($v['modelo'] . ' ' . $v['anio']) ?></span>
  </nav>

  <div class="mt-5 grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
    <!-- GALERÍA -->
    <div class="min-w-0">
      <div class="relative overflow-hidden rounded-2xl bg-ink-100">
        <div class="swiper galeria aspect-[4/3]">
          <div class="swiper-wrapper">
            <?php if ($fotos): foreach ($fotos as $i => $ft): ?>
              <div class="swiper-slide cursor-zoom-in" data-i="<?= $i ?>">
                <img src="<?= e(foto_url($ft['archivo'])) ?>" alt="<?= e($nombre . ' foto ' . ($i + 1)) ?>" class="h-full w-full object-cover" <?= $i > 1 ? 'loading="lazy"' : '' ?>>
              </div>
            <?php endforeach; else: ?>
              <div class="swiper-slide"><img src="<?= url('assets/img/sin-foto.svg') ?>" alt="" class="h-full w-full object-cover"></div>
            <?php endif; ?>
          </div>
        </div>
        <?php if (count($fotos) > 1): ?>
          <button class="gal-prev absolute left-3 top-1/2 z-10 -translate-y-1/2 grid h-11 w-11 place-items-center rounded-full bg-white/90 text-ink-800 shadow hover:bg-white" aria-label="Anterior"><?= icon('chev-l') ?></button>
          <button class="gal-next absolute right-3 top-1/2 z-10 -translate-y-1/2 grid h-11 w-11 place-items-center rounded-full bg-white/90 text-ink-800 shadow hover:bg-white" aria-label="Siguiente"><?= icon('chev-r') ?></button>
          <span class="absolute bottom-3 left-3 z-10 rounded-lg bg-ink-900/75 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur"><span id="gal-num">1</span> / <?= count($fotos) ?></span>
          <button class="gal-zoom absolute bottom-3 right-3 z-10 inline-flex items-center gap-1.5 rounded-lg bg-ink-900/75 px-2.5 py-1 text-xs font-semibold text-white backdrop-blur"><?= icon('zoom', 'w-4 h-4') ?>Pantalla completa</button>
        <?php endif; ?>
      </div>
      <?php if (count($fotos) > 1): ?>
      <div class="swiper thumbs mt-3">
        <div class="swiper-wrapper">
          <?php foreach ($fotos as $ft): ?>
            <div class="swiper-slide !w-24 sm:!w-28 overflow-hidden rounded-lg aspect-[4/3]"><img src="<?= e(foto_url($ft['archivo'], 'sm')) ?>" alt="" loading="lazy" class="h-full w-full object-cover"></div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Specs -->
      <section class="mt-10">
        <h2 class="text-xl font-extrabold tracking-tight">Características</h2>
        <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-ink-100 ring-1 ring-ink-100 sm:grid-cols-3">
          <?php foreach ($specs as $s): ?>
            <div class="flex items-center gap-3 bg-white p-4">
              <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-ink-50 text-ink-500"><?= icon($s[0]) ?></span>
              <div class="min-w-0">
                <dt class="text-xs font-medium text-ink-400"><?= e($s[1]) ?></dt>
                <dd class="truncate text-[15px] font-bold text-ink-900"><?= e($s[2]) ?></dd>
              </div>
            </div>
          <?php endforeach; ?>
        </dl>
      </section>

      <?php if ($v['descripcion']): ?>
      <section class="mt-10">
        <h2 class="text-xl font-extrabold tracking-tight">Descripción</h2>
        <div class="mt-4 max-w-3xl whitespace-pre-line text-[15px] leading-relaxed text-ink-600"><?= e($v['descripcion']) ?></div>
      </section>
      <?php endif; ?>

      <?php if ($etiquetas): ?>
      <section class="mt-8 flex flex-wrap gap-2">
        <?php foreach ($etiquetas as $t): ?>
          <a href="<?= url('autos.php?q=' . urlencode($t)) ?>" class="inline-flex items-center gap-1.5 rounded-full bg-ink-50 px-3 py-1.5 text-sm font-medium text-ink-700 ring-1 ring-ink-100 hover:ring-ink-300"><?= icon('check', 'w-3.5 h-3.5 text-emerald-600') ?><?= e($t) ?></a>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>
    </div>

    <!-- PANEL DE COMPRA -->
    <aside>
      <div class="lg:sticky lg:top-24 space-y-4">
        <div class="rounded-2xl bg-white p-6 ring-1 ring-ink-100 shadow-soft">
          <div class="flex flex-wrap items-center gap-2">
            <?= badge_procedencia($v['procedencia']) ?>
            <?php if ($v['estado'] === 'reservado'): ?><span class="inline-flex rounded-md bg-amber-400 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-950">Reservado</span><?php endif; ?>
            <span class="ml-auto text-xs font-semibold text-ink-400"><?= e($v['codigo']) ?></span>
          </div>
          <h1 class="mt-3 text-2xl font-extrabold leading-tight tracking-tight sm:text-[28px]"><?= e($nombre) ?></h1>
          <p class="mt-1 text-ink-500"><?= e(trim(($v['version'] ?: '') . ' · ' . $v['anio'], ' ·')) ?></p>
          <div class="mt-4 flex flex-wrap gap-2 text-sm">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-ink-50 px-2.5 py-1.5 font-semibold text-ink-700"><?= icon('calendar', 'w-4 h-4 text-ink-400') ?><?= (int)$v['anio'] ?></span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-ink-50 px-2.5 py-1.5 font-semibold text-ink-700"><?= icon('gauge', 'w-4 h-4 text-ink-400') ?><?= km($v['kilometraje']) ?></span>
            <?php if ($v['transmision']): ?><span class="inline-flex items-center gap-1.5 rounded-lg bg-ink-50 px-2.5 py-1.5 font-semibold text-ink-700"><?= icon('gear', 'w-4 h-4 text-ink-400') ?><?= e(TRANSMISIONES[$v['transmision']]) ?></span><?php endif; ?>
          </div>
          <div class="mt-6 border-t border-ink-100 pt-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">Precio</p>
            <p class="mt-1 text-4xl font-extrabold tracking-tight tabular-nums"><?= money($v['precio_publicado']) ?></p>
            <p class="mt-1 text-sm text-ink-500">Pregunta por nuestras facilidades de pago.</p>
          </div>
          <div class="mt-6 grid gap-2">
            <?php if (wa_numero()): ?>
            <a href="<?= e(wa_link($msg)) ?>" target="_blank" rel="noopener" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-[#25D366] font-bold text-white hover:brightness-95 transition"><?= icon('message') ?>Me interesa · WhatsApp</a>
            <?php endif; ?>
            <div class="grid grid-cols-2 gap-2">
              <a href="<?= e(tel_link()) ?>" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-ink-200 font-semibold text-ink-800 hover:bg-ink-50"><?= icon('phone', 'w-4 h-4') ?>Llamar</a>
              <button type="button" id="compartir" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-ink-200 font-semibold text-ink-800 hover:bg-ink-50"><?= icon('share', 'w-4 h-4') ?><span>Compartir</span></button>
            </div>
          </div>
        </div>
        <ul class="grid gap-3 rounded-2xl bg-ink-50 p-5 text-sm text-ink-600 ring-1 ring-ink-100">
          <li class="flex gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-ink-700"><?= icon('cash', 'w-4 h-4') ?></span><span><b class="text-ink-900">Facilidades de pago.</b> Te ayudamos con el financiamiento.</span></li>
          <li class="flex gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-ink-700"><?= icon('handshake', 'w-4 h-4') ?></span><span><b class="text-ink-900">Recibimos tu carro.</b> Pregunta por consignación.</span></li>
          <?php if (ajuste('direccion')): ?><li class="flex gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-ink-700"><?= icon('pin', 'w-4 h-4') ?></span><button type="button" data-abrir-mapa class="text-left"><b class="text-ink-900">Visítanos.</b> <span class="underline decoration-ink-300 underline-offset-4 hover:decoration-ink-700"><?= e(ajuste('direccion')) ?></span> <span class="whitespace-nowrap font-semibold text-brand-600">· Cómo llegar</span></button></li><?php endif; ?>
        </ul>
      </div>
    </aside>
  </div>

  <?php if ($similares): ?>
  <section class="mt-20">
    <div class="flex items-end justify-between">
      <h2 class="text-2xl font-extrabold tracking-tight">También te puede interesar</h2>
      <a href="<?= url('autos.php') ?>" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-ink-700 hover:text-brand-600">Ver todos <?= icon('arrow-r', 'w-4 h-4') ?></a>
    </div>
    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <?php $actual = $v; foreach ($similares as $v) { include __DIR__ . '/partials/card.php'; } $v = $actual; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<!-- Barra fija móvil -->
<div class="fixed inset-x-0 bottom-0 z-40 border-t border-ink-100 bg-white/95 px-4 py-3 backdrop-blur lg:hidden" style="padding-bottom:max(.75rem,env(safe-area-inset-bottom))">
  <div class="flex items-center gap-3">
    <div class="min-w-0 flex-1">
      <p class="truncate text-xs font-medium text-ink-500"><?= e($nombre . ' ' . $v['anio']) ?></p>
      <p class="text-xl font-extrabold tabular-nums"><?= money($v['precio_publicado']) ?></p>
    </div>
    <a href="<?= e(tel_link()) ?>" class="grid h-12 w-12 place-items-center rounded-xl border border-ink-200 text-ink-800" aria-label="Llamar"><?= icon('phone') ?></a>
    <?php if (wa_numero()): ?><a href="<?= e(wa_link($msg)) ?>" target="_blank" rel="noopener" class="inline-flex h-12 items-center gap-2 rounded-xl bg-[#25D366] px-5 font-bold text-white"><?= icon('message') ?>WhatsApp</a><?php endif; ?>
  </div>
</div>

<!-- Visor pantalla completa -->
<?php if ($fotos): ?>
<div id="visor" class="fixed inset-0 z-[70] hidden bg-ink-950">
  <button id="visor-cerrar" class="absolute right-4 top-4 z-10 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Cerrar"><?= icon('x') ?></button>
  <span class="absolute left-4 top-6 z-10 text-sm font-semibold text-white/80"><span id="visor-num">1</span> / <?= count($fotos) ?></span>
  <div class="swiper visor h-full">
    <div class="swiper-wrapper">
      <?php foreach ($fotos as $ft): ?>
        <div class="swiper-slide grid place-items-center"><div class="swiper-zoom-container"><img src="<?= e(foto_url($ft['archivo'])) ?>" alt="" loading="lazy"></div></div>
      <?php endforeach; ?>
    </div>
    <button class="visor-prev absolute left-4 top-1/2 z-10 hidden -translate-y-1/2 sm:grid h-12 w-12 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20"><?= icon('chev-l') ?></button>
    <button class="visor-next absolute right-4 top-1/2 z-10 hidden -translate-y-1/2 sm:grid h-12 w-12 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20"><?= icon('chev-r') ?></button>
  </div>
</div>
<?php endif; ?>

<?php
$scripts = ['https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js', asset('assets/js/detalle.js')];
$script_config = '<script>window.VEHICULO = ' . json_encode(['titulo' => $nombre . ' ' . $v['anio'], 'url' => abs_url('auto/' . $v['id'])]) . ';</script>';
include __DIR__ . '/partials/footer.php';
