<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
$u = requiere_login();
$admin = es_admin();

$id = gi('id');
$v = $id ? vehiculo($id) : null;
if ($id && !$v) {
    flash('error', 'El vehículo no existe.');
    redirect('admin/vehiculos.php');
}
$vendido = $v && $v['estado'] === 'vendido';
if ($vendido && !$admin) {
    flash('error', 'Ese vehículo ya fue vendido y no puede editarse.');
    redirect('admin/vehiculos.php');
}

$errores = [];
$d = $v ?: [
    'marca_id' => '', 'modelo_id' => '', 'version' => '', 'anio' => '', 'kilometraje' => '', 'procedencia' => 'rodado',
    'color' => '', 'transmision' => 'automatica', 'combustible' => 'gasolina', 'motor' => '', 'carroceria' => '', 'traccion' => '',
    'puertas' => '', 'pasajeros' => '', 'descripcion' => '', 'etiquetas' => '', 'precio_publicado' => '', 'precio_oferta' => '', 'destacado' => 0,
    'estado' => 'disponible', 'tipo_ingreso' => 'consignacion', 'propietario_id' => '', 'precio_propietario' => '', 'gastos' => '0',
    'fecha_ingreso' => date('Y-m-d'), 'notas_internas' => '',
];

if (es_post()) {
    csrf_check();
    $n = function ($k) {
        $x = p($k);
        return $x === '' ? null : $x;
    };
    $in = [
        'marca_id'         => (int)p('marca_id'),
        'modelo_id'        => (int)p('modelo_id'),
        'version'          => mb_substr(p('version'), 0, 80),
        'anio'             => (int)p('anio'),
        'kilometraje'      => (int)a_numero(p('kilometraje')),
        'procedencia'      => isset(PROCEDENCIAS[p('procedencia')]) ? p('procedencia') : '',
        'color'            => mb_substr(p('color'), 0, 40),
        'transmision'      => isset(TRANSMISIONES[p('transmision')]) ? p('transmision') : null,
        'combustible'      => isset(COMBUSTIBLES[p('combustible')]) ? p('combustible') : null,
        'motor'            => mb_substr(p('motor'), 0, 30),
        'carroceria'       => in_array(p('carroceria'), CARROCERIAS, true) ? p('carroceria') : null,
        'traccion'         => in_array(p('traccion'), opciones('traccion'), true) ? p('traccion') : null,
        'puertas'          => $n('puertas') !== null ? (int)p('puertas') : null,
        'pasajeros'        => $n('pasajeros') !== null ? (int)p('pasajeros') : null,
        'descripcion'      => p('descripcion'),
        'etiquetas'        => mb_substr(implode(', ', etiquetas_lista(p('etiquetas'))), 0, 255),
        'precio_publicado' => a_numero(p('precio_publicado')),
        'precio_oferta'    => a_numero(p('precio_oferta')) ?: null,
        'destacado'        => p('destacado') === '1' ? 1 : 0,
    ];
    if (!$vendido) {
        $in['estado'] = in_array(p('estado'), ['disponible', 'reservado', 'oculto'], true) ? p('estado') : 'disponible';
    }
    if ($admin) {
        $in['tipo_ingreso']       = isset(TIPOS_INGRESO[p('tipo_ingreso')]) ? p('tipo_ingreso') : 'consignacion';
        $in['propietario_id']     = (int)p('propietario_id') ?: null;
        $in['precio_propietario'] = a_numero(p('precio_propietario'));
        $in['gastos']             = a_numero(p('gastos')) ?: 0;
        $in['fecha_ingreso']      = preg_match('/^\d{4}-\d{2}-\d{2}$/', p('fecha_ingreso')) ? p('fecha_ingreso') : date('Y-m-d');
        $in['notas_internas']     = p('notas_internas');
    }

    if (!$in['marca_id'] || !$in['modelo_id'] || !q_val('SELECT 1 FROM modelos WHERE id = ? AND marca_id = ?', [$in['modelo_id'], $in['marca_id']])) {
        $errores[] = 'Elige la marca y el modelo.';
    }
    if ($in['anio'] < 1950 || $in['anio'] > (int)date('Y') + 1) {
        $errores[] = 'Escribe un año válido.';
    }
    if ($in['procedencia'] === '') {
        $errores[] = 'Indica si es de agencia o rodado.';
    }
    if (!$in['precio_publicado'] || $in['precio_publicado'] <= 0 || $in['precio_publicado'] > 99999999) {
        $errores[] = 'Escribe un precio de venta al público válido.';
    }
    if ($in['precio_oferta'] !== null && $in['precio_publicado'] && $in['precio_oferta'] >= $in['precio_publicado']) {
        $errores[] = 'El precio de oferta debe ser menor al precio normal (o déjalo vacío).';
    }
    if ($in['kilometraje'] < 0 || $in['kilometraje'] > 2000000) {
        $errores[] = 'Revisa el kilometraje.';
    }
    if ($admin && (($in['precio_propietario'] ?? 0) > 99999999 || $in['gastos'] > 99999999 || ($in['precio_propietario'] ?? 0) < 0 || $in['gastos'] < 0)) {
        $errores[] = 'Revisa el precio del propietario y los gastos.';
    }
    if ($in['propietario_id'] ?? null) {
        if (!q_val('SELECT 1 FROM clientes WHERE id = ?', [$in['propietario_id']])) {
            $in['propietario_id'] = null;
        }
    }

    if (!$errores) {
        $in['actualizado_en'] = date('Y-m-d H:i:s');
        if ($v) {
            $sets = implode(', ', array_map(function ($k) {
                return "$k = ?";
            }, array_keys($in)));
            q("UPDATE vehiculos SET $sets WHERE id = ?", array_merge(array_values($in), [$id]));
            flash('ok', 'Cambios guardados.');
        } else {
            if (!$admin) {
                $in['fecha_ingreso'] = date('Y-m-d');
            }
            $in['creado_por'] = $u['id'];
            $cols = implode(', ', array_keys($in));
            $qm = implode(', ', array_fill(0, count($in), '?'));
            q("INSERT INTO vehiculos ($cols) VALUES ($qm)", array_values($in));
            $id = (int)db()->lastInsertId();
            q('UPDATE vehiculos SET codigo = ? WHERE id = ?', [codigo_vehiculo($id), $id]);
            flash('ok', 'Vehículo ingresado con código ' . codigo_vehiculo($id) . '. Ahora sube las fotos.');
        }
        etiquetas_registrar(etiquetas_lista($in['etiquetas']));
        vehiculo_actualizar_buscar($id);
        redirect('admin/vehiculo.php?id=' . $id . ($v ? '' : '#fotos'));
    }
    $d = array_merge($d, $in);
}

$marcas = q_all('SELECT id, nombre FROM marcas ORDER BY nombre');
$modelos = $d['marca_id'] ? q_all('SELECT id, nombre FROM modelos WHERE marca_id = ? ORDER BY nombre', [(int)$d['marca_id']]) : [];
$clientes = $admin ? q_all('SELECT id, nombre, telefono FROM clientes ORDER BY nombre') : [];
$fotos = $v ? vehiculo_fotos($id) : [];

$fmt = function ($n) {
    return ($n === null || $n === '') ? '' : number_format((float)$n, 0, '.', ',');
};
$sel = function ($a, $b) {
    return (string)$a === (string)$b ? ' selected' : '';
};

$titulo = $v ? vehiculo_titulo($v) . ' ' . $v['anio'] : 'Ingresar vehículo';
$acc = $v && !$vendido && $v['estado'] !== 'oculto'
    ? '<a href="' . e(vehiculo_url($v)) . '" target="_blank" class="' . C_BTN2 . '">' . icon('eye', 'w-4 h-4') . '<span class="hidden sm:inline">Ver en sitio</span></a>' : '';
if ($v && $admin) {
    $acc .= '<a href="' . url('admin/vender.php?id=' . $id) . '" class="' . C_BTN . '">' . icon('handshake', 'w-4 h-4') . '<span class="hidden sm:inline">' . ($vendido ? 'Ver venta' : 'Registrar venta') . '</span></a>';
}
admin_inicio($titulo, $v ? 'vehiculos' : 'nuevo', ['acciones' => $acc]);
?>

<?php if ($errores): ?>
  <div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">
    <p class="font-bold">Revisa lo siguiente:</p>
    <ul class="mt-1 list-disc pl-5"><?php foreach ($errores as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<?php if ($vendido): ?>
  <div class="mb-5 flex items-center gap-3 rounded-xl bg-brand-50 p-4 text-sm font-medium text-brand-800 ring-1 ring-brand-200">
    <?= icon('handshake', 'w-5 h-5') ?>Vendido el <?= fecha($v['fecha_venta']) ?> en <?= money($v['precio_venta_final']) ?>. Ya no aparece en el sitio.
  </div>
<?php endif; ?>

<form method="post" id="form-vehiculo" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
  <?= csrf_field() ?>
  <div class="space-y-6 min-w-0">
    <!-- DATOS -->
    <section class="<?= C_CARD ?> p-5 sm:p-6">
      <h2 class="flex items-center gap-2 font-bold"><?= icon('car', 'w-5 h-5 text-ink-400') ?>Datos del vehículo</h2>
      <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <label class="<?= C_LABEL ?>">Marca *
          <div class="flex gap-1.5">
            <select name="marca_id" id="marca" required class="select <?= C_INPUT ?>">
              <option value="">Elegir…</option>
              <?php foreach ($marcas as $m): ?><option value="<?= $m['id'] ?>"<?= $sel($m['id'], $d['marca_id']) ?>><?= e($m['nombre']) ?></option><?php endforeach; ?>
            </select>
            <button type="button" id="nueva-marca" class="mt-1.5 grid w-11 shrink-0 place-items-center rounded-xl border border-ink-200 text-ink-500 hover:bg-ink-50" title="Agregar marca"><?= icon('plus', 'w-4 h-4') ?></button>
          </div>
        </label>
        <label class="<?= C_LABEL ?>">Modelo *
          <div class="flex gap-1.5">
            <select name="modelo_id" id="modelo" required class="select <?= C_INPUT ?>" <?= $modelos ? '' : 'disabled' ?>>
              <option value=""><?= $d['marca_id'] ? 'Elegir…' : 'Primero la marca' ?></option>
              <?php foreach ($modelos as $m): ?><option value="<?= $m['id'] ?>"<?= $sel($m['id'], $d['modelo_id']) ?>><?= e($m['nombre']) ?></option><?php endforeach; ?>
            </select>
            <button type="button" id="nuevo-modelo" class="mt-1.5 grid w-11 shrink-0 place-items-center rounded-xl border border-ink-200 text-ink-500 hover:bg-ink-50" title="Agregar modelo"><?= icon('plus', 'w-4 h-4') ?></button>
          </div>
        </label>
        <label class="<?= C_LABEL ?>">Versión / línea
          <input name="version" value="<?= e($d['version']) ?>" placeholder="Ej. SRV 4x4, EX, M Sport" class="<?= C_INPUT ?>">
        </label>
        <label class="<?= C_LABEL ?>">Año *
          <input type="number" name="anio" value="<?= e($d['anio']) ?>" min="1950" max="<?= date('Y') + 1 ?>" required placeholder="<?= date('Y') - 3 ?>" class="<?= C_INPUT ?>">
        </label>
        <label class="<?= C_LABEL ?>">Kilometraje *
          <input name="kilometraje" value="<?= e($fmt($d['kilometraje'])) ?>" inputmode="numeric" data-numero required placeholder="45,000" class="<?= C_INPUT ?>">
        </label>
        <div>
          <span class="<?= C_LABEL ?>">Procedencia *</span>
          <div class="mt-1.5 grid grid-cols-2 gap-1 rounded-xl bg-ink-50 p-1 ring-1 ring-ink-200">
            <?php foreach (PROCEDENCIAS as $k => $l): ?>
              <label class="cursor-pointer"><input type="radio" name="procedencia" value="<?= $k ?>" class="peer sr-only" <?= $d['procedencia'] === $k ? 'checked' : '' ?>>
                <span class="block rounded-lg py-2 text-center text-sm font-semibold text-ink-500 peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm"><?= $l ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <label class="<?= C_LABEL ?>">Color
          <input name="color" value="<?= e($d['color']) ?>" list="colores" placeholder="Ej. Rojo" class="<?= C_INPUT ?>">
          <datalist id="colores"><?php foreach (COLORES as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
        </label>
        <label class="<?= C_LABEL ?>">Transmisión
          <select name="transmision" class="select <?= C_INPUT ?>"><option value="">—</option><?php foreach (TRANSMISIONES as $k => $l): ?><option value="<?= $k ?>"<?= $sel($k, $d['transmision']) ?>><?= $l ?></option><?php endforeach; ?></select>
        </label>
        <label class="<?= C_LABEL ?>">Combustible
          <select name="combustible" class="select <?= C_INPUT ?>"><option value="">—</option><?php foreach (COMBUSTIBLES as $k => $l): ?><option value="<?= $k ?>"<?= $sel($k, $d['combustible']) ?>><?= $l ?></option><?php endforeach; ?></select>
        </label>
        <label class="<?= C_LABEL ?>">Carrocería
          <select name="carroceria" class="select <?= C_INPUT ?>"><option value="">—</option><?php foreach (CARROCERIAS as $c): ?><option<?= $sel($c, $d['carroceria']) ?>><?= $c ?></option><?php endforeach; ?></select>
        </label>
        <label class="<?= C_LABEL ?>">Tracción
          <div class="flex gap-1.5">
            <select name="traccion" id="traccion" class="select <?= C_INPUT ?>"><option value="">—</option><?php $trs = opciones('traccion'); if ($d['traccion'] && !in_array($d['traccion'], $trs, true)) { $trs[] = $d['traccion']; } foreach ($trs as $c): ?><option<?= $sel($c, $d['traccion']) ?>><?= e($c) ?></option><?php endforeach; ?></select>
            <button type="button" id="nueva-traccion" class="mt-1.5 grid w-11 shrink-0 place-items-center rounded-xl border border-ink-200 text-ink-500 hover:bg-ink-50" title="Agregar tracción"><?= icon('plus', 'w-4 h-4') ?></button>
          </div>
        </label>
        <label class="<?= C_LABEL ?>">Motor
          <input name="motor" value="<?= e($d['motor']) ?>" placeholder="Ej. 2.4L Turbo" class="<?= C_INPUT ?>">
        </label>
        <div class="grid grid-cols-2 gap-3">
          <label class="<?= C_LABEL ?>">Puertas<input type="number" name="puertas" min="2" max="6" value="<?= e($d['puertas']) ?>" class="<?= C_INPUT ?>"></label>
          <label class="<?= C_LABEL ?>">Pasajeros<input type="number" name="pasajeros" min="1" max="20" value="<?= e($d['pasajeros']) ?>" class="<?= C_INPUT ?>"></label>
        </div>
      </div>
    </section>

    <!-- PUBLICACIÓN -->
    <section class="<?= C_CARD ?> p-5 sm:p-6">
      <h2 class="flex items-center gap-2 font-bold"><?= icon('tag', 'w-5 h-5 text-ink-400') ?>Publicación</h2>
      <div class="mt-5 grid gap-4 sm:grid-cols-2">
        <label class="<?= C_LABEL ?>">Precio al cliente (<?= e(ajuste('moneda', 'Q')) ?>) *
          <input name="precio_publicado" id="precio_publicado" value="<?= e($fmt($d['precio_publicado'])) ?>" inputmode="decimal" data-numero required placeholder="150,000" class="<?= C_INPUT ?> text-base font-bold">
        </label>
        <label class="<?= C_LABEL ?>">Precio de oferta <span class="font-normal text-ink-400">(opcional)</span>
          <input name="precio_oferta" id="precio_oferta" value="<?= e($fmt($d['precio_oferta'])) ?>" inputmode="decimal" data-numero placeholder="Vacío = sin oferta" class="<?= C_INPUT ?> font-bold text-red-600 placeholder:font-normal">
          <span id="oferta-info" class="mt-1 block text-xs font-medium text-ink-400">Si lo llenas, el sitio muestra el precio anterior tachado y el descuento.</span>
        </label>
        <label class="<?= C_LABEL ?>">Estado
          <?php if ($vendido): ?>
            <div class="mt-1.5"><?= badge_estado('vendido') ?></div>
          <?php else: ?>
            <select name="estado" class="select <?= C_INPUT ?>">
              <option value="disponible"<?= $sel('disponible', $d['estado']) ?>>Disponible · visible en el sitio</option>
              <option value="reservado"<?= $sel('reservado', $d['estado']) ?>>Reservado · visible con etiqueta</option>
              <option value="oculto"<?= $sel('oculto', $d['estado']) ?>>Oculto · no se muestra</option>
            </select>
          <?php endif; ?>
        </label>
        <div class="sm:col-span-2">
          <div class="flex items-center justify-between">
            <span class="<?= C_LABEL ?>">Etiquetas <span class="font-normal text-ink-400">(toca para agregar o quitar; ayudan a la búsqueda)</span></span>
            <?php if ($admin): ?><a href="<?= url('admin/etiquetas.php') ?>" class="text-xs font-semibold text-brand-600 hover:underline">Administrar</a><?php endif; ?>
          </div>
          <input type="hidden" name="etiquetas" id="etiquetas" value="<?= e($d['etiquetas']) ?>">
          <div id="etiquetas-chips" class="mt-2 flex flex-wrap gap-1.5">
            <?php
            $catalogo = array_column(etiquetas_catalogo(), 'nombre');
            foreach (etiquetas_lista($d['etiquetas']) as $t) {
                if (!in_array(mb_strtolower($t), array_map('mb_strtolower', $catalogo), true)) {
                    $catalogo[] = $t;
                }
            }
            foreach ($catalogo as $t): ?>
              <button type="button" data-etiqueta="<?= e($t) ?>" class="rounded-lg border border-ink-200 px-2.5 py-1.5 text-xs font-semibold text-ink-600 transition hover:border-ink-400"><?= e($t) ?></button>
            <?php endforeach; ?>
            <span class="inline-flex items-center gap-1 rounded-lg border border-dashed border-ink-300 pl-2 pr-1">
              <input type="text" id="etiqueta-nueva" placeholder="Nueva etiqueta…" maxlength="60" class="w-32 border-0 bg-transparent p-1 text-xs focus:ring-0">
              <button type="button" id="etiqueta-agregar" class="grid h-6 w-6 place-items-center rounded-md text-ink-500 hover:bg-ink-100" title="Agregar"><?= icon('plus', 'w-3.5 h-3.5') ?></button>
            </span>
          </div>
        </div>
        <label class="<?= C_LABEL ?> sm:col-span-2">Descripción
          <textarea name="descripcion" rows="5" placeholder="Estado general, servicios, extras, detalles importantes…" class="<?= C_INPUT ?>"><?= e($d['descripcion']) ?></textarea>
        </label>
        <label class="flex items-center gap-3 rounded-xl bg-ink-50 p-4 text-sm sm:col-span-2">
          <input type="checkbox" name="destacado" value="1" <?= (int)$d['destacado'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-ink-300 text-brand-500 focus:ring-brand-500">
          <span><b>Destacado</b> <span class="text-ink-500">· aparece en el carrusel de la página principal</span></span>
        </label>
      </div>
    </section>

    <!-- FOTOS -->
    <section id="fotos" class="<?= C_CARD ?> p-5 sm:p-6 scroll-mt-24">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="flex items-center gap-2 font-bold"><?= icon('camera', 'w-5 h-5 text-ink-400') ?>Fotos <span class="text-sm font-medium text-ink-400">(<span id="num-fotos"><?= count($fotos) ?></span>/<?= MAX_FOTOS ?>)</span></h2>
        <?php if ($v): ?><p class="text-xs text-ink-400">Arrastra para ordenar · la primera es la portada</p><?php endif; ?>
      </div>
      <?php if (!$v): ?>
        <div class="mt-4 rounded-xl border-2 border-dashed border-ink-200 p-8 text-center text-sm text-ink-500">Guarda el vehículo primero y luego podrás subir hasta <?= MAX_FOTOS ?> fotos.</div>
      <?php else: ?>
        <label id="dropzone" class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-ink-200 bg-ink-50/50 px-6 py-8 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
          <span class="grid h-12 w-12 place-items-center rounded-xl bg-white text-brand-600 shadow-sm"><?= icon('upload', 'w-6 h-6') ?></span>
          <span class="mt-3 text-sm font-bold">Toca para elegir fotos o arrástralas aquí</span>
          <span class="mt-1 text-xs text-ink-400">JPG, PNG o WEBP · se optimizan automáticamente</span>
          <input type="file" id="input-fotos" accept="image/*" multiple class="sr-only">
        </label>
        <div id="galeria-admin" class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
          <?php foreach ($fotos as $i => $ft): ?>
            <div class="foto group relative aspect-[4/3] overflow-hidden rounded-xl bg-ink-100 ring-1 ring-ink-200" data-id="<?= $ft['id'] ?>">
              <img src="<?= e(foto_url($ft['archivo'], 'sm')) ?>" class="h-full w-full object-cover" alt="">
              <span class="portada absolute left-2 top-2 rounded-md bg-brand-500 px-2 py-0.5 text-[11px] font-bold text-white">Portada</span>
              <span class="absolute bottom-2 left-2 grid h-7 w-7 cursor-grab place-items-center rounded-md bg-white/90 text-ink-600 shadow"><?= icon('grip', 'w-4 h-4') ?></span>
              <button type="button" data-borrar-foto class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-lg bg-white/90 text-ink-700 shadow hover:bg-red-500 hover:text-white" title="Eliminar foto"><?= icon('trash', 'w-4 h-4') ?></button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <!-- LATERAL -->
  <div class="space-y-6">
    <?php if ($admin): ?>
    <section class="<?= C_CARD ?> p-5 sm:p-6">
      <h2 class="flex items-center gap-2 font-bold"><?= icon('cash', 'w-5 h-5 text-ink-400') ?>Ingreso y costos <span class="ml-auto rounded-md bg-ink-100 px-2 py-0.5 text-[11px] font-semibold text-ink-500">Solo admin</span></h2>
      <div class="mt-5 space-y-4">
        <div>
          <span class="<?= C_LABEL ?>">Tipo de ingreso</span>
          <div class="mt-1.5 grid grid-cols-2 gap-1 rounded-xl bg-ink-50 p-1 ring-1 ring-ink-200">
            <?php foreach (TIPOS_INGRESO as $k => $l): ?>
              <label class="cursor-pointer"><input type="radio" name="tipo_ingreso" value="<?= $k ?>" class="peer sr-only" <?= $d['tipo_ingreso'] === $k ? 'checked' : '' ?>>
                <span class="block rounded-lg py-2 text-center text-sm font-semibold text-ink-500 peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm"><?= $l ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <label class="<?= C_LABEL ?>"><span id="lbl-propietario"><?= $d['tipo_ingreso'] === 'compra' ? 'Le compramos a' : 'Propietario (cliente)' ?></span>
          <div class="flex gap-1.5">
            <select name="propietario_id" id="propietario" class="select <?= C_INPUT ?>">
              <option value="">— Sin asignar —</option>
              <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>"<?= $sel($c['id'], $d['propietario_id']) ?>><?= e($c['nombre'] . ($c['telefono'] ? ' · ' . $c['telefono'] : '')) ?></option><?php endforeach; ?>
            </select>
            <button type="button" id="nuevo-cliente" class="mt-1.5 grid w-11 shrink-0 place-items-center rounded-xl border border-ink-200 text-ink-500 hover:bg-ink-50" title="Nuevo cliente"><?= icon('plus', 'w-4 h-4') ?></button>
          </div>
        </label>
        <label class="<?= C_LABEL ?>"><span id="lbl-precio-prop"><?= $d['tipo_ingreso'] === 'compra' ? 'Precio de compra' : 'Precio que pide el propietario' ?></span>
          <input name="precio_propietario" id="precio_propietario" value="<?= e($fmt($d['precio_propietario'])) ?>" inputmode="decimal" data-numero placeholder="140,000" class="<?= C_INPUT ?> font-semibold">
        </label>
        <label class="<?= C_LABEL ?>">Gastos (reparaciones, papelería…)
          <input name="gastos" id="gastos" value="<?= e($fmt($d['gastos'])) ?>" inputmode="decimal" data-numero placeholder="0" class="<?= C_INPUT ?>">
        </label>
        <label class="<?= C_LABEL ?>">Fecha de ingreso al predio
          <input type="date" name="fecha_ingreso" value="<?= e($d['fecha_ingreso']) ?>" max="<?= date('Y-m-d') ?>" class="<?= C_INPUT ?>">
        </label>
        <label class="<?= C_LABEL ?>">Notas internas
          <textarea name="notas_internas" rows="3" placeholder="Acuerdos con el propietario, ofertas recibidas…" class="<?= C_INPUT ?>"><?= e($d['notas_internas']) ?></textarea>
        </label>
      </div>
      <div id="resumen" class="mt-5 rounded-xl bg-brand-950 p-4 text-sm text-ink-300" data-comision="<?= e(ajuste('comision_pct', '5')) ?>" data-moneda="<?= e(ajuste('moneda', 'Q')) ?>">
        <div class="flex justify-between"><span>Precio al cliente</span><span id="r-precio" class="font-semibold text-white tabular-nums">—</span></div>
        <div class="mt-1 flex justify-between"><span>− Propietario</span><span id="r-prop" class="tabular-nums">—</span></div>
        <div class="mt-1 flex justify-between"><span>− Gastos</span><span id="r-gastos" class="tabular-nums">—</span></div>
        <div class="mt-3 flex items-end justify-between border-t border-white/10 pt-3"><span class="font-semibold text-white"><?= $vendido ? 'Ganancia (según precio actual)' : 'Ganancia esperada' ?></span><span id="r-ganancia" class="text-xl font-extrabold text-emerald-400 tabular-nums">—</span></div>
        <p id="r-margen" class="mt-1 text-right text-xs text-ink-400"></p>
        <p id="r-comision" class="mt-2 text-xs text-ink-500"></p>
      </div>
    </section>
    <?php endif; ?>

    <div class="<?= C_CARD ?> sticky top-20 p-5">
      <button class="<?= C_BTN_BRAND ?> h-12 w-full text-base"><?= icon('check', 'w-5 h-5') ?><?= $v ? 'Guardar cambios' : 'Guardar y subir fotos' ?></button>
      <a href="<?= url('admin/vehiculos.php') ?>" class="<?= C_BTN2 ?> mt-2 w-full">Cancelar</a>
      <?php if ($v): ?>
        <dl class="mt-4 space-y-1.5 border-t border-ink-100 pt-4 text-xs text-ink-500">
          <div class="flex justify-between"><dt>Código</dt><dd class="font-semibold text-ink-800"><?= e($v['codigo']) ?></dd></div>
          <div class="flex justify-between"><dt>Días en predio</dt><dd><?= badge_dias(dias_predio($v)) ?></dd></div>
          <div class="flex justify-between"><dt>Visitas en el sitio</dt><dd class="font-semibold text-ink-800"><?= num($v['vistas']) ?></dd></div>
        </dl>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php
admin_fin(
    [asset('assets/vendor/Sortable.min.js'), asset('assets/js/admin.js')],
    '<script>window.PANEL = ' . json_encode([
        'ajax'       => url('admin/ajax.php'),
        'vehiculoId' => $v ? (int)$id : null,
        'maxFotos'   => MAX_FOTOS,
        'csrf'       => csrf_token(),
    ]) . '; Panel.vehiculo();</script>'
);
