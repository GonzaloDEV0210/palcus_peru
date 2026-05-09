<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/functions.php';

// Ensure DB connection via helper function db()
$db = db();

// 1. Populate missing prefijo values with generated code (CAT####)
$missing = $db->fetchAll("SELECT id FROM categorias WHERE (prefijo IS NULL OR prefijo = '') AND activo = 1");
foreach ($missing as $row) {
    $id = $row['id'];
    $code = 'CAT' . str_pad($id, 4, '0', STR_PAD_LEFT);
    $db->execute("UPDATE categorias SET prefijo = ? WHERE id = ?", [$code, $id]);
}

// 2. Enforce NOT NULL on prefijo
$db->execute("ALTER TABLE categorias MODIFY prefijo VARCHAR(20) NOT NULL");

// 3. Add UNIQUE constraint (if not exists)
$db->execute("ALTER TABLE categorias ADD CONSTRAINT uq_categorias_prefijo UNIQUE (prefijo)");

echo "Migration completed.\n";
?>
