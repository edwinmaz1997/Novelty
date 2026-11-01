<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
$u = requiere_login();
$admin = es_admin();

$enPredio = "estado IN ('disponible','reservado','oculto')";
$k = q_one("SELECT COUNT(*) total, COALESCE(SUM(precio_publicado),0) valor,
    COALESCE(AVG(DATEDIFF(CURDATE(), fecha_ingreso)),0) dias,
    SUM(estado = 'reservado') reservados, SUM(estado = 'oculto') ocultos
    FROM vehiculos WHERE $enPredio");
$mes = q_one("SELECT COUNT(*) n, COALESCE(SUM(precio_venta_final),0) vendido,
    COALESCE(SUM(precio_venta_final - COALESCE(precio_propietario,0) - gastos),0) ganancia
    FROM vehiculos WHERE estado = 'vendido' AND fecha_venta >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$sinFotos = (int)q_val("SELECT COUNT(*) FROM vehiculos v WHERE $enPredio AND NOT EXISTS (SELECT 1 FROM vehiculo_fotos f WHERE f.vehiculo_id = v.id)");

$antiguos = q_all(VEH_SELECT . " WHERE v.estado IN ('disponible','reservado','oculto') ORDER BY v.fecha_ingreso ASC LIMIT 6");
$ultimos  = q_all(VEH_SELECT . ' ORDER BY v.creado_en DESC, v.id DESC LIMIT 6');

$serie = [];
if ($admin) {
    $rows = q_all("SELECT DATE_FORMAT(fecha_venta, '%Y-%m') ym, COUNT(*) n,
        SUM(precio_venta_final - COALESCE(precio_propietario,0) - gastos) g
        FROM vehiculos WHERE estado = 'vendido' AND fecha_venta >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
        GROUP BY ym");
    $map = [];
    foreach ($rows as $r) {
        $map[$r['ym']] = $r;
    }
    for ($i = 11; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
        $serie[] = [
            'mes' => mb_substr(MESES[(int)substr($ym, 5)], 0, 3),
            'n'   => (int)($map[$ym]['n'] ?? 0),
            'g'   => (float)($map[$ym]['g'] ?? 0),
        ];
    }
}

admin_inicio('Hola, ' . explode(' ', $u['nombre'])[0], 'dashboard', [
    'acciones' => '<a href="' . url('admin/vehiculo.php') . '" class="' . C_BTN_BRAND . '">' . icon('plus', 'w-4 h-4') . '<span class="hidden sm:inline">Ingresar vehículo</span></a>',
]);

$tarjeta = function ($icono, $titulo, $valor, $nota = '', $color = 'bg-ink-900 text-white') {
    return '<div class="' . C_CARD . ' p-5"><div class="flex items-center justify-between"><p class="text-sm font-medium text-ink-500">' . e($titulo) . '</p>' .
        '<span class="grid h-9 w-9 place-items-center rounded-xl ' . $color . '">' . icon($icono, 'w-[18px] h-[18px]') . '</span></div>' .
        '<p class="mt-3 text-2xl font-extrabold tracking-tight tabular-nums">' . $valor . '</p>' .
        ($nota ? '<p class="mt-1 text-xs text-ink-400">' . $nota . '</p>' : '') . '</div>';
};
?>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?= $tarjeta('car', 'En predio', num($k['total']), (int)$k['reservados'] . ' reservados · ' . (int)$k['ocultos'] . ' ocultos') ?>
  <?php if ($admin): ?>
    <?= $tarjeta('cash', 'Valor del inventario', money($k['valor']), 'Suma de precios publicados', 'bg-sky-500 text-white') ?>
    <?= $tarjeta('handshake', 'Vendidos este mes', num($mes['n']), money($mes['vendido']) . ' en ventas', 'bg-brand-500 text-white') ?>
    <?= $tarjeta('chart', 'Ganancia del mes', money($mes['ganancia']), 'Promedio en predio: ' . round((float)$k['dias']) . ' días', 'bg-emerald-500 text-white') ?>
  <?php else: ?>
    <?= $tarjeta('image', 'Sin fotos', num($sinFotos), 'Vehículos que necesitan fotos', 'bg-brand-500 text-white') ?>
    <?= $tarjeta('clock', 'Promedio en predio', round((float)$k['dias']) . ' días', '', 'bg-sky-500 text-white') ?>
  <?php endif; ?>
</div>

<?php if ($sinFotos && $admin): ?>
  <a href="<?= url('admin/vehiculos.php?sin_fotos=1') ?>" class="mt-4 flex items-center gap-3 rounded-xl bg-amber-50 p-4 text-sm font-medium text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100">
    <?= icon('alert', 'w-5 h-5') ?><?= $sinFotos ?> vehículo(s) en predio aún no tienen fotos. <span class="ml-auto font-bold">Revisar →</span>
  </a>
<?php endif; ?>

<div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
  <?php if ($admin): ?>
  <section class="<?= C_CARD ?> p-5 sm:p-6">
    <div class="flex items-center justify-between">
      <h2 class="font-bold">Ventas y ganancia · últimos 12 meses</h2>
      <a href="<?= url('admin/reportes.php') ?>" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Reportes →</a>
    </div>
    <div class="mt-4 h-72"><canvas id="grafica"></canvas></div>
  </section>
  <?php endif; ?>

  <section class="<?= C_CARD ?> overflow-hidden <?= $admin ? '' : 'xl:col-span-2' ?>">
    <div class="flex items-center justify-between p-5 sm:px-6">
      <h2 class="font-bold">Más tiempo en predio</h2>
      <a href="<?= url('admin/vehiculos.php?orden=dias_desc') ?>" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Ver todos →</a>
    </div>
    <ul class="divide-y divide-ink-100">
      <?php foreach ($antiguos as $v): ?>
        <li><a href="<?= url('admin/vehiculo.php?id=' . $v['id']) ?>" class="flex items-center gap-3 px-5 py-3 hover:bg-ink-50 sm:px-6">
          <img src="<?= e(foto_url($v['portada'], 'sm')) ?>" class="h-11 w-14 rounded-lg object-cover" alt="">
          <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold"><?= e(vehiculo_titulo($v) . ' ' . $v['anio']) ?></p><p class="text-xs text-ink-400"><?= e($v['codigo']) ?> · ingresó <?= fecha($v['fecha_ingreso']) ?></p></div>
          <?= badge_dias(dias_predio($v)) ?>
        </a></li>
      <?php endforeach; ?>
      <?php if (!$antiguos): ?><li class="px-6 py-8 text-center text-sm text-ink-400">Sin vehículos en predio.</li><?php endif; ?>
    </ul>
  </section>
</div>

<section class="<?= C_CARD ?> mt-6 overflow-hidden">
  <div class="flex items-center justify-between p-5 sm:px-6">
    <h2 class="font-bold">Últimos ingresados</h2>
    <a href="<?= url('admin/vehiculos.php') ?>" class="text-sm font-semibold text-brand-600 hover:text-brand-700">Ver inventario →</a>
  </div>
  <div class="grid gap-px bg-ink-100 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($ultimos as $v): ?>
      <a href="<?= url('admin/vehiculo.php?id=' . $v['id']) ?>" class="flex items-center gap-3 bg-white p-4 hover:bg-ink-50">
        <img src="<?= e(foto_url($v['portada'], 'sm')) ?>" class="h-14 w-20 rounded-lg object-cover" alt="">
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold"><?= e(vehiculo_titulo($v) . ' ' . $v['anio']) ?></p>
          <p class="text-sm font-bold tabular-nums"><?= money($v['precio_publicado']) ?></p>
          <div class="mt-1"><?= badge_estado($v['estado']) ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php
$inline = '';
if ($admin) {
    $inline = '<script>
new Chart(document.getElementById("grafica"), {
  data: {
    labels: ' . json_encode(array_column($serie, 'mes')) . ',
    datasets: [
      { type: "bar", label: "Ganancia", data: ' . json_encode(array_column($serie, 'g')) . ', backgroundColor: "' . e(ajuste('color_principal', '#005A50')) . '", borderRadius: 6, yAxisID: "y", maxBarThickness: 34 },
      { type: "line", label: "Vendidos", data: ' . json_encode(array_column($serie, 'n')) . ', borderColor: "#0B1220", backgroundColor: "#0B1220", tension: .3, cubicInterpolationMode: "monotone", pointRadius: 3, yAxisID: "y1" }
    ]
  },
  options: {
    maintainAspectRatio: false, interaction: { mode: "index", intersect: false },
    plugins: { legend: { position: "bottom", labels: { usePointStyle: true, boxWidth: 8 } },
      tooltip: { callbacks: { label: function (c) { return c.dataset.label + ": " + (c.datasetIndex === 0 ? "' . e(ajuste('moneda', 'Q')) . ' " + c.parsed.y.toLocaleString("es-GT") : c.parsed.y); } } } },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, grid: { color: "#E9EDF3" }, ticks: { callback: function (v) { return v >= 1000 ? (v / 1000) + "k" : v; } } },
      y1: { beginAtZero: true, position: "right", grid: { display: false }, ticks: { precision: 0 } }
    }
  }
});
</script>';
}
admin_fin($admin ? ['https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'] : [], $inline);
