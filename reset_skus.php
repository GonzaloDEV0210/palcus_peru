<?php
$ADMIN = __DIR__ . '/admin';
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';

$pdo = db()->getConnection();

try {
    $pdo->beginTransaction();

    // 1. Limpiar duplicados de categorías (si hay)
    // Mantener solo los IDs 1, 2, 3 que son los que están en uso
    $pdo->exec("DELETE FROM categorias WHERE id > 3");

    // 2. Eliminar productos de prueba antiguos
    $pdo->exec("DELETE FROM productos WHERE id IN (1, 2)");
    // También borrar sus variaciones si existen
    $pdo->exec("DELETE FROM variaciones WHERE producto_id IN (1, 2)");

    // 3. Re-categorizar y re-generar SKUs secuenciales
    $categorias = db()->fetchAll("SELECT id, prefijo FROM categorias WHERE prefijo IS NOT NULL");
    
    foreach ($categorias as $cat) {
        $productos = db()->fetchAll("SELECT id FROM productos WHERE categoria_id = ? ORDER BY id ASC", [$cat['id']]);
        $i = 1;
        foreach ($productos as $p) {
            $newSku = $cat['prefijo'] . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $pdo->prepare("UPDATE productos SET sku = ? WHERE id = ?")->execute([$newSku, $p['id']]);
            $i++;
        }
    }

    $pdo->commit();
    echo "Categorización y SKUs actualizados correctamente. Códigos secuenciales aplicados.";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
