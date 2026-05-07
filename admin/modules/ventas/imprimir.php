<?php
$ADMIN = dirname(dirname(__DIR__));
require_once $ADMIN . '/config/config.php';
require_once $ADMIN . '/config/database.php';
require_once $ADMIN . '/includes/auth.php';
require_once $ADMIN . '/includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$v  = db()->fetchOne(
    "SELECT v.*, c.nombre AS cliente_nombre, c.dni_ruc, c.direccion, c.telefono, u.nombre AS vendedor_nombre
     FROM ventas v
     LEFT JOIN clientes c ON c.id = v.cliente_id
     LEFT JOIN usuarios u ON u.id = v.usuario_id
     WHERE v.id = ?",
    [$id]
);
if (!$v) { echo "Venta no encontrada."; exit; }

$detalles = db()->fetchAll(
    "SELECT d.*, p.nombre AS prod_nombre, va.talla, va.color
     FROM detalles_venta d
     JOIN variaciones va ON va.id = d.variacion_id
     JOIN productos p ON p.id = va.producto_id
     WHERE d.venta_id = ?",
    [$id]
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <title>Ticket_<?= $v['codigo_venta'] ?></title>
  <style>
    body { font-family: 'Courier New', Courier, monospace; font-size: 12px; margin: 0; padding: 20px; color: #000; width: 300px; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .divider { border-top: 1px dashed #000; margin: 10px 0; }
    .logo { width: 60px; margin-bottom: 5px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; border-bottom: 1px solid #000; }
    .total { font-size: 14px; margin-top: 10px; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body onload="window.print()">
  
  <div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()">Imprimir</button>
    <button onclick="window.close()">Cerrar</button>
  </div>

  <div class="text-center">
    <img src="https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png" class="logo" alt="Logo"/>
    <div class="bold" style="font-size: 16px;">PALCUS PERÚ</div>
    <div>RUC: 20123456789</div>
    <div>Av. Gamarra 123 - La Victoria</div>
    <div>Lima, Perú</div>
  </div>

  <div class="divider"></div>

  <div>
    <div class="bold">COMPROBANTE: <?= $v['codigo_venta'] ?></div>
    <div>Fecha: <?= date('d/m/Y H:i', strtotime($v['fecha'])) ?></div>
    <div>Vendedor: <?= e($v['vendedor_nombre'] ?: 'Sistema') ?></div>
  </div>

  <div class="divider"></div>

  <div>
    <div class="bold">CLIENTE:</div>
    <div><?= e($v['cliente_nombre'] ?: 'Cliente Final') ?></div>
    <?php if ($v['dni_ruc']): ?><div>DNI/RUC: <?= e($v['dni_ruc']) ?></div><?php endif; ?>
  </div>

  <div class="divider"></div>

  <table>
    <thead>
      <tr>
        <th>CANT</th>
        <th>DESC</th>
        <th class="text-right">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($detalles as $d): ?>
      <tr>
        <td valign="top"><?= $d['cantidad'] ?></td>
        <td>
          <?= e($d['prod_nombre']) ?><br/>
          <small><?= e($d['talla']) ?> / <?= e($d['color']) ?></small>
        </td>
        <td class="text-right" valign="top"><?= number_format($d['subtotal'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="divider"></div>

  <div class="text-right total">
    <span class="bold">TOTAL: S/ <?= number_format($v['total'], 2) ?></span>
  </div>

  <div class="divider"></div>

  <div class="text-center">
    <p>¡Gracias por su compra!</p>
    <p>Visítanos en palcus.pe</p>
  </div>

</body>
</html>
