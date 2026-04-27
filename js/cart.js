// Carrito + código de compra (localStorage)
(function () {
  const KEY = 'palcus_cart_v1';
  const CODE_KEY = 'palcus_order_code_v1';
  const PHONE = '51999999999';

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

  function add(product, size, color, quantity = 1) {
    const items = read();
    const idx = items.findIndex(i => i.id === product.id && i.size === size && i.color === color);
    if (idx >= 0) items[idx].quantity += quantity;
    else items.push({ id: product.id, name: product.name, price: product.price, image: product.image, size, color, quantity });
    write(items);
    refreshOrderCode();
  }
  function remove(id, size, color) {
    write(read().filter(i => !(i.id === id && i.size === size && i.color === color)));
    refreshOrderCode();
  }
  function setQty(id, size, color, qty) {
    if (qty <= 0) return remove(id, size, color);
    const items = read().map(i =>
      (i.id === id && i.size === size && i.color === color) ? { ...i, quantity: qty } : i
    );
    write(items);
    refreshOrderCode();
  }
  function clear() { write([]); refreshOrderCode(); }

  function whatsappUrl() {
    const items = read();
    const code = getOrderCode();
    const lines = items.map(i => `• ${i.name} (Talla: ${i.size}, Color: ${i.color}) x${i.quantity} - S/${(i.price * i.quantity).toFixed(2)}`).join('\n');
    const text = `¡Hola PalCus Perú! 🛒 Quiero hacer un pedido:\n\n📋 Código de compra: ${code}\n\n${lines}\n\nTotal: S/${totalPrice().toFixed(2)}`;
    return `https://wa.me/${PHONE}?text=${encodeURIComponent(text)}`;
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
    totalItems, totalPrice, whatsappUrl,
    getOrderCode, refreshOrderCode,
    update, PHONE,
  };

  document.addEventListener('DOMContentLoaded', update);
})();
