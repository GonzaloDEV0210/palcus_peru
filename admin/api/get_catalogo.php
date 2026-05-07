<?php
// API para obtener el catálogo completo desde MySQL
$ADMIN = dirname(__DIR__);
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

try {
    // 1. Categorías
    $categorias = db()->fetchAll("SELECT id, nombre, prefijo FROM categorias WHERE activo = 1");
    $catMap = [];
    foreach ($categorias as $c) {
        $slug = strtolower(str_replace(' ', '-', $c['nombre']));
        $catMap[$c['id']] = $slug;
        $categoryLabels[$slug] = $c['nombre'];
    }

    // 2. Productos y sus variaciones
    $productosRaw = db()->fetchAll("
        SELECT p.*, c.nombre as category_name 
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE p.activo = 1
    ");

    $productos = [];
    foreach ($productosRaw as $p) {
        // Obtener variaciones
        $vars = db()->fetchAll("SELECT talla, color, stock FROM variaciones WHERE producto_id = ? AND activo = 1", [$p['id']]);
        
        $sizes = array_unique(array_column($vars, 'talla'));
        $colorsRaw = array_unique(array_column($vars, 'color'));
        
        $colors = [];
        $colorHexMap = [
            'Blanco' => '#ffffff', 'Negro' => '#000000', 'Azul' => '#1e3a8a', 
            'Marrón' => '#78350f', 'Mostaza' => '#eab308', 'Gris' => '#6b7280',
            'Rojo' => '#ef4444', 'Verde' => '#10b981'
        ];

        foreach ($colorsRaw as $cname) {
            $colors[] = ['name' => $cname, 'hex' => $colorHexMap[$cname] ?? '#cccccc'];
        }

        $slug = strtolower(str_replace(' ', '-', $p['category_name'] ?? 'general'));

        $productos[] = [
            'id' => $p['id'],
            'name' => $p['nombre'],
            'price' => (float)$p['precio_venta'],
            'description' => $p['descripcion'],
            'image' => $p['imagen_url'],
            'category' => $slug,
            'sizes' => array_values($sizes),
            'colors' => $colors,
            'stock' => (int)db()->fetchOne("SELECT SUM(stock) as total FROM variaciones WHERE producto_id = ?", [$p['id']])['total'],
            'createdAt' => $p['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'products' => $productos,
        'categories' => $categoryLabels ?? []
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
