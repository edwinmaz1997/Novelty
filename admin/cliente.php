<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$id = gi('id');
$c = $id ? q_one('SELECT * FROM clientes WHERE id = ?', [$id]) : null;
if ($id && !$c) {
    flash('error', 'El cliente no existe.');
    redirect('admin/clientes.php');
}
$d = $c ?: ['nombre' => '', 'telefono' => '', 'email' => '', 'dpi' => '', 'nit' => '', 'direccion' => '', 'notas' => ''];
$error = '';

if (es_post()) {
    csrf_check();
    if (p('accion') === 'eliminar' && $c) {
        q('DELETE FROM clientes WHERE id = ?', [$id]);
        flash('ok', 'Cliente eliminado.');
        redirect('admin/clientes.php');
    }
    $in = [];
    foreach (['nombre' => 150, 'telefono' => 30, 'email' => 120, 'dpi' => 20, 'nit' => 20, 'direccion' => 255] as $k => $max) {
        $in[$k] = mb_substr(p($k), 0, $max) ?: null;
    }
    $in['notas'] = p('notas') ?: null;
    if (!$in['nombre']) {
        $error = 'El nombre es obligatorio.';
    } else {
        if ($c) {
            q('UPDATE clientes SET nombre=?, telefono=?, email=?, dpi=?, nit=?, direccion=?, notas=? WHERE id = ?', array_merge(array_values($in), [$id]));
        } else {
            q('INSERT INTO clientes (nombre, telefono, email, dpi, nit, direccion, notas) VALUES (?,?,?,?,?,?,?)', array_values($in));
            $id = (int)db()->lastInsertId();
        }
        flash('ok', 'Cliente guardado.');
        redirect('admin/cliente.php?id=' . $id);
    }
    $d = $in;
}

$consignados = $c ? q_all(VEH_SELECT . ' WHERE v.propietario_id = ? ORDER BY v.fecha_ingreso DESC', [$id]) : [];
$comprados   = $c ? q_all(VEH_SELECT . ' WHERE v.comprador_id = ? ORDER BY v.fecha_venta DESC', [$id]) : [];

admin_inicio($c ? $c['nombre'] : 'Nuevo cliente', 'clientes');

$lista = function (array $items, string $vacio, bool $venta) {
    if (!$items) {
        return '<p class="px-5 py-8 text-center text-sm text-ink-400">' . e($vacio) . '</p>';
    }
    $h = '<ul class="divide-y divide-ink-100">';
    foreach ($items as $v) {
        $h .= '<li><a href="' . url('admin/vehiculo.php?id=' . $v['id']) . '" class="flex items-center gap-3 px-5 py-3 hover:bg-ink-50">' .
            '<img src="' . e(foto_url($v['portada'], 'sm')) . '" class="h-11 w-14 rounded-lg object-cover" alt="">' .
            '<div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">' . e(vehiculo_titulo($v) . ' ' . $v['anio']) . '</p>' .
            '<p class="text-xs text-ink-400">' . e($v['codigo']) . ' · ' . ($venta ? 'comprado ' . fecha($v['fecha_venta']) : 'ingresó ' . fecha($v['fecha_ingreso'])) . '</p></div>' .
            '<div class="text-right"><p class="text-sm font-semibold tabular-nums">' . money($venta ? $v['precio_venta_final'] : $v['precio_publicado']) . '</p>' . badge_estado($v['estado']) . '</div></a></li>';
    }
    return $h . '</ul>';
};
?>
<?php if ($error): ?><div class="mb-5 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-800 ring-1 ring-red-200"><?= e($error) ?></div><?php endif; ?>

<div class="grid gap-6 lg:grid-cols-[400px_minmax(0,1fr)]">
  <form method="post" class="<?= C_CARD ?> h-fit p-5 sm:p-6">
    <?= csrf_field() ?>
    <div class="space-y-4">
      <label class="<?= C_LABEL ?>">Nombre completo *<input name="nombre" value="<?= e($d['nombre']) ?>" required class="<?= C_INPUT ?>"></label>
      <div class="grid grid-cols-2 gap-3">
        <label class="<?= C_LABEL ?>">Teléfono<input name="telefono" value="<?= e($d['telefono']) ?>" class="<?= C_INPUT ?>"></label>
        <label class="<?= C_LABEL ?>">Correo<input type="email" name="email" value="<?= e($d['email']) ?>" class="<?= C_INPUT ?>"></label>
        <label class="<?= C_LABEL ?>">DPI<input name="dpi" value="<?= e($d['dpi']) ?>" class="<?= C_INPUT ?>"></label>
        <label class="<?= C_LABEL ?>">NIT<input name="nit" value="<?= e($d['nit']) ?>" class="<?= C_INPUT ?>"></label>
      </div>
      <label class="<?= C_LABEL ?>">Dirección<input name="direccion" value="<?= e($d['direccion']) ?>" class="<?= C_INPUT ?>"></label>
      <label class="<?= C_LABEL ?>">Notas<textarea name="notas" rows="3" class="<?= C_INPUT ?>"><?= e($d['notas']) ?></textarea></label>
    </div>
    <div class="mt-6 flex gap-2">
      <button class="<?= C_BTN_BRAND ?> flex-1"><?= icon('check', 'w-4 h-4') ?>Guardar</button>
      <a href="<?= url('admin/clientes.php') ?>" class="<?= C_BTN2 ?>">Volver</a>
    </div>
    <?php if ($c && $c['telefono']): ?>
      <a href="https://wa.me/<?= e(preg_replace('/\D/', '', (strlen(preg_replace('/\D/', '', $c['telefono'])) === 8 ? '502' : '') . $c['telefono'])) ?>" target="_blank" class="mt-3 flex items-center justify-center gap-2 rounded-xl bg-[#25D366]/10 py-2.5 text-sm font-semibold text-[#128C4B] hover:bg-[#25D366]/20"><?= icon('whatsapp', 'w-4 h-4') ?>Escribir por WhatsApp</a>
    <?php endif; ?>
  </form>

  <?php if ($c): ?>
  <div class="space-y-6">
    <section class="<?= C_CARD ?> overflow-hidden">
      <h2 class="flex items-center gap-2 border-b border-ink-100 px-5 py-4 font-bold"><?= icon('car', 'w-5 h-5 text-sky-500') ?>Vehículos que dejó (<?= count($consignados) ?>)</h2>
      <?= $lista($consignados, 'No ha dejado vehículos.', false) ?>
    </section>
    <section class="<?= C_CARD ?> overflow-hidden">
      <h2 class="flex items-center gap-2 border-b border-ink-100 px-5 py-4 font-bold"><?= icon('handshake', 'w-5 h-5 text-emerald-500') ?>Vehículos que compró (<?= count($comprados) ?>)</h2>
      <?= $lista($comprados, 'No ha comprado vehículos.', true) ?>
    </section>
    <form method="post" data-confirmar="¿Eliminar este cliente? Los vehículos asociados quedarán sin cliente asignado.">
      <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar">
      <button class="text-sm font-semibold text-red-600 hover:underline">Eliminar cliente</button>
    </form>
  </div>
  <?php endif; ?>
</div>
<?php admin_fin();
