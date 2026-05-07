// Carrito + código de compra (localStorage)
(function () {
  const KEY = 'palcus_cart_v1';
  const CODE_KEY = 'palcus_order_code_v1';
  const PHONE = '51981293422';

  function read() { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch { return []; } }
  function write(items) { localStorage.setItem(KEY, JSON.stringify(items)); update(); }

  function generateOrderCode() {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let p1 = '';
    for (let i = 0; i < 6; i++) p1 += alphabet[Math.floor(Math.random() * alphabet.length)];
    const p2 = Math.floor(1000 + Math.random() * 9000).toString();
    return `PC-${p1}-${p2}`;
  }

  function getOrderCode() {
    let c = localStorage.getItem(CODE_KEY);
    if (!c) { c = generateOrderCode(); localStorage.setItem(CODE_KEY, c); }
    return c;
  }
  function refreshOrderCode() {
    const c = generateOrderCode();
    localStorage.setItem(CODE_KEY, c);
    return c;
  }

  function totalItems() { return read().reduce((s, i) => s + i.quantity, 0); }
  function totalPrice() { return read().reduce((s, i) => s + i.price * i.quantity, 0); }

  function add(product, size, color, design, quantity = 1) {
    const items = read();
    const idx = items.findIndex(i => i.id === product.id && i.size === size && i.color === color && i.design === design);
    if (idx >= 0) items[idx].quantity += quantity;
    else items.push({ id: product.id, name: product.name, price: product.price, image: product.image, size, color, design, quantity });
    write(items);
    refreshOrderCode();
  }
  function remove(id, size, color, design) {
    write(read().filter(i => !(i.id === id && i.size === size && i.color === color && i.design === design)));
    refreshOrderCode();
  }
  function setQty(id, size, color, design, qty) {
    if (qty <= 0) return remove(id, size, color, design);
    const items = read().map(i =>
      (i.id === id && i.size === size && i.color === color && i.design === design) ? { ...i, quantity: qty } : i
    );
    write(items);
    refreshOrderCode();
  }
  function clear() { write([]); refreshOrderCode(); }

  function whatsappUrl() {
    const items = read();
    const code = getOrderCode();
    const domain = window.location.origin;
    const lines = items.map(i => {
      let imgUrl = '';
      if (window.PalcusUtil && window.PalcusUtil.imageUrl) {
        imgUrl = window.PalcusUtil.imageUrl(i.image);
      } else {
        imgUrl = (i.image && typeof i.image === 'string' && i.image.startsWith('http')) ? i.image : `${domain}/assets/${i.image || 'placeholder'}.jpg`;
      }
      return `• ${i.name} (Talla: ${i.size}, Color: ${i.color}, Diseño: ${i.design || 'Sin diseño'}) x${i.quantity} - S/${(i.price * i.quantity).toFixed(2)}\n  📷 Foto: ${imgUrl}`;
    }).join('\n\n');
    const text = `¡Hola PalCus Perú! 🛒 Quiero hacer un pedido:\n\n📋 Código de compra: ${code}\n\n${lines}\n\nTotal: S/${totalPrice().toFixed(2)}`;
    return `https://wa.me/${PHONE}?text=${encodeURIComponent(text)}`;
  }
 
  async function checkout() {
    const items = read();
    if (items.length === 0) return;
    
    try {
      // 1. Registrar pedido en el sistema (MySQL) vía PHP API
      // Usar ruta relativa al root del sitio
      const response = await fetch('admin/api/registrar_pedido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: items.map(i => ({
            nombre: i.name,
            size: i.size,
            color: i.color,
            cantidad: i.quantity,
            precio: i.price
          })),
          nombre: 'Cliente Web',
          telefono: ''
        })
      });
      
      const res = await response.json();
      if (!res.success) {
        console.warn("No se pudo registrar el pedido en el admin:", res.error);
      }

      // 2. Abrir WhatsApp (Independientemente de si el registro falló, para no bloquear la compra)
      const url = whatsappUrl();
      window.open(url, '_blank');
      
      // 3. Limpiar carrito
      setTimeout(() => {
        clear();
        update();
        if (window.PalcusLayout) window.PalcusLayout.closeCart();
      }, 500);

    } catch (error) {
      console.error("Error en checkout:", error);
      // Fallback: Si falla la API, igual abrir WhatsApp
      window.open(whatsappUrl(), '_blank');
      clear();
      update();
    }
  }

  function update() {
    const badge = document.querySelector('[data-cart-count]');
    if (badge) {
      const n = totalItems();
      badge.textContent = n;
      badge.style.display = n > 0 ? 'flex' : 'none';
    }
    if (window.PalcusLayout && window.PalcusLayout.renderCart) window.PalcusLayout.renderCart();
  }

  window.PalcusCart = {
    read, add, remove, setQty, clear,
    totalItems, totalPrice, whatsappUrl, checkout,
    getOrderCode, refreshOrderCode,
    update, PHONE,
  };

  document.addEventListener('DOMContentLoaded', update);
})();
