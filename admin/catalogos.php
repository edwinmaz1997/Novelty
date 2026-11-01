<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require_once ROOT . '/app/imagen.php';
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
        } elseif ($acc === 'marca_portada') {
            $m = q_one('SELECT * FROM marcas WHERE id = ?', [(int)p('id')]);
            if ($m) {
                $logo = $m['logo'];
                $f = $_FILES['logo'] ?? null;
                if ($f && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    try {
                        $logo = imagen_guardar_sitio($f, 'marca-' . $m['slug'], 480, 320);
                        imagen_sitio_borrar($m['logo']);
                    } catch (RuntimeException $e) {
                        flash('error', $e->getMessage());
                    }
                } elseif (p('quitar_logo') === '1') {
                    imagen_sitio_borrar($m['logo']);
                    $logo = null;
                }
                q('UPDATE marcas SET logo = ?, en_portada = ?, orden = ? WHERE id = ?', [$logo, p('en_portada') === '1' && $logo ? 1 : 0, (int)p('orden'), $m['id']]);
                flash('ok', 'Logo y portada actualizados.');
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

$marcas = q_all('SELECT m.*, (SELECT COUNT(*) FROM modelos x WHERE x.marca_id = m.id) modelos, (SELECT COUNT(*) FROM vehiculos v WHERE v.marca_id = m.id) vehiculos FROM marcas m ORDER BY m.en_portada DESC, m.orden, m.nombre');
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
        <li><a href="?marca=<?= $m['id'] ?>" class="flex items-center justify-between gap-3 px-4 py-3 text-sm <?= $marcaId === (int)$m['id'] ? 'bg-brand-50 font-semibold' : 'hover:bg-ink-50' ?>">
          <span class="flex items-center gap-3">
            <span class="grid h-8 w-12 shrink-0 place-items-center rounded-md <?= $m['logo'] ? 'bg-brand-950' : 'bg-ink-50' ?>"><?php if ($m['logo']): ?><img src="<?= e(url($m['logo'])) ?>" alt="" class="max-h-5 max-w-[36px] object-contain"><?php endif; ?></span>
            <?= e($m['nombre']) ?>
            <?php if ((int)$m['en_portada']): ?><span class="rounded bg-brand-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-brand-700">Portada</span><?php endif; ?>
          </span>
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
        <form method="post" enctype="multipart/form-data" class="mt-3 flex flex-col gap-3 rounded-xl bg-ink-50 p-3 sm:flex-row sm:items-center">
          <?= csrf_field() ?><input type="hidden" name="accion" value="marca_portada"><input type="hidden" name="id" value="<?= $marca['id'] ?>">
          <span class="grid h-20 w-full shrink-0 place-items-center rounded-lg bg-brand-950 sm:w-32">
            <?php if ($marca['logo']): ?><img src="<?= e(url($marca['logo'])) ?>" alt="" class="max-h-12 max-w-[80%] object-contain"><?php else: ?><span class="text-xs text-white/50">Sin logo</span><?php endif; ?>
          </span>
          <div class="min-w-0 flex-1 space-y-2 text-sm">
            <input type="file" name="logo" accept="image/png,image/webp,image/jpeg" class="block w-full text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-ink-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
              <label class="inline-flex items-center gap-2 font-semibold"><input type="checkbox" name="en_portada" value="1" <?= (int)$marca['en_portada'] ? 'checked' : '' ?> class="rounded border-ink-300 text-brand-500 focus:ring-brand-500">Mostrar en "Nuestras Marcas"</label>
              <label class="inline-flex items-center gap-2 text-ink-600">Orden <input type="number" name="orden" value="<?= (int)$marca['orden'] ?>" class="h-8 w-16 rounded-lg border-ink-200 text-sm"></label>
              <?php if ($marca['logo']): ?><label class="inline-flex items-center gap-2 text-red-600"><input type="checkbox" name="quitar_logo" value="1" class="rounded border-ink-300 text-red-500">Quitar logo</label><?php endif; ?>
            </div>
            <p class="text-xs text-ink-400">Usa el logo en blanco con fondo transparente (PNG); se muestra sobre el color principal.</p>
          </div>
          <button class="<?= C_BTN ?> h-10 shrink-0">Guardar</button>
        </form>
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
