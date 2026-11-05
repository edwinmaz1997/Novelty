<?php
function usuario(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = q_one('SELECT id, nombre, usuario, rol, activo FROM usuarios WHERE id = ?', [(int)$_SESSION['uid']]);
            if (!$u || !(int)$u['activo']) {
                $u = null;
                unset($_SESSION['uid']);
            }
        }
    }
    return $u;
}

/** Gestión completa del negocio (vehículos, costos, clientes, ventas, reportes, catálogos): admin y test. */
function es_admin(): bool
{
    $u = usuario();
    return $u !== null && in_array($u['rol'], ['admin', 'test'], true);
}

/** Solo el administrador principal: Ajustes del sitio y Usuarios. */
function es_superadmin(): bool
{
    $u = usuario();
    return $u !== null && $u['rol'] === 'admin';
}

function requiere_login(): array
{
    $u = usuario();
    if (!$u) {
        redirect('admin/login.php');
    }
    return $u;
}

function requiere_admin(): array
{
    $u = requiere_login();
    if (!es_admin()) {
        flash('error', 'Tu usuario no tiene acceso a esa sección.');
        redirect('admin/');
    }
    return $u;
}

function requiere_superadmin(): array
{
    $u = requiere_login();
    if (!es_superadmin()) {
        flash('error', 'Tu usuario no tiene acceso a esa sección.');
        redirect('admin/');
    }
    return $u;
}

function intentar_login(string $usuario, string $pass): bool
{
    $intentos = $_SESSION['login_intentos'] ?? [];
    $intentos = array_filter($intentos, function ($t) {
        return $t > time() - 600;
    });
    if (count($intentos) >= 8) {
        return false;
    }
    $u = q_one('SELECT * FROM usuarios WHERE usuario = ? AND activo = 1', [$usuario]);
    if (!$u || !password_verify($pass, $u['password_hash'])) {
        $intentos[] = time();
        $_SESSION['login_intentos'] = $intentos;
        return false;
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE usuarios SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    unset($_SESSION['login_intentos']);
    q('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?', [$u['id']]);
    return true;
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}
