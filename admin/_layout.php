<?php
/** Plantilla del panel. Uso: admin_inicio('Título', 'clave'); ... admin_fin(); */

function admin_inicio(string $titulo, string $activo = '', array $opc = []): void
{
    $u = usuario();
    $admin = es_admin();
    $menu = [
        ['dashboard', 'Inicio', 'home', 'admin/', true],
        ['vehiculos', 'Vehículos', 'car', 'admin/vehiculos.php', true],
        ['nuevo', 'Ingresar vehículo', 'plus', 'admin/vehiculo.php', true],
        ['clientes', 'Clientes', 'users', 'admin/clientes.php', $admin],
        ['reportes', 'Reportes', 'chart', 'admin/reportes.php', $admin],
        ['catalogos', 'Marcas y modelos', 'layers', 'admin/catalogos.php', $admin],
        ['usuarios', 'Usuarios', 'key', 'admin/usuarios.php', $admin],
        ['ajustes', 'Ajustes', 'settings', 'admin/ajustes.php', $admin],
    ];
    ?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <meta name="csrf" content="<?= e(csrf_token()) ?>">
  <title><?= e($titulo) ?> · Panel <?= e(negocio()) ?></title>
  <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('assets/css/tw.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-900 antialiased">
<div id="admin-velo" class="fixed inset-0 z-40 hidden bg-ink-950/50 lg:hidden"></div>
<aside id="admin-menu" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-ink-950 text-ink-300 transition-transform lg:translate-x-0">
  <a href="<?= url('admin/') ?>" class="flex h-16 items-center gap-2.5 px-5">
    <span class="grid h-9 w-9 place-items-center rounded-xl bg-white font-extrabold text-ink-900">N</span>
    <span class="leading-tight"><span class="block font-extrabold text-white"><?= e(negocio()) ?></span><span class="text-[11px] font-semibold uppercase tracking-wider text-ink-500">Panel</span></span>
  </a>
  <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4 text-[14px] font-medium">
    <?php foreach ($menu as $m): if (!$m[4]) continue; $on = $m[0] === $activo; ?>
      <a href="<?= url($m[3]) ?>" class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition <?= $on ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' ?>">
        <?= icon($m[2], 'w-[18px] h-[18px] ' . ($on ? 'text-brand-400' : 'text-ink-500')) ?><?= e($m[1]) ?>
      </a>
    <?php endforeach; ?>
    <a href="<?= url('') ?>" target="_blank" class="mt-4 flex items-center gap-3 rounded-lg px-3 py-2.5 hover:bg-white/5 hover:text-white"><?= icon('external', 'w-[18px] h-[18px] text-ink-500') ?>Ver sitio</a>
  </nav>
  <div class="border-t border-white/5 p-3">
    <div class="flex items-center gap-3 rounded-lg px-3 py-2">
      <span class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-sm font-bold text-white"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-white"><?= e($u['nombre']) ?></p>
        <p class="text-xs text-ink-500"><?= $admin ? 'Administrador' : 'Test · carga de vehículos' ?></p>
      </div>
      <a href="<?= url('admin/logout.php') ?>" class="grid h-8 w-8 place-items-center rounded-lg hover:bg-white/10 hover:text-white" title="Salir"><?= icon('logout', 'w-4 h-4') ?></a>
    </div>
  </div>
</aside>

<div class="lg:pl-64">
  <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-ink-100 bg-white/90 px-4 backdrop-blur sm:px-6">
    <button id="admin-abrir" class="grid h-10 w-10 place-items-center rounded-lg hover:bg-ink-50 lg:hidden" aria-label="Menú"><?= icon('menu') ?></button>
    <h1 class="truncate text-lg font-bold"><?= e($titulo) ?></h1>
    <div class="ml-auto flex items-center gap-2"><?= $opc['acciones'] ?? '' ?></div>
  </header>
  <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8">
    <?php foreach (flashes() as $f): $ok = $f[0] === 'ok'; ?>
      <div class="mb-5 flex items-start gap-3 rounded-xl p-4 text-sm font-medium ring-1 <?= $ok ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-red-50 text-red-800 ring-red-200' ?>">
        <?= icon($ok ? 'check' : 'alert', 'w-5 h-5 shrink-0') ?><span><?= e($f[1]) ?></span>
      </div>
    <?php endforeach; ?>
<?php
}

function admin_fin(array $scripts = [], string $inline = ''): void
{
    ?>
  </main>
</div>
<script>
  (function () {
    var m = document.getElementById('admin-menu'), v = document.getElementById('admin-velo');
    function t(o) { m.classList.toggle('-translate-x-full', !o); v.classList.toggle('hidden', !o); }
    document.getElementById('admin-abrir').addEventListener('click', function () { t(true); });
    v.addEventListener('click', function () { t(false); });
    document.querySelectorAll('[data-confirmar]').forEach(function (f) {
      f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirmar'))) e.preventDefault(); });
    });
  })();
</script>
<?php foreach ($scripts as $s): ?><script src="<?= e($s) ?>"></script><?php endforeach; ?>
<?= $inline ?>
</body>
</html>
<?php
}

/** Clases reutilizables */
const C_INPUT = 'mt-1.5 block w-full rounded-xl border-ink-200 bg-white text-sm focus:border-brand-500 focus:ring-brand-500';
const C_LABEL = 'block text-sm font-semibold text-ink-700';
const C_CARD  = 'rounded-2xl bg-white ring-1 ring-ink-100';
const C_BTN   = 'inline-flex items-center justify-center gap-2 rounded-xl bg-ink-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-ink-700 transition';
const C_BTN2  = 'inline-flex items-center justify-center gap-2 rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 hover:bg-ink-50 transition';
const C_BTN_BRAND = 'inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-600 transition';

function paginacion(int $total, int $pagina, int $por, array $query): string
{
    $paginas = (int)ceil($total / $por);
    if ($paginas <= 1) {
        return '';
    }
    $html = '<nav class="mt-6 flex flex-wrap items-center justify-center gap-1">';
    for ($i = max(1, $pagina - 3); $i <= min($paginas, $pagina + 3); $i++) {
        $query['p'] = $i;
        $on = $i === $pagina;
        $html .= '<a href="?' . e(http_build_query($query)) . '" class="grid h-9 min-w-9 place-items-center rounded-lg px-3 text-sm font-semibold ' .
            ($on ? 'bg-ink-900 text-white' : 'bg-white text-ink-600 ring-1 ring-ink-200 hover:bg-ink-50') . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}
