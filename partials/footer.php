<?php
$redes = array_filter([
    'Facebook'  => ajuste('facebook'),
    'Instagram' => ajuste('instagram'),
    'TikTok'    => ajuste('tiktok'),
]);
?>
<footer id="contacto" class="mt-24 bg-ink-950 text-ink-300">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 grid gap-10 md:grid-cols-[1.4fr_1fr_1fr]">
    <div>
      <div class="flex items-center gap-2.5">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-white text-ink-900 font-extrabold text-lg">N</span>
        <span class="text-lg font-extrabold text-white tracking-tight"><?= e(negocio()) ?></span>
      </div>
      <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-400"><?= e(ajuste('eslogan', 'Vehículos seminuevos con confianza')) ?>. Recibimos tu carro a consignación y te damos facilidades de pago al comprar.</p>
      <?php if ($redes): ?>
      <div class="mt-5 flex flex-wrap gap-2">
        <?php foreach ($redes as $n => $u): ?>
          <a href="<?= e($u) ?>" target="_blank" rel="noopener" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-ink-300 hover:text-white hover:border-white/30"><?= e($n) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <h4 class="text-xs font-semibold uppercase tracking-[.16em] text-ink-500">Contacto</h4>
      <ul class="mt-4 space-y-3 text-sm">
        <?php if (ajuste('telefono')): ?><li class="flex gap-2.5"><?= icon('phone', 'w-4 h-4 mt-0.5 text-ink-500') ?><a href="<?= e(tel_link()) ?>" class="hover:text-white"><?= e(ajuste('telefono')) ?></a></li><?php endif; ?>
        <?php if (wa_numero()): ?><li class="flex gap-2.5"><?= icon('message', 'w-4 h-4 mt-0.5 text-ink-500') ?><a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a></li><?php endif; ?>
        <?php if (ajuste('email')): ?><li class="flex gap-2.5"><?= icon('external', 'w-4 h-4 mt-0.5 text-ink-500') ?><a href="mailto:<?= e(ajuste('email')) ?>" class="hover:text-white"><?= e(ajuste('email')) ?></a></li><?php endif; ?>
        <?php if (ajuste('direccion')): ?><li class="flex gap-2.5"><?= icon('pin', 'w-4 h-4 mt-0.5 text-ink-500') ?><span><?= e(ajuste('direccion')) ?></span></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h4 class="text-xs font-semibold uppercase tracking-[.16em] text-ink-500">Horario</h4>
      <p class="mt-4 flex gap-2.5 text-sm"><?= icon('clock', 'w-4 h-4 mt-0.5 text-ink-500') ?><?= e(ajuste('horario', 'Lunes a sábado')) ?></p>
      <a href="<?= url('autos.php') ?>" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-white hover:text-brand-300">Ver inventario <?= icon('arrow-r', 'w-4 h-4') ?></a>
    </div>
  </div>
  <div class="border-t border-white/5">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-5 flex flex-col sm:flex-row gap-2 justify-between text-xs text-ink-500">
      <span>© <?= date('Y') ?> <?= e(negocio()) ?>. Todos los derechos reservados.</span>
      <a href="<?= url('admin/') ?>" class="hover:text-ink-300">Acceso administración</a>
    </div>
  </div>
</footer>

<?php if (wa_numero() && empty($ocultar_wa_flotante)): ?>
<a href="<?= e(wa_link('Hola, quisiera información sobre sus vehículos.')) ?>" target="_blank" rel="noopener"
   class="fixed bottom-5 right-5 z-40 grid h-14 w-14 place-items-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/20 hover:scale-105 transition" aria-label="WhatsApp">
  <?= icon('message', 'w-6 h-6') ?>
</a>
<?php endif; ?>

<script>
  (function () {
    var b = document.getElementById('btn-menu'), m = document.getElementById('menu-movil');
    if (b && m) b.addEventListener('click', function () { m.classList.toggle('hidden'); });
  })();
</script>
<?= $script_config ?? '' ?>
<?php foreach (($scripts ?? []) as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
<?= $script_inline ?? '' ?>
</body>
</html>
