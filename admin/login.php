<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor completa todos los campos.';
    } else {
        $result = login($email, $password);
        if ($result === true) {
            header('Location: ' . APP_URL . '/index.php');
            exit;
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión — Palcus Peru</title>
  <link rel="icon" href="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png' ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      background: #f5f5f5;
      color: #111;
    }

    /* ── LEFT PANEL ─────────────────────────────────── */
    .left-panel {
      width: 45%;
      background: #0a0a0a;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 3rem;
      position: relative;
      overflow: hidden;
    }

    /* Subtle texture */
    .left-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image:
        radial-gradient(circle at 20% 20%, rgba(255,255,255,0.04) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.03) 0%, transparent 50%);
      pointer-events: none;
    }

    /* Decorative line pattern */
    .left-panel::after {
      content: '';
      position: absolute;
      bottom: -60px;
      right: -60px;
      width: 300px;
      height: 300px;
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 50%;
      box-shadow: 0 0 0 60px rgba(255,255,255,0.03), 0 0 0 120px rgba(255,255,255,0.02);
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 0.875rem;
      position: relative;
      z-index: 1;
    }

    .brand-logo {
      width: 44px;
      height: 44px;
      object-fit: contain;
      filter: brightness(0) invert(1);
    }

    .brand-name {
      color: #fff;
      font-size: 1.125rem;
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .brand-sub {
      color: rgba(255,255,255,0.35);
      font-size: 0.75rem;
      font-weight: 400;
      margin-top: 2px;
    }

    .left-hero {
      position: relative;
      z-index: 1;
    }

    .left-hero h1 {
      font-size: clamp(2rem, 3.5vw, 2.75rem);
      font-weight: 800;
      color: #ffffff;
      line-height: 1.15;
      letter-spacing: -0.03em;
      margin-bottom: 1.25rem;
    }

    .left-hero h1 span {
      color: rgba(255,255,255,0.35);
    }

    .left-hero p {
      color: rgba(255,255,255,0.45);
      font-size: 0.9375rem;
      line-height: 1.65;
      max-width: 340px;
    }

    .features {
      position: relative;
      z-index: 1;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-top: 2.5rem;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 10px;
      background: rgba(255,255,255,0.04);
    }

    .feature-item .feat-icon {
      width: 32px;
      height: 32px;
      background: rgba(255,255,255,0.08);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 0.9rem;
    }

    .feature-item span {
      color: rgba(255,255,255,0.65);
      font-size: 0.8125rem;
      line-height: 1.4;
    }

    .left-footer {
      position: relative;
      z-index: 1;
      color: rgba(255,255,255,0.2);
      font-size: 0.75rem;
    }

    /* ── RIGHT PANEL ─────────────────────────────────── */
    .right-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem 2rem;
      background: #ffffff;
    }

    .login-box {
      width: 100%;
      max-width: 400px;
    }

    .login-header {
      margin-bottom: 2.5rem;
    }

    .login-header .welcome {
      font-size: 1.875rem;
      font-weight: 800;
      color: #0a0a0a;
      letter-spacing: -0.03em;
      line-height: 1.2;
    }

    .login-header .subtitle {
      color: #6b7280;
      font-size: 0.875rem;
      margin-top: 0.5rem;
    }

    /* Error alert */
    .alert-error {
      display: flex;
      align-items: center;
      gap: 0.625rem;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #dc2626;
      font-size: 0.8125rem;
      border-radius: 10px;
      padding: 0.875rem 1rem;
      margin-bottom: 1.5rem;
    }

    .alert-error svg { flex-shrink: 0; }

    /* Form */
    .form-group {
      margin-bottom: 1.25rem;
    }

    label {
      display: block;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
    }

    .input-wrap {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 0.875rem;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      pointer-events: none;
      display: flex;
    }

    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      height: 48px;
      padding: 0 1rem 0 2.75rem;
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      font-size: 0.9375rem;
      font-family: 'Inter', sans-serif;
      color: #111827;
      background: #fafafa;
      transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
      outline: none;
    }

    input::placeholder { color: #c0c7d0; }

    input:focus {
      border-color: #111827;
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(17,24,39,0.08);
    }

    .input-wrap .toggle-pass {
      position: absolute;
      right: 0.875rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: #9ca3af;
      display: flex;
      padding: 4px;
      transition: color 0.15s;
    }

    .toggle-pass:hover { color: #374151; }

    /* Submit button */
    .btn-submit {
      width: 100%;
      height: 50px;
      background: #0a0a0a;
      color: #ffffff;
      font-family: 'Inter', sans-serif;
      font-size: 0.9375rem;
      font-weight: 600;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 0.5rem;
      transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
      letter-spacing: -0.01em;
    }

    .btn-submit:hover {
      background: #1f2937;
      box-shadow: 0 4px 20px rgba(0,0,0,0.18);
      transform: translateY(-1px);
    }

    .btn-submit:active {
      transform: translateY(0);
      box-shadow: none;
    }

    .btn-submit svg { flex-shrink: 0; }

    /* Divider */
    .divider {
      border: none;
      border-top: 1px solid #f3f4f6;
      margin: 2rem 0 1.25rem;
    }

    .help-text {
      text-align: center;
      font-size: 0.8125rem;
      color: #9ca3af;
    }

    /* ── RESPONSIVE ─────────────────────────────────── */
    @media (max-width: 768px) {
      .left-panel { display: none; }
      .right-panel { background: #f5f5f5; padding: 2rem 1.5rem; }
      .login-box {
        background: #fff;
        padding: 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 32px rgba(0,0,0,0.07);
      }
    }
  </style>
</head>
<body>

  <!-- ── LEFT PANEL ──────────────────────────────── -->
  <div class="left-panel">

    <div class="brand">
      <img
        src="<?= getConfig('url_icono') ?: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/v1778354037/vjypdweg16udzxoptdxz.png' ?>"
        alt="<?= e(getConfig('nombre_tienda') ?: 'Palcus Peru') ?>"
        class="brand-logo"
      />
      <div>
        <div class="brand-name"><?= e(getConfig('nombre_tienda') ?: 'Palcus Peru') ?></div>
        <div class="brand-sub">Sistema de Gestión</div>
      </div>
    </div>

    <div class="left-hero">
      <h1>
        Gestiona tu<br>
        negocio<br>
        <span>con control total.</span>
      </h1>
      <p>
        Inventario, ventas, gastos, reportes y más,
        todo en un solo panel diseñado para tu negocio.
      </p>

      <div class="features">
        <div class="feature-item">
          <div class="feat-icon">📦</div>
          <span>Control de inventario con alertas de stock</span>
        </div>
        <div class="feature-item">
          <div class="feat-icon">💰</div>
          <span>Ventas y gastos registrados en tiempo real</span>
        </div>
        <div class="feature-item">
          <div class="feat-icon">📊</div>
          <span>Reportes exportables en PDF y Excel</span>
        </div>
        <div class="feature-item">
          <div class="feat-icon">📱</div>
          <span>Alertas automáticas por WhatsApp</span>
        </div>
      </div>
    </div>

    <div class="left-footer">
      © <?= date('Y') ?> Palcus Peru &nbsp;·&nbsp; v<?= APP_VERSION ?>
    </div>
  </div>

  <!-- ── RIGHT PANEL ─────────────────────────────── -->
  <div class="right-panel">
    <div class="login-box">

      <div class="login-header">
        <div class="welcome">Bienvenido de vuelta</div>
        <div class="subtitle">Ingresa tus credenciales para acceder al panel</div>
      </div>

      <?php if ($error): ?>
      <div class="alert-error">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>" />

        <!-- Email -->
        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </span>
            <input
              id="email"
              name="email"
              type="email"
              required
              autocomplete="email"
              placeholder="admin@palcus.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            />
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="input-wrap">
            <span class="input-icon">
              <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </span>
            <input
              id="password"
              name="password"
              type="password"
              required
              autocomplete="current-password"
              placeholder="••••••••"
            />
            <button type="button" class="toggle-pass" id="togglePass" aria-label="Mostrar contraseña">
              <svg id="eyeShow" width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <svg id="eyeHide" width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
          </svg>
          Iniciar sesión
        </button>
      </form>

      <hr class="divider" />
      <p class="help-text">¿Olvidaste tu contraseña? Contacta al administrador.</p>

    </div>
  </div>

  <script>
    const toggleBtn = document.getElementById('togglePass');
    const passField = document.getElementById('password');
    const eyeShow   = document.getElementById('eyeShow');
    const eyeHide   = document.getElementById('eyeHide');

    toggleBtn.addEventListener('click', () => {
      const isPass = passField.type === 'password';
      passField.type = isPass ? 'text' : 'password';
      eyeShow.style.display = isPass ? 'none' : '';
      eyeHide.style.display = isPass ? '' : 'none';
    });
  </script>
</body>
</html>
