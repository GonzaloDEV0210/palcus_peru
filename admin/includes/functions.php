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

    $lines = ["⚠️ *PalCus Admin — Alerta de Stock Bajo*\n"];
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

function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
