<?php
require __DIR__ . '/app/bootstrap.php';

$f       = vehiculos_filtros($_GET);
$res     = vehiculos_buscar($f, true, 1, 12);
$marcas  = facetas_marcas();
$modelos = $f['marca'] ? facetas_modelos($f['marca']) : [];
$anios   = facetas_anios();
$procs   = facetas_procedencia();
$kms     = [10000, 25000, 50000, 75000, 100000, 150000, 200000];

$titulo     = ($f['oferta'] ? 'Ofertas' : 'Vehículos disponibles') . ' · ' . negocio();
$nav_activo = $f['oferta'] ? 'ofertas' : 'vehiculos';
$body_class = 'bg-ink-50/60';
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';

$sel = function ($a, $b) {
    return (string)$a === (string)$b ? ' selected' : '';
};
$campo = 'h-11 w-full rounded-xl border-ink-200 bg-white text-sm font-medium text-ink-800 focus:border-brand-500 focus:ring-brand-500';
?>

<section class="border-b border-ink-100 bg-white">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-8">
    <nav class="text-xs font-medium text-ink-400"><a href="<?= url('') ?>" class="hover:text-ink-700">Inicio</a> <span class="mx-1">/</span> Vehículos</nav>
    <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Vehículos disponibles</h1>
    <p class="mt-1 text-ink-500"><span id="total-top" class="font-semibold text-ink-800"><?= num($res['total']) ?></span> resultados</p>
  </div>
</section>

<div class="mx-auto max-w-7xl px-4 sm:px-6 py-8 lg:grid lg:grid-cols-[290px_1fr] lg:gap-8">

  <!-- FILTROS -->
  <div id="velo" class="fixed inset-0 z-50 hidden bg-ink-950/50 backdrop-blur-sm lg:hidden"></div>
  <aside id="panel-filtros" class="bg-white lg:bg-transparent">
    <form id="filtros" action="<?= url('autos.php') ?>" method="get" class="flex h-full flex-col lg:sticky lg:top-24 lg:h-auto">
      <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4 lg:hidden">
        <span class="text-lg font-bold">Filtros</span>
        <button type="button" data-cerrar class="grid h-10 w-10 place-items-center rounded-xl hover:bg-ink-50" aria-label="Cerrar"><?= icon('x') ?></button>
      </div>

      <div class="flex-1 space-y-6 overflow-y-auto p-5 lg:overflow-visible lg:rounded-2xl lg:bg-white lg:p-5 lg:ring-1 lg:ring-ink-100">
        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100">
          <span class="flex items-center gap-2 text-sm font-bold text-red-700"><?= icon('tag', 'w-4 h-4') ?>Solo ofertas</span>
          <input type="checkbox" name="oferta" value="1" <?= $f['oferta'] ? 'checked' : '' ?> class="peer sr-only">
          <span class="relative h-6 w-11 rounded-full bg-red-200 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-red-600 peer-checked:after:translate-x-5"></span>
        </label>
        <div>
          <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-ink-500" for="f-marca">Marca</label>
          <select id="f-marca" name="marca" class="select <?= $campo ?>">
            <option value="">Todas</option>
            <?php foreach ($marcas as $m): ?>
              <option value="<?= (int)$m['id'] ?>"<?= $sel($m['id'], $f['marca']) ?>><?= e($m['nombre']) ?> (<?= (int)$m['total'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-ink-500" for="f-modelo">Modelo</label>
          <select id="f-modelo" name="modelo" class="select <?= $campo ?> disabled:bg-ink-50 disabled:text-ink-400" <?= $f['marca'] ? '' : 'disabled' ?>>
            <option value=""><?= $f['marca'] ? 'Todos' : 'Elige una marca' ?></option>
            <?php foreach ($modelos as $m): ?>
              <option value="<?= (int)$m['id'] ?>"<?= $sel($m['id'], $f['modelo']) ?>><?= e($m['nombre']) ?> (<?= (int)$m['total'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-ink-500">Procedencia</span>
          <div class="grid grid-cols-3 gap-1 rounded-xl bg-ink-50 p-1">
            <?php foreach (['' => 'Todos', 'agencia' => 'Agencia', 'rodado' => 'Rodado'] as $k => $l): ?>
              <label class="cursor-pointer">
                <input type="radio" name="procedencia" value="<?= $k ?>" class="peer sr-only" <?= $f['procedencia'] === $k ? 'checked' : '' ?>>
                <span class="block rounded-lg py-2 text-center text-sm font-semibold text-ink-500 transition peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500"><?= $l ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="mt-2 text-xs text-ink-400">Agencia: vendido nuevo en Guatemala · Rodado: importado.</p>
        </div>

        <div>
          <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-ink-500">Año</span>
          <div class="grid grid-cols-2 gap-2">
            <select name="anio_min" class="select <?= $campo ?>" aria-label="Año desde">
              <option value="">Desde</option>
              <?php foreach (array_reverse($anios) as $a): ?><option value="<?= $a ?>"<?= $sel($a, $f['anio_min']) ?>><?= $a ?></option><?php endforeach; ?>
            </select>
            <select name="anio_max" class="select <?= $campo ?>" aria-label="Año hasta">
              <option value="">Hasta</option>
              <?php foreach ($anios as $a): ?><option value="<?= $a ?>"<?= $sel($a, $f['anio_max']) ?>><?= $a ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <div>
          <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-ink-500">Kilometraje</span>
          <div class="grid grid-cols-2 gap-2">
            <select name="km_min" class="select <?= $campo ?>" aria-label="Kilometraje desde">
              <option value="">Desde</option>
              <?php foreach ($kms as $k): ?><option value="<?= $k ?>"<?= $sel($k, $f['km_min']) ?>><?= num($k) ?></option><?php endforeach; ?>
            </select>
            <select name="km_max" class="select <?= $campo ?>" aria-label="Kilometraje hasta">
              <option value="">Hasta</option>
              <?php foreach ($kms as $k): ?><option value="<?= $k ?>"<?= $sel($k, $f['km_max']) ?>><?= num($k) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mt-2 flex flex-wrap gap-1.5">
            <?php foreach ([50000 => 'Menos de 50 mil', 100000 => 'Menos de 100 mil'] as $k => $l): ?>
              <button type="button" data-km="<?= $k ?>" class="rounded-lg border border-ink-200 px-2.5 py-1 text-xs font-semibold text-ink-600 hover:border-ink-400"><?= $l ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <span class="mb-2 block text-xs font-bold uppercase tracking-wider text-ink-500">Precio (<?= e(ajuste('moneda', 'Q')) ?>)</span>
          <div class="grid grid-cols-2 gap-2">
            <input type="number" inputmode="numeric" min="0" step="1000" name="precio_min" value="<?= e($f['precio_min'] ?: '') ?>" placeholder="Mínimo" class="<?= $campo ?>">
            <input type="number" inputmode="numeric" min="0" step="1000" name="precio_max" value="<?= e($f['precio_max'] ?: '') ?>" placeholder="Máximo" class="<?= $campo ?>">
          </div>
        </div>

        <button type="button" data-limpiar class="hidden w-full items-center justify-center gap-2 rounded-xl py-2.5 text-sm font-semibold text-ink-500 hover:bg-ink-50 hover:text-ink-900 lg:inline-flex"><?= icon('undo', 'w-4 h-4') ?>Limpiar filtros</button>
      </div>

      <div class="grid grid-cols-[auto_1fr] gap-2 border-t border-ink-100 p-4 lg:hidden">
        <button type="button" data-limpiar class="rounded-xl px-4 py-3 text-sm font-semibold text-ink-600 hover:bg-ink-50">Limpiar</button>
        <button type="button" data-cerrar class="rounded-xl bg-ink-900 px-4 py-3 text-sm font-bold text-white">Ver <span data-total><?= num($res['total']) ?></span> resultados</button>
      </div>
      <noscript><button class="m-4 rounded-xl bg-ink-900 px-4 py-3 text-white">Aplicar</button></noscript>
    </form>
  </aside>

  <!-- RESULTADOS -->
  <main class="min-w-0">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
      <label class="relative flex-1">
        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-ink-400"><?= icon('search') ?></span>
        <input id="q" form="filtros" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Busca por marca, modelo, color o etiqueta… ej. BMW rojo"
               autocomplete="off" class="h-12 w-full rounded-xl border-ink-200 bg-white pl-12 pr-4 text-[15px] font-medium shadow-sm placeholder:text-ink-400 focus:border-brand-500 focus:ring-brand-500">
      </label>
      <div class="flex gap-2">
        <button type="button" id="abrir-filtros" class="relative inline-flex h-12 items-center gap-2 rounded-xl border border-ink-200 bg-white px-4 text-sm font-semibold shadow-sm lg:hidden">
          <?= icon('sliders', 'w-4 h-4') ?>Filtros<span id="badge-filtros" class="hidden rounded-full bg-brand-500 px-1.5 text-[11px] font-bold text-white"></span>
        </button>
        <select id="orden" form="filtros" name="orden" class="select h-12 flex-1 rounded-xl border-ink-200 bg-white pl-4 text-sm font-semibold shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:w-56" aria-label="Ordenar">
          <?php foreach (ORDENES as $k => $l): ?><option value="<?= $k ?>"<?= $sel($k, $f['orden']) ?>><?= $l ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>

    <div id="chips" class="mt-4 flex flex-wrap gap-2"></div>

    <div id="resultados" class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-3" aria-live="polite">
      <?php foreach ($res['items'] as $v) { include __DIR__ . '/partials/card.php'; } ?>
    </div>

    <div id="vacio" class="<?= $res['total'] ? 'hidden' : '' ?> mt-5 rounded-2xl border border-dashed border-ink-200 bg-white px-6 py-16 text-center">
      <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-ink-50 text-ink-400"><?= icon('search', 'w-6 h-6') ?></span>
      <h3 class="mt-4 text-lg font-bold">No encontramos vehículos con esos filtros</h3>
      <p class="mt-1 text-sm text-ink-500">Prueba con menos filtros o escríbenos y te ayudamos a encontrarlo.</p>
      <div class="mt-5 flex justify-center gap-2">
        <button type="button" data-limpiar class="rounded-xl border border-ink-200 px-4 py-2.5 text-sm font-semibold hover:bg-ink-50">Limpiar filtros</button>
        <?php if (wa_numero()): ?><a href="<?= e(wa_link('Hola, estoy buscando un vehículo y no lo encontré en el sitio.')) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-white"><?= icon('whatsapp', 'w-4 h-4') ?>Pedir por WhatsApp</a><?php endif; ?>
      </div>
    </div>

    <div class="mt-10 text-center">
      <p id="contador" class="text-sm text-ink-500"><?= $res['total'] ? 'Mostrando ' . count($res['items']) . ' de ' . num($res['total']) : '' ?></p>
      <button type="button" id="ver-mas" class="<?= $res['has_more'] ? '' : 'hidden' ?> mt-3 inline-flex items-center gap-2 rounded-xl bg-ink-900 px-7 py-3.5 text-sm font-bold text-white hover:bg-ink-700 transition">
        Cargar más vehículos
      </button>
    </div>
  </main>
</div>

<?php
$scripts = [asset('assets/js/catalogo.js')];
$script_config = '<script>window.NOVELTY = ' . json_encode([
    'api'     => url('api/vehiculos.php'),
    'modelos' => url('api/modelos.php'),
    'pagina'  => url('autos.php'),
]) . ';</script>';
include __DIR__ . '/partials/footer.php';
