<?php
/** /ofertas/ → catálogo filtrado por precio de oferta. */
define('PAGINA_OFERTAS', true);
$_GET['oferta'] = '1';
if (!isset($_GET['orden'])) {
    $_GET['orden'] = 'descuento';
}
require __DIR__ . '/autos.php';
