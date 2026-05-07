<?php
require_once 'admin/config/config.php';
require_once 'admin/config/database.php';
try {
    $db = db();
    $res = $db->execute("INSERT INTO ventas (codigo, usuario_id, subtotal, total, metodo_pago, estado, fecha) VALUES (?,?,?,?,?,?,?)", 
        ['TEST-001', 1, 100, 100, 'efectivo', 'pendiente', date('Y-m-d')]);
    echo "Insert Result: " . $res;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
