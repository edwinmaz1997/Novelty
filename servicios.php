<?php
require __DIR__ . '/app/bootstrap.php';

$titulo      = 'Venta, compra y consignación de vehículos';
$descripcion = 'En ' . negocio() . ' vendemos tu vehículo, lo compramos o lo recibimos a consignación en Guatemala. ' . ajuste('servicio1_texto');
$canonical   = 'servicios.php';
$nav_activo  = 'servicios';
$servicios = [];
for ($i = 1; $i <= 3; $i++) {
    if (ajuste('servicio' . $i . '_titulo') !== '') {
        $servicios[] = [$i, ajuste('servicio' . $i . '_titulo'), ajuste('servicio' . $i . '_texto')];
    }
}
$jsonld = [
    ld_migas([['Inicio', abs_url('')], ['Servicios', abs_url('servicios.php')]]),
    [
        '@context' => 'https://schema.org',
        '@type'    => 'ItemList',
        'name'     => 'Servicios de ' . negocio(),
        'itemListElement' => array_map(function ($s) {
            return ['@type' => 'ListItem', 'position' => $s[0], 'item' => [
                '@type' => 'Service', 'name' => $s[1], 'description' => $s[2],
                'provider' => ['@id' => abs_url('') . '#negocio'], 'areaServed' => 'Guatemala',
            ]];
        }, $servicios),
    ],
];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
$iconos = [1 => 'tag', 2 => 'cash', 3 => 'shield'];
?>
<section class="bg-brand-950 hero-grid">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 sm:py-20">
    <nav class="text-xs font-medium text-white/50" aria-label="Migas de pan"><a href="<?= url('') ?>" class="hover:text-white">Inicio</a> <span class="mx-1">/</span> Servicios</nav>
    <h1 class="mt-3 max-w-2xl text-4xl font-extrabold tracking-tight text-white sm:text-5xl"><?= e(ajuste('servicios_titulo', 'Te acompañamos en cada paso')) ?></h1>
    <p class="mt-4 max-w-xl text-lg text-white/70"><?= e(ajuste('marcas_subtitulo', 'Venta, consignación y compra de vehículos semi-nuevos de agencia e importados.')) ?></p>
  </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
  <ol class="grid gap-5 md:grid-cols-3">
    <?php foreach ($servicios as $s): ?>
      <li class="rounded-3xl bg-ink-50 p-7 ring-1 ring-ink-100">
        <div class="flex items-start justify-between">
          <span class="text-5xl font-extrabold tracking-tight text-brand-500 tabular-nums"><?= sprintf('%02d', $s[0]) ?></span>
          <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-brand-600 ring-1 ring-ink-100"><?= icon($iconos[$s[0]] ?? 'check', 'w-6 h-6') ?></span>
        </div>
        <h2 class="mt-6 text-2xl font-bold"><?= e($s[1]) ?></h2>
        <p class="mt-2 leading-relaxed text-ink-600"><?= e($s[2]) ?></p>
      </li>
    <?php endforeach; ?>
  </ol>

  <div class="mt-12 flex flex-col items-start justify-between gap-5 rounded-3xl bg-brand-500 p-8 text-white sm:flex-row sm:items-center sm:p-10">
    <div>
      <h2 class="text-2xl font-extrabold sm:text-3xl">¿Quieres vender o consignar tu vehículo?</h2>
      <p class="mt-1 text-white/80">Escríbenos y te damos una cotización sin compromiso.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if (wa_numero()): ?><a href="<?= e(wa_link('Hola, quiero vender / consignar mi vehículo.')) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 font-bold text-brand-700"><?= icon('whatsapp', 'w-5 h-5') ?>Escríbenos</a><?php endif; ?>
      <a href="<?= url('contacto.php') ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/40 px-6 py-3.5 font-bold text-white hover:bg-white/10">Contacto</a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php';
