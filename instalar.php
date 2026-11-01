<?php
/**
 * Instalador web: crea config.local.php, tablas, datos base y el administrador.
 * Se bloquea solo una vez instalado.
 */
define('INSTALADOR', true);
require __DIR__ . '/app/bootstrap.php';
require_once ROOT . '/app/seed.php';

$cfgFile = ROOT . '/app/config.local.php';

function ya_instalado(): bool
{
    if (empty($GLOBALS['config']['db'])) {
        return false;
    }
    try {
        return (int)q_val('SELECT COUNT(*) FROM usuarios') > 0;
    } catch (Throwable $e) {
        return false;
    }
}

$errores = [];
$listo = false;
$config_manual = null;

if (ya_instalado()) {
    $listo = true;
} elseif (es_post()) {
    $db = [
        'host' => p('db_host', 'localhost'),
        'port' => (int)p('db_port', '3306') ?: 3306,
        'name' => p('db_name'),
        'user' => p('db_user'),
        'pass' => (string)($_POST['db_pass'] ?? ''),
    ];
    $admin_nombre = p('admin_nombre');
    $admin_usuario = p('admin_usuario');
    $admin_pass = (string)($_POST['admin_pass'] ?? '');

    if ($db['name'] === '' || $db['user'] === '') {
        $errores[] = 'Escribe el nombre y usuario de la base de datos.';
    }
    if ($admin_nombre === '' || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $admin_usuario)) {
        $errores[] = 'Escribe el nombre del administrador y un usuario válido (letras, números, punto o guion).';
    }
    if (strlen($admin_pass) < 8) {
        $errores[] = 'La contraseña del administrador debe tener al menos 8 caracteres.';
    }

    if (!$errores) {
        try {
            $pdo = db_conectar($db);
        } catch (Throwable $e) {
            $errores[] = 'No se pudo conectar a MySQL: ' . $e->getMessage();
        }
    }

    if (!$errores) {
        try {
            $sql = file_get_contents(ROOT . '/app/schema.sql');
            foreach (array_filter(array_map('trim', explode(";\n", str_replace("\r\n", "\n", $sql)))) as $st) {
                $pdo->exec($st);
            }
            $GLOBALS['config'] = ['db' => $db, 'debug' => false];
            seed_catalogos($pdo);
            seed_ajustes($pdo);
            if (p('negocio') !== '') {
                $pdo->prepare('UPDATE ajustes SET valor = ? WHERE clave = ?')->execute([p('negocio'), 'negocio_nombre']);
            }
            if (p('whatsapp') !== '') {
                $pdo->prepare('UPDATE ajustes SET valor = ? WHERE clave = ?')->execute([preg_replace('/\D/', '', p('whatsapp')), 'whatsapp']);
            }
            $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)')
                ->execute([$admin_nombre, $admin_usuario, password_hash($admin_pass, PASSWORD_DEFAULT), 'admin']);
            $uid = (int)$pdo->lastInsertId();
            migrar();
            if (!empty($_POST['demo'])) {
                seed_demo($pdo, $uid);
                foreach ($pdo->query('SELECT id FROM vehiculos')->fetchAll(PDO::FETCH_COLUMN) as $vid) {
                    vehiculo_actualizar_buscar((int)$vid);
                }
            }

            $contenido = "<?php\n// Generado por instalar.php — NO subir a git.\nreturn " . var_export(['db' => $db, 'debug' => false], true) . ";\n";
            if (@file_put_contents($cfgFile, $contenido) === false) {
                $config_manual = $contenido;
            }
            @mkdir(ROOT . '/uploads/vehiculos', 0755, true);
            $listo = true;
        } catch (Throwable $e) {
            $errores[] = 'Error durante la instalación: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>Instalación · Novelty</title>
  <link rel="stylesheet" href="<?= asset('assets/css/tw.css') ?>">
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-900">
<div class="mx-auto max-w-2xl px-4 py-12">
  <div class="flex items-center gap-3">
    <span class="grid h-11 w-11 place-items-center rounded-xl bg-ink-900 text-xl font-extrabold text-white">N</span>
    <div><h1 class="text-2xl font-extrabold">Instalación de Novelty</h1><p class="text-sm text-ink-500">Configura la base de datos y el usuario administrador.</p></div>
  </div>

  <?php if ($listo): ?>
    <div class="mt-8 rounded-2xl bg-white p-8 ring-1 ring-ink-100">
      <h2 class="text-xl font-bold text-emerald-700">✓ Sistema instalado</h2>
      <?php if ($config_manual): ?>
        <p class="mt-3 text-sm text-ink-600">No se pudo escribir <code>app/config.local.php</code>. Créalo desde el Administrador de archivos de cPanel con este contenido:</p>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-ink-900 p-4 text-xs text-ink-100"><?= e($config_manual) ?></pre>
      <?php endif; ?>
      <div class="mt-6 flex gap-3">
        <a href="<?= url('admin/') ?>" class="rounded-xl bg-ink-900 px-5 py-3 font-bold text-white">Entrar al panel</a>
        <a href="<?= url('') ?>" class="rounded-xl border border-ink-200 px-5 py-3 font-semibold">Ver sitio</a>
      </div>
    </div>
  <?php else: ?>
    <?php foreach ($errores as $er): ?>
      <div class="mt-6 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700 ring-1 ring-red-200"><?= e($er) ?></div>
    <?php endforeach; ?>
    <form method="post" class="mt-8 space-y-6">
      <fieldset class="rounded-2xl bg-white p-6 ring-1 ring-ink-100">
        <legend class="px-1 text-sm font-bold uppercase tracking-wider text-ink-500">Base de datos MySQL</legend>
        <div class="grid gap-4 sm:grid-cols-[1fr_120px]">
          <label class="block text-sm font-semibold">Servidor<input name="db_host" value="<?= e(p('db_host', 'localhost')) ?>" class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold">Puerto<input name="db_port" value="<?= e(p('db_port', '3306')) ?>" class="mt-1 w-full rounded-xl border-ink-200"></label>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-3">
          <label class="block text-sm font-semibold">Base de datos<input name="db_name" value="<?= e(p('db_name')) ?>" required class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold">Usuario<input name="db_user" value="<?= e(p('db_user')) ?>" required class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold">Contraseña<input type="password" name="db_pass" class="mt-1 w-full rounded-xl border-ink-200"></label>
        </div>
      </fieldset>
      <fieldset class="rounded-2xl bg-white p-6 ring-1 ring-ink-100">
        <legend class="px-1 text-sm font-bold uppercase tracking-wider text-ink-500">Negocio y administrador</legend>
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="block text-sm font-semibold">Nombre del negocio<input name="negocio" value="<?= e(p('negocio', 'Novelty Motors')) ?>" class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold">WhatsApp (con 502)<input name="whatsapp" value="<?= e(p('whatsapp')) ?>" placeholder="50255555555" class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold">Tu nombre<input name="admin_nombre" value="<?= e(p('admin_nombre')) ?>" required class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold">Usuario<input name="admin_usuario" value="<?= e(p('admin_usuario', 'admin')) ?>" required class="mt-1 w-full rounded-xl border-ink-200"></label>
          <label class="block text-sm font-semibold sm:col-span-2">Contraseña (mín. 8)<input type="password" name="admin_pass" required minlength="8" class="mt-1 w-full rounded-xl border-ink-200"></label>
        </div>
        <label class="mt-5 flex items-start gap-3 rounded-xl bg-ink-50 p-4 text-sm">
          <input type="checkbox" name="demo" value="1" checked class="mt-0.5 rounded border-ink-300 text-brand-500 focus:ring-brand-500">
          <span><b>Cargar vehículos de ejemplo</b><br><span class="text-ink-500">12 vehículos y algunas ventas para ver el sitio y los reportes. Puedes borrarlos después.</span></span>
        </label>
      </fieldset>
      <button class="w-full rounded-xl bg-brand-500 py-3.5 font-bold text-white hover:bg-brand-600">Instalar</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>
