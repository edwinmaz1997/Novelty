<?php
require __DIR__ . '/app/bootstrap.php';

$marcasTodas = q_all('SELECT m.id, m.nombre, m.slug, m.logo, COUNT(v.id) AS total FROM marcas m
    LEFT JOIN vehiculos v ON v.marca_id = m.id AND v.estado IN ' . ESTADOS_PUBLICOS . '
    GROUP BY m.id, m.nombre, m.slug, m.logo, m.en_portada, m.orden
    HAVING total > 0 OR (m.en_portada = 1 AND m.logo IS NOT NULL)
    ORDER BY total DESC, m.orden, m.nombre');

$titulo      = 'Marcas de vehículos seminuevos';
$descripcion = negocio() . ': ' . implode(', ', array_slice(array_column($marcasTodas, 'nombre'), 0, 10)) . ' y más marcas de vehículos seminuevos de agencia e importados en Guatemala.';
$canonical   = 'marcas.php';
$nav_activo  = 'marcas';
$body_class  = 'bg-ink-50/60';
$jsonld      = [ld_migas([['Inicio', abs_url('')], ['Marcas', abs_url('marcas.php')]])];
include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/header.php';
?>
<section class="bg-brand-950 hero-grid">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 sm:py-20 text-center">
    <nav class="text-xs font-medium text-white/50" aria-label="Migas de pan"><a href="<?= url('') ?>" class="hover:text-white">Inicio</a> <span class="mx-1">/</span> Marcas</nav>
    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-white sm:text-5xl"><?= e(ajuste('marcas_titulo', 'Nuestras Marcas')) ?></h1>
    <p class="mx-auto mt-4 max-w-xl text-lg text-white/70"><?= e(ajuste('marcas_subtitulo')) ?></p>
  </div>
</section>
<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
  <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <?php foreach ($marcasTodas as $m): ?>
      <li>
        <a href="<?= e(marca_url($m['slug'])) ?>" class="group flex h-full flex-col items-center justify-center gap-3 rounded-2xl bg-white p-6 text-center ring-1 ring-ink-100 transition hover:-translate-y-0.5 hover:shadow-soft hover:ring-ink-200">
          <span class="grid h-20 w-full place-items-center rounded-xl bg-brand-950 px-4">
            <?php if ($m['logo']): ?><img src="<?= e(url($m['logo'])) ?>" alt="<?= e($m['nombre']) ?>" loading="lazy" class="max-h-12 max-w-[75%] object-contain"><?php else: ?><span class="text-lg font-extrabold text-white"><?= e($m['nombre']) ?></span><?php endif; ?>
          </span>
          <span><span class="block font-bold"><?= e($m['nombre']) ?></span><span class="text-sm text-ink-500"><?= (int)$m['total'] ?> disponible<?= (int)$m['total'] === 1 ? '' : 's' ?></span></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php include __DIR__ . '/partials/footer.php';
