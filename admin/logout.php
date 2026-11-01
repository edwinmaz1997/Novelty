<?php
require dirname(__DIR__) . '/app/bootstrap.php';
cerrar_sesion();
redirect('admin/login.php');
