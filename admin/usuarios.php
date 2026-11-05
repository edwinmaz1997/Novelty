<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/_layout.php';
$yo = requiere_superadmin();

$editId = gi('id');
$edit = $editId ? q_one('SELECT * FROM usuarios WHERE id = ?', [$editId]) : null;
$error = '';

if (es_post()) {
    csrf_check();
    $id = (int)p('id');
    $nombre = mb_substr(p('nombre'), 0, 100);
    $usuario = p('usuario');
    $rol = p('rol') === 'admin' ? 'admin' : 'test';
    $activo = p('activo') === '1' ? 1 : 0;
    $pass = (string)($_POST['password'] ?? '');
    $admins = (int)q_val("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin' AND activo = 1" . ($id ? ' AND id <> ' . $id : ''));

    if ($nombre === '' || !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $usuario)) {
        $error = 'Escribe el nombre y un usuario válido (3 a 50 letras, números, punto o guion).';
    } elseif (q_val('SELECT 1 FROM usuarios WHERE usuario = ? AND id <> ?', [$usuario, $id])) {
        $error = 'Ese usuario ya existe.';
    } elseif (!$id && strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($id && $pass !== '' && strlen($pass) < 8) {
        $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($id && ($rol !== 'admin' || !$activo) && $admins === 0) {
        $error = 'Debe quedar al menos un administrador activo.';
    } else {
        if ($id) {
            q('UPDATE usuarios SET nombre = ?, usuario = ?, rol = ?, activo = ? WHERE id = ?', [$nombre, $usuario, $rol, $activo, $id]);
            if ($pass !== '') {
                q('UPDATE usuarios SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $id]);
            }
            flash('ok', 'Usuario actualizado.');
        } else {
            q('INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?)', [$nombre, $usuario, password_hash($pass, PASSWORD_DEFAULT), $rol, $activo]);
            flash('ok', 'Usuario creado.');
        }
        redirect('admin/usuarios.php');
    }
    $edit = ['id' => $id, 'nombre' => $nombre, 'usuario' => $usuario, 'rol' => $rol, 'activo' => $activo];
}

$usuarios = q_all('SELECT u.*, (SELECT COUNT(*) FROM vehiculos v WHERE v.creado_por = u.id) AS subidos FROM usuarios u ORDER BY u.rol, u.nombre');
$d = $edit ?: ['id' => 0, 'nombre' => '', 'usuario' => '', 'rol' => 'test', 'activo' => 1];

admin_inicio('Usuarios', 'usuarios');
?>
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
  <section class="<?= C_CARD ?> overflow-hidden">
    <ul class="divide-y divide-ink-100">
      <?php foreach ($usuarios as $x): ?>
        <li class="flex items-center gap-4 px-5 py-4 <?= (int)$d['id'] === (int)$x['id'] ? 'bg-brand-50/50' : '' ?>">
          <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full <?= $x['rol'] === 'admin' ? 'bg-ink-900 text-white' : 'bg-ink-100 text-ink-600' ?> text-sm font-bold"><?= e(mb_strtoupper(mb_substr($x['nombre'], 0, 1))) ?></span>
          <div class="min-w-0 flex-1">
            <p class="font-semibold"><?= e($x['nombre']) ?> <?php if ((int)$x['id'] === (int)$yo['id']): ?><span class="text-xs font-medium text-ink-400">(tú)</span><?php endif; ?></p>
            <p class="text-xs text-ink-500">@<?= e($x['usuario']) ?> · <?= (int)$x['subidos'] ?> vehículos ingresados · último acceso <?= $x['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($x['ultimo_acceso'])) : 'nunca' ?></p>
          </div>
          <span class="rounded-md px-2 py-0.5 text-xs font-semibold <?= $x['rol'] === 'admin' ? 'bg-ink-900 text-white' : 'bg-sky-50 text-sky-700' ?>"><?= $x['rol'] === 'admin' ? 'Admin' : 'Test' ?></span>
          <?php if (!(int)$x['activo']): ?><span class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Inactivo</span><?php endif; ?>
          <a href="?id=<?= $x['id'] ?>" class="grid h-8 w-8 place-items-center rounded-lg text-ink-500 hover:bg-ink-100"><?= icon('edit', 'w-4 h-4') ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <form method="post" class="<?= C_CARD ?> h-fit p-5 sm:p-6">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
    <h2 class="font-bold"><?= $d['id'] ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
    <?php if ($error): ?><p class="mt-3 rounded-xl bg-red-50 p-3 text-sm font-medium text-red-700"><?= e($error) ?></p><?php endif; ?>
    <div class="mt-4 space-y-4">
      <label class="<?= C_LABEL ?>">Nombre<input name="nombre" value="<?= e($d['nombre']) ?>" required class="<?= C_INPUT ?>"></label>
      <label class="<?= C_LABEL ?>">Usuario<input name="usuario" value="<?= e($d['usuario']) ?>" required autocomplete="off" class="<?= C_INPUT ?>"></label>
      <label class="<?= C_LABEL ?>"><?= $d['id'] ? 'Nueva contraseña (opcional)' : 'Contraseña' ?><input type="password" name="password" autocomplete="new-password" <?= $d['id'] ? '' : 'required' ?> minlength="8" class="<?= C_INPUT ?>"></label>
      <div>
        <span class="<?= C_LABEL ?>">Rol</span>
        <div class="mt-1.5 space-y-2">
          <label class="flex cursor-pointer gap-3 rounded-xl p-3 ring-1 ring-ink-200 has-[:checked]:bg-ink-50 has-[:checked]:ring-ink-900">
            <input type="radio" name="rol" value="admin" <?= $d['rol'] === 'admin' ? 'checked' : '' ?> class="mt-0.5 text-ink-900 focus:ring-ink-900">
            <span class="text-sm"><b>Administrador</b><br><span class="text-ink-500">Acceso total, incluidos Ajustes del sitio y Usuarios.</span></span>
          </label>
          <label class="flex cursor-pointer gap-3 rounded-xl p-3 ring-1 ring-ink-200 has-[:checked]:bg-ink-50 has-[:checked]:ring-ink-900">
            <input type="radio" name="rol" value="test" <?= $d['rol'] === 'test' ? 'checked' : '' ?> class="mt-0.5 text-ink-900 focus:ring-ink-900">
            <span class="text-sm"><b>Test</b><br><span class="text-ink-500">Todo lo del negocio (vehículos, costos, ganancias, ventas, clientes, reportes y catálogos), excepto Ajustes del sitio y Usuarios.</span></span>
          </label>
        </div>
      </div>
      <label class="flex items-center gap-3 text-sm font-semibold">
        <input type="checkbox" name="activo" value="1" <?= (int)$d['activo'] ? 'checked' : '' ?> class="h-5 w-5 rounded border-ink-300 text-brand-500 focus:ring-brand-500">Activo (puede ingresar)
      </label>
    </div>
    <div class="mt-6 flex gap-2">
      <button class="<?= C_BTN_BRAND ?> flex-1"><?= icon('check', 'w-4 h-4') ?>Guardar</button>
      <?php if ($d['id']): ?><a href="<?= url('admin/usuarios.php') ?>" class="<?= C_BTN2 ?>">Nuevo</a><?php endif; ?>
    </div>
  </form>
</div>
<?php admin_fin();
