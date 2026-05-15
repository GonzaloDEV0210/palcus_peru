<?php
/**
 * API: Obtener catálogo completo para el cliente
 * Retorna productos con sus variaciones, categorías y branding.
 */
header('Content-Type: application/json');
$ADMIN = dirname(__DIR__); // admin
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/functions.php';

try {
    // 1. Categorías activas
    $categoriasRaw = db()->fetchAll("SELECT id, nombre, slug, prefijo, parent_id FROM categorias WHERE activo = 1 ORDER BY orden ASC, nombre ASC");
    $catById = [];
    foreach ($categoriasRaw as $c) $catById[$c['id']] = $c;

    function buildSlugPath($all, $id) {
        $path = [];
        $curr = $id;
        while ($curr && isset($all[$curr])) {
            array_unshift($path, $all[$curr]['slug']);
            $curr = $all[$curr]['parent_id'];
        }
        return implode('/', $path);
    }

    $categoryLabels = [];
    $categorySlugPaths = [];
    foreach ($categoriasRaw as $c) {
        $path = buildSlugPath($catById, $c['id']);
        $categoryLabels[$path] = $c['nombre'];
        $categorySlugPaths[$c['id']] = $path;
    }

    // Lista plana enriquecida para el frontend
    $categoriasList = [];
    foreach ($categoriasRaw as $c) {
        $categoriasList[] = [
            'id'        => (int)$c['id'],
            'name'      => $c['nombre'],
            'slug'      => $c['slug'],
            'slug_path' => buildSlugPath($catById, $c['id']),
            'parent_id' => $c['parent_id'] ? (int)$c['parent_id'] : null,
        ];
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
        
        foreach ($vars as $index => $v) {
            $cname = $v['color'];
            if (!isset($colorData[$cname])) {
                $colorData[$cname] = $v['color_hex'] ?: '#cccccc';
            }

            // Procesar imagen_url (puede ser JSON o string simple)
            $v_images = [];
            if (!empty($v['imagen_url'])) {
                if (str_starts_with($v['imagen_url'], '[')) {
                    $v_images = json_decode($v['imagen_url'], true) ?: [];
                } else {
                    $v_images = [$v['imagen_url']];
                }
            }
            $primary_v_img = $v_images[0] ?? null;

            // Construir mapa de imágenes por color y diseño
            if ($primary_v_img) {
                $imageMap[$cname][$v['diseno']] = $primary_v_img;
            }
            
            // Enriquecer la variación para el frontend
            $vars[$index]['image'] = $primary_v_img;
            $vars[$index]['gallery'] = $v_images;
        }

        foreach ($colorData as $name => $hex) {
            $colors[] = ['name' => $name, 'hex' => $hex];
        }

        $fullCategoryPath = $categorySlugPaths[$p['categoria_id']] ?? 'general';
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
            'category' => $fullCategoryPath,
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
        'success'         => true,
        'products'        => $productos,
        'categories'      => $categoryLabels ?? [],
        'categories_list' => $categoriasList ?? [],
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
