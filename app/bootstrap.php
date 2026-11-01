<?php
/**
 * Novelty — arranque común (PHP 7.4+)
 */
define('ROOT', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

date_default_timezone_set('America/Guatemala');
mb_internal_encoding('UTF-8');

require_once ROOT . '/app/helpers.php';
require_once ROOT . '/app/icons.php';

$__cfg = ROOT . '/app/config.local.php';
if (is_file($__cfg)) {
    $GLOBALS['config'] = require $__cfg;
} else {
    $GLOBALS['config'] = [];
    if (!defined('INSTALADOR')) {
        header('Location: ' . url('instalar.php'));
        exit;
    }
}

$__debug = !empty($GLOBALS['config']['debug']);
error_reporting(E_ALL);
ini_set('display_errors', $__debug ? '1' : '0');
ini_set('log_errors', '1');

require_once ROOT . '/app/db.php';
require_once ROOT . '/app/auth.php';
require_once ROOT . '/app/vehiculos.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure = es_https();
    session_name('novelty_s');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (base_path() === '' ? '/' : base_path() . '/'),
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}
