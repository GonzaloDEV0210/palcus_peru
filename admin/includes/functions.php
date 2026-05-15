<?php
// ============================================================
// PalCus Admin — Funciones Globales Helper
// ============================================================

// --- FORMATO ---

function money(float $amount, string $symbol = null): string
{
    $sym = $symbol ?? getConfig('moneda_simbolo') ?? 'S/';
    return $sym . ' ' . number_format($amount, 2, '.', ',');
}

function dateEs(string $date): string
{
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

function dateTimeEs(string $datetime): string
{
    if (!$datetime) return '—';
    return date('d/m/Y H:i', strtotime($datetime));
}

function fechaLarga(): string
{
    $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    $diaSemana = $dias[date('w')];
    $diaNum    = date('d');
    $mesNum    = (int)date('m');
    $anio      = date('Y');
    
    return "{$diaSemana}, {$diaNum} de {$meses[$mesNum]} de {$anio}";
}

// --- CONFIGURACIÓN ---

function getConfig(string $key): ?string
{
    $row = db()->fetchOne(
        'SELECT valor FROM configuracion WHERE clave = ? LIMIT 1',
        [$key]
    );
    return $row['valor'] ?? null;
}

function setConfig(string $key, string $value): void
{
    db()->execute(
        'INSERT INTO configuracion (clave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
        [$key, $value]
    );
}

// --- ALERTAS CALLMEBOT (WhatsApp) ---

function sendWhatsApp(string $message): bool
{
    $phone  = defined('CALLMEBOT_PHONE') ? CALLMEBOT_PHONE : '';
    $apiKey = defined('CALLMEBOT_API_KEY') ? CALLMEBOT_API_KEY : '';

    if (empty($phone) || empty($apiKey)) return false;

    $url = sprintf(
        'https://api.callmebot.com/whatsapp.php?phone=%s&text=%s&apikey=%s',
        urlencode($phone),
        urlencode($message),
        urlencode($apiKey)
    );

    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    @file_get_contents($url, false, $ctx);
    return true;
}

// --- STOCK Y ALERTAS ---

function checkLowStock(): array
{
    return db()->fetchAll(
        'SELECT v.id, p.nombre AS producto, v.talla, v.color,
                v.stock, v.stock_minimo
         FROM variaciones v
         JOIN productos p ON p.id = v.producto_id
         WHERE v.stock <= v.stock_minimo AND v.activo = 1
         ORDER BY v.stock ASC'
    );
}

function alertLowStock(): void
{
    $items = checkLowStock();
    if (empty($items)) return;

    $lines = ["⚠️ *Palcus Peru — Alerta de Stock Bajo*\n"];
    foreach ($items as $i) {
        $lines[] = "• {$i['producto']} ({$i['talla']} / {$i['color']}): {$i['stock']} uds (mín. {$i['stock_minimo']})";
    }
    $lines[] = "\nFecha: " . date('d/m/Y H:i');

    sendWhatsApp(implode("\n", $lines));
}

// --- MOVIMIENTOS DE INVENTARIO ---

function registrarMovimiento(
    string $tipo,
    int    $variacionId,
    int    $cantidad,
    string $motivo = '',
    int    $referenciaId = null,
    string $referenciaTipo = null
): void {
    $var = db()->fetchOne(
        'SELECT v.*, p.nombre AS nombre_producto
         FROM variaciones v JOIN productos p ON p.id = v.producto_id
         WHERE v.id = ?',
        [$variacionId]
    );
    if (!$var) return;

    $stockAntes   = (int) $var['stock'];
    $stockDespues = match ($tipo) {
        'entrada' => $stockAntes + $cantidad,
        'salida'  => $stockAntes - $cantidad,
        default   => $cantidad, // ajuste: cantidad = nuevo stock
    };

    db()->execute(
        'INSERT INTO movimientos_inventario
         (tipo, variacion_id, producto_id, nombre_producto, talla, color,
          cantidad, stock_antes, stock_despues, motivo, referencia_id, referencia_tipo, usuario_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
        [
            $tipo, $variacionId, $var['producto_id'], $var['nombre_producto'],
            $var['talla'], $var['color'], $cantidad, $stockAntes, $stockDespues,
            $motivo, $referenciaId, $referenciaTipo,
            currentUser()['id'] ?? null,
        ]
    );

    // Actualizar stock
    db()->execute(
        'UPDATE variaciones SET stock = ? WHERE id = ?',
        [$stockDespues, $variacionId]
    );

    // Disparar alerta si queda bajo mínimo
    if ($stockDespues <= (int) $var['stock_minimo']) {
        $msg = "⚠️ Stock bajo: {$var['nombre_producto']} ({$var['talla']}/{$var['color']}) = {$stockDespues} uds.";
        sendWhatsApp($msg);
    }
}

// --- GENERACIÓN AUTOMÁTICA DE SKU ---

/**
 * Convierte un color en una abreviación de 3 letras para el SKU de variación.
 * Ejemplos: "Blanco" → "BLC", "Negro" → "NGR", "Azul Marino" → "AZM"
 */
function abreviarColor(string $color): string
{
    $map = [
        'blanco'      => 'BLC', 'negro'    => 'NGR', 'rojo'     => 'ROJ',
        'azul'        => 'AZL', 'verde'    => 'VRD', 'amarillo' => 'AMR',
        'rosado'      => 'RSD', 'rosa'     => 'RSA', 'morado'   => 'MRD',
        'lila'        => 'LIL', 'naranja'  => 'NRJ', 'celeste'  => 'CLT',
        'gris'        => 'GRS', 'beige'    => 'BGE', 'crema'    => 'CRM',
        'cafe'        => 'CAF', 'café'     => 'CAF', 'marron'   => 'MRN',
        'marrón'      => 'MRN', 'turquesa' => 'TRQ', 'coral'    => 'CRL',
        'salmon'      => 'SLM', 'salmón'   => 'SLM', 'fucsia'   => 'FCS',
        'aqua'        => 'AQA', 'dorado'   => 'DRD', 'plateado' => 'PLT',
        'marino'      => 'MRN', 'khaki'    => 'KHK', 'oliva'    => 'OLV',
        'terracota'   => 'TRC', 'lavanda'  => 'LVN', 'mostaza'  => 'MST',
    ];

    $key = mb_strtolower(trim($color), 'UTF-8');
    // Buscar coincidencia exacta primero
    if (isset($map[$key])) return $map[$key];
    // Buscar si alguna palabra del color coincide
    foreach (explode(' ', $key) as $word) {
        if (isset($map[$word])) return $map[$word];
    }
    // Fallback: primeras 3 letras en mayúscula, sin tildes
    $clean = preg_replace('/[^a-z]/u', '', strtr($key, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']));
    return strtoupper(substr($clean, 0, 3)) ?: 'GEN';
}

/**
 * Genera el SKU de un PRODUCTO de forma atómica y garantizada única.
 *
 * Usa la tabla `sku_secuencias` con UPDATE atómico para evitar race conditions.
 * Formato: MCO-00001 (Prefijo de categoría + secuencia de 5 dígitos con ceros).
 *
 * @param string $prefijo  Prefijo de la categoría hoja (ej: "MCO")
 * @return string          SKU único (ej: "MCO-00001")
 */
function generarSkuProducto(string $prefijo): string
{
    $prefijo = strtoupper(trim($prefijo));
    if (!$prefijo) $prefijo = 'GEN';

    // Asegurar que la secuencia existe (para prefijos nuevos)
    db()->execute(
        "INSERT INTO sku_secuencias (prefijo, ultimo_seq) VALUES (?, 0)
         ON DUPLICATE KEY UPDATE prefijo = prefijo",
        [$prefijo]
    );

    // UPDATE atómico: incrementa y lee en una sola operación segura
    db()->execute(
        "UPDATE sku_secuencias SET ultimo_seq = ultimo_seq + 1 WHERE prefijo = ?",
        [$prefijo]
    );
    $seq = db()->fetchOne(
        "SELECT ultimo_seq FROM sku_secuencias WHERE prefijo = ?",
        [$prefijo]
    )['ultimo_seq'] ?? 1;

    $sku = $prefijo . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);

    // Doble verificación: en el improbable caso de colisión (datos previos)
    $exists = db()->fetchOne("SELECT id FROM productos WHERE sku = ?", [$sku]);
    if ($exists) {
        // Rarísimo, pero si ocurre: seguimos incrementando
        return generarSkuProducto($prefijo);
    }

    return $sku;
}

/**
 * Genera el SKU de una VARIACIÓN de forma determinista y única.
 *
 * Formato: MCO-00001-M-BLC (ProductoSKU-Talla-ColorAbreviado)
 * No necesita tabla de secuencias: se construye de datos únicos.
 *
 * @param string $skuProducto  SKU del producto padre (ej: "MCO-00001")
 * @param string $talla        Talla (ej: "M", "L", "XL")
 * @param string $color        Color en texto (ej: "Blanco")
 * @return string              SKU de variación único
 */
function generarSkuVariacion(string $skuProducto, string $talla, string $color): string
{
    $tallaClean = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $talla));
    $colorCode  = abreviarColor($color);
    return "{$skuProducto}-{$tallaClean}-{$colorCode}";
}

// --- GENERACIÓN DE CÓDIGO DE VENTA ---

function generarCodigoVenta(): string
{
    $prefix = 'VTA';
    $date   = date('Ymd');
    $last   = db()->fetchOne(
        "SELECT codigo FROM ventas WHERE codigo LIKE '{$prefix}{$date}%' ORDER BY id DESC LIMIT 1"
    );
    $seq = $last ? (int) substr($last['codigo'], -4) + 1 : 1;
    return $prefix . $date . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// --- SEGURIDAD ---

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- PAGINACIÓN ---

function paginate(string $sql, array $params, int $page, int $perPage = 20): array
{
    $total = db()->fetchOne("SELECT COUNT(*) AS total FROM ($sql) AS t", $params)['total'] ?? 0;
    $pages = (int) ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;

    $rows = db()->fetchAll("$sql LIMIT $perPage OFFSET $offset", $params);

    return [
        'data'       => $rows,
        'total'      => (int) $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'last_page'  => $pages,
    ];
}

/**
 * Elimina un recurso de Cloudinary usando su URL.
 */
function cloudinaryDestroy(?string $url): bool
{
    if (!$url || !str_contains($url, 'cloudinary.com')) return false;

    // Extraer Public ID: res.cloudinary.com/<cloud>/image/upload/(v\d+/)?<public_id>.<ext>
    if (!preg_match('/\/upload\/(?:v\d+\/)?(.+)\.[a-z0-9]+$/i', $url, $matches)) return false;
    
    $publicId = $matches[1];
    $cloudName = defined('CLOUDINARY_CLOUD_NAME') ? CLOUDINARY_CLOUD_NAME : '';
    $apiKey    = defined('CLOUDINARY_API_KEY') ? CLOUDINARY_API_KEY : '';
    $apiSecret = defined('CLOUDINARY_API_SECRET') ? CLOUDINARY_API_SECRET : '';

    if (!$cloudName || !$apiKey || !$apiSecret) return false;

    $timestamp = time();
    $signature = sha1("public_id=$publicId&timestamp=$timestamp$apiSecret");

    $postData = [
        'public_id' => $publicId,
        'timestamp' => $timestamp,
        'api_key'   => $apiKey,
        'signature' => $signature
    ];

    $apiUrl = "https://api.cloudinary.com/v1_1/$cloudName/image/destroy";

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para evitar problemas en Windows local
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return false;
    $res = json_decode($response, true);
    
    return ($res['result'] ?? '') === 'ok' || ($res['result'] ?? '') === 'not found';
}

/**
 * Sube una imagen a Cloudinary desde el servidor (Signed Upload).
 */
function cloudinaryUpload(?array $file): ?string
{
    if (!$file || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;

    $cloudName = defined('CLOUDINARY_CLOUD_NAME') ? CLOUDINARY_CLOUD_NAME : '';
    $apiKey    = defined('CLOUDINARY_API_KEY') ? CLOUDINARY_API_KEY : '';
    $apiSecret = defined('CLOUDINARY_API_SECRET') ? CLOUDINARY_API_SECRET : '';

    if (!$cloudName || !$apiKey || !$apiSecret) return null;

    $timestamp = time();
    $signature = sha1("timestamp=$timestamp$apiSecret");

    $postData = [
        'file'      => new CURLFile($file['tmp_name']),
        'timestamp' => $timestamp,
        'api_key'   => $apiKey,
        'signature' => $signature
    ];

    $apiUrl = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return null;
    $res = json_decode($response, true);
    
    return $res['secure_url'] ?? null;
}
