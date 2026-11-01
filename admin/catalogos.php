<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$marcaId = gi('marca');

if (es_post()) {
    csrf_check();
    $acc = p('accion');
    try {
        if ($acc === 'marca_guardar') {
            $n = mb_substr(p('nombre'), 0, 60);
            if ($n !== '') {
                if ((int)p('id')) {
                    q('UPDATE marcas SET nombre = ?, slug = ? WHERE id = ?', [$n, slugify($n), (int)p('id')]);
                } else {
                    q('INSERT INTO marcas (nombre, slug) VALUES (?, ?)', [$n, slugify($n)]);
                    $marcaId = (int)db()->lastInsertId();
                }
                flash('ok', 'Marca guardada.');
            }
        } elseif ($acc === 'marca_eliminar') {
            $id = (int)p('id');
            if (q_val('SELECT COUNT(*) FROM vehiculos WHERE marca_id = ?', [$id])) {
                flash('error', 'No se puede eliminar: hay vehículos con esa marca.');
            } else {
                q('DELETE FROM marcas WHERE id = ?', [$id]);
                flash('ok', 'Marca eliminada.');
                $marcaId = 0;
            }
        } elseif ($acc === 'modelo_guardar') {
            $n = mb_substr(p('nombre'), 0, 80);
            if ($n !== '' && $marcaId) {
                if ((int)p('id')) {
                    q('UPDATE modelos SET nombre = ? WHERE id = ? AND marca_id = ?', [$n, (int)p('id'), $marcaId]);
                } else {
                    q('INSERT INTO modelos (marca_id, nombre) VALUES (?, ?)', [$marcaId, $n]);
                }
                flash('ok', 'Modelo guardado.');
            }
        } elseif ($acc === 'modelo_eliminar') {
            $id = (int)p('id');
            if (q_val('SELECT COUNT(*) FROM vehiculos WHERE modelo_id = ?', [$id])) {
                flash('error', 'No se puede eliminar: hay vehículos con ese modelo.');
            } else {
                q('DELETE FROM modelos WHERE id = ?', [$id]);
                flash('ok', 'Modelo eliminado.');
            }
        }
        // Nombres cambiados → actualizar texto de búsqueda
        if (in_array($acc, ['marca_guardar', 'modelo_guardar'], true) && (int)p('id')) {
            $col = $acc === 'marca_guardar' ? 'marca_id' : 'modelo_id';
            foreach (q_all("SELECT id FROM vehiculos WHERE $col = ?", [(int)p('id')]) as $r) {
                vehiculo_actualizar_buscar((int)$r['id']);
            }
        }
    } catch (PDOException $e) {
        flash('error', 'Ese nombre ya existe.');
    }
    redirect('admin/catalogos.php' . ($marcaId ? '?marca=' . $marcaId : ''));
}

$marcas = q_all('SELECT m.*, (SELECT COUNT(*) FROM modelos x WHERE x.marca_id = m.id) modelos, (SELECT COUNT(*) FROM vehiculos v WHERE v.marca_id = m.id) vehiculos FROM marcas m ORDER BY m.nombre');
$marca = $marcaId ? q_one('SELECT * FROM marcas WHERE id = ?', [$marcaId]) : null;
$modelos = $marca ? q_all('SELECT m.*, (SELECT COUNT(*) FROM vehiculos v WHERE v.modelo_id = m.id) vehiculos FROM modelos m WHERE m.marca_id = ? ORDER BY m.nombre', [$marcaId]) : [];

admin_inicio('Marcas y modelos', 'catalogos');
$mini = 'h-10 flex-1 rounded-xl border-ink-200 text-sm focus:border-brand-500 focus:ring-brand-500';
?>
<div class="grid gap-6 lg:grid-cols-2">
  <section class="<?= C_CARD ?> overflow-hidden">
    <div class="border-b border-ink-100 p-4">
      <h2 class="font-bold">Marcas (<?= count($marcas) ?>)</h2>
      <form method="post" class="mt-3 flex gap-2"><?= csrf_field() ?><input type="hidden" name="accion" value="marca_guardar">
        <input name="nombre" placeholder="Nueva marca" required class="<?= $mini ?>"><button class="<?= C_BTN ?> h-10"><?= icon('plus', 'w-4 h-4') ?></button></form>
    </div>
    <ul class="max-h-[60vh] divide-y divide-ink-100 overflow-y-auto">
      <?php foreach ($marcas as $m): ?>
        <li><a href="?marca=<?= $m['id'] ?>" class="flex items-center justify-between px-4 py-3 text-sm <?= $marcaId === (int)$m['id'] ? 'bg-brand-50 font-semibold' : 'hover:bg-ink-50' ?>">
          <span><?= e($m['nombre']) ?></span>
          <span class="text-xs text-ink-400"><?= (int)$m['modelos'] ?> modelos · <?= (int)$m['vehiculos'] ?> vehículos <?= icon('chev-r', 'w-4 h-4 inline') ?></span>
        </a></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="<?= C_CARD ?> h-fit overflow-hidden">
    <?php if (!$marca): ?>
      <p class="p-10 text-center text-sm text-ink-400">Elige una marca para ver y editar sus modelos.</p>
    <?php else: ?>
      <div class="border-b border-ink-100 p-4">
        <form method="post" class="flex gap-2"><?= csrf_field() ?><input type="hidden" name="accion" value="marca_guardar"><input type="hidden" name="id" value="<?= $marca['id'] ?>">
          <input name="nombre" value="<?= e($marca['nombre']) ?>" class="<?= $mini ?> font-bold"><button class="<?= C_BTN2 ?> h-10">Renombrar</button></form>
        <form method="post" class="mt-3 flex gap-2"><?= csrf_field() ?><input type="hidden" name="accion" value="modelo_guardar">
          <input name="nombre" placeholder="Nuevo modelo de <?= e($marca['nombre']) ?>" required class="<?= $mini ?>"><button class="<?= C_BTN ?> h-10"><?= icon('plus', 'w-4 h-4') ?></button></form>
      </div>
      <ul class="divide-y divide-ink-100">
        <?php foreach ($modelos as $mo): ?>
          <li class="flex items-center gap-2 px-4 py-2">
            <form method="post" class="flex flex-1 gap-2"><?= csrf_field() ?><input type="hidden" name="accion" value="modelo_guardar"><input type="hidden" name="id" value="<?= $mo['id'] ?>">
              <input name="nombre" value="<?= e($mo['nombre']) ?>" class="h-9 flex-1 rounded-lg border-transparent bg-transparent text-sm hover:border-ink-200 focus:border-brand-500 focus:ring-brand-500">
              <span class="self-center text-xs text-ink-400"><?= (int)$mo['vehiculos'] ?> veh.</span>
            </form>
            <?php if (!(int)$mo['vehiculos']): ?>
            <form method="post" data-confirmar="¿Eliminar el modelo <?= e($mo['nombre']) ?>?"><?= csrf_field() ?><input type="hidden" name="accion" value="modelo_eliminar"><input type="hidden" name="id" value="<?= $mo['id'] ?>">
              <button class="grid h-8 w-8 place-items-center rounded-lg text-ink-400 hover:bg-red-50 hover:text-red-600"><?= icon('trash', 'w-4 h-4') ?></button></form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
        <?php if (!$modelos): ?><li class="p-6 text-center text-sm text-ink-400">Sin modelos.</li><?php endif; ?>
      </ul>
      <?php if (!(int)q_val('SELECT COUNT(*) FROM vehiculos WHERE marca_id = ?', [$marcaId])): ?>
      <form method="post" class="border-t border-ink-100 p-4" data-confirmar="¿Eliminar la marca <?= e($marca['nombre']) ?> y sus modelos?"><?= csrf_field() ?><input type="hidden" name="accion" value="marca_eliminar"><input type="hidden" name="id" value="<?= $marca['id'] ?>">
        <button class="text-sm font-semibold text-red-600 hover:underline">Eliminar marca</button></form>
      <?php endif; ?>
      <p class="border-t border-ink-100 px-4 py-3 text-xs text-ink-400">Para renombrar un modelo, edita el texto y presiona Enter.</p>
    <?php endif; ?>
  </section>
</div>
<?php admin_fin();
