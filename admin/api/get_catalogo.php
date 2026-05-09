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
        $vars = db()->fetchAll("SELECT talla, color, color_hex, diseno, imagen_url, stock FROM variaciones WHERE producto_id = ? AND activo = 1", [$p['id']]);
        
        $sizes = array_unique(array_column($vars, 'talla'));
        $designs = array_unique(array_column($vars, 'diseno'));
        
        // Agrupar colores únicos con su respectivo HEX desde la DB
        $colors = [];
        $colorData = [];
        $imageMap = [];
        foreach ($vars as $v) {
            $cname = $v['color'];
            if (!isset($colorData[$cname])) {
                $colorData[$cname] = $v['color_hex'] ?: '#cccccc';
            }
            // Construir mapa de imágenes por color y diseño
            if ($v['imagen_url']) {
                $imageMap[$cname][$v['diseno']] = $v['imagen_url'];
            }
        }
        foreach ($colorData as $name => $hex) {
            $colors[] = ['name' => $name, 'hex' => $hex];
        }

        $slug = strtolower(str_replace(' ', '-', $p['category_name'] ?? 'general'));
        $gallery = db()->fetchAll("SELECT imagen_url FROM producto_imagenes WHERE producto_id = ? ORDER BY orden ASC", [$p['id']]);

        $productos[] = [
            'id' => $p['id'],
            'name' => $p['nombre'],
            'price' => (float)$p['precio_venta'],
            'description' => $p['descripcion'],
            'image' => $p['imagen_url'],
            'gallery' => array_column($gallery, 'imagen_url'),
            'features' => $p['caracteristicas'],
            'modelInfo' => $p['info_modelo'],
            'category' => $slug,
            'sizes' => array_values($sizes),
            'colors' => $colors,
            'designs' => array_values($designs),
            'imageMap' => $imageMap,
            'variations' => $vars,
            'stock' => (int)db()->fetchOne("SELECT SUM(stock) as total FROM variaciones WHERE producto_id = ? AND activo = 1", [$p['id']])['total'],
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
