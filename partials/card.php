<?php
/** Tarjeta de vehículo. Requiere $v. */
$__u = vehiculo_url($v);
?>
<a href="<?= e($__u) ?>" class="fade-in group flex h-full flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-ink-100 transition hover:-translate-y-0.5 hover:shadow-soft hover:ring-ink-200">
  <div class="relative aspect-[4/3] overflow-hidden bg-ink-50">
    <img src="<?= e(foto_url($v['portada'], 'sm')) ?>" alt="<?= e(vehiculo_titulo($v) . ' ' . $v['anio']) ?>" loading="lazy"
         class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]">
    <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
      <?= badge_procedencia($v['procedencia'], 'bg-white/95 backdrop-blur') ?>
      <?php if ($v['estado'] === 'reservado'): ?>
        <span class="inline-flex items-center rounded-md bg-amber-400 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-950">Reservado</span>
      <?php endif; ?>
    </div>
    <?php if ((int)$v['destacado']): ?>
      <span class="absolute right-3 top-3 grid h-7 w-7 place-items-center rounded-full bg-brand-500 text-white shadow" title="Destacado"><?= icon('star', 'w-3.5 h-3.5') ?></span>
    <?php endif; ?>
    <?php if ((int)$v['num_fotos'] > 1): ?>
      <span class="absolute bottom-3 right-3 inline-flex items-center gap-1 rounded-md bg-ink-900/70 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur"><?= icon('camera', 'w-3.5 h-3.5') ?><?= (int)$v['num_fotos'] ?></span>
    <?php endif; ?>
  </div>
  <div class="flex flex-1 flex-col p-4">
    <p class="text-xs font-semibold text-ink-400 tabular-nums"><?= (int)$v['anio'] ?></p>
    <h3 class="mt-0.5 text-[17px] font-bold leading-snug text-ink-900"><?= e(vehiculo_titulo($v)) ?></h3>
    <p class="text-sm text-ink-500 truncate"><?= e($v['version'] ?: ' ') ?></p>
    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-ink-500">
      <span class="inline-flex items-center gap-1"><?= icon('gauge', 'w-4 h-4 text-ink-400') ?><?= km($v['kilometraje']) ?></span>
      <?php if ($v['transmision']): ?><span class="inline-flex items-center gap-1"><?= icon('gear', 'w-4 h-4 text-ink-400') ?><?= e(TRANSMISIONES[$v['transmision']]) ?></span><?php endif; ?>
      <?php if ($v['combustible']): ?><span class="inline-flex items-center gap-1"><?= icon('fuel', 'w-4 h-4 text-ink-400') ?><?= e(COMBUSTIBLES[$v['combustible']]) ?></span><?php endif; ?>
    </div>
    <div class="mt-auto flex items-end justify-between pt-4">
      <span class="text-[22px] font-extrabold tracking-tight text-ink-900 tabular-nums"><?= money($v['precio_publicado']) ?></span>
      <span class="grid h-9 w-9 place-items-center rounded-full bg-ink-50 text-ink-600 transition group-hover:bg-brand-500 group-hover:text-white"><?= icon('arrow-r', 'w-4 h-4') ?></span>
    </div>
  </div>
</a>
