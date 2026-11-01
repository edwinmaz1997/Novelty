<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$f    = vehiculos_filtros($_GET);
$page = max(1, gi('page', 1));
$res  = vehiculos_buscar($f, true, $page, 12);

ob_start();
foreach ($res['items'] as $v) {
    include ROOT . '/partials/card.php';
}
$html = ob_get_clean();

header('Cache-Control: no-store');
json_out([
    'html'     => $html,
    'total'    => $res['total'],
    'page'     => $res['page'],
    'mostrados'=> ($res['page'] - 1) * $res['per'] + count($res['items']),
    'has_more' => $res['has_more'],
]);
