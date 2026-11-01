(function () {
  'use strict';
  var C = window.NOVELTY || {};
  var form = document.getElementById('filtros');
  if (!form) return;

  var grid = document.getElementById('resultados');
  var vacio = document.getElementById('vacio');
  var masBtn = document.getElementById('ver-mas');
  var contador = document.getElementById('contador');
  var chipsBox = document.getElementById('chips');
  var totalTop = document.getElementById('total-top');
  var badge = document.getElementById('badge-filtros');
  var panel = document.getElementById('panel-filtros');
  var velo = document.getElementById('velo');
  var selMarca = document.getElementById('f-marca');
  var selModelo = document.getElementById('f-modelo');
  var q = document.getElementById('q');

  var pagina = 1;
  var ctrl = null;
  var timer = null;
  var fmt = new Intl.NumberFormat('es-GT');

  var ETIQUETAS = {
    marca: 'Marca', modelo: 'Modelo', procedencia: 'Procedencia', anio_min: 'Desde', anio_max: 'Hasta',
    km_min: 'Km desde', km_max: 'Km hasta', precio_min: 'Precio mín.', precio_max: 'Precio máx.', q: 'Búsqueda', oferta: 'Solo'
  };

  function parametros() {
    var p = new URLSearchParams();
    new FormData(form).forEach(function (v, k) {
      v = String(v).trim();
      if (v === '' || (k === 'orden' && v === 'relevantes')) return;
      p.append(k, v);
    });
    return p;
  }

  function cargar(reiniciar) {
    if (reiniciar) pagina = 1;
    var p = parametros();
    var url = window.location.pathname + (p.toString() ? '?' + p.toString() : '');
    history.replaceState(null, '', url);
    p.set('page', pagina);
    if (ctrl) ctrl.abort();
    ctrl = new AbortController();
    if (reiniciar) grid.style.opacity = '.45';
    masBtn.disabled = true;

    fetch(C.api + '?' + p.toString(), { signal: ctrl.signal, headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (reiniciar) { grid.innerHTML = d.html; window.scrollTo({ top: Math.min(window.scrollY, grid.getBoundingClientRect().top + window.scrollY - 160), behavior: 'smooth' }); }
        else grid.insertAdjacentHTML('beforeend', d.html);
        grid.style.opacity = '';
        vacio.classList.toggle('hidden', d.total > 0);
        masBtn.classList.toggle('hidden', !d.has_more);
        masBtn.disabled = false;
        contador.textContent = d.total ? 'Mostrando ' + fmt.format(d.mostrados) + ' de ' + fmt.format(d.total) : '';
        totalTop.textContent = fmt.format(d.total);
        document.querySelectorAll('[data-total]').forEach(function (el) { el.textContent = fmt.format(d.total); });
        pintarChips();
      })
      .catch(function (e) { if (e.name !== 'AbortError') { grid.style.opacity = ''; masBtn.disabled = false; } });
  }

  function textoDe(nombre, valor) {
    var el = form.elements[nombre] || document.querySelector('[name="' + nombre + '"][form="filtros"]');
    if (el && el.tagName === 'SELECT') {
      var o = el.querySelector('option[value="' + CSS.escape(valor) + '"]');
      if (o) return o.textContent.replace(/\s*\(\d+\)\s*$/, '');
    }
    if (nombre === 'procedencia') return valor === 'agencia' ? 'Agencia' : 'Rodado';
    if (nombre === 'oferta') return 'ofertas';
    if (nombre.indexOf('precio') === 0) return 'Q ' + fmt.format(valor);
    if (nombre.indexOf('km') === 0) return fmt.format(valor) + ' km';
    return valor;
  }

  function pintarChips() {
    var p = parametros();
    p.delete('orden');
    var n = 0, html = '';
    p.forEach(function (v, k) {
      if (k !== 'q') n++;
      html += '<button type="button" data-quitar="' + k + '" class="inline-flex items-center gap-1.5 rounded-full bg-white py-1.5 pl-3 pr-2 text-sm font-medium text-ink-700 ring-1 ring-ink-200 hover:ring-ink-400">' +
        '<span class="text-ink-400">' + (ETIQUETAS[k] || k) + ':</span> ' + escapar(textoDe(k, v)) +
        '<svg class="h-3.5 w-3.5 text-ink-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
    });
    if (html) html += '<button type="button" data-limpiar class="px-2 text-sm font-semibold text-brand-600 hover:text-brand-700">Limpiar todo</button>';
    chipsBox.innerHTML = html;
    badge.textContent = n;
    badge.classList.toggle('hidden', n === 0);
  }

  function escapar(s) {
    return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
  }

  function cargarModelos(marca, seleccionado) {
    selModelo.innerHTML = '<option value="">' + (marca ? 'Cargando…' : 'Elige una marca') + '</option>';
    selModelo.disabled = !marca;
    if (!marca) return Promise.resolve();
    return fetch(C.modelos + '?marca=' + encodeURIComponent(marca))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var html = '<option value="">Todos</option>';
        d.modelos.forEach(function (m) {
          html += '<option value="' + m.id + '"' + (String(m.id) === String(seleccionado) ? ' selected' : '') + '>' + escapar(m.nombre) + ' (' + m.total + ')</option>';
        });
        selModelo.innerHTML = html;
      });
  }

  function limpiar() {
    form.reset();
    Array.prototype.forEach.call(form.elements, function (el) {
      if (el.tagName === 'SELECT') el.value = el.id === 'orden' ? el.value : '';
      else if (el.type === 'radio') el.checked = el.value === '';
      else if (el.type === 'checkbox') el.checked = false;
      else if (el.type !== 'button' && el.type !== 'submit') el.value = '';
    });
    q.value = '';
    cargarModelos('');
    cargar(true);
  }

  /* Eventos */
  form.addEventListener('submit', function (e) { e.preventDefault(); cargar(true); });

  // Se escucha en document: el buscador y "Ordenar" están fuera del <form> (atributo form="filtros")
  // y su evento change no llega al formulario.
  document.addEventListener('change', function (e) {
    if (!e.target.form || e.target.form !== form) return;
    if (e.target === selMarca) { cargarModelos(selMarca.value).then(function () { cargar(true); }); return; }
    if (e.target.matches('input[type=number], input[type=search]')) return;
    cargar(true);
  });

  document.addEventListener('input', function (e) {
    if (e.target.form !== form || !e.target.matches('input[type=number], input[type=search]')) return;
    clearTimeout(timer);
    timer = setTimeout(function () { cargar(true); }, 380);
  });

  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-quitar],[data-limpiar],[data-km],[data-cerrar],#abrir-filtros,#velo');
    if (!t) return;
    if (t.hasAttribute('data-limpiar')) { limpiar(); return; }
    if (t.hasAttribute('data-quitar')) {
      var k = t.getAttribute('data-quitar');
      if (k === 'q') q.value = '';
      else if (k === 'procedencia') form.querySelector('input[name=procedencia][value=""]').checked = true;
      else if (k === 'oferta') form.elements.oferta.checked = false;
      else if (form.elements[k]) form.elements[k].value = '';
      if (k === 'marca') { cargarModelos('').then(function () { cargar(true); }); return; }
      cargar(true); return;
    }
    if (t.hasAttribute('data-km')) { form.elements.km_min.value = ''; form.elements.km_max.value = t.getAttribute('data-km'); cargar(true); return; }
    if (t.id === 'abrir-filtros') { panel.classList.add('abierto'); velo.classList.remove('hidden'); document.body.style.overflow = 'hidden'; return; }
    panel.classList.remove('abierto'); velo.classList.add('hidden'); document.body.style.overflow = '';
  });

  masBtn.addEventListener('click', function () { pagina++; cargar(false); });

  pintarChips();
})();
