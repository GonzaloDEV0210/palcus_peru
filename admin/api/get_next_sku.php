<?php
// ================================================================
// admin/api/get_next_sku.php
// Devuelve el PRÓXIMO SKU disponible para un prefijo dado.
// NO incrementa la secuencia — solo la previsualiza.
// La generación real ocurre al guardar el producto.
// ================================================================
$ADMIN = dirname(__DIR__);
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/functions.php';
require_once $ADMIN . '/includes/auth.php';

header('Content-Type: application/json; charset=UTF-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$prefijo = strtoupper(trim($_GET['prefijo'] ?? ''));
if (!$prefijo) {
    echo json_encode(['success' => false, 'error' => 'Prefijo requerido']);
    exit;
}

// Solo previsualizar: leer el último seq SIN incrementar
$row = db()->fetchOne(
    "SELECT ultimo_seq FROM sku_secuencias WHERE prefijo = ?",
    [$prefijo]
);
$nextSeq = ($row['ultimo_seq'] ?? 0) + 1;
$nextSku = $prefijo . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

echo json_encode([
    'success'  => true,
    'prefijo'  => $prefijo,
    'next_sku' => $nextSku,
    'next_seq' => $nextSeq,
]);
