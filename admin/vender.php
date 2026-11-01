<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$id = gi('id');
$v = $id ? vehiculo($id) : null;
if (!$v) {
    flash('error', 'El vehículo no existe.');
    redirect('admin/vehiculos.php');
}
$vendido = $v['estado'] === 'vendido';
$errores = [];

if (es_post()) {
    csrf_check();
    if (p('accion') === 'revertir') {
        q("UPDATE vehiculos SET estado = 'disponible', fecha_venta = NULL, precio_venta_final = NULL, comprador_id = NULL, notas_venta = NULL, actualizado_en = NOW() WHERE id = ?", [$id]);
        flash('ok', 'Se anuló la venta. El vehículo vuelve a estar disponible.');
        redirect('admin/vehiculo.php?id=' . $id);
    }

    $precio = a_numero(p('precio_venta_final'));
    $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', p('fecha_venta')) ? p('fecha_venta') : '';
    $comprador = p('comprador_id');
    if (!$precio || $precio <= 0 || $precio > 99999999) {
        $errores[] = 'Escribe un precio final de venta válido.';
    }
    if ($fecha === '' || $fecha < $v['fecha_ingreso'] || $fecha > date('Y-m-d')) {
        $errores[] = 'La fecha de venta debe estar entre la fecha de ingreso y hoy.';
    }
    if ($comprador === 'nuevo' && p('c_nombre') === '') {
        $errores[] = 'Escribe el nombre del nuevo cliente.';
    }
    if (!$errores) {
        $pdo = db();
        $pdo->beginTransaction();
        if ($comprador === 'nuevo') {
            q('INSERT INTO clientes (nombre, telefono, dpi, nit, email) VALUES (?, ?, ?, ?, ?)', [
                mb_substr(p('c_nombre'), 0, 150), p('c_telefono') ?: null, p('c_dpi') ?: null, p('c_nit') ?: null, p('c_email') ?: null,
            ]);
            $compradorId = (int)$pdo->lastInsertId();
        } else {
            $compradorId = (int)$comprador ?: null;
        }
        q("UPDATE vehiculos SET estado = 'vendido', fecha_venta = ?, precio_venta_final = ?, comprador_id = ?, notas_venta = ?, actualizado_en = NOW() WHERE id = ?",
            [$fecha, $precio, $compradorId, p('notas_venta') ?: null, $id]);
        $pdo->commit();
        $g = $precio - (float)$v['precio_propietario'] - (float)$v['gastos'];
        flash('ok', 'Venta registrada. Ganancia: ' . money($g) . '.');
        redirect('admin/vender.php?id=' . $id);
    }
}

$clientes = q_all('SELECT id, nombre, telefono FROM clientes ORDER BY nombre');
$comprador = $v['comprador_id'] ? q_one('SELECT * FROM clientes WHERE id = ?', [$v['comprador_id']]) : null;
$propietario = $v['propietario_id'] ? q_one('SELECT * FROM clientes WHERE id = ?', [$v['propietario_id']]) : null;
$costo = (float)$v['precio_propietario'] + (float)$v['gastos'];

admin_inicio(($vendido ? 'Venta · ' : 'Registrar venta · ') . vehiculo_titulo($v) . ' ' . $v['anio'], 'vehiculos', [
    'acciones' => '<a href="' . url('admin/vehiculo.php?id=' . $id) . '" class="' . C_BTN2 . '">' . icon('edit', 'w-4 h-4') . '<span class="hidden sm:inline">Ver vehículo</span></a>',
]);
?>

<?php if ($errores): ?>
  <div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200"><?php foreach ($errores as $er): ?><p><?= e($er) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
  <div>
    <section class="<?= C_CARD ?> flex gap-4 p-5">
      <img src="<?= e(foto_url($v['portada'], 'sm')) ?>" class="h-24 w-32 rounded-xl object-cover" alt="">
      <div class="min-w-0">
        <p class="text-xs font-semibold text-ink-400"><?= e($v['codigo']) ?> · <?= e(PROCEDENCIAS[$v['procedencia']]) ?> · <?= e(TIPOS_INGRESO[$v['tipo_ingreso']]) ?></p>
        <h2 class="text-lg font-bold"><?= e(vehiculo_titulo($v) . ' ' . ($v['version'] ?: '') . ' ' . $v['anio']) ?></h2>
        <p class="text-sm text-ink-500"><?= km($v['kilometraje']) ?> · ingresó <?= fecha($v['fecha_ingreso']) ?> · <?= dias_predio($v) ?> días en predio</p>
        <?php if ($propietario): ?><p class="mt-1 text-sm text-ink-500">Propietario: <a class="font-semibold text-ink-800 hover:underline" href="<?= url('admin/cliente.php?id=' . $propietario['id']) ?>"><?= e($propietario['nombre']) ?></a></p><?php endif; ?>
      </div>
    </section>

    <?php if ($vendido): ?>
      <section class="<?= C_CARD ?> mt-6 p-6">
        <div class="flex items-center gap-3">
          <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-500 text-white"><?= icon('check') ?></span>
          <div><h3 class="font-bold">Vendido el <?= fecha($v['fecha_venta']) ?></h3><p class="text-sm text-ink-500">Comprador: <?= $comprador ? '<a class="font-semibold text-ink-800 hover:underline" href="' . url('admin/cliente.php?id=' . $comprador['id']) . '">' . e($comprador['nombre']) . '</a>' : 'sin registrar' ?></p></div>
        </div>
        <?php if ($v['notas_venta']): ?><p class="mt-4 whitespace-pre-line rounded-xl bg-ink-50 p-4 text-sm text-ink-600"><?= e($v['notas_venta']) ?></p><?php endif; ?>
        <form method="post" class="mt-6" data-confirmar="¿Anular esta venta? El vehículo volverá a estar disponible en el sitio.">
          <?= csrf_field() ?><input type="hidden" name="accion" value="revertir">
          <button class="<?= C_BTN2 ?> text-red-600"><?= icon('undo', 'w-4 h-4') ?>Anular venta</button>
        </form>
      </section>
    <?php else: ?>
      <form method="post" id="form-venta" class="<?= C_CARD ?> mt-6 p-5 sm:p-6" data-costo="<?= $costo ?>" data-moneda="<?= e(ajuste('moneda', 'Q')) ?>">
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="<?= C_LABEL ?>">Precio final de venta *
            <input name="precio_venta_final" id="precio_venta_final" value="<?= e(number_format((float)(a_numero(p('precio_venta_final')) ?: precio_actual($v)), 0, '.', ',')) ?>" inputmode="decimal" data-numero required class="<?= C_INPUT ?> text-lg font-bold">
          </label>
          <label class="<?= C_LABEL ?>">Fecha de venta *
            <input type="date" name="fecha_venta" value="<?= e(p('fecha_venta', date('Y-m-d'))) ?>" min="<?= e($v['fecha_ingreso']) ?>" max="<?= date('Y-m-d') ?>" required class="<?= C_INPUT ?>">
          </label>
          <label class="<?= C_LABEL ?> sm:col-span-2">Comprador
            <select name="comprador_id" id="comprador_id" class="select <?= C_INPUT ?>">
              <option value="">— Sin registrar —</option>
              <option value="nuevo" <?= p('comprador_id') === 'nuevo' ? 'selected' : '' ?>>+ Nuevo cliente…</option>
              <?php foreach ($clientes as $c): ?><option value="<?= $c['id'] ?>" <?= p('comprador_id') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nombre'] . ($c['telefono'] ? ' · ' . $c['telefono'] : '')) ?></option><?php endforeach; ?>
            </select>
          </label>
          <div id="comprador-nuevo" class="hidden grid gap-3 rounded-xl bg-ink-50 p-4 sm:col-span-2 sm:grid-cols-2">
            <label class="<?= C_LABEL ?> sm:col-span-2">Nombre completo *<input name="c_nombre" value="<?= e(p('c_nombre')) ?>" class="<?= C_INPUT ?>"></label>
            <label class="<?= C_LABEL ?>">Teléfono<input name="c_telefono" value="<?= e(p('c_telefono')) ?>" class="<?= C_INPUT ?>"></label>
            <label class="<?= C_LABEL ?>">DPI<input name="c_dpi" value="<?= e(p('c_dpi')) ?>" class="<?= C_INPUT ?>"></label>
            <label class="<?= C_LABEL ?>">NIT<input name="c_nit" value="<?= e(p('c_nit')) ?>" class="<?= C_INPUT ?>"></label>
            <label class="<?= C_LABEL ?>">Correo<input type="email" name="c_email" value="<?= e(p('c_email')) ?>" class="<?= C_INPUT ?>"></label>
          </div>
          <label class="<?= C_LABEL ?> sm:col-span-2">Notas de la venta
            <textarea name="notas_venta" rows="3" placeholder="Forma de pago, enganche, financiamiento, acuerdos…" class="<?= C_INPUT ?>"><?= e(p('notas_venta')) ?></textarea>
          </label>
        </div>
        <button class="<?= C_BTN_BRAND ?> mt-6 h-12 w-full sm:w-auto sm:px-8"><?= icon('handshake', 'w-5 h-5') ?>Registrar venta</button>
      </form>
    <?php endif; ?>
  </div>

  <aside class="<?= C_CARD ?> h-fit p-6">
    <h3 class="font-bold">Resumen</h3>
    <dl class="mt-4 space-y-2.5 text-sm">
      <?php if (tiene_oferta($v)): ?><div class="flex justify-between"><dt class="text-ink-500">Precio de oferta</dt><dd class="font-semibold text-red-600 tabular-nums"><?= money($v['precio_oferta']) ?></dd></div><?php endif; ?>
      <div class="flex justify-between"><dt class="text-ink-500">Precio publicado</dt><dd class="font-semibold tabular-nums"><?= money($v['precio_publicado']) ?></dd></div>
      <?php if ($vendido): ?><div class="flex justify-between"><dt class="text-ink-500">Precio final</dt><dd class="font-semibold tabular-nums"><?= money($v['precio_venta_final']) ?></dd></div><?php endif; ?>
      <div class="flex justify-between"><dt class="text-ink-500"><?= $v['tipo_ingreso'] === 'compra' ? 'Costo de compra' : 'Para el propietario' ?></dt><dd class="tabular-nums"><?= $v['precio_propietario'] !== null ? '− ' . money($v['precio_propietario']) : '<span class="text-amber-600">sin definir</span>' ?></dd></div>
      <div class="flex justify-between"><dt class="text-ink-500">Gastos</dt><dd class="tabular-nums">− <?= money($v['gastos']) ?></dd></div>
    </dl>
    <div class="mt-4 border-t border-ink-100 pt-4">
      <p class="text-sm text-ink-500">Ganancia</p>
      <p id="v-ganancia" class="text-2xl font-extrabold tabular-nums text-emerald-600"><?= $vendido ? money(vehiculo_ganancia($v)) : '—' ?></p>
    </div>
    <?php if ($v['precio_propietario'] === null): ?>
      <p class="mt-4 rounded-xl bg-amber-50 p-3 text-xs text-amber-800">Falta el precio del propietario: la ganancia se calcula sobre el precio completo. <a class="font-semibold underline" href="<?= url('admin/vehiculo.php?id=' . $id) ?>">Completar</a></p>
    <?php endif; ?>
  </aside>
</div>

<?php admin_fin([asset('assets/js/admin.js')], '<script>window.PANEL = ' . json_encode(['ajax' => url('admin/ajax.php'), 'csrf' => csrf_token()]) . '; Panel.venta();</script>');
