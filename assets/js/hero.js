/* Portada: título que se escribe solo y carro que pasa dejando el subtítulo con humo */
        /* Pasa un carro, deja atrás el subtítulo y una estela de humo que se desvanece */
        window.heroLlegada = function () {
          var p = document.getElementById('hero-sub'), humo = document.getElementById('hero-humo');
          if (!p) return;
          if (!p.animate || !window.requestAnimationFrame) { p.style.opacity = 1; return; }
          var alto = p.offsetHeight, ancho = p.scrollWidth;

          function bocanada(x, y, op, tamBase) {
            var b = document.createElement('span'), tam = (tamBase || 40) + Math.random() * 60;
            b.className = 'humo';
            b.style.width = b.style.height = tam + 'px';
            b.style.left = (x - tam / 2) + 'px';
            b.style.top = (y - tam / 2) + 'px';
            humo.appendChild(b);
            var dx = -(30 + Math.random() * 120), dy = -(20 + Math.random() * 60);
            b.animate([
              { transform: 'translate(0,0) scale(.35)', opacity: 0 },
              { transform: 'translate(' + dx * .2 + 'px,' + dy * .15 + 'px) scale(1)', opacity: op, offset: .14 },
              { transform: 'translate(' + dx * .6 + 'px,' + dy * .6 + 'px) scale(1.9)', opacity: op * .45, offset: .55 },
              { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(' + (2.6 + Math.random() * 1.4) + ')', opacity: 0 }
            ], { duration: 2000 + Math.random() * 1700, easing: 'cubic-bezier(.25,.6,.35,1)', fill: 'forwards' })
              .onfinish = function () { b.remove(); };
          }

          var carroSrc = p.getAttribute('data-carro');
          if (!carroSrc) { return derrape(); }
          var carro = new Image();
          carro.className = 'hero-carro';
          carro.alt = '';
          carro.onerror = derrape;
          carro.onload = function () {
            var movil = window.innerWidth < 640;
            var altoCarro = movil ? 62 : 104, anchoCarro = Math.round(altoCarro * carro.naturalWidth / carro.naturalHeight);
            var izq = p.getBoundingClientRect().left;
            var x0 = -izq - anchoCarro - 40, x1 = window.innerWidth - izq + 60;
            var y = (alto - altoCarro) / 2 + altoCarro * .12;
            carro.style.width = anchoCarro + 'px';
            carro.style.top = y + 'px';
            p.parentNode.appendChild(carro);
            p.style.opacity = 1;
            p.style.clipPath = 'inset(-20px 100% -20px -20px)';
            // Tramo 1: entra rápido y baja la velocidad al pasar sobre el texto. Tramo 2: acelera y sale disparado.
            var xm = ancho + anchoCarro * .15;
            var dur = movil ? 1700 : 2100, corte = .58, t0 = performance.now(), ultimo = 0;
            function posicion(t) {
              if (t < corte) { var a = t / corte; return x0 + (xm - x0) * (1 - Math.pow(1 - a, 2.2)); }
              var b = (t - corte) / (1 - corte); return xm + (x1 - xm) * Math.pow(b, 2.4);
            }
            (function cuadro(ahora) {
              var t = Math.min(1, (ahora - t0) / dur), x = posicion(t);
              var salto = Math.sin(ahora / 38) * (movil ? .6 : 1);
              carro.style.transform = 'translate3d(' + x + 'px,' + salto + 'px,0)';
              // el texto queda "detrás" del carro
              var revela = x + anchoCarro * .12;
              p.style.clipPath = revela >= ancho ? 'none' : 'inset(-20px ' + Math.max(0, ancho - revela) + 'px -20px -20px)';
              // humo de la llanta trasera y del escape
              if (ahora - ultimo > 22 && x > -izq - anchoCarro * .3 && x < window.innerWidth - izq) {
                ultimo = ahora;
                var base = y + altoCarro * .82;
                bocanada(x + anchoCarro * .2, base, .55, 34);
                bocanada(x + anchoCarro * .05, y + altoCarro * .6, .45, 26);
                if (Math.random() < .5) bocanada(x + anchoCarro * .78, base + 4, .35, 28);
              }
              if (t < 1) { requestAnimationFrame(cuadro); }
              else { carro.remove(); p.style.clipPath = 'none'; }
            })(t0);
          };
          carro.src = carroSrc;

          /* Respaldo sin foto: el texto entra derrapando */
          function derrape() {
            if (carro && carro.parentNode) carro.remove();
            p.style.clipPath = 'none';
            var dur = 1100, inicio = -Math.min(window.innerWidth * .4, 300);
            p.animate([
              { transform: 'translateX(' + inicio + 'px) skewX(-16deg)', opacity: 0, filter: 'blur(4px)' },
              { transform: 'translateX(' + (inicio * .3) + 'px) skewX(-12deg)', opacity: 1, filter: 'blur(1.5px)', offset: .45 },
              { transform: 'translateX(10px) skewX(5deg)', opacity: 1, filter: 'blur(0)', offset: .8 },
              { transform: 'translateX(0) skewX(0)', opacity: 1, filter: 'blur(0)' }
            ], { duration: dur, easing: 'cubic-bezier(.22,.7,.25,1)', fill: 'forwards' });
            var t0 = performance.now(), ultimo = 0;
            (function cuadro(ahora) {
              var t = ahora - t0, borde = new DOMMatrixReadOnly(getComputedStyle(p).transform).m41;
              if (t < dur) {
                if (ahora - ultimo > 28) {
                  ultimo = ahora;
                  bocanada(borde - Math.random() * 50, alto * (.25 + Math.random() * .85), t > dur * .45 ? .55 : .45);
                  if (t > dur * .45) bocanada(borde + Math.random() * ancho * .85, alto * (.7 + Math.random() * .5), .45);
                }
                requestAnimationFrame(cuadro);
              } else {
                for (var k = 0; k < 9; k++) bocanada(Math.random() * ancho * .9 - 40, alto * (.7 + Math.random() * .5), .4);
              }
            })(t0);
          }
        };
        if (window.heroListo) window.heroLlegada();

        (function () {
          var h = document.getElementById('hero-titulo');
          if (!h || !h.classList.contains('escribiendo')) return;
          var lineas = h.querySelectorAll('.maquina-texto'), cursor = document.createElement('span');
          cursor.className = 'maquina-cursor';
          var li = 0, ci = 0;
          function paso() {
            var el = lineas[li], t = el.getAttribute('data-texto');
            el.textContent = t.slice(0, ++ci); el.appendChild(cursor);
            if (ci < t.length) { setTimeout(paso, t[ci - 1] === ' ' ? 60 : 26 + Math.random() * 26); return; }
            if (++li < lineas.length) { ci = 0; setTimeout(paso, 170); return; }
            setTimeout(function () { cursor.classList.add('fin'); }, 2200);
            if (window.heroLlegada) window.heroLlegada(); else window.heroListo = true;
          }
          setTimeout(paso, 300);
        })();
