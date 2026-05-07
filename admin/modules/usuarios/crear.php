<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin');

$activePage = 'usuarios';
$pageTitle  = 'Nuevo Usuario';
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $nombre   = trim($_POST['nombre']   ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $rol      =      $_POST['rol']      ?? 'vendedor';
    $telefono = trim($_POST['telefono'] ?? '');

    if (!$nombre) $errors[] = 'El nombre es obligatorio.';
    if (!$email)  $errors[] = 'El email es obligatorio.';
    if (!$password || strlen($password) < 6) $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    
    $exists = db()->fetchOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
    if ($exists) $errors[] = 'Este correo ya está registrado.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->execute(
            "INSERT INTO usuarios (nombre, email, password_hash, rol, telefono) VALUES (?,?,?,?,?)",
            [$nombre, $email, $hash, $rol, $telefono]
        );
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Usuario creado correctamente.'];
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    * {font-family:'Inter',sans-serif;}
    .form-input {width:100%;padding:.625rem .875rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.9375rem;color:#111827;background:#fafafa;outline:none;transition:all .15s;}
    .form-input:focus{border-color:#111827;background:#fff;box-shadow:0 0 0 3px rgba(17,24,39,.07);}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6 max-w-2xl mx-auto w-full">

      <nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
        <a href="index.php" class="hover:text-gray-700 transition-colors">Usuarios</a>
        <span>/</span><span class="text-gray-700">Nuevo</span>
      </nav>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Información del Usuario</h2>
        
        <?php if ($errors): ?>
        <div class="mb-6 bg-red-50 border border-red-100 text-red-600 text-sm p-4 rounded-xl">
          <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
          
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nombre Completo</label>
            <input name="nombre" class="form-input" required value="<?= e($_POST['nombre']??'') ?>" placeholder="Ej: Juan Pérez"/>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Email</label>
              <input name="email" type="email" class="form-input" required value="<?= e($_POST['email']??'') ?>" placeholder="juan@palcus.com"/>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Teléfono</label>
              <input name="telefono" class="form-input" value="<?= e($_POST['telefono']??'') ?>" placeholder="999 999 999"/>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Contraseña</label>
              <input name="password" type="password" class="form-input" required placeholder="Mínimo 6 caracteres"/>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Rol del Usuario</label>
              <select name="rol" class="form-input">
                <option value="vendedor" <?= ($_POST['rol']??'')==='vendedor'?'selected':'' ?>>Vendedor (Ventas/Clientes)</option>
                <option value="almacenero" <?= ($_POST['rol']??'')==='almacenero'?'selected':'' ?>>Almacenero (Inventario/Productos)</option>
                <option value="admin" <?= ($_POST['rol']??'')==='admin'?'selected':'' ?>>Administrador (Todo)</option>
              </select>
            </div>
          </div>

          <div class="pt-4 flex gap-3">
            <a href="index.php" class="flex-1 text-center py-3 border border-gray-100 text-gray-500 font-bold text-sm rounded-xl hover:bg-gray-50 transition-colors">Cancelar</a>
            <button type="submit" class="flex-1 py-3 bg-gray-900 text-white font-bold text-sm rounded-xl hover:bg-gray-700 transition-all shadow-sm">Crear Usuario</button>
          </div>
        </form>
      </div>

    </main>
  </div>
</div>
<?php include $ADMIN . '/includes/foot.php'; ?>
</body>
</html>
