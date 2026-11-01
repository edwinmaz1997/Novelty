<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

if (es_post()) {
    csrf_check();
    $acc = p('accion');
    $id = (int)p('id');
    $nombre = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', p('nombre'))));
    $nombre = mb_substr($nombre, 0, 60);
    try {
        if ($acc === 'crear') {
            if ($nombre === '') {
                throw new RuntimeException('Escribe el nombre de la etiqueta.');
            }
            q('INSERT INTO etiquetas (nombre, popular, orden) VALUES (?, ?, ?)', [$nombre, p('popular') === '1' ? 1 : 0, (int)p('orden') ?: 100]);
            flash('ok', 'Etiqueta "' . $nombre . '" creada.');
        } elseif ($acc === 'editar' && $id) {
            $et = q_one('SELECT * FROM etiquetas WHERE id = ?', [$id]);
            if (!$et) {
                throw new RuntimeException('La etiqueta no existe.');
            }
            if ($nombre === '') {
                throw new RuntimeException('El nombre no puede quedar vacío.');
            }
            q('UPDATE etiquetas SET nombre = ?, popular = ?, orden = ? WHERE id = ?', [$nombre, p('popular') === '1' ? 1 : 0, (int)p('orden'), $id]);
            $n = $et['nombre'] !== $nombre ? etiquetas_reemplazar_en_vehiculos($et['nombre'], $nombre) : 0;
            flash('ok', 'Etiqueta actualizada' . ($n ? " y renombrada en $n vehículo(s)." : '.'));
        } elseif ($acc === 'eliminar' && $id) {
            $et = q_one('SELECT * FROM etiquetas WHERE id = ?', [$id]);
            if ($et) {
                $n = etiquetas_reemplazar_en_vehiculos($et['nombre'], null);
                q('DELETE FROM etiquetas WHERE id = ?', [$id]);
                flash('ok', 'Etiqueta "' . $et['nombre'] . '" eliminada' . ($n ? " y quitada de $n vehículo(s)." : '.'));
            }
        }
    } catch (PDOException $e) {
        flash('error', 'Ya existe una etiqueta con ese nombre.');
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    }
    redirect('admin/etiquetas.php');
}

$etiquetas = etiquetas_catalogo();
foreach ($etiquetas as &$et) {
    $et['uso'] = etiqueta_uso($et['nombre']);
}
unset($et);

admin_inicio('Etiquetas', 'etiquetas');
$mini = 'h-10 rounded-xl border-ink-200 text-sm focus:border-brand-500 focus:ring-brand-500';
?>
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
  <section class="<?= C_CARD ?> overflow-hidden">
    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
      <h2 class="font-bold">Etiquetas (<?= count($etiquetas) ?>)</h2>
      <p class="hidden text-xs text-ink-400 sm:block">Cambia el texto y presiona Guardar · al renombrar se actualiza en los vehículos</p>
    </div>
    <div class="hidden grid-cols-[1fr_90px_80px_70px_96px] gap-3 bg-ink-50 px-5 py-2.5 text-xs font-semibold uppercase tracking-wider text-ink-500 sm:grid">
      <span>Nombre</span><span class="text-center">Popular</span><span class="text-center">Orden</span><span class="text-center">Uso</span><span></span>
    </div>
    <ul class="divide-y divide-ink-100">
      <?php foreach ($etiquetas as $et): ?>
        <li class="px-5 py-3">
          <form method="post" class="grid grid-cols-[1fr_auto] items-center gap-3 sm:grid-cols-[1fr_90px_80px_70px_96px]">
            <?= csrf_field() ?><input type="hidden" name="accion" value="editar"><input type="hidden" name="id" value="<?= $et['id'] ?>">
            <input name="nombre" value="<?= e($et['nombre']) ?>" maxlength="60" required class="<?= $mini ?> font-semibold">
            <label class="flex items-center justify-center gap-2 text-xs font-semibold text-ink-500 sm:text-[0px]" title="Aparece en los accesos rápidos de la portada">
              <input type="checkbox" name="popular" value="1" <?= (int)$et['popular'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-ink-300 text-brand-500 focus:ring-brand-500"><span class="sm:hidden">Popular</span>
            </label>
            <input type="number" name="orden" value="<?= (int)$et['orden'] ?>" class="<?= $mini ?> hidden w-full text-center sm:block" title="Orden">
            <a href="<?= url('admin/vehiculos.php?estado=todos&q=' . urlencode($et['nombre'])) ?>" class="hidden text-center text-sm font-semibold sm:block <?= $et['uso'] ? 'text-brand-600 hover:underline' : 'text-ink-300' ?>" title="Vehículos con esta etiqueta"><?= $et['uso'] ?></a>
            <div class="col-span-2 flex justify-end gap-1 sm:col-span-1">
              <button class="grid h-9 w-9 place-items-center rounded-lg text-ink-500 hover:bg-emerald-50 hover:text-emerald-700" title="Guardar"><?= icon('check', 'w-4 h-4') ?></button>
              <button form="del-<?= $et['id'] ?>" class="grid h-9 w-9 place-items-center rounded-lg text-ink-500 hover:bg-red-50 hover:text-red-600" title="Eliminar"><?= icon('trash', 'w-4 h-4') ?></button>
            </div>
          </form>
          <form method="post" id="del-<?= $et['id'] ?>" data-confirmar="¿Eliminar la etiqueta &quot;<?= e($et['nombre']) ?>&quot;?<?= $et['uso'] ? ' Se quitará de ' . $et['uso'] . ' vehículo(s).' : '' ?>">
            <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $et['id'] ?>">
          </form>
        </li>
      <?php endforeach; ?>
      <?php if (!$etiquetas): ?><li class="px-6 py-12 text-center text-sm text-ink-400">Aún no hay etiquetas.</li><?php endif; ?>
    </ul>
  </section>

  <div class="space-y-6">
    <form method="post" class="<?= C_CARD ?> p-5 sm:p-6">
      <?= csrf_field() ?><input type="hidden" name="accion" value="crear">
      <h2 class="font-bold">Nueva etiqueta</h2>
      <div class="mt-4 space-y-4">
        <label class="<?= C_LABEL ?>">Nombre<input name="nombre" maxlength="60" required placeholder="Ej. Blindado" class="<?= C_INPUT ?>"></label>
        <label class="<?= C_LABEL ?>">Orden<input type="number" name="orden" value="100" class="<?= C_INPUT ?>"></label>
        <label class="flex items-center gap-3 text-sm font-semibold"><input type="checkbox" name="popular" value="1" class="h-5 w-5 rounded border-ink-300 text-brand-500 focus:ring-brand-500">Popular (acceso rápido en la portada)</label>
      </div>
      <button class="<?= C_BTN_BRAND ?> mt-5 w-full"><?= icon('plus', 'w-4 h-4') ?>Crear etiqueta</button>
    </form>
    <div class="rounded-2xl bg-ink-50 p-5 text-sm text-ink-600 ring-1 ring-ink-100">
      <p class="font-semibold text-ink-800">¿Cómo funcionan?</p>
      <ul class="mt-2 list-disc space-y-1 pl-5">
        <li>Se eligen al ingresar o editar un vehículo.</li>
        <li>El buscador del sitio las encuentra: si escriben "único dueño" aparecen esos carros.</li>
        <li>Las marcadas como <b>Popular</b> salen como accesos rápidos debajo del buscador de la portada (máximo 8).</li>
        <li>Renombrar o eliminar una etiqueta también la actualiza en todos los vehículos.</li>
      </ul>
    </div>
  </div>
</div>
<?php admin_fin();
