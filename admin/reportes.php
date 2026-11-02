<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
requiere_admin();

$tab = in_array(g('tab'), ['ventas', 'predio', 'detalle'], true) ? g('tab') : 'ventas';
$anio = gi('anio', (int)date('Y'));
$desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', g('desde')) ? g('desde') : date('Y-m-01');
$hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', g('hasta')) ? g('hasta') : date('Y-m-d');
$csv = g('csv') === '1';
$ganSQL = 'COALESCE(v.precio_venta_final,0) - COALESCE(v.precio_propietario,0) - v.gastos';

function enviar_csv(string $nombre, array $cabecera, array $filas): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $cabecera);
    foreach ($filas as $f) {
        fputcsv($out, $f);
    }
    exit;
}

/* ---------- Datos ---------- */
if ($tab === 'ventas') {
    $anios = q_all("SELECT DISTINCT YEAR(fecha_venta) a FROM vehiculos WHERE estado = 'vendido' ORDER BY a DESC");
    $anios = array_map('intval', array_column($anios, 'a'));
    if (!in_array((int)date('Y'), $anios, true)) {
        array_unshift($anios, (int)date('Y'));
    }
    $rows = q_all("SELECT MONTH(v.fecha_venta) m, COUNT(*) n, SUM(v.precio_venta_final) total, SUM($ganSQL) g,
        AVG(DATEDIFF(v.fecha_venta, v.fecha_ingreso)) dias
        FROM vehiculos v WHERE v.estado = 'vendido' AND YEAR(v.fecha_venta) = ? GROUP BY m", [$anio]);
    $meses = [];
    for ($m = 1; $m <= 12; $m++) {
        $meses[$m] = ['n' => 0, 'total' => 0, 'g' => 0, 'dias' => null];
    }
    foreach ($rows as $r) {
        $meses[(int)$r['m']] = ['n' => (int)$r['n'], 'total' => (float)$r['total'], 'g' => (float)$r['g'], 'dias' => $r['dias'] !== null ? (float)$r['dias'] : null];
    }
    $tot = ['n' => array_sum(array_column($meses, 'n')), 'total' => array_sum(array_column($meses, 'total')), 'g' => array_sum(array_column($meses, 'g'))];
    if ($csv) {
        $f = [];
        foreach ($meses as $m => $r) {
            $f[] = [MESES[$m], $r['n'], $r['total'], $r['g'], $r['dias'] !== null ? round($r['dias']) : ''];
        }
        $f[] = ['TOTAL', $tot['n'], $tot['total'], $tot['g'], ''];
        enviar_csv('ventas-' . $anio, ['Mes', 'Vendidos', 'Total vendido', 'Ganancia', 'Días promedio para vender'], $f);
    }
} elseif ($tab === 'predio') {
    $items = q_all(VEH_SELECT . " WHERE v.estado IN ('disponible','reservado','oculto') ORDER BY v.fecha_ingreso ASC");
    $rangos = ['0–30 días' => 0, '31–60 días' => 0, '61–90 días' => 0, 'Más de 90' => 0];
    $suma = 0;
    foreach ($items as $v) {
        $d = dias_predio($v);
        $suma += $d;
        $k = $d <= 30 ? '0–30 días' : ($d <= 60 ? '31–60 días' : ($d <= 90 ? '61–90 días' : 'Más de 90'));
        $rangos[$k]++;
    }
    $promVenta = (float)q_val("SELECT AVG(DATEDIFF(fecha_venta, fecha_ingreso)) FROM vehiculos WHERE estado = 'vendido'");
    if ($csv) {
        $f = [];
        foreach ($items as $v) {
            $f[] = [$v['codigo'], vehiculo_titulo($v), $v['anio'], ESTADOS[$v['estado']], $v['fecha_ingreso'], dias_predio($v), $v['precio_publicado'], $v['precio_propietario'], $v['vistas']];
        }
        enviar_csv('dias-en-predio-' . date('Y-m-d'), ['Código', 'Vehículo', 'Año', 'Estado', 'Ingreso', 'Días', 'Precio publicado', 'Precio propietario', 'Visitas'], $f);
    }
} else {
    $items = q_all(str_replace('FROM vehiculos v', ', c.nombre AS comprador FROM vehiculos v', VEH_SELECT) . "
        LEFT JOIN clientes c ON c.id = v.comprador_id
        WHERE v.estado = 'vendido' AND v.fecha_venta BETWEEN ? AND ? ORDER BY v.fecha_venta DESC", [$desde, $hasta]);
    $tot = ['total' => 0, 'costo' => 0, 'gastos' => 0, 'g' => 0];
    foreach ($items as $v) {
        $tot['total'] += (float)$v['precio_venta_final'];
        $tot['costo'] += (float)$v['precio_propietario'];
        $tot['gastos'] += (float)$v['gastos'];
        $tot['g'] += vehiculo_ganancia($v);
    }
    if ($csv) {
        $f = [];
        foreach ($items as $v) {
            $f[] = [$v['fecha_venta'], $v['codigo'], vehiculo_titulo($v) . ' ' . $v['anio'], TIPOS_INGRESO[$v['tipo_ingreso']], $v['comprador'], $v['precio_venta_final'], $v['precio_propietario'], $v['gastos'], vehiculo_ganancia($v), dias_predio($v)];
        }
        enviar_csv('ventas-' . $desde . '-a-' . $hasta, ['Fecha', 'Código', 'Vehículo', 'Ingreso', 'Comprador', 'Precio venta', 'Propietario/compra', 'Gastos', 'Ganancia', 'Días en predio'], $f);
    }
}

$qsCsv = $_GET;
$qsCsv['csv'] = '1';
$qsCsv['tab'] = $tab;
admin_inicio('Reportes', 'reportes', [
    'acciones' => '<a href="?' . e(http_build_query($qsCsv)) . '" class="' . C_BTN2 . '">' . icon('download', 'w-4 h-4') . '<span class="hidden sm:inline">Exportar Excel (CSV)</span></a>',
]);
$tabs = ['ventas' => 'Ventas por mes', 'detalle' => 'Detalle de ventas', 'predio' => 'Días en predio'];
?>
<div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-ink-200">
  <?php foreach ($tabs as $k => $l): ?>
    <a href="?tab=<?= $k ?>" class="shrink-0 border-b-2 px-3 pb-3 text-sm font-semibold <?= $tab === $k ? 'border-brand-500 text-ink-900' : 'border-transparent text-ink-500 hover:text-ink-800' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'ventas'): ?>
  <form method="get" class="mt-5 flex items-center gap-2">
    <input type="hidden" name="tab" value="ventas">
    <span class="text-sm font-semibold text-ink-600">Año</span>
    <select name="anio" onchange="this.form.submit()" class="select h-10 rounded-xl border-ink-200 text-sm font-semibold">
      <?php foreach ($anios as $a): ?><option <?= $a === $anio ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?>
    </select>
  </form>
  <div class="mt-5 grid gap-4 sm:grid-cols-3">
    <div class="<?= C_CARD ?> p-5"><p class="text-sm text-ink-500">Vehículos vendidos</p><p class="mt-2 text-3xl font-extrabold tabular-nums"><?= num($tot['n']) ?></p></div>
    <div class="<?= C_CARD ?> p-5"><p class="text-sm text-ink-500">Total vendido</p><p class="mt-2 text-3xl font-extrabold tabular-nums"><?= money($tot['total']) ?></p></div>
    <div class="<?= C_CARD ?> p-5"><p class="text-sm text-ink-500">Ganancia</p><p class="mt-2 text-3xl font-extrabold tabular-nums text-emerald-600"><?= money($tot['g']) ?></p></div>
  </div>
  <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
    <section class="<?= C_CARD ?> p-5"><div class="h-80"><canvas id="g-ventas"></canvas></div></section>
    <section class="<?= C_CARD ?> overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-ink-50 text-left text-xs font-semibold uppercase tracking-wider text-ink-500"><tr><th class="px-5 py-3">Mes</th><th class="px-3 py-3 text-center">Vendidos</th><th class="px-3 py-3 text-right">Total</th><th class="px-3 py-3 text-right">Ganancia</th><th class="px-5 py-3 text-right">Días prom.</th></tr></thead>
        <tbody class="divide-y divide-ink-100">
          <?php foreach ($meses as $m => $r): ?>
            <tr class="<?= $r['n'] ? '' : 'text-ink-300' ?>">
              <td class="px-5 py-2.5 font-medium"><?php if ($r['n']): ?><a class="hover:underline" href="?tab=detalle&desde=<?= sprintf('%04d-%02d-01', $anio, $m) ?>&hasta=<?= date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $anio, $m))) ?>"><?= MESES[$m] ?></a><?php else: ?><?= MESES[$m] ?><?php endif; ?></td>
              <td class="px-3 py-2.5 text-center tabular-nums"><?= $r['n'] ?></td>
              <td class="px-3 py-2.5 text-right tabular-nums"><?= $r['n'] ? money($r['total']) : '—' ?></td>
              <td class="px-3 py-2.5 text-right font-semibold tabular-nums <?= $r['n'] ? 'text-emerald-700' : '' ?>"><?= $r['n'] ? money($r['g']) : '—' ?></td>
              <td class="px-5 py-2.5 text-right tabular-nums"><?= $r['dias'] !== null ? round($r['dias']) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-ink-50 font-bold"><tr><td class="px-5 py-3">Total</td><td class="px-3 py-3 text-center"><?= $tot['n'] ?></td><td class="px-3 py-3 text-right tabular-nums"><?= money($tot['total']) ?></td><td class="px-3 py-3 text-right tabular-nums text-emerald-700"><?= money($tot['g']) ?></td><td></td></tr></tfoot>
      </table>
    </section>
  </div>

<?php elseif ($tab === 'detalle'): ?>
  <form method="get" class="mt-5 flex flex-wrap items-end gap-2">
    <input type="hidden" name="tab" value="detalle">
    <label class="text-sm font-semibold text-ink-600">Desde<input type="date" name="desde" value="<?= e($desde) ?>" class="mt-1 block h-10 rounded-xl border-ink-200 text-sm"></label>
    <label class="text-sm font-semibold text-ink-600">Hasta<input type="date" name="hasta" value="<?= e($hasta) ?>" class="mt-1 block h-10 rounded-xl border-ink-200 text-sm"></label>
    <button class="<?= C_BTN ?> h-10">Ver</button>
  </form>
  <div class="mt-5 grid gap-4 sm:grid-cols-4">
    <div class="<?= C_CARD ?> p-4"><p class="text-xs text-ink-500">Vendidos</p><p class="mt-1 text-2xl font-extrabold"><?= count($items) ?></p></div>
    <div class="<?= C_CARD ?> p-4"><p class="text-xs text-ink-500">Total vendido</p><p class="mt-1 text-2xl font-extrabold tabular-nums"><?= money($tot['total']) ?></p></div>
    <div class="<?= C_CARD ?> p-4"><p class="text-xs text-ink-500">Pagado a propietarios + gastos</p><p class="mt-1 text-2xl font-extrabold tabular-nums"><?= money($tot['costo'] + $tot['gastos']) ?></p></div>
    <div class="<?= C_CARD ?> p-4"><p class="text-xs text-ink-500">Ganancia</p><p class="mt-1 text-2xl font-extrabold tabular-nums text-emerald-600"><?= money($tot['g']) ?></p></div>
  </div>
  <div class="<?= C_CARD ?> mt-5 overflow-hidden"><div class="overflow-x-auto">
    <table class="w-full min-w-[900px] text-sm">
      <thead class="bg-ink-50 text-left text-xs font-semibold uppercase tracking-wider text-ink-500"><tr><th class="px-5 py-3">Fecha</th><th class="px-3 py-3">Vehículo</th><th class="px-3 py-3">Comprador</th><th class="px-3 py-3 text-right">Venta</th><th class="px-3 py-3 text-right">Propietario</th><th class="px-3 py-3 text-right">Gastos</th><th class="px-3 py-3 text-right">Ganancia</th><th class="px-5 py-3 text-right">Días</th></tr></thead>
      <tbody class="divide-y divide-ink-100">
        <?php foreach ($items as $v): $g = vehiculo_ganancia($v); ?>
          <tr class="hover:bg-ink-50/60">
            <td class="px-5 py-3 tabular-nums"><?= fecha($v['fecha_venta']) ?></td>
            <td class="px-3 py-3"><a href="<?= url('admin/vender.php?id=' . $v['id']) ?>" class="font-semibold hover:underline"><?= e(vehiculo_titulo($v) . ' ' . $v['anio']) ?></a><span class="block text-xs text-ink-400"><?= e($v['codigo'] . ' · ' . TIPOS_INGRESO[$v['tipo_ingreso']]) ?></span></td>
            <td class="px-3 py-3 text-ink-600"><?= e($v['comprador'] ?: '—') ?></td>
            <td class="px-3 py-3 text-right font-semibold tabular-nums"><?= money($v['precio_venta_final']) ?></td>
            <td class="px-3 py-3 text-right tabular-nums text-ink-600"><?= money($v['precio_propietario']) ?></td>
            <td class="px-3 py-3 text-right tabular-nums text-ink-600"><?= money($v['gastos']) ?></td>
            <td class="px-3 py-3 text-right font-bold tabular-nums <?= $g < 0 ? 'text-red-600' : 'text-emerald-700' ?>"><?= money($g) ?></td>
            <td class="px-5 py-3 text-right"><?= badge_dias(dias_predio($v)) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="8" class="px-6 py-14 text-center text-ink-400">No hay ventas en ese periodo.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>

<?php else: ?>
  <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
    <div class="<?= C_CARD ?> p-4 xl:col-span-1"><p class="text-xs text-ink-500">En predio</p><p class="mt-1 text-2xl font-extrabold"><?= count($items) ?></p></div>
    <div class="<?= C_CARD ?> p-4 xl:col-span-1"><p class="text-xs text-ink-500">Promedio actual</p><p class="mt-1 text-2xl font-extrabold"><?= $items ? round($suma / count($items)) : 0 ?> <span class="text-sm font-semibold text-ink-400">días</span></p></div>
    <div class="<?= C_CARD ?> p-4 xl:col-span-1"><p class="text-xs text-ink-500">Promedio para vender</p><p class="mt-1 text-2xl font-extrabold"><?= round($promVenta) ?> <span class="text-sm font-semibold text-ink-400">días</span></p></div>
    <div class="<?= C_CARD ?> p-4 sm:col-span-2 xl:col-span-3">
      <p class="text-xs text-ink-500">Distribución</p>
      <div class="mt-2 grid grid-cols-4 gap-2 text-center">
        <?php $cols = ['bg-emerald-500', 'bg-amber-400', 'bg-orange-500', 'bg-red-500']; $i = 0; foreach ($rangos as $l => $n): ?>
          <div><div class="mx-auto h-1.5 rounded-full <?= $cols[$i++] ?>"></div><p class="mt-1.5 text-lg font-extrabold"><?= $n ?></p><p class="text-[11px] text-ink-500"><?= $l ?></p></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="<?= C_CARD ?> mt-5 overflow-hidden"><div class="overflow-x-auto">
    <table class="w-full min-w-[780px] text-sm">
      <thead class="bg-ink-50 text-left text-xs font-semibold uppercase tracking-wider text-ink-500"><tr><th class="px-5 py-3">Vehículo</th><th class="px-3 py-3">Ingreso</th><th class="px-3 py-3">Días</th><th class="px-3 py-3 text-right">Precio</th><th class="px-3 py-3 text-right">Visitas</th><th class="px-5 py-3">Estado</th></tr></thead>
      <tbody class="divide-y divide-ink-100">
        <?php foreach ($items as $v): ?>
          <tr class="hover:bg-ink-50/60">
            <td class="px-5 py-3"><a href="<?= url('admin/vehiculo.php?id=' . $v['id']) ?>" class="flex items-center gap-3"><img src="<?= e(foto_url($v['portada'], 'sm')) ?>" class="h-10 w-14 rounded-lg object-cover" alt=""><span><span class="block font-semibold"><?= e(vehiculo_titulo($v) . ' ' . $v['anio']) ?></span><span class="text-xs text-ink-400"><?= e($v['codigo']) ?></span></span></a></td>
            <td class="px-3 py-3 tabular-nums text-ink-600"><?= fecha($v['fecha_ingreso']) ?></td>
            <td class="px-3 py-3"><?= badge_dias(dias_predio($v)) ?></td>
            <td class="px-3 py-3 text-right font-semibold tabular-nums"><?= money($v['precio_publicado']) ?></td>
            <td class="px-3 py-3 text-right tabular-nums text-ink-600"><?= num($v['vistas']) ?></td>
            <td class="px-5 py-3"><?= badge_estado($v['estado']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="6" class="px-6 py-14 text-center text-ink-400">No hay vehículos en predio.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div></div>
<?php endif; ?>

<?php
$inline = '';
if ($tab === 'ventas') {
    $inline = '<script>new Chart(document.getElementById("g-ventas"), {
  data: { labels: ' . json_encode(array_map(function ($m) { return mb_substr(MESES[$m], 0, 3); }, range(1, 12))) . ',
    datasets: [
      { type: "bar", label: "Ganancia", data: ' . json_encode(array_values(array_column($meses, 'g'))) . ', backgroundColor: "#10B981", borderRadius: 6, yAxisID: "y", maxBarThickness: 30 },
      { type: "line", label: "Vendidos", data: ' . json_encode(array_values(array_column($meses, 'n'))) . ', borderColor: "' . e(ajuste('color_principal', '#005A50')) . '", backgroundColor: "' . e(ajuste('color_principal', '#005A50')) . '", tension: .3, cubicInterpolationMode: "monotone", yAxisID: "y1" }
    ] },
  options: { maintainAspectRatio: false, interaction: { mode: "index", intersect: false },
    plugins: { legend: { position: "bottom", labels: { usePointStyle: true, boxWidth: 8 } } },
    scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: "#E9EDF3" }, ticks: { callback: function (v) { return v >= 1000 ? (v/1000) + "k" : v; } } },
      y1: { beginAtZero: true, position: "right", grid: { display: false }, ticks: { precision: 0 } } } }
});</script>';
}
admin_fin($tab === 'ventas' ? [asset('assets/vendor/chart.umd.min.js')] : [], $inline);
