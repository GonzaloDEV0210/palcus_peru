<?php
// ============================================================
// PalCus Admin — Autenticación y Gestión de Sesiones
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Iniciar sesión de forma segura
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false, // true en producción HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// Verificar si el usuario está logueado
function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Obtener datos del usuario actual
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;
    return $_SESSION['user'] ?? null;
}

// Obtener rol del usuario actual
function currentRole(): string
{
    return currentUser()['rol'] ?? '';
}

// Verificar si tiene un rol específico
function hasRole(string ...$roles): bool
{
    return in_array(currentRole(), $roles, true);
}

// Proteger una página (redirige a login si no está autenticado)
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Proteger por rol
function requireRole(string ...$roles): void
{
    requireLogin();
    if (!hasRole(...$roles)) {
        http_response_code(403);
        include APP_ROOT . '/includes/403.php';
        exit;
    }
}

// Login: verifica credenciales y crea sesión
function login(string $email, string $password): bool|string
{
    $user = db()->fetchOne(
        'SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1',
        [strtolower(trim($email))]
    );

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return 'Correo o contraseña incorrectos.';
    }

    startSession();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user']    = [
        'id'      => $user['id'],
        'nombre'  => $user['nombre'],
        'email'   => $user['email'],
        'rol'     => $user['rol'],
        'telefono'=> $user['telefono'],
    ];

    // Actualizar último acceso
    db()->execute(
        'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?',
        [$user['id']]
    );

    return true;
}

// Cerrar sesión
function logout(): void
{
    startSession();
    $_SESSION = [];
    session_destroy();
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// CSRF: generar token
function csrfToken(): string
{
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF: verificar token
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die(json_encode(['error' => 'Token CSRF inválido.']));
    }
}

// Iniciar sesión al cargar el archivo
startSession();
