// Inyección de Header, Footer, Drawer Carrito, Search Modal, FAQ Chat y WhatsApp flotante
(function () {
  const I = window.PalcusIcons;
  const U = window.PalcusUtil;
  const PHONE = '51981293422';

  let navLinks = [
    { href: 'index.html', label: 'Inicio' },
  ];

  function updateNavLinks() {
    navLinks = [
      { href: 'index.html', label: 'Inicio' },
      ...Object.entries(window.PALCUS_CATEGORY_LABELS).map(([slug, name]) => ({
        href: `catalogo.html?categoria=${slug}`,
        label: name,
        category: slug
      }))
    ];
  }

  const currentPage = (location.pathname.split('/').pop() || 'index.html');

  function buildHeader() {
    const navHTML = navLinks.map(link => {
      const types = link.category ? U.typesByCategory(link.category) : [];
      const active = currentPage === link.href ? 'active' : '';
      const hasDrop = link.category && types.length > 0;
      const dropdown = hasDrop ? `
        <div class="dropdown">
          <div class="dropdown-card">
            <p style="padding:0 1.25rem 0.5rem;font-size:0.625rem;text-transform:uppercase;letter-spacing:0.2em;color:var(--muted-foreground);font-family:'Syne',sans-serif;font-weight:600;">Categorías</p>
            ${types.map(t => `<a href="${link.href}">${window.PALCUS_TYPE_LABELS[t]}</a>`).join('')}
            <div style="border-top:1px solid var(--border);margin-top:0.5rem;padding:0.5rem 1.25rem 0;">
              <a href="${link.href}" style="font-size:0.625rem;text-transform:uppercase;letter-spacing:0.15em;font-weight:600;color:var(--foreground);padding:0;">Ver todo →</a>
            </div>
          </div>
        </div>` : '';
      return `<div class="nav-group" style="position:relative;">
        <a href="${link.href}" class="nav-link ${active}">${link.label}${link.category ? I.chevronDown(12) : ''}</a>
        ${dropdown}
      </div>`;
    }).join('');

    return `
    <header style="position:sticky;top:0;z-index:40;background:oklch(1 0 0 / 0.95);backdrop-filter:blur(8px);border-bottom:1px solid var(--border);">
      <div style="background:var(--primary);color:var(--primary-foreground);text-align:center;padding:0.5rem 1rem;">
        <p style="font-size:0.75rem;letter-spacing:0.2em;font-family:'Syne',sans-serif;text-transform:uppercase;margin:0;">
          Envío gratis en compras mayores a S/200 · 100% Algodón Peruano
        </p>
      </div>
      <div style="max-width:80rem;margin:0 auto;padding:0 1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;height:4rem;">
          <button id="mobileMenuBtn" style="background:none;border:none;padding:0.5rem;display:none;" aria-label="Menú" class="mobile-only">${I.menu()}</button>
          <a href="index.html" style="flex-shrink:0;"><img src="assets/logo_palcus.png" alt="PalCus Perú" style="height:3rem;width:auto;"></a>
          <nav class="desktop-nav" style="display:flex;align-items:center;gap:2rem;">${navHTML}</nav>
          <div style="display:flex;align-items:center;gap:0.75rem;">
            <button id="searchBtn" style="background:none;border:none;padding:0.5rem;" aria-label="Buscar">${I.search()}</button>
            <button id="cartBtn" style="background:none;border:none;padding:0.5rem;position:relative;" aria-label="Carrito">
              ${I.shoppingBag()}
              <span data-cart-count style="position:absolute;top:-2px;right:-2px;background:var(--foreground);color:var(--background);font-size:10px;font-weight:bold;width:1rem;height:1rem;border-radius:9999px;display:none;align-items:center;justify-content:center;">0</span>
            </button>
          </div>
        </div>
      </div>
      <div id="mobileMenu" style="display:none;border-top:1px solid var(--border);background:var(--background);padding:1rem;">
        ${navLinks.map(l => `<a href="${l.href}" style="display:block;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.15em;font-weight:500;padding:0.5rem 0;color:${currentPage===l.href?'var(--foreground)':'var(--muted-foreground)'};">${l.label}</a>`).join('')}
      </div>
    </header>
    <style>
      @media (max-width: 1024px) {
        .desktop-nav { display: none !important; }
        .mobile-only { display: block !important; }
      }
    </style>`;
  }

  function buildFooter() {
    const social = [
      { href: 'https://facebook.com/palcusperu', label: 'Facebook', icon: I.facebook(), handle: '@PalCusPeru' },
      { href: 'https://instagram.com/palcusperu', label: 'Instagram', icon: I.instagram(), handle: '@PalCusPeru' },
      { href: 'https://tiktok.com/@palcusperu', label: 'TikTok', icon: I.tiktok(), handle: '@PalCusPeru' },
    ];
    return `
    <footer style="background:var(--primary);color:var(--primary-foreground);">
      <div style="max-width:80rem;margin:0 auto;padding:4rem 1rem;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2.5rem;">
          <div>
            <img src="assets/logo_palcus.png" alt="PalCus Perú" class="invert" style="height:3.5rem;width:auto;margin-bottom:1rem;">
            <p style="font-size:0.875rem;opacity:0.7;line-height:1.6;">Moda casual exclusiva para mujer con 100% algodón peruano. Calidad, confort y estilo en cada prenda.</p>
          </div>
          <div>
            <h4 style="font-family:'Syne',sans-serif;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.2em;font-weight:600;margin-bottom:1rem;">Colección</h4>
            <ul style="list-style:none;padding:0;margin:0;font-size:0.875rem;opacity:0.7;display:flex;flex-direction:column;gap:0.5rem;">
              ${Object.entries(window.PALCUS_CATEGORY_LABELS).map(([slug, name]) => `
                <li><a href="catalogo.html?categoria=${slug}">Polo ${name}</a></li>
              `).join('')}
            </ul>
          </div>
          <div>
            <h4 style="font-family:'Syne',sans-serif;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.2em;font-weight:600;margin-bottom:1rem;">Ayuda</h4>
            <ul style="list-style:none;padding:0;margin:0;font-size:0.875rem;opacity:0.7;display:flex;flex-direction:column;gap:0.5rem;">
              <li><a href="faq.html">Preguntas Frecuentes</a></li>
              <li><a href="devoluciones.html">Devoluciones</a></li>
              <li><a href="libro-reclamaciones.html">Libro de Reclamaciones</a></li>
            </ul>
          </div>
          <div>
            <h4 style="font-family:'Syne',sans-serif;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.2em;font-weight:600;margin-bottom:1rem;">Síguenos</h4>
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
              ${social.map(s => `
                <a href="${s.href}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:0.75rem;">
                  <span style="width:2.25rem;height:2.25rem;border-radius:9999px;border:1px solid oklch(1 0 0 / 0.3);display:flex;align-items:center;justify-content:center;">${s.icon}</span>
                  <span style="font-size:0.875rem;"><span style="opacity:0.7;">${s.label}</span><span style="display:block;font-size:0.75rem;opacity:0.5;">${s.handle}</span></span>
                </a>`).join('')}
              <a href="https://wa.me/${PHONE}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:0.75rem;margin-top:0.5rem;">
                <span style="width:2.25rem;height:2.25rem;border-radius:9999px;border:1px solid oklch(1 0 0 / 0.3);background:oklch(1 0 0 / 0.1);display:flex;align-items:center;justify-content:center;">${I.whatsapp(18)}</span>
                <span style="font-size:0.875rem;"><span style="opacity:0.7;">WhatsApp</span><span style="display:block;font-size:0.75rem;opacity:0.5;">Contáctanos</span></span>
              </a>
            </div>
          </div>
        </div>
        <div style="border-top:1px solid oklch(1 0 0 / 0.2);margin-top:3rem;padding-top:2rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;">
          <p style="font-size:0.75rem;opacity:0.5;margin:0;">© ${new Date().getFullYear()} PalCus Perú. Todos los derechos reservados.</p>
          <a href="libro-reclamaciones.html" style="font-size:0.75rem;opacity:0.5;text-decoration:underline;">📋 Libro de Reclamaciones</a>
        </div>
      </div>
    </footer>`;
  }

  function buildFloating() {
    return `
      <div id="chatPanel" style="display:none;"></div>
      <button id="faqBtn" class="faq-float animate-bounce-slow" aria-label="Preguntas frecuentes">
        ${I.helpCircle(24)}
        <span class="ping"></span>
      </button>
      <a href="https://wa.me/${PHONE}?text=Hola%20PalCus%20Per%C3%BA!%20Tengo%20una%20consulta" target="_blank" rel="noopener" class="whatsapp-float animate-pulse-soft" aria-label="Contactar por WhatsApp">
        ${I.whatsapp(28)}
      </a>
      <div id="cartDrawerHost"></div>
      <div id="searchModalHost"></div>
    `;
  }

  // ---------- Cart Drawer ----------
  let cartOpen = false;
  function renderCart() {
    const host = document.getElementById('cartDrawerHost');
    if (!host) return;
    if (!cartOpen) { host.innerHTML = ''; return; }
    const items = window.PalcusCart.read();
    const code = window.PalcusCart.getOrderCode();
    const total = window.PalcusCart.totalPrice();
    const itemsHTML = items.length === 0 ? `
      <div style="text-align:center;padding:4rem 0;">
        ${I.shoppingBag(48)}
        <p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:1rem;">Tu carrito está vacío</p>
      </div>` : items.map(it => `
      <div style="display:flex;gap:1rem;">
        <img src="${U.imageUrl(it.image)}" alt="${it.name}" style="width:5rem;height:6rem;object-fit:cover;">
        <div style="flex:1;">
          <h3 style="font-size:0.875rem;font-weight:500;margin:0;">${it.name}</h3>
          <p style="font-size:0.75rem;color:var(--muted-foreground);margin:0.125rem 0 0;">Talla: ${it.size} · Color: ${it.color}${it.design ? ` · Diseño: ${it.design}` : ''}</p>
          <p style="font-size:0.875rem;font-weight:600;margin:0.25rem 0 0;">S/${it.price.toFixed(2)}</p>
          <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;">
            <button class="qty-dec" data-id="${it.id}" data-size="${it.size}" data-color="${it.color}" data-design="${it.design || ''}" data-q="${it.quantity-1}" style="width:1.5rem;height:1.5rem;border:1px solid var(--border);background:var(--background);display:flex;align-items:center;justify-content:center;">${I.minus()}</button>
            <span style="font-size:0.75rem;width:1.5rem;text-align:center;">${it.quantity}</span>
            <button class="qty-inc" data-id="${it.id}" data-size="${it.size}" data-color="${it.color}" data-design="${it.design || ''}" data-q="${it.quantity+1}" style="width:1.5rem;height:1.5rem;border:1px solid var(--border);background:var(--background);display:flex;align-items:center;justify-content:center;">${I.plus()}</button>
            <button class="qty-rm" data-id="${it.id}" data-size="${it.size}" data-color="${it.color}" data-design="${it.design || ''}" style="margin-left:auto;background:none;border:none;color:var(--muted-foreground);">${I.trash()}</button>
          </div>
        </div>
      </div>`).join('');

    const footer = items.length === 0 ? '' : `
      <div style="border-top:1px solid var(--border);padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
        <div style="background:var(--accent);padding:0.75rem;display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
          <div style="min-width:0;">
            <p style="font-size:0.625rem;text-transform:uppercase;letter-spacing:0.2em;font-family:'Syne',sans-serif;color:var(--muted-foreground);margin:0;">Código de compra</p>
            <p style="font-family:'Syne',sans-serif;font-weight:bold;font-size:0.875rem;margin:0;">${code}</p>
          </div>
          <button id="copyCodeBtn" style="padding:0.5rem;background:none;border:none;" title="Copiar código">${I.copy()}</button>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <span style="font-family:'Syne',sans-serif;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em;">Total</span>
          <span style="font-family:'Syne',sans-serif;font-size:1.25rem;font-weight:bold;">S/${total.toFixed(2)}</span>
        </div>
        <a href="${window.PalcusCart.whatsappUrl()}" target="_blank" rel="noopener" class="btn-primary" style="width:100%;">${I.whatsapp(16)} Pedir por WhatsApp</a>
        <button id="clearCartBtn" style="font-size:0.75rem;color:var(--muted-foreground);background:none;border:none;text-align:center;text-transform:uppercase;letter-spacing:0.1em;">Vaciar carrito</button>
      </div>`;

    host.innerHTML = `
      <div class="drawer-overlay" id="drawerOverlay"></div>
      <div class="drawer">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1.5rem;border-bottom:1px solid var(--border);">
          <h2 style="font-family:'Syne',sans-serif;font-size:1.125rem;font-weight:bold;text-transform:uppercase;letter-spacing:0.1em;display:flex;align-items:center;gap:0.5rem;">${I.shoppingBag()} Tu Carrito</h2>
          <button id="closeCart" style="background:none;border:none;padding:0.25rem;">${I.x()}</button>
        </div>
        <div style="flex:1;overflow-y:auto;padding:1.5rem;display:flex;flex-direction:column;gap:1.5rem;">${itemsHTML}</div>
        ${footer}
      </div>`;

    document.getElementById('drawerOverlay').onclick = closeCart;
    document.getElementById('closeCart').onclick = closeCart;
    host.querySelectorAll('.qty-dec,.qty-inc').forEach(b => b.onclick = () => {
      window.PalcusCart.setQty(b.dataset.id, b.dataset.size, b.dataset.color, b.dataset.design, parseInt(b.dataset.q));
      renderCart();
    });
    host.querySelectorAll('.qty-rm').forEach(b => b.onclick = () => {
      window.PalcusCart.remove(b.dataset.id, b.dataset.size, b.dataset.color, b.dataset.design);
      renderCart();
    });
    const clr = document.getElementById('clearCartBtn');
    if (clr) clr.onclick = () => { window.PalcusCart.clear(); renderCart(); };
    const cp = document.getElementById('copyCodeBtn');
    if (cp) cp.onclick = () => { navigator.clipboard?.writeText(code); cp.innerHTML = I.check(14); setTimeout(() => cp.innerHTML = I.copy(), 1200); };
  }
  function openCart() { cartOpen = true; renderCart(); }
  function closeCart() { cartOpen = false; renderCart(); }

  // ---------- Search Modal ----------
  let searchOpen = false;
  function renderSearch() {
    const host = document.getElementById('searchModalHost');
    if (!host) return;
    if (!searchOpen) { host.innerHTML = ''; return; }
    host.innerHTML = `
      <div class="search-overlay" id="searchOverlay"></div>
      <div class="search-modal">
        <div style="max-width:42rem;margin:0 auto;">
          <div style="display:flex;align-items:center;gap:0.75rem;border-bottom:1px solid var(--border);padding-bottom:1rem;">
            ${I.search(20)}
            <input id="searchInput" type="text" placeholder="Buscar productos..." style="flex:1;background:transparent;font-family:'Syne',sans-serif;font-size:1.125rem;border:none;outline:none;" autofocus>
            <button id="closeSearch" style="background:none;border:none;padding:0.25rem;">${I.x()}</button>
          </div>
          <div id="searchResults" style="margin-top:1.5rem;"></div>
        </div>
      </div>`;
    document.getElementById('searchOverlay').onclick = () => { searchOpen = false; renderSearch(); };
    document.getElementById('closeSearch').onclick = () => { searchOpen = false; renderSearch(); };
    const input = document.getElementById('searchInput');
    input.oninput = (e) => {
      const q = e.target.value;
      const res = q.length >= 2 ? U.search(q) : [];
      const resBox = document.getElementById('searchResults');
      if (q.length < 2) { resBox.innerHTML = ''; return; }
      if (res.length === 0) {
        resBox.innerHTML = `<p style="color:var(--muted-foreground);font-size:0.875rem;text-align:center;padding:2rem 0;">No se encontraron productos para "${q}"</p>`;
        return;
      }
      resBox.innerHTML = `
        <p style="font-size:0.75rem;color:var(--muted-foreground);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:1rem;">${res.length} resultado${res.length!==1?'s':''}</p>
        ${res.map(p => `
          <a href="producto.html?id=${p.id}" style="display:flex;gap:1rem;padding:0.75rem;transition:background 0.2s;" onmouseover="this.style.background='var(--accent)'" onmouseout="this.style.background='transparent'">
            <img src="${U.imageUrl(p.image)}" alt="${p.name}" style="width:4rem;height:5rem;object-fit:cover;">
            <div>
              <h3 style="font-size:0.875rem;font-weight:500;margin:0;">${p.name}</h3>
              <p style="font-size:0.75rem;color:var(--muted-foreground);margin:0.125rem 0 0;">${window.PALCUS_CATEGORY_LABELS[p.category]}</p>
              <p style="font-size:0.875rem;font-weight:600;margin:0.25rem 0 0;">S/${p.price.toFixed(2)}</p>
            </div>
          </a>`).join('')}`;
    };
  }

  // ---------- Chat FAQ ----------
  let chatOpen = false;
  const QUICK = ['¿Cómo realizo un pedido?', '¿Métodos de pago?', '¿Tiempo de envío?', '¿Cómo elijo mi talla?', '¿Hacen devoluciones?'];
  const ANSWERS = {
    '¿Cómo realizo un pedido?': 'Agrega productos al carrito y al finalizar envías tu pedido por WhatsApp con un código único de compra. Te confirmamos disponibilidad y coordinamos pago + envío.',
    '¿Métodos de pago?': 'Aceptamos transferencias (BCP, Interbank, BBVA), Yape, Plin y pago contra entrega en Lima Metropolitana.',
    '¿Tiempo de envío?': 'Lima Metropolitana: 1-2 días hábiles. Provincias: 3-5 días hábiles. Envío gratis en compras mayores a S/200.',
    '¿Cómo elijo mi talla?': 'Cada producto tiene su guía de tallas. Si tienes dudas, escríbenos por WhatsApp con tus medidas y te ayudamos.',
    '¿Hacen devoluciones?': 'Sí, tienes 7 días calendario desde la recepción para solicitar cambio o devolución.',
  };
  let messages = [{ from: 'bot', text: '¡Hola! 👋 Soy el asistente de PalCus Perú. ¿En qué te puedo ayudar?' }];

  function renderChat() {
    const panel = document.getElementById('chatPanel');
    const faqBtn = document.getElementById('faqBtn');
    if (!panel) return;
    if (!chatOpen) { panel.style.display = 'none'; faqBtn.innerHTML = I.helpCircle(24) + '<span class="ping"></span>'; return; }
    faqBtn.innerHTML = I.x(22);
    panel.style.display = 'block';
    panel.className = 'chat-panel';
    panel.innerHTML = `
      <div class="chat-header">
        <div style="display:flex;align-items:center;gap:0.75rem;">
          <div style="width:2.25rem;height:2.25rem;border-radius:9999px;background:oklch(1 0 0 / 0.15);display:flex;align-items:center;justify-content:center;">${I.helpCircle(18)}</div>
          <div>
            <p style="font-family:'Syne',sans-serif;font-weight:bold;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em;margin:0;">Ayuda PalCus</p>
            <p style="font-size:0.625rem;opacity:0.7;margin:0;">Respuestas rápidas</p>
          </div>
        </div>
        <button id="closeChat" style="background:none;border:none;color:inherit;padding:0.25rem;">${I.x(18)}</button>
      </div>
      <div class="chat-messages" id="chatMsgs">
        ${messages.map(m => `
          <div class="chat-msg ${m.from}">
            <div class="chat-bubble">
              ${m.text}
              ${m.link ? `<a href="${m.link.to}" style="display:block;margin-top:0.5rem;font-size:0.75rem;font-family:'Syne',sans-serif;text-transform:uppercase;letter-spacing:0.1em;text-decoration:underline;">${m.link.label}</a>` : ''}
            </div>
          </div>`).join('')}
      </div>
      <div class="chat-quick">
        <p style="font-size:0.625rem;text-transform:uppercase;letter-spacing:0.2em;font-family:'Syne',sans-serif;color:var(--muted-foreground);margin:0;">Preguntas rápidas</p>
        <div class="chat-quick-btns">
          ${QUICK.map(q => `<button class="chat-quick-btn" data-q="${q}">${q}</button>`).join('')}
        </div>
        <a href="faq.html" style="margin-top:0.75rem;display:flex;align-items:center;justify-content:center;gap:0.5rem;font-size:0.75rem;font-family:'Syne',sans-serif;text-transform:uppercase;letter-spacing:0.1em;padding:0.5rem;border:1px solid var(--border);">${I.send(12)} Ver todas las preguntas</a>
      </div>`;
    document.getElementById('closeChat').onclick = () => { chatOpen = false; renderChat(); };
    panel.querySelectorAll('.chat-quick-btn').forEach(b => b.onclick = () => {
      const q = b.dataset.q;
      messages.push({ from: 'user', text: q });
      messages.push({ from: 'bot', text: ANSWERS[q] || 'Te recomiendo revisar nuestra sección de Preguntas Frecuentes.', link: { to: 'faq.html', label: 'Ver más preguntas →' } });
      renderChat();
      const m = document.getElementById('chatMsgs'); m.scrollTop = m.scrollHeight;
    });
  }

  // ---------- Init ----------
  function init() {
    const headerHost = document.getElementById('site-header');
    const footerHost = document.getElementById('site-footer');
    const floatHost = document.getElementById('site-floating');
    if (headerHost) headerHost.innerHTML = buildHeader();
    if (footerHost) footerHost.innerHTML = buildFooter();
    if (floatHost) floatHost.innerHTML = buildFloating();

    document.getElementById('cartBtn')?.addEventListener('click', openCart);
    document.getElementById('searchBtn')?.addEventListener('click', () => { searchOpen = true; renderSearch(); });
    document.getElementById('faqBtn')?.addEventListener('click', () => { chatOpen = !chatOpen; renderChat(); });
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
      const m = document.getElementById('mobileMenu');
      m.style.display = m.style.display === 'block' ? 'none' : 'block';
    });

    window.PalcusCart.update();

    setTimeout(() => {
      const p = document.getElementById('global-preloader');
      if(p) {
        p.classList.add('fade-out');
        setTimeout(() => p.remove(), 500);
      }
    }, 1300);
  }

  window.PalcusLayout = { renderCart, openCart, closeCart };
  
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  // Re-renderizar cuando los datos de Firebase lleguen
  window.addEventListener('palcus-data-ready', () => {
    updateNavLinks();
    const headerHost = document.getElementById('site-header');
    const footerHost = document.getElementById('site-footer');
    if (headerHost) headerHost.innerHTML = buildHeader();
    if (footerHost) footerHost.innerHTML = buildFooter();
    
    // Volver a asignar eventos del menú móvil
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
      const m = document.getElementById('mobileMenu');
      m.style.display = m.style.display === 'block' ? 'none' : 'block';
    });
  });
})();
