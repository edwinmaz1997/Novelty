<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$q = g('q');
$pagina = max(1, gi('p', 1));
$por = 25;
$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE c.nombre LIKE ? OR c.telefono LIKE ? OR c.dpi LIKE ? OR c.nit LIKE ? OR c.email LIKE ?';
    $params = array_fill(0, 5, '%' . $q . '%');
}
$total = (int)q_val("SELECT COUNT(*) FROM clientes c $where", $params);
$clientes = q_all("SELECT c.*,
    (SELECT COUNT(*) FROM vehiculos v WHERE v.propietario_id = c.id) AS consignados,
    (SELECT COUNT(*) FROM vehiculos v WHERE v.comprador_id = c.id) AS comprados
    FROM clientes c $where ORDER BY c.nombre LIMIT $por OFFSET " . (($pagina - 1) * $por), $params);

admin_inicio('Clientes', 'clientes', [
    'acciones' => '<a href="' . url('admin/cliente.php') . '" class="' . C_BTN_BRAND . '">' . icon('plus', 'w-4 h-4') . '<span class="hidden sm:inline">Nuevo cliente</span></a>',
]);
?>
<form method="get" class="flex gap-2">
  <label class="relative flex-1">
    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400"><?= icon('search', 'w-4 h-4') ?></span>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, teléfono, DPI, NIT o correo" class="h-11 w-full rounded-xl border-ink-200 pl-9 text-sm focus:border-brand-500 focus:ring-brand-500">
  </label>
  <button class="<?= C_BTN ?> h-11">Buscar</button>
</form>

<div class="<?= C_CARD ?> mt-5 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full min-w-[720px] text-sm">
      <thead class="bg-ink-50 text-left text-xs font-semibold uppercase tracking-wider text-ink-500">
        <tr><th class="px-5 py-3">Cliente</th><th class="px-3 py-3">Teléfono</th><th class="px-3 py-3">DPI / NIT</th><th class="px-3 py-3 text-center">Consignó</th><th class="px-3 py-3 text-center">Compró</th><th class="px-5 py-3"></th></tr>
      </thead>
      <tbody class="divide-y divide-ink-100">
        <?php foreach ($clientes as $c): ?>
          <tr class="hover:bg-ink-50/60">
            <td class="px-5 py-3">
              <a href="<?= url('admin/cliente.php?id=' . $c['id']) ?>" class="flex items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink-100 text-sm font-bold text-ink-600"><?= e(mb_strtoupper(mb_substr($c['nombre'], 0, 1))) ?></span>
                <span><span class="block font-semibold"><?= e($c['nombre']) ?></span><?php if ($c['email']): ?><span class="text-xs text-ink-400"><?= e($c['email']) ?></span><?php endif; ?></span>
              </a>
            </td>
            <td class="px-3 py-3 text-ink-600"><?= e($c['telefono'] ?: '—') ?></td>
            <td class="px-3 py-3 text-ink-600"><?= e(trim(($c['dpi'] ?: '') . ($c['nit'] ? ' · NIT ' . $c['nit'] : ''), ' ·') ?: '—') ?></td>
            <td class="px-3 py-3 text-center"><?= (int)$c['consignados'] ? '<span class="rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">' . (int)$c['consignados'] . '</span>' : '<span class="text-ink-300">0</span>' ?></td>
            <td class="px-3 py-3 text-center"><?= (int)$c['comprados'] ? '<span class="rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">' . (int)$c['comprados'] . '</span>' : '<span class="text-ink-300">0</span>' ?></td>
            <td class="px-5 py-3 text-right"><a href="<?= url('admin/cliente.php?id=' . $c['id']) ?>" class="inline-grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-ink-100"><?= icon('edit', 'w-4 h-4') ?></a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$clientes): ?><tr><td colspan="6" class="px-6 py-14 text-center text-ink-400"><?= $q ? 'Sin resultados.' : 'Aún no hay clientes.' ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<p class="mt-3 text-sm text-ink-500"><?= num($total) ?> cliente(s)</p>
<?= paginacion($total, $pagina, $por, ['q' => $q]) ?>
<?php admin_fin();
