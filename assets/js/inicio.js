/* Inicio: carruseles infinitos y efecto de la imagen principal */
document.addEventListener('DOMContentLoaded', function () {
  // Hero: la imagen sigue levemente al mouse (sin moverse con el scroll)
  var hero = document.getElementById('hero'), capa = document.querySelector('.hero-parallax');
  if (hero && capa && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    var mx = 0, my = 0, tick = false;
    function pintar() { capa.style.transform = 'translate3d(' + mx + 'px,' + my + 'px,0)'; tick = false; }
    function pedir() { if (!tick) { tick = true; requestAnimationFrame(pintar); } }
    if (window.matchMedia('(pointer: fine)').matches) {
      hero.addEventListener('mousemove', function (e) {
        var r = hero.getBoundingClientRect();
        mx = ((e.clientX - r.left) / r.width - .5) * -18; my = ((e.clientY - r.top) / r.height - .5) * -12; pedir();
      });
      hero.addEventListener('mouseleave', function () { mx = 0; my = 0; pedir(); });
    }
  }
  // Carrusel infinito: al llegar al último, "siguiente" vuelve al primero.
  // Swiper necesita suficientes tarjetas para el loop; si hay pocas se duplican (solo en pantalla).
  var vehBp = { 640: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 24 }, 1280: { slidesPerView: 4, spaceBetween: 24 } };
  function porVista(base, bp) {
    var v = base;
    Object.keys(bp).forEach(function (w) { if (window.innerWidth >= +w) v = bp[w].slidesPerView; });
    return v;
  }
  function carrusel(sel, opc) {
    var el = document.querySelector(sel);
    if (!el) return null;
    var wrap = el.querySelector('.swiper-wrapper'), originales = Array.prototype.slice.call(wrap.children);
    var n = originales.length, vista = Math.ceil(porVista(opc.slidesPerView, opc.breakpoints || {}));
    var minimo = Math.max(vista * 2 + 2, 6);
    opc.loop = n > 1 && n > Math.floor(porVista(opc.slidesPerView, opc.breakpoints || {}));
    if (opc.loop) {
      // Se duplican juegos completos para no repetir dos tarjetas seguidas
      while (wrap.children.length < minimo) {
        originales.forEach(function (o) {
          var c = o.cloneNode(true);
          c.setAttribute('aria-hidden', 'true');
          c.querySelectorAll('a').forEach(function (a) { a.setAttribute('tabindex', '-1'); });
          wrap.appendChild(c);
        });
      }
    } else if (opc.navigation) {
      [opc.navigation.nextEl, opc.navigation.prevEl].forEach(function (s) { document.querySelectorAll(s).forEach(function (b) { b.classList.add('hidden'); }); });
    }
    return new Swiper(el, opc);
  }

  carrusel('.marcas', {
    slidesPerView: 2.2, spaceBetween: 12, grabCursor: true, speed: 450,
    autoplay: { delay: 1600, disableOnInteraction: false, pauseOnMouseEnter: true },
    navigation: { nextEl: '.marcas-next', prevEl: '.marcas-prev' },
    breakpoints: { 640: { slidesPerView: 3, spaceBetween: 16 }, 1024: { slidesPerView: 5, spaceBetween: 16 }, 1280: { slidesPerView: 6, spaceBetween: 16 } }
  });
  carrusel('.destacados', {
    slidesPerView: 1.12, spaceBetween: 16, grabCursor: true,
    navigation: { nextEl: '.dest-next', prevEl: '.dest-prev' },
    speed: 450, autoplay: { delay: 1000, disableOnInteraction: false, pauseOnMouseEnter: true },
    breakpoints: JSON.parse(JSON.stringify(vehBp))
  });
  carrusel('.ofertas', {
    slidesPerView: 1.12, spaceBetween: 16, grabCursor: true,
    navigation: { nextEl: '.ofe-next', prevEl: '.ofe-prev' },
    speed: 450, autoplay: { delay: 1000, disableOnInteraction: false, pauseOnMouseEnter: true }, breakpoints: JSON.parse(JSON.stringify(vehBp))
  });
  carrusel('.recientes', {
    slidesPerView: 1.12, spaceBetween: 16, grabCursor: true,
    navigation: { nextEl: '.rec-next', prevEl: '.rec-prev' },
    speed: 450, autoplay: { delay: 1000, disableOnInteraction: false, pauseOnMouseEnter: true }, breakpoints: JSON.parse(JSON.stringify(vehBp))
  });
});
