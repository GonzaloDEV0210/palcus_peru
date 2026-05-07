<?php
// Endpoint para registrar pedidos desde el carrito de la web (WhatsApp)
$ADMIN = dirname(__DIR__);
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/functions.php';

header('Content-Type: application/json');
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] Request: " . $_SERVER['REQUEST_METHOD'] . " from " . $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);

// Permitir peticiones desde el mismo dominio o configurar CORS si es necesario
file_put_contents($ADMIN . '/api_log.txt', "[" . date('Y-m-d H:i:s') . "] Call: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos', 'raw' => $rawInput]);
        exit;
    }
    error_log("API Input: " . $rawInput);

    $items   = $input['items']   ?? [];
    $nombre  = trim($input['nombre']  ?? 'Cliente WhatsApp');
    $telefono= trim($input['telefono']?? '');
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'El pedido está vacío']);
        exit;
    }

    $pdo = db()->getConnection();
    try {
        $pdo->beginTransaction();

        // 1. Buscar o Crear Cliente (opcional, basado en nombre/teléfono)
        $clienteId = null;
        if ($nombre) {
            $stmtC = $pdo->prepare("INSERT INTO clientes (nombre, telefono) VALUES (?, ?) ON DUPLICATE KEY UPDATE telefono = VALUES(telefono)");
            $stmtC->execute([$nombre, $telefono]);
            $clienteId = $pdo->lastInsertId();
            if (!$clienteId) {
                $c = db()->fetchOne("SELECT id FROM clientes WHERE nombre = ?", [$nombre]);
                $clienteId = $c['id'] ?? null;
            }
        }

        // 2. Calcular totales
        $total = 0;
        foreach ($items as $it) { $total += $it['precio'] * $it['cantidad']; }

        // 3. Generar código
        function genCode($pdo) {
            $last = $pdo->query("SELECT codigo FROM ventas ORDER BY id DESC LIMIT 1")->fetch();
            $n = $last ? (int)substr($last['codigo'], 2) + 1 : 1;
            return 'V-' . str_pad($n, 6, '0', STR_PAD_LEFT);
        }
        $codigo = genCode($pdo);

        // 4. Insertar Venta Pendiente (Sin usuario_id fijo, o usar uno de sistema)
        // Buscamos el ID del admin principal para asignar el pedido
        $admin = db()->fetchOne("SELECT id FROM usuarios WHERE rol='admin' LIMIT 1");
        $usuarioId = $admin['id'] ?? 1;

        $stmtV = $pdo->prepare("INSERT INTO ventas (codigo, cliente_id, usuario_id, subtotal, total, metodo_pago, estado, fecha) VALUES (?,?,?,?,?,?,?,?)");
        $stmtV->execute([$codigo, $clienteId, $usuarioId, $total, $total, 'whatsapp', 'pendiente', date('Y-m-d')]);
        $ventaId = $pdo->lastInsertId();

        // 5. Insertar Detalles
        foreach ($items as $it) {
            $varId = $it['variacion_id'] ?? null;
            
            // Fallback: Buscar por nombre y variaciones si no hay ID
            if (!$varId && isset($it['nombre'])) {
                $found = db()->fetchOne(
                    "SELECT v.id FROM variaciones v 
                     JOIN productos p ON p.id = v.producto_id 
                     WHERE p.nombre = ? AND v.talla = ? AND v.color = ? LIMIT 1",
                    [$it['nombre'], $it['size'] ?? '', $it['color'] ?? '']
                );
                $varId = $found['id'] ?? null;
            }

            $stmtD = $pdo->prepare("INSERT INTO ventas_detalle (venta_id, variacion_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
            $stmtD->execute([$ventaId, $varId, $it['cantidad'], $it['precio'], $it['precio'] * $it['cantidad']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'codigo' => $codigo]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Método no permitido']);
