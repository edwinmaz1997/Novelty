<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
$u = requiere_login();
$admin = es_admin();

if (es_post() && p('accion') === 'eliminar') {
    csrf_check();
    requiere_admin();
    $id = (int)p('id');
    $v = vehiculo($id);
    if ($v) {
        vehiculo_eliminar($id);
        flash('ok', 'Se eliminó ' . vehiculo_titulo($v) . ' ' . $v['anio'] . ' (' . $v['codigo'] . ').');
    }
    redirect('admin/vehiculos.php');
}

$f = vehiculos_filtros($_GET);
if (!isset($_GET['orden'])) {
    $f['orden'] = 'recientes';
}
$pagina = max(1, gi('p', 1));
$res = vehiculos_buscar($f, false, $pagina, 20);
$marcas = q_all('SELECT id, nombre FROM marcas ORDER BY nombre');

$tabs = ['predio' => 'En predio', 'disponible' => 'Disponibles', 'reservado' => 'Reservados', 'oculto' => 'Ocultos', 'vendido' => 'Vendidos', 'todos' => 'Todos'];
$estadoAct = $f['estado'] === '' ? 'predio' : $f['estado'];
$conteos = [];
foreach (q_all('SELECT estado, COUNT(*) n FROM vehiculos GROUP BY estado') as $r) {
    $conteos[$r['estado']] = (int)$r['n'];
}
$conteos['predio'] = ($conteos['disponible'] ?? 0) + ($conteos['reservado'] ?? 0) + ($conteos['oculto'] ?? 0);
$conteos['todos'] = array_sum(array_intersect_key($conteos, ESTADOS));

$qs = $_GET;
unset($qs['p']);

admin_inicio('Vehículos', 'vehiculos', [
    'acciones' => '<a href="' . url('admin/vehiculo.php') . '" class="' . C_BTN_BRAND . '">' . icon('plus', 'w-4 h-4') . '<span class="hidden sm:inline">Ingresar vehículo</span></a>',
]);
?>

<div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-ink-200">
  <?php foreach ($tabs as $k => $l): $q = $qs; $q['estado'] = $k; ?>
    <a href="?<?= e(http_build_query($q)) ?>" class="shrink-0 border-b-2 px-3 pb-3 text-sm font-semibold <?= $estadoAct === $k ? 'border-brand-500 text-ink-900' : 'border-transparent text-ink-500 hover:text-ink-800' ?>">
      <?= $l ?> <span class="ml-1 rounded-md bg-ink-100 px-1.5 text-xs text-ink-600"><?= (int)($conteos[$k] ?? 0) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<form method="get" class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr_auto]">
  <input type="hidden" name="estado" value="<?= e($estadoAct) ?>">
  <label class="relative">
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400"><?= icon('search', 'w-4 h-4') ?></span>
    <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Buscar marca, modelo, color, código…" class="h-11 w-full rounded-xl border-ink-200 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
  </label>
  <select name="marca" class="select h-11 rounded-xl border-ink-200 text-sm" onchange="this.form.submit()">
    <option value="">Todas las marcas</option>
    <?php foreach ($marcas as $m): ?><option value="<?= $m['id'] ?>" <?= (int)$f['marca'] === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['nombre']) ?></option><?php endforeach; ?>
  </select>
  <select name="procedencia" class="select h-11 rounded-xl border-ink-200 text-sm" onchange="this.form.submit()">
    <option value="">Agencia y rodado</option>
    <?php foreach (PROCEDENCIAS as $k => $l): ?><option value="<?= $k ?>" <?= $f['procedencia'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
  </select>
  <select name="orden" class="select h-11 rounded-xl border-ink-200 text-sm" onchange="this.form.submit()">
    <?php foreach (['recientes' => 'Recién ingresados', 'dias_desc' => 'Más días en predio', 'precio_desc' => 'Precio mayor', 'precio_asc' => 'Precio menor', 'anio_desc' => 'Año más reciente'] as $k => $l): ?>
      <option value="<?= $k ?>" <?= $f['orden'] === $k ? 'selected' : '' ?>><?= $l ?></option>
    <?php endforeach; ?>
  </select>
  <button class="<?= C_BTN ?> h-11">Filtrar</button>
</form>
<?php if ($f['sin_fotos']): ?>
  <p class="mt-3 text-sm text-amber-700">Mostrando solo vehículos sin fotos. <a class="font-semibold underline" href="?estado=<?= e($estadoAct) ?>">Quitar</a></p>
<?php endif; ?>

<div class="<?= C_CARD ?> mt-5 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full min-w-[860px] text-sm">
      <thead class="bg-ink-50 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">
        <tr>
          <th class="px-4 py-3">Vehículo</th>
          <th class="px-3 py-3">Km</th>
          <th class="px-3 py-3 text-right">Precio</th>
          <?php if ($admin): ?>
            <th class="px-3 py-3 text-right">Propietario</th>
            <th class="px-3 py-3 text-right">Ganancia</th>
          <?php endif; ?>
          <th class="px-3 py-3">Días</th>
          <th class="px-3 py-3">Estado</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-ink-100">
        <?php foreach ($res['items'] as $v): $g = vehiculo_ganancia($v); ?>
          <tr class="hover:bg-ink-50/60">
            <td class="px-4 py-3">
              <a href="<?= url('admin/vehiculo.php?id=' . $v['id']) ?>" class="flex items-center gap-3">
                <span class="relative shrink-0">
                  <img src="<?= e(foto_url($v['portada'], 'sm')) ?>" class="h-12 w-16 rounded-lg object-cover" alt="">
                  <?php if ((int)$v['destacado']): ?><span class="absolute -right-1 -top-1 grid h-5 w-5 place-items-center rounded-full bg-brand-500 text-white"><?= icon('star', 'w-3 h-3') ?></span><?php endif; ?>
                </span>
                <span class="min-w-0">
                  <span class="block font-semibold text-ink-900"><?= e(vehiculo_titulo($v)) ?> <span class="text-ink-500"><?= (int)$v['anio'] ?></span></span>
                  <span class="flex items-center gap-2 text-xs text-ink-400"><?= e($v['codigo']) ?> · <?= e(PROCEDENCIAS[$v['procedencia']]) ?> · <?= icon('camera', 'w-3.5 h-3.5') ?><?= (int)$v['num_fotos'] ?></span>
                </span>
              </a>
            </td>
            <td class="px-3 py-3 tabular-nums text-ink-600"><?= num($v['kilometraje']) ?></td>
            <td class="px-3 py-3 text-right font-semibold tabular-nums">
              <?php if ($v['estado'] !== 'vendido' && tiene_oferta($v)): ?>
                <span class="block text-xs font-medium text-ink-400 line-through"><?= money($v['precio_publicado']) ?></span>
                <span class="text-red-600"><?= money($v['precio_oferta']) ?></span> <span class="rounded bg-red-50 px-1 text-[10px] font-bold text-red-700">-<?= descuento_pct($v) ?>%</span>
              <?php else: ?>
                <?= money($v['estado'] === 'vendido' ? $v['precio_venta_final'] : $v['precio_publicado']) ?>
              <?php endif; ?>
            </td>
            <?php if ($admin): ?>
              <td class="px-3 py-3 text-right tabular-nums text-ink-600"><?= $v['precio_propietario'] !== null ? money($v['precio_propietario']) : '<span class="text-amber-600" title="Falta precio del propietario">' . icon('alert', 'w-4 h-4 inline') . '</span>' ?></td>
              <td class="px-3 py-3 text-right font-semibold tabular-nums <?= $g !== null && $g < 0 ? 'text-red-600' : 'text-emerald-700' ?>"><?= $v['precio_propietario'] !== null ? money($g) : '—' ?></td>
            <?php endif; ?>
            <td class="px-3 py-3"><?= badge_dias(dias_predio($v)) ?></td>
            <td class="px-3 py-3"><?= badge_estado($v['estado']) ?></td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-1">
                <?php if ($v['estado'] !== 'vendido' && $v['estado'] !== 'oculto'): ?>
                  <a href="<?= e(vehiculo_url($v)) ?>" target="_blank" class="grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-ink-100 hover:text-ink-900" title="Ver en el sitio"><?= icon('eye', 'w-4 h-4') ?></a>
                <?php endif; ?>
                <a href="<?= url('admin/vehiculo.php?id=' . $v['id']) ?>" class="grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-ink-100 hover:text-ink-900" title="Editar"><?= icon('edit', 'w-4 h-4') ?></a>
                <?php if ($admin): ?>
                  <a href="<?= url('admin/vender.php?id=' . $v['id']) ?>" class="grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-emerald-50 hover:text-emerald-700" title="<?= $v['estado'] === 'vendido' ? 'Ver venta' : 'Registrar venta' ?>"><?= icon('handshake', 'w-4 h-4') ?></a>
                  <form method="post" data-confirmar="¿Eliminar <?= e(vehiculo_titulo($v) . ' ' . $v['anio']) ?> y todas sus fotos? No se puede deshacer.">
                    <?= csrf_field() ?><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= $v['id'] ?>">
                    <button class="grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-red-50 hover:text-red-600" title="Eliminar"><?= icon('trash', 'w-4 h-4') ?></button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$res['items']): ?>
          <tr><td colspan="9" class="px-6 py-14 text-center text-ink-400">No hay vehículos con esos filtros.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="mt-3 text-sm text-ink-500"><?= num($res['total']) ?> vehículo(s)</p>
<?= paginacion($res['total'], $pagina, 20, $qs) ?>

<?php admin_fin();
