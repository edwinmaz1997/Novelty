document.addEventListener('DOMContentLoaded', function () {
  'use strict';
  var thumbs = null, principal = null, visor = null;

  if (document.querySelector('.thumbs')) {
    thumbs = new Swiper('.thumbs', { slidesPerView: 'auto', spaceBetween: 8, freeMode: true, watchSlidesProgress: true });
  }
  principal = new Swiper('.galeria', {
    spaceBetween: 0, grabCursor: true, keyboard: { enabled: true },
    navigation: { nextEl: '.gal-next', prevEl: '.gal-prev' },
    thumbs: thumbs ? { swiper: thumbs } : undefined,
    on: { slideChange: function (s) { var n = document.getElementById('gal-num'); if (n) n.textContent = s.activeIndex + 1; } }
  });

  var cont = document.getElementById('visor');
  function abrir(i) {
    if (!cont) return;
    cont.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (!visor) {
      visor = new Swiper('.visor', {
        zoom: { maxRatio: 3 }, keyboard: { enabled: true },
        navigation: { nextEl: '.visor-next', prevEl: '.visor-prev' },
        on: { slideChange: function (s) { document.getElementById('visor-num').textContent = s.activeIndex + 1; principal.slideTo(s.activeIndex); } }
      });
    }
    visor.slideTo(i, 0);
    document.getElementById('visor-num').textContent = i + 1;
  }
  function cerrar() { cont.classList.add('hidden'); document.body.style.overflow = ''; }

  document.querySelectorAll('.galeria .swiper-slide[data-i]').forEach(function (el) {
    el.addEventListener('click', function () { abrir(parseInt(el.getAttribute('data-i'), 10)); });
  });
  var z = document.querySelector('.gal-zoom');
  if (z) z.addEventListener('click', function () { abrir(principal.activeIndex); });
  var c = document.getElementById('visor-cerrar');
  if (c) c.addEventListener('click', cerrar);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && cont && !cont.classList.contains('hidden')) cerrar(); });

  var sh = document.getElementById('compartir');
  if (sh) sh.addEventListener('click', function () {
    var d = window.VEHICULO || {};
    if (navigator.share) { navigator.share({ title: d.titulo, url: d.url }).catch(function () {}); return; }
    navigator.clipboard.writeText(d.url).then(function () {
      var s = sh.querySelector('span'); var t = s.textContent; s.textContent = '¡Enlace copiado!';
      setTimeout(function () { s.textContent = t; }, 1800);
    });
  });
});
