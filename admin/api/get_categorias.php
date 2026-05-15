<?php
// ================================================================
// admin/api/get_categorias.php
// API pública para obtener el árbol completo de categorías
// Usada tanto por el panel admin como por el frontend (layout.js)
// ================================================================
$ADMIN = dirname(__DIR__);
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store'); // siempre fresco

// Traer todas las categorías activas, ordenadas
$categorias = db()->fetchAll(
    "SELECT id, nombre, slug, prefijo, descripcion, parent_id, orden, activo
     FROM categorias
     ORDER BY orden ASC, nombre ASC"
);

// Construir el árbol recursivamente
function buildSlugPath(array &$map, int $id, string $sep = '/'): string
{
    $cat = $map[$id] ?? null;
    if (!$cat) return '';
    $slug = $cat['slug'] ?: strtolower(preg_replace('/\s+/', '-', $cat['nombre']));
    if ($cat['parent_id']) {
        $parentPath = buildSlugPath($map, $cat['parent_id'], $sep);
        return $parentPath ? "{$parentPath}{$sep}{$slug}" : $slug;
    }
    return $slug;
}

// Indexar por id
$map = [];
foreach ($categorias as $c) {
    $map[$c['id']] = $c;
}

// Agregar slug_path completo a cada categoría
$result = [];
foreach ($categorias as $c) {
    $result[] = [
        'id'          => (int) $c['id'],
        'nombre'      => $c['nombre'],
        'slug'        => $c['slug'],
        'slug_path'   => buildSlugPath($map, $c['id']), // ej: "mujer/polos/manga-corta"
        'prefijo'     => $c['prefijo'],
        'descripcion' => $c['descripcion'],
        'parent_id'   => $c['parent_id'] ? (int) $c['parent_id'] : null,
        'orden'       => (int) $c['orden'],
        'activo'      => (bool) $c['activo'],
    ];
}

echo json_encode(['success' => true, 'categorias' => $result], JSON_UNESCAPED_UNICODE);
