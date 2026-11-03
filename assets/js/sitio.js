/* Sitio: menú móvil, hoja de mapas y entrada animada de títulos */
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
    window.addEventListener('resize', function () { if (window.innerWidth >= 1024) poner(false); });
  })();


  /* Entrada animada de los títulos de sección al aparecer en pantalla */
  (function () {
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var titulos = document.querySelectorAll('main h1, main h2, section h1, section h2, #resultados ~ h2, body > div h2, body > div h1');
    var vistos = [];
    titulos.forEach(function (h) {
      if (h.id === 'hero-titulo' || vistos.indexOf(h) >= 0 || h.closest('footer, #menu-movil, #hoja-mapa')) return;
      vistos.push(h);
      h.classList.add('rev-titulo');
      // Divide en palabras solo si el título es texto plano
      if (h.children.length === 0) {
        var palabras = h.textContent.trim().split(/\s+/);
        h.textContent = '';
        palabras.forEach(function (w, i) {
          var o = document.createElement('span'), d = document.createElement('span');
          o.className = 'rev-mascara'; d.className = 'rev-palabra';
          d.style.transitionDelay = (i * 70) + 'ms';
          d.textContent = w;
          o.appendChild(d); h.appendChild(o);
          if (i < palabras.length - 1) h.appendChild(document.createTextNode(' '));
        });
      } else {
        h.classList.add('rev-bloque');
      }
      // Ceja (texto pequeño en mayúsculas encima del título)
      var ceja = h.previousElementSibling;
      if (ceja && ceja.tagName === 'P' && /uppercase/.test(ceja.className)) ceja.classList.add('rev-ceja');
      var sub = h.nextElementSibling;
      if (sub && sub.tagName === 'P' && !sub.id) sub.classList.add('rev-sub');
    });
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        // Si el usuario saltó más abajo (enlace #ancla), los títulos que quedaron arriba se muestran sin animar
        if (!e.isIntersecting && e.boundingClientRect.bottom > 0) return;
        var h = e.target;
        h.classList.add('rev-visible');
        var c = h.previousElementSibling, s = h.nextElementSibling;
        if (c && c.classList.contains('rev-ceja')) c.classList.add('rev-visible');
        if (s && s.classList.contains('rev-sub')) s.classList.add('rev-visible');
        io.unobserve(h);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.2 });
    vistos.forEach(function (h) { io.observe(h); });
    var pend = false;
    window.addEventListener('scroll', function () {
      if (pend) return; pend = true;
      requestAnimationFrame(function () {
        pend = false;
        vistos.forEach(function (h) {
          if (!h.classList.contains('rev-visible') && h.getBoundingClientRect().bottom < 0) {
            [h, h.previousElementSibling, h.nextElementSibling].forEach(function (x) { if (x) x.classList.add('rev-visible'); });
            io.unobserve(h);
          }
        });
      });
    }, { passive: true });
  })();
