<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$name = trim($_GET['nombre'] ?? '');
if (!$name) { echo json_encode(['prefix' => '']); exit; }

$name = strtoupper($name);
$words = preg_split('/\s+/', $name);

$candidates = [];

// 1. Primeras 4 letras de la primera palabra (PANT)
$candidates[] = substr($words[0], 0, 4);

// 2. 3 letras de la 1era + 2 letras de la 2da (MANCO)
if (count($words) >= 2) {
    $candidates[] = substr($words[0], 0, 3) . substr($words[1], 0, 2);
}

// 3. 2 letras de cada palabra (MACECO)
if (count($words) >= 2) {
    $pref = '';
    foreach ($words as $w) { $pref .= substr($w, 0, 2); }
    $candidates[] = substr($pref, 0, 6);
}

// 4. Primera palabra completa (si es corta)
if (strlen($words[0]) <= 5) {
    $candidates[] = $words[0];
}

$finalPrefix = '';
foreach ($candidates as $c) {
    if (strlen($c) < 2) continue;
    $exists = db()->fetchOne("SELECT id FROM categorias WHERE prefijo = ? AND activo = 1", [$c]);
    if (!$exists) {
        $finalPrefix = $c;
        break;
    }
}

// 5. Fallback con números
if (!$finalPrefix) {
    $base = substr($words[0], 0, 3);
    for ($i = 1; $i < 1000; $i++) {
        $candidate = $base . str_pad($i, 2, '0', STR_PAD_LEFT);
        $exists = db()->fetchOne("SELECT id FROM categorias WHERE prefijo = ? AND activo = 1", [$candidate]);
        if (!$exists) {
            $finalPrefix = $candidate;
            break;
        }
    }
}

echo json_encode(['prefix' => $finalPrefix]);
