(function () {
  'use strict';
  var P = window.PANEL || {};
  var fmt = new Intl.NumberFormat('es-GT', { maximumFractionDigits: 2 });

  function numero(v) { var n = parseFloat(String(v || '').replace(/[^0-9.\-]/g, '')); return isNaN(n) ? null : n; }
  function dinero(n, m) { return (n < 0 ? '-' : '') + (m || 'Q') + ' ' + fmt.format(Math.abs(Math.round(n))); }

  function post(accion, datos) {
    var fd = datos instanceof FormData ? datos : new FormData();
    if (!(datos instanceof FormData)) Object.keys(datos || {}).forEach(function (k) { fd.append(k, datos[k]); });
    fd.append('_csrf', P.csrf);
    return fetch(P.ajax + '?accion=' + accion, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (!d.ok) throw new Error(d.error || 'Error'); return d; });
  }

  function aviso(msg, error) {
    var t = document.createElement('div');
    t.className = 'fixed bottom-5 left-1/2 z-[80] -translate-x-1/2 rounded-xl px-4 py-3 text-sm font-semibold shadow-lg ' + (error ? 'bg-red-600 text-white' : 'bg-ink-900 text-white');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 3200);
  }

  /* ---------- Campos numéricos con comas ---------- */
  function numerosConFormato() {
    document.querySelectorAll('[data-numero]').forEach(function (el) {
      el.addEventListener('blur', function () { var n = numero(el.value); el.value = n === null ? '' : fmt.format(n); });
    });
  }

  /* ---------- Formulario de vehículo ---------- */
  function vehiculo() {
    P = window.PANEL || {};
    numerosConFormato();
    var marca = document.getElementById('marca');
    var modelo = document.getElementById('modelo');

    function llenarModelos(lista, sel) {
      modelo.innerHTML = '<option value="">Elegir…</option>' + lista.map(function (m) {
        return '<option value="' + m.id + '"' + (String(m.id) === String(sel) ? ' selected' : '') + '>' + m.nombre.replace(/</g, '&lt;') + '</option>';
      }).join('');
      modelo.disabled = false;
    }
    function cargarModelos(sel) {
      if (!marca.value) { modelo.innerHTML = '<option value="">Primero la marca</option>'; modelo.disabled = true; return Promise.resolve(); }
      return fetch(P.ajax + '?accion=modelos&marca=' + marca.value, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).then(function (d) { llenarModelos(d.modelos, sel); });
    }
    marca.addEventListener('change', function () { cargarModelos(); });

    document.getElementById('nueva-marca').addEventListener('click', function () {
      var n = prompt('Nombre de la nueva marca:');
      if (!n || !n.trim()) return;
      post('crear_marca', { nombre: n.trim() }).then(function (d) {
        if (!marca.querySelector('option[value="' + d.id + '"]')) marca.add(new Option(d.nombre, d.id));
        marca.value = d.id; cargarModelos();
      }).catch(function (e) { aviso(e.message, true); });
    });
    document.getElementById('nuevo-modelo').addEventListener('click', function () {
      if (!marca.value) { aviso('Primero elige la marca', true); return; }
      var n = prompt('Nuevo modelo de ' + marca.options[marca.selectedIndex].text + ':');
      if (!n || !n.trim()) return;
      post('crear_modelo', { marca_id: marca.value, nombre: n.trim() }).then(function (d) { cargarModelos(d.id); })
        .catch(function (e) { aviso(e.message, true); });
    });

    var nt = document.getElementById('nueva-traccion');
    if (nt) nt.addEventListener('click', function () {
      var n = prompt('Nueva tracción (ej. 4x4, AWD, RWD):');
      if (!n || !n.trim()) return;
      post('crear_opcion', { tipo: 'traccion', nombre: n.trim() }).then(function (d) {
        var s = document.getElementById('traccion');
        var o = Array.prototype.find.call(s.options, function (x) { return x.value.toLowerCase() === d.nombre.toLowerCase(); });
        if (!o) { o = new Option(d.nombre, d.nombre); s.add(o); }
        s.value = o.value;
      }).catch(function (e) { aviso(e.message, true); });
    });

    var nc = document.getElementById('nuevo-cliente');
    if (nc) nc.addEventListener('click', function () {
      var n = prompt('Nombre completo del cliente:');
      if (!n || !n.trim()) return;
      var t = prompt('Teléfono (opcional):') || '';
      post('crear_cliente', { nombre: n.trim(), telefono: t.trim() }).then(function (d) {
        var s = document.getElementById('propietario');
        s.add(new Option(d.nombre + (d.telefono ? ' · ' + d.telefono : ''), d.id)); s.value = d.id;
      }).catch(function (e) { aviso(e.message, true); });
    });

    // Etiquetas (chips que se activan/desactivan + nueva etiqueta)
    var et = document.getElementById('etiquetas'), chips = document.getElementById('etiquetas-chips');
    function lista() { return et.value.split(',').map(function (x) { return x.trim(); }).filter(Boolean); }
    function igual(a, b) { return a.toLowerCase() === b.toLowerCase(); }
    function marcarEtiquetas() {
      var l = lista();
      chips.querySelectorAll('[data-etiqueta]').forEach(function (b) {
        var on = l.some(function (x) { return igual(x, b.getAttribute('data-etiqueta')); });
        b.classList.toggle('bg-brand-500', on); b.classList.toggle('text-white', on); b.classList.toggle('border-brand-500', on);
        b.setAttribute('aria-pressed', on);
      });
    }
    function alternar(t) {
      var l = lista(), i = l.findIndex(function (x) { return igual(x, t); });
      if (i >= 0) l.splice(i, 1); else l.push(t);
      et.value = l.join(', '); marcarEtiquetas();
    }
    chips.addEventListener('click', function (e) { var b = e.target.closest('[data-etiqueta]'); if (b) alternar(b.getAttribute('data-etiqueta')); });
    var nueva = document.getElementById('etiqueta-nueva');
    function agregarNueva() {
      var t = nueva.value.replace(/,/g, ' ').replace(/\s+/g, ' ').trim();
      if (!t) return;
      var existe = Array.prototype.find.call(chips.querySelectorAll('[data-etiqueta]'), function (b) { return igual(b.getAttribute('data-etiqueta'), t); });
      if (!existe) {
        existe = document.createElement('button'); existe.type = 'button'; existe.setAttribute('data-etiqueta', t);
        existe.className = 'rounded-lg border border-ink-200 px-2.5 py-1.5 text-xs font-semibold text-ink-600 transition hover:border-ink-400';
        existe.textContent = t; nueva.parentNode.before(existe);
      }
      if (!lista().some(function (x) { return igual(x, t); })) alternar(existe.getAttribute('data-etiqueta'));
      nueva.value = '';
    }
    document.getElementById('etiqueta-agregar').addEventListener('click', agregarNueva);
    nueva.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); agregarNueva(); } });
    marcarEtiquetas();

    // Resumen de ganancia
    var res = document.getElementById('resumen');
    if (res) {
      var m = res.getAttribute('data-moneda'), com = parseFloat(res.getAttribute('data-comision')) || 0;
      var ids = ['precio_publicado', 'precio_oferta', 'precio_propietario', 'gastos'];
      var calc = function () {
        var pr = numero(document.getElementById('precio_publicado').value) || 0;
        var of = numero(document.getElementById('precio_oferta').value);
        if (of && of < pr) pr = of;
        var po = numero(document.getElementById('precio_propietario').value);
        var ga = numero(document.getElementById('gastos').value) || 0;
        document.getElementById('r-precio').textContent = pr ? dinero(pr, m) : '—';
        document.getElementById('r-prop').textContent = po !== null ? dinero(po, m) : 'falta';
        document.getElementById('r-gastos').textContent = dinero(ga, m);
        var g = po !== null && pr ? pr - po - ga : null;
        var rg = document.getElementById('r-ganancia');
        rg.textContent = g === null ? '—' : dinero(g, m);
        rg.className = 'text-xl font-extrabold tabular-nums ' + (g !== null && g < 0 ? 'text-red-400' : 'text-emerald-400');
        document.getElementById('r-margen').textContent = g !== null && pr ? (g / pr * 100).toFixed(1) + '% del precio' : '';
        document.getElementById('r-comision').textContent = pr && com ? 'Referencia: ' + com + '% de comisión = ' + dinero(pr * com / 100, m) : '';
      };
      ids.forEach(function (id) { document.getElementById(id).addEventListener('input', calc); });
      calc();
      document.querySelectorAll('input[name=tipo_ingreso]').forEach(function (r) {
        r.addEventListener('change', function () {
          var compra = r.value === 'compra' && r.checked;
          document.getElementById('lbl-propietario').textContent = compra ? 'Le compramos a' : 'Propietario (cliente)';
          document.getElementById('lbl-precio-prop').textContent = compra ? 'Precio de compra' : 'Precio que pide el propietario';
        });
      });
    }

    // Indicador del descuento de la oferta
    var pOf = document.getElementById('precio_oferta'), pPu = document.getElementById('precio_publicado'), info = document.getElementById('oferta-info');
    function infoOferta() {
      var a = numero(pPu.value), o = numero(pOf.value);
      if (!o) { info.className = 'mt-1 block text-xs font-medium text-ink-400'; info.textContent = 'Si lo llenas, el sitio muestra el precio anterior tachado y el descuento.'; return; }
      if (!a || o >= a) { info.className = 'mt-1 block text-xs font-bold text-red-600'; info.textContent = 'Debe ser menor al precio al cliente.'; return; }
      info.className = 'mt-1 block text-xs font-bold text-emerald-700';
      info.textContent = '−' + Math.round((1 - o / a) * 100) + '% · el cliente ahorra Q ' + fmt.format(a - o);
    }
    pOf.addEventListener('input', infoOferta); pPu.addEventListener('input', infoOferta); infoOferta();

    // Al enviar, quitar comas
    document.getElementById('form-vehiculo').addEventListener('submit', function () {
      document.querySelectorAll('[data-numero]').forEach(function (el) { var n = numero(el.value); el.value = n === null ? '' : n; });
    });

    if (P.vehiculoId) fotos();
  }

  /* ---------- Fotos ---------- */
  function fotos() {
    var grid = document.getElementById('galeria-admin');
    var input = document.getElementById('input-fotos');
    var zona = document.getElementById('dropzone');
    var contador = document.getElementById('num-fotos');
    var cola = [], subiendo = false;

    function total() { return grid.querySelectorAll('.foto').length; }
    function actualizar() {
      contador.textContent = total();
      grid.querySelectorAll('.foto').forEach(function (el, i) { var p = el.querySelector('.portada'); if (p) p.classList.toggle('hidden', i !== 0); });
    }

    function reducir(file) {
      if (!window.createImageBitmap) return Promise.resolve(file);
      return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
        var max = 1920, s = Math.min(1, max / Math.max(bmp.width, bmp.height));
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * s); c.height = Math.round(bmp.height * s);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        return new Promise(function (res) { c.toBlob(function (b) { res(b || file); }, 'image/jpeg', 0.86); });
      }).catch(function () { return file; });
    }

    function tarjeta() {
      var d = document.createElement('div');
      d.className = 'foto relative aspect-[4/3] overflow-hidden rounded-xl bg-ink-100 ring-1 ring-ink-200';
      d.innerHTML = '<div class="skeleton absolute inset-0"></div><div class="absolute inset-x-3 bottom-3 h-1.5 overflow-hidden rounded-full bg-white/70"><div class="barra h-full w-0 bg-brand-500 transition-all"></div></div>';
      grid.appendChild(d);
      return d;
    }

    function siguiente() {
      if (subiendo || !cola.length) return;
      subiendo = true;
      var item = cola.shift();
      reducir(item.file).then(function (blob) {
        var fd = new FormData();
        fd.append('vehiculo_id', P.vehiculoId);
        fd.append('foto', blob, 'foto.jpg');
        fd.append('_csrf', P.csrf);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', P.ajax + '?accion=subir_foto');
        xhr.upload.onprogress = function (e) { if (e.lengthComputable) item.el.querySelector('.barra').style.width = (e.loaded / e.total * 100) + '%'; };
        xhr.onload = function () {
          var d = {}; try { d = JSON.parse(xhr.responseText); } catch (e) { d = { ok: false, error: 'Respuesta inválida del servidor' }; }
          if (d.ok) {
            item.el.setAttribute('data-id', d.foto.id);
            item.el.className = 'foto group relative aspect-[4/3] overflow-hidden rounded-xl bg-ink-100 ring-1 ring-ink-200 fade-in';
            item.el.innerHTML = '<img src="' + d.foto.url + '" class="h-full w-full object-cover" alt="">' +
              '<span class="portada absolute left-2 top-2 rounded-md bg-brand-500 px-2 py-0.5 text-[11px] font-bold text-white">Portada</span>' +
              '<span class="absolute bottom-2 left-2 grid h-7 w-7 cursor-grab place-items-center rounded-md bg-white/90 text-ink-600 shadow"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="6" r="1"/><circle cx="15" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="18" r="1"/><circle cx="15" cy="18" r="1"/></svg></span>' +
              '<button type="button" data-borrar-foto class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-lg bg-white/90 text-ink-700 shadow hover:bg-red-500 hover:text-white" title="Eliminar foto"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 6h18M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>';
          } else {
            item.el.remove();
            aviso(d.error || 'No se pudo subir una foto', true);
          }
          actualizar(); subiendo = false; siguiente();
        };
        xhr.onerror = function () { item.el.remove(); aviso('Error de conexión al subir una foto', true); actualizar(); subiendo = false; siguiente(); };
        xhr.send(fd);
      });
    }

    function agregar(files) {
      var disponibles = P.maxFotos - total();
      var lista = Array.prototype.filter.call(files, function (f) { return /^image\//.test(f.type) || /\.(jpe?g|png|webp|heic)$/i.test(f.name); });
      if (lista.length > disponibles) { aviso('Solo puedes agregar ' + Math.max(0, disponibles) + ' foto(s) más', true); lista = lista.slice(0, Math.max(0, disponibles)); }
      lista.forEach(function (f) { cola.push({ file: f, el: tarjeta() }); });
      actualizar(); siguiente();
    }

    input.addEventListener('change', function () { agregar(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) { zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.add('border-brand-500', 'bg-brand-50'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zona.addEventListener(ev, function (e) { e.preventDefault(); zona.classList.remove('border-brand-500', 'bg-brand-50'); }); });
    zona.addEventListener('drop', function (e) { agregar(e.dataTransfer.files); });

    grid.addEventListener('click', function (e) {
      var b = e.target.closest('[data-borrar-foto]');
      if (!b) return;
      var card = b.closest('.foto');
      if (!confirm('¿Eliminar esta foto?')) return;
      post('eliminar_foto', { id: card.getAttribute('data-id') }).then(function () { card.remove(); actualizar(); guardarOrden(); })
        .catch(function (er) { aviso(er.message, true); });
    });

    function guardarOrden() {
      var ids = Array.prototype.map.call(grid.querySelectorAll('.foto[data-id]'), function (el) { return el.getAttribute('data-id'); });
      return post('ordenar_fotos', { vehiculo_id: P.vehiculoId, ids: ids.join(',') }).catch(function (er) { aviso(er.message, true); });
    }
    if (window.Sortable) {
      Sortable.create(grid, { animation: 160, filter: '[data-borrar-foto]', preventOnFilter: false, onEnd: function () { actualizar(); guardarOrden().then(function () { aviso('Orden guardado'); }); } });
    }
    actualizar();
  }

  /* ---------- Venta ---------- */
  function venta() {
    P = window.PANEL || {};
    numerosConFormato();
    var f = document.getElementById('form-venta');
    if (!f) return;
    var precio = document.getElementById('precio_venta_final');
    var costo = parseFloat(f.getAttribute('data-costo')) || 0;
    var m = f.getAttribute('data-moneda');
    var out = document.getElementById('v-ganancia');
    function calc() {
      var p = numero(precio.value);
      out.textContent = p === null ? '—' : dinero(p - costo, m);
      out.className = 'text-2xl font-extrabold tabular-nums ' + (p !== null && p - costo < 0 ? 'text-red-600' : 'text-emerald-600');
    }
    precio.addEventListener('input', calc); calc();
    var sel = document.getElementById('comprador_id'), nuevo = document.getElementById('comprador-nuevo');
    function toggle() { nuevo.classList.toggle('hidden', sel.value !== 'nuevo'); }
    sel.addEventListener('change', toggle); toggle();
    f.addEventListener('submit', function () {
      document.querySelectorAll('[data-numero]').forEach(function (el) { var n = numero(el.value); el.value = n === null ? '' : n; });
    });
  }

  window.Panel = { vehiculo: vehiculo, venta: venta, numeros: numerosConFormato };
})();
