<?php
$redes = array_filter([
    'Facebook'  => ajuste('facebook'),
    'Instagram' => ajuste('instagram'),
    'TikTok'    => ajuste('tiktok'),
]);
?>
<footer id="contacto" class="mt-24 bg-brand-950 text-brand-100/80">
  <div class="mx-auto max-w-7xl px-4 sm:px-6 py-14 grid gap-10 md:grid-cols-[1.4fr_1fr_1fr]">
    <div>
      <?= logo_html('oscuro', 'h-7 w-auto') ?>
      <p class="mt-5 max-w-sm text-sm leading-relaxed text-brand-100/60"><?= e(ajuste('marcas_subtitulo', 'Venta, consignación y compra de vehículos semi-nuevos de agencia e importados.')) ?></p>
      <?php if ($redes): ?>
      <div class="mt-5 flex flex-wrap gap-2">
        <?php foreach ($redes as $n => $u): ?>
          <a href="<?= e($u) ?>" target="_blank" rel="noopener" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-brand-100/80 hover:text-white hover:border-white/30"><?= e($n) ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div>
      <h4 class="text-xs font-semibold uppercase tracking-[.16em] text-brand-200/50">Contacto</h4>
      <ul class="mt-4 space-y-3 text-sm">
        <?php if (ajuste('telefono')): ?><li class="flex gap-2.5"><?= icon('phone', 'w-4 h-4 mt-0.5 text-brand-200/50') ?><a href="<?= e(tel_link()) ?>" class="hover:text-white"><?= e(ajuste('telefono')) ?></a></li><?php endif; ?>
        <?php if (wa_numero()): ?><li class="flex gap-2.5"><?= icon('whatsapp', 'w-4 h-4 mt-0.5 text-brand-200/50') ?><a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener" class="hover:text-white">WhatsApp</a></li><?php endif; ?>
        <?php if (ajuste('email')): ?><li class="flex gap-2.5"><?= icon('external', 'w-4 h-4 mt-0.5 text-brand-200/50') ?><a href="mailto:<?= e(ajuste('email')) ?>" class="hover:text-white"><?= e(ajuste('email')) ?></a></li><?php endif; ?>
        <?php if (ajuste('direccion')): ?><li class="flex gap-2.5"><?= icon('pin', 'w-4 h-4 mt-0.5 text-brand-200/50') ?><button type="button" data-abrir-mapa class="text-left hover:text-white underline decoration-white/20 underline-offset-4 hover:decoration-white"><?= e(ajuste('direccion')) ?></button></li><?php endif; ?>
      </ul>
    </div>
    <div>
      <h4 class="text-xs font-semibold uppercase tracking-[.16em] text-brand-200/50">Horario</h4>
      <p class="mt-4 flex gap-2.5 text-sm"><?= icon('clock', 'w-4 h-4 mt-0.5 text-brand-200/50') ?><?= e(ajuste('horario', 'Lunes a sábado')) ?></p>
      <a href="<?= url('autos.php') ?>" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-white hover:text-brand-200">Ver inventario <?= icon('arrow-r', 'w-4 h-4') ?></a>
    </div>
  </div>
  <div class="border-t border-white/5">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 py-5 flex flex-col sm:flex-row gap-2 justify-between text-xs text-brand-200/50">
      <span>© <?= date('Y') ?> <?= e(negocio()) ?>. Todos los derechos reservados.</span>
      <a href="<?= url('admin/') ?>" class="hover:text-white">Acceso administración</a>
    </div>
  </div>
</footer>

<?php if (ajuste('direccion') || mapa_destino()): ?>
<div id="hoja-mapa" class="hoja-mapa fixed inset-0 z-[70]" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="hoja-mapa-titulo">
  <div class="hoja-velo absolute inset-0 bg-ink-950/50 backdrop-blur-sm" data-cerrar-mapa></div>
  <div class="hoja-panel absolute inset-x-0 bottom-0 mx-auto w-full max-w-md rounded-t-3xl bg-white p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] shadow-2xl sm:bottom-auto sm:top-1/2 sm:rounded-3xl">
    <div class="mx-auto mb-5 h-1.5 w-12 rounded-full bg-ink-200 sm:hidden"></div>
    <div class="flex items-start gap-3">
      <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-600"><?= icon('pin') ?></span>
      <div class="min-w-0 flex-1">
        <h3 id="hoja-mapa-titulo" class="font-bold">¿Cómo quieres llegar?</h3>
        <p class="mt-0.5 text-sm text-ink-500"><?= e(ajuste('direccion')) ?></p>
      </div>
      <button type="button" data-cerrar-mapa class="grid h-9 w-9 shrink-0 place-items-center rounded-xl text-ink-400 hover:bg-ink-50 hover:text-ink-800" aria-label="Cerrar"><?= icon('x', 'w-5 h-5') ?></button>
    </div>
    <div class="mt-5 grid gap-2">
      <a href="<?= e(mapa_google()) ?>" target="_blank" rel="noopener" data-cerrar-mapa class="hoja-opcion group flex items-center gap-4 rounded-2xl p-3.5 ring-1 ring-ink-200 transition hover:bg-ink-50 hover:ring-ink-300">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-[#E8F0FE]"><svg viewBox="0 0 24 24" class="h-7 w-7" aria-hidden="true"><path fill="#EA4335" d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2.6" fill="#fff"/></svg></span>
        <span class="flex-1"><span class="block font-bold">Google Maps</span><span class="text-xs text-ink-500">Ver ubicación y cómo llegar</span></span>
        <span class="text-ink-300 transition group-hover:translate-x-1 group-hover:text-ink-600"><?= icon('arrow-r', 'w-5 h-5') ?></span>
      </a>
      <a href="<?= e(mapa_waze()) ?>" target="_blank" rel="noopener" data-cerrar-mapa class="hoja-opcion group flex items-center gap-4 rounded-2xl p-3.5 ring-1 ring-ink-200 transition hover:bg-ink-50 hover:ring-ink-300">
        <span class="grid h-12 w-12 place-items-center rounded-xl bg-[#E6F7FD]"><svg viewBox="0 0 24 24" class="h-7 w-7" aria-hidden="true"><path fill="#33CCFF" d="M12 3C7 3 3.5 6.3 3.5 10.6c0 1.7.6 3.2 1.6 4.4-.3.9-.9 1.6-1.6 2 1.3.4 2.7.2 3.8-.4 1.4.8 3 1.2 4.7 1.2 5 0 8.5-3.3 8.5-7.2S17 3 12 3z"/><circle cx="9" cy="10" r="1.1" fill="#0B1220"/><circle cx="14.5" cy="10" r="1.1" fill="#0B1220"/><path d="M9 13c1.6 1.4 4 1.4 5.6 0" stroke="#0B1220" stroke-width="1.3" fill="none" stroke-linecap="round"/><circle cx="8" cy="19.5" r="1.8" fill="#0B1220"/><circle cx="15.5" cy="19.5" r="1.8" fill="#0B1220"/></svg></span>
        <span class="flex-1"><span class="block font-bold">Waze</span><span class="text-xs text-ink-500">Navegar paso a paso</span></span>
        <span class="text-ink-300 transition group-hover:translate-x-1 group-hover:text-ink-600"><?= icon('arrow-r', 'w-5 h-5') ?></span>
      </a>
    </div>
    <button type="button" data-copiar-direccion data-texto="<?= e(ajuste('direccion')) ?>" class="mt-3 w-full rounded-xl py-2.5 text-sm font-semibold text-ink-500 hover:bg-ink-50 hover:text-ink-800">Copiar dirección</button>
  </div>
</div>
<?php endif; ?>

<?php if (wa_numero() && empty($ocultar_wa_flotante)): ?>
<a href="<?= e(wa_link('Hola, quisiera información sobre sus vehículos.')) ?>" target="_blank" rel="noopener"
   class="wa-flotante fixed bottom-5 right-5 z-40 grid h-14 w-14 place-items-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/20 hover:scale-105 transition" aria-label="WhatsApp">
  <?= icon('whatsapp', 'w-6 h-6') ?>
</a>
<?php endif; ?>

<script>
  (function () {
    // Hoja "¿Cómo quieres llegar?" (Google Maps / Waze)
    var hoja = document.getElementById('hoja-mapa');
    if (hoja) {
      var mapa = function (abrir) {
        hoja.classList.toggle('abierta', abrir); hoja.setAttribute('aria-hidden', !abrir);
        document.documentElement.classList.toggle('menu-abierto', abrir || (document.getElementById('menu-movil') || {classList:{contains:function(){return false;}}}).classList.contains('abierto'));
      };
      document.addEventListener('click', function (e) {
        if (e.target.closest('[data-abrir-mapa]')) { e.preventDefault(); mapa(true); return; }
        if (e.target.closest('[data-cerrar-mapa]')) { setTimeout(function () { mapa(false); }, 10); return; }
        var cp = e.target.closest('[data-copiar-direccion]');
        if (cp && navigator.clipboard) navigator.clipboard.writeText(cp.getAttribute('data-texto')).then(function () { var t = cp.textContent; cp.textContent = '¡Dirección copiada!'; setTimeout(function () { cp.textContent = t; }, 1600); });
      });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') mapa(false); });
    }

    var b = document.getElementById('btn-menu'), m = document.getElementById('menu-movil');
    if (!b || !m) return;
    function poner(abrir) {
      m.classList.toggle('abierto', abrir); b.classList.toggle('abierto', abrir);
      b.setAttribute('aria-expanded', abrir); m.setAttribute('aria-hidden', !abrir);
      b.setAttribute('aria-label', abrir ? 'Cerrar menú' : 'Abrir menú');
      document.documentElement.style.overflow = abrir ? 'hidden' : '';
      document.documentElement.classList.toggle('menu-abierto', abrir);
    }
    b.addEventListener('click', function () { poner(!m.classList.contains('abierto')); });
    m.querySelectorAll('[data-cierra-menu]').forEach(function (a) { a.addEventListener('click', function () { poner(false); }); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') poner(false); });
    window.addEventListener('resize', function () { if (window.innerWidth >= 768) poner(false); });
  })();
</script>
<?= $script_config ?? '' ?>
<?php foreach (($scripts ?? []) as $s): ?>
<script src="<?= e($s) ?>"></script>
<?php endforeach; ?>
<?= $script_inline ?? '' ?>
</body>
</html>
