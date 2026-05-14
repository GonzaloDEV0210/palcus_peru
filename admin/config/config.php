<?php
// ============================================================
// PalCus Admin — Configuración Global
// IMPORTANTE: Ajusta estas variables según tu entorno
// ============================================================

// --- BASE DE DATOS ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'palcus_admin');
define('DB_USER', 'root');       // En Hostinger: tu usuario de BD cPanel
define('DB_PASS', '');           // En Hostinger: tu contraseña de BD cPanel
define('DB_CHARSET', 'utf8mb4');

// --- CLOUDINARY ---
define('CLOUDINARY_CLOUD_NAME', 'dv7nmkmpm');
define('CLOUDINARY_API_KEY', '923664445241815');
define('CLOUDINARY_API_SECRET', 'Z8LcceCtkrmWYb2dIWym1ey8vVY');

// --- APLICACIÓN ---
define('APP_NAME', 'Palcus Peru');
define('APP_VERSION', '1.0.0');
// En local: http://localhost/palcus_peru/admin
// En Hostinger: https://tudominio.com/admin
define('APP_URL', 'http://localhost/palcus_peru/admin');
define('APP_ROOT', __DIR__ . '/..');

// --- SESIÓN ---
define('SESSION_NAME', 'palcus_admin_sess');
define('SESSION_LIFETIME', 3600 * 8); // 8 horas

// --- CALLMEBOT (WhatsApp gratuito) ---
// Obtén tu API Key en: https://www.callmebot.com/blog/free-api-whatsapp-messages/
define('CALLMEBOT_PHONE', '');    // Ej: 51981293422
define('CALLMEBOT_API_KEY', ''); // API Key recibida por WhatsApp

// --- GOOGLE DRIVE API ---
// Configurar cuando implementemos la integración con Drive
define('GOOGLE_DRIVE_FOLDER_ID', '');
define('GOOGLE_CREDENTIALS_PATH', APP_ROOT . '/config/google_credentials.json');

// --- ZONA HORARIA ---
date_default_timezone_set('America/Lima');

// --- ENTORNO ---
define('APP_ENV', 'development'); // Cambiar a 'production' en Hostinger

// Mostrar errores solo en desarrollo
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
