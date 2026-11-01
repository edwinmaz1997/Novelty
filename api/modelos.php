<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$marca = gi('marca');
json_out(['modelos' => $marca ? facetas_modelos($marca) : []]);
