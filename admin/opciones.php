<?php
/** CRUD de opciones simples de los vehículos (por ahora: tracción). */
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$tipo = g('tipo', 'traccion');
if (!isset(TIPOS_OPCION[$tipo])) {
    redirect('admin/');
}
$cfg = TIPOS_OPCION[$tipo];
$col = $cfg['columna'];
$aqui = 'admin/opciones.php?tipo=' . $tipo;

if (es_post()) {
    csrf_check();
    $acc = p('accion');
    $id = (int)p('id');
    $nombre = mb_substr(trim(preg_replace('/\s+/', ' ', p('nombre'))), 0, 40);
    try {
        if ($acc === 'crear') {
            if ($nombre === '') {
                throw new RuntimeException('Escribe el nombre.');
            }
            q('INSERT INTO opciones (tipo, nombre, orden) VALUES (?, ?, ?)', [$tipo, $nombre, (int)p('orden') ?: 100]);
            flash('ok', ucfirst($cfg['singular']) . ' "' . $nombre . '" creada.');
        } elseif ($acc === 'editar' && $id) {
            $op = q_one('SELECT * FROM opciones WHERE id = ? AND tipo = ?', [$id, $tipo]);
            if (!$op) {
                throw new RuntimeException('No existe.');
            }
            if ($nombre === '') {
                throw new RuntimeException('El nombre no puede quedar vacío.');
            }
            q('UPDATE opciones SET nombre = ?, orden = ? WHERE id = ?', [$nombre, (int)p('orden'), $id]);
            $n = 0;
            if ($op['nombre'] !== $nombre) {
                $ids = array_column(q_all("SELECT id FROM vehiculos WHERE $col = ?", [$op['nombre']]), 'id');
                q("UPDATE vehiculos SET $col = ? WHERE $col = ?", [$nombre, $op['nombre']]);
                foreach ($ids as $vid) {
                    vehiculo_actualizar_buscar((int)$vid);
                }
                $n = count($ids);
            }
            flash('ok', 'Guardado' . ($n ? " y actualizado en $n vehículo(s)." : '.'));
        } elseif ($acc === 'eliminar' && $id) {
            $op = q_one('SELECT * FROM opciones WHERE id = ? AND tipo = ?', [$id, $tipo]);
            if ($op) {
                $ids = array_column(q_all("SELECT id FROM vehiculos WHERE $col = ?", [$op['nombre']]), 'id');
                q("UPDATE vehiculos SET $col = NULL WHERE $col = ?", [$op['nombre']]);
                foreach ($ids as $vid) {
                    vehiculo_actualizar_buscar((int)$vid);
                }
                q('DELETE FROM opciones WHERE id = ?', [$id]);
                flash('ok', '"' . $op['nombre'] . '" eliminada' . ($ids ? ' y quitada de ' . count($ids) . ' vehículo(s).' : '.'));
            }
        }
    } catch (PDOException $e) {
        flash('error', 'Ya existe una ' . $cfg['singular'] . ' con ese nombre.');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    }
    redirect($aqui);
}

$lista = q_all("SELECT o.*, (SELECT COUNT(*) FROM vehiculos v WHERE v.$col = o.nombre) AS uso FROM opciones o WHERE o.tipo = ? ORDER BY o.orden, o.nombre", [$tipo]);

admin_inicio($cfg['titulo'], 'op-' . $tipo);
$mini = 'h-10 rounded-xl border-ink-200 text-sm focus:border-brand-500 focus:ring-brand-500';
?>
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
  <section class="<?= C_CARD ?> overflow-hidden">
    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
      <h2 class="flex items-center gap-2 font-bold"><?= icon($cfg['icono'], 'w-5 h-5 text-ink-400') ?><?= e($cfg['titulo']) ?> (<?= count($lista) ?>)</h2>
      <p class="hidden text-xs text-ink-400 sm:block">Al renombrar se actualiza en los vehículos</p>
    </div>
    <div class="hidden grid-cols-[1fr_80px_70px_96px] gap-3 bg-ink-50 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-ink-500 sm:grid">
      <span>Nombre</span><span class="text-center">Orden</span><span class="text-center">Uso</span><span></span>
    </div>
    <ul class="divide-y divide-ink-100">
      <?php foreach ($lista as $op): ?>
        <li class="px-5 py-3">
          <form method="post" class="grid grid-cols-[1fr_auto] items-center gap-3 sm:grid-cols-[1fr_80px_70px_96px]">
            <?= csrf_field() ?><input type="hidden" name="accion" value="editar"><input type="hidden" name="id" value="<?= $op['id'] ?>">
            <input name="nombre" value="<?= e($op['nombre']) ?>" maxlength="40" required class="<?= $mini ?> font-semibold">
            <input type="number" name="orden" value="<?= (int)$op['orden'] ?>" class="<?= $mini ?> hidden w-full text-center sm:block" title="Orden">
            <a href="<?= url('admin/vehiculos.php?estado=todos&q=' . urlencode($op['nombre'])) ?>" class="hidden text-center text-sm font-semibold sm:block <?= $op['uso'] ? 'text-brand-600 hover:underline' : 'text-ink-300' ?>"><?= (int)$op['uso'] ?></a>
            <div class="flex justify-end gap-1">
              <button class="grid h-9 w-9 place-items-center rounded-lg text-ink-500 hover:bg-emerald-50 hover:text-emerald-700" title="Guardar"><?= icon('check', 'w-4 h-4') ?></button>
              <button form="del-<?= $op['id'] ?>" class="grid h-9 w-9 place-items-center rounded-lg text-ink-500 hover:bg-red-50 hover:text-red-600" title="Eliminar"><?= icon('trash', 'w-4 h-4') ?></button>
            </div>
          </form>
          <form method="post" id="del-<?= $op['id'] ?>" data-confirmar="¿Eliminar &quot;<?= e($op['nombre']) ?>&quot;?<?= $op['uso'] ? ' Se quitará de ' . (int)$op['uso'] . ' vehículo(s).' : '' ?>">
            <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $op['id'] ?>">
          </form>
        </li>
      <?php endforeach; ?>
      <?php if (!$lista): ?><li class="px-6 py-12 text-center text-sm text-ink-400">Aún no hay opciones.</li><?php endif; ?>
    </ul>
  </section>

  <form method="post" class="<?= C_CARD ?> h-fit p-5 sm:p-6">
    <?= csrf_field() ?><input type="hidden" name="accion" value="crear">
    <h2 class="font-bold">Nueva <?= e($cfg['singular']) ?></h2>
    <div class="mt-4 space-y-4">
      <label class="<?= C_LABEL ?>">Nombre<input name="nombre" maxlength="40" required placeholder="<?= e($cfg['ejemplo']) ?>" class="<?= C_INPUT ?>"></label>
      <label class="<?= C_LABEL ?>">Orden<input type="number" name="orden" value="100" class="<?= C_INPUT ?>"></label>
    </div>
    <button class="<?= C_BTN_BRAND ?> mt-5 w-full"><?= icon('plus', 'w-4 h-4') ?>Crear</button>
    <p class="mt-4 text-xs text-ink-400">Aparece en el selector "<?= e(ucfirst($cfg['singular'])) ?>" al ingresar o editar un vehículo, en la ficha pública y en el buscador.</p>
  </form>
</div>
<?php admin_fin();
