<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();
requireRole('admin', 'vendedor');

$activePage = 'ventas';
$pageTitle  = 'Nueva Venta';
$errors = [];

// Generate next Code
function nextVentaCode(): string {
    $last = db()->fetchOne("SELECT codigo FROM ventas ORDER BY id DESC LIMIT 1");
    $n = $last ? (int)substr($last['codigo'], 2) + 1 : 1;
    return 'V-' . str_pad($n, 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $clienteId   = (int)($_POST['cliente_id'] ?? 0) ?: null;
    $metodoPago  = $_POST['metodo_pago'] ?? 'efectivo'; // lower case for enum
    $items       = json_decode($_POST['items_json'], true);
    $fecha       = date('Y-m-d');
    
    if (empty($items)) { $errors[] = 'El carrito está vacío.'; }
    
    if (!$errors) {
        $pdo = db()->getConnection();
        try {
            $pdo->beginTransaction();
            
            $subtotal = 0;
            foreach ($items as $it) { $subtotal += $it['precio'] * $it['cantidad']; }
            $total = $subtotal; 
            
            $codigo = nextVentaCode();
            
            // 1. Insert Venta
            $stmt = $pdo->prepare("INSERT INTO ventas (codigo, cliente_id, usuario_id, subtotal, total, metodo_pago, estado, fecha) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$codigo, $clienteId, currentUser()['id'], $subtotal, $total, strtolower($metodoPago), 'completada', $fecha]);
            $ventaId = $pdo->lastInsertId();
            
            foreach ($items as $item) {
                $varId = (int)$item['variacion_id'];
                $cant  = (int)$item['cantidad'];
                $prec  = (float)$item['precio'];
                
                // 2. Insert Detalle (ventas_detalle)
                $stmtDet = $pdo->prepare("INSERT INTO ventas_detalle (venta_id, variacion_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
                $stmtDet->execute([$ventaId, $varId, $cant, $prec, $prec * $cant]);
                
                // 3. Update Stock
                $var = db()->fetchOne("SELECT v.*, p.nombre FROM variaciones v JOIN productos p ON p.id = v.producto_id WHERE v.id=?", [$varId]);
                $stockAntes = (int)$var['stock'];
                $stockDesp  = $stockAntes - $cant;
                
                $stmtStock = $pdo->prepare("UPDATE variaciones SET stock = stock - ? WHERE id = ?");
                $stmtStock->execute([$cant, $varId]);
                
                // 4. Kardex
                $stmtKardex = $pdo->prepare(
                    "INSERT INTO movimientos_inventario (tipo, variacion_id, producto_id, nombre_producto, talla, color, cantidad, stock_antes, stock_despues, motivo, usuario_id)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
                );
                $stmtKardex->execute([
                    'salida', $varId, $var['producto_id'], $var['nombre'], $var['talla'], $var['color'],
                    $cant, $stockAntes, $stockDesp, "Venta $codigo", currentUser()['id']
                ]);
            }
            
            $pdo->commit();
            $_SESSION['flash'] = ['type'=>'success', 'msg'=>"Venta $codigo registrada correctamente."];
            header("Location: index.php"); exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error al procesar la venta: " . $e->getMessage();
        }
    }
}

$clientes = db()->fetchAll('SELECT id, nombre, dni_ruc FROM clientes WHERE activo=1 ORDER BY nombre ASC');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?> — PalCus Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    * {font-family:'Inter',sans-serif;}
    .form-input {width:100%;padding:.5rem .75rem;border:1.5px solid #e5e7eb;border-radius:.625rem;font-size:.875rem;outline:none;}
    .form-input:focus{border-color:#111827;}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
<div id="app-wrapper" class="flex min-h-screen">
  <?php include $ADMIN . '/includes/sidebar.php'; ?>
  <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
    <?php include $ADMIN . '/includes/header.php'; ?>
    <main class="flex-1 p-6">

      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">Nueva Venta</h2>
        <a href="index.php" class="text-gray-500 hover:text-gray-800 text-sm font-medium">Volver al listado</a>
      </div>

      <?php if ($errors): ?><div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3"><?php foreach ($errors as $e): ?><p>• <?= e($e) ?></p><?php endforeach; ?></div><?php endif; ?>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <!-- Left: Search and Cart -->
        <div class="xl:col-span-2 space-y-6">
          
          <!-- Product Search -->
          <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Buscar Producto</label>
            <div class="relative">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <input type="text" id="prodSearch" placeholder="Nombre o SKU del producto..." class="w-full pl-9 pr-4 py-3 text-sm border border-gray-200 rounded-xl focus:border-gray-900 outline-none" autocomplete="off"/>
              
              <!-- Search Results Dropdown -->
              <div id="searchResults" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-10 max-h-64 overflow-y-auto"></div>
            </div>
          </div>

          <!-- Cart Table -->
          <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden min-h-[300px]">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 text-[10px] text-gray-500 font-bold uppercase">
                <tr>
                  <th class="px-5 py-3 text-left">Producto</th>
                  <th class="px-5 py-3 text-center">Cant.</th>
                  <th class="px-5 py-3 text-right">Precio</th>
                  <th class="px-5 py-3 text-right">Subtotal</th>
                  <th class="px-5 py-3"></th>
                </tr>
              </thead>
              <tbody id="cartTable" class="divide-y divide-gray-100">
                <!-- Items added dynamically -->
                <tr id="emptyRow"><td colspan="5" class="py-20 text-center text-gray-400">El carrito está vacío. Busca un producto para empezar.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right: Checkout Sidebar -->
        <div class="space-y-6">
          <form method="POST" id="ventaForm">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
            <input type="hidden" name="items_json" id="itemsJson"/>
            
            <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4">
              <h3 class="font-bold text-gray-900">Resumen de Venta</h3>
              
              <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Cliente</label>
                <select name="cliente_id" class="form-input text-sm">
                  <option value="">Cliente Final (Sin nombre)</option>
                  <?php foreach ($clientes as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?> (<?= e($c['dni_ruc']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Método de Pago</label>
                <select name="metodo_pago" class="form-input text-sm">
                  <option>Efectivo</option>
                  <option>Yape</option>
                  <option>Plin</option>
                  <option>Transferencia</option>
                  <option>Tarjeta</option>
                </select>
              </div>

              <hr class="border-gray-100"/>

              <div class="space-y-2">
                <div class="flex justify-between text-sm text-gray-500"><span>Subtotal</span><span id="txtSubtotal">S/ 0.00</span></div>
                <div class="flex justify-between text-lg font-bold text-gray-900"><span>Total</span><span id="txtTotal">S/ 0.00</span></div>
              </div>

              <button type="submit" id="btnFinalizar" class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Finalizar Venta
              </button>
            </div>
          </form>
        </div>

      </div>
    </main>
  </div>
</div>

<?php include $ADMIN . '/includes/foot.php'; ?>
<script>
let cart = [];
const searchInput = document.getElementById('prodSearch');
const resultsDiv  = document.getElementById('searchResults');
const cartTable   = document.getElementById('cartTable');
const emptyRow    = document.getElementById('emptyRow');
const itemsJson   = document.getElementById('itemsJson');
const btnFinalizar= document.getElementById('btnFinalizar');

// ── Search Logic ──────────────────────────────────────────────────
let searchTimeout;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    const q = searchInput.value.trim();
    if (q.length < 2) { resultsDiv.classList.add('hidden'); return; }
    
    searchTimeout = setTimeout(() => {
        fetch(`api_productos.php?q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="p-4 text-xs text-gray-400">No se encontraron productos.</div>';
                } else {
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-50';
                        div.innerHTML = `
                            <img src="${p.imagen_url || 'https://via.placeholder.com/40'}" class="w-10 h-10 rounded object-cover border border-gray-100">
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-900">${p.nombre}</p>
                                <p class="text-[10px] text-gray-500 uppercase">${p.talla} / ${p.color} — Stock: ${p.stock}</p>
                            </div>
                            <p class="text-sm font-bold text-gray-900">S/ ${parseFloat(p.precio_venta).toFixed(2)}</p>
                        `;
                        div.onclick = () => addToCart(p);
                        resultsDiv.appendChild(div);
                    });
                }
                resultsDiv.classList.remove('hidden');
            });
    }, 300);
});

// Close dropdown on click outside
document.addEventListener('click', (e) => {
    if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
        resultsDiv.classList.add('hidden');
    }
});

// ── Cart Logic ──────────────────────────────────────────────────
function addToCart(p) {
    const existing = cart.find(item => item.variacion_id === p.variacion_id);
    if (existing) {
        if (existing.cantidad < p.stock) {
            existing.cantidad++;
        } else {
            alert('No hay más stock disponible para este producto.');
        }
    } else {
        if (p.stock > 0) {
            cart.push({
                variacion_id: p.variacion_id,
                nombre: p.nombre,
                variacion: `${p.talla} / ${p.color}`,
                precio: parseFloat(p.precio_venta),
                cantidad: 1,
                maxStock: parseInt(p.stock)
            });
        } else {
            alert('Producto sin stock.');
        }
    }
    searchInput.value = '';
    resultsDiv.classList.add('hidden');
    renderCart();
}

function removeCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, delta) {
    const it = cart[index];
    const newQty = it.cantidad + delta;
    if (newQty > 0 && newQty <= it.maxStock) {
        it.cantidad = newQty;
        renderCart();
    }
}

function renderCart() {
    cartTable.innerHTML = '';
    if (cart.length === 0) {
        cartTable.appendChild(emptyRow);
        btnFinalizar.disabled = true;
    } else {
        let total = 0;
        cart.forEach((it, idx) => {
            const sub = it.precio * it.cantidad;
            total += sub;
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            tr.innerHTML = `
                <td class="px-5 py-3">
                    <p class="font-bold text-gray-900">${it.nombre}</p>
                    <p class="text-[10px] text-gray-500 uppercase">${it.variacion}</p>
                </td>
                <td class="px-5 py-3 text-center">
                    <div class="inline-flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" onclick="updateQty(${idx}, -1)" class="px-2 py-1 bg-gray-50 hover:bg-gray-100 border-r border-gray-200 text-gray-600">-</button>
                        <span class="px-3 py-1 font-bold text-gray-800">${it.cantidad}</span>
                        <button type="button" onclick="updateQty(${idx}, 1)" class="px-2 py-1 bg-gray-50 hover:bg-gray-100 border-l border-gray-200 text-gray-600">+</button>
                    </div>
                </td>
                <td class="px-5 py-3 text-right text-gray-600">S/ ${it.precio.toFixed(2)}</td>
                <td class="px-5 py-3 text-right font-bold text-gray-900">S/ ${sub.toFixed(2)}</td>
                <td class="px-5 py-3 text-right">
                    <button type="button" onclick="removeCart(${idx})" class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </td>
            `;
            cartTable.appendChild(tr);
        });
        
        const moneyTotal = `S/ ${total.toFixed(2)}`;
        document.getElementById('txtSubtotal').innerText = moneyTotal;
        document.getElementById('txtTotal').innerText = moneyTotal;
        itemsJson.value = JSON.stringify(cart);
        btnFinalizar.disabled = false;
    }
}
</script>
</body>
</html>
