<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    if (!$nombre) {
        echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
        exit;
    }
    
    try {
        $prefijo = trim($_POST['prefijo'] ?? '');
        db()->execute('INSERT INTO categorias (nombre, prefijo, activo) VALUES (?, ?, 1)', [$nombre, $prefijo]);
        $id = db()->lastInsertId();
        echo json_encode(['success' => true, 'id' => $id, 'nombre' => $nombre]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error al crear la categoría']);
    }
    exit;
}
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
