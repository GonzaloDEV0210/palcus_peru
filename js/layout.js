// Inyección de Header, Footer, Drawer Carrito, Search Modal, FAQ Chat y WhatsApp flotante
(function () {
  const I = window.PalcusIcons;
  const U = window.PalcusUtil;
  const PHONE = '51981293422';
  const API_BRANDING = 'admin/api/get_branding.php';

  let navLinks = [
    { href: 'index.html', label: 'Inicio', children: [] },
  ];

  window.PalcusBranding = {
    url_icono: 'preloader-icon.png',
    url_logo: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/v1778354037/vjypdweg16udzxoptdxz.png',
    url_hero: 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/hero-banner-mujer.jpg',
    nombre_tienda: 'PalCus Perú',
    top_announcement: 'Envío gratis en compras mayores a S/200 · 100% Algodón Peruano'
  };

  function updateNavLinks() {
    const list = window.PALCUS_CATEGORIES_LIST || [];
    if (!list.length) return false;

    function buildNode(cat) {
      return {
        id: cat.id,
        label: cat.name,
        href: `catalogo.html?categoria=${cat.slug_path}`,
        slug_path: cat.slug_path,
        children: list
          .filter(c => c.parent_id && Number(c.parent_id) === Number(cat.id))
          .sort((a, b) => (a.orden || 0) - (b.orden || 0))
          .map(buildNode)
      };
    }

    const roots = list
      .filter(c => !c.parent_id || c.parent_id == 0 || c.parent_id == "0" || c.parent_id == "null")
      .sort((a, b) => (a.orden || 0) - (b.orden || 0))
      .map(buildNode);

    if (roots.length === 0) return false;

    navLinks = [
      { label: 'Inicio', href: 'index.html', children: [] },
      ...roots
    ];
    return true;
  }

  const currentPage = (location.pathname.split('/').pop() || 'index.html');

  function buildHeader() {
    function renderMegaColumn(node, depth) {
      const hasChildren = node.children && node.children.length > 0;
      const childColor  = depth === 1 ? 'var(--foreground)' : 'var(--muted-foreground)';
      const childSize   = depth === 1 ? '0.8rem' : '0.75rem';
      const childWeight = depth === 1 ? '700' : '500';
      const childrenHTML = hasChildren
        ? `<div style="display:flex;flex-direction:column;gap:0.3rem;margin-top:0.4rem;padding-left:0.75rem;border-left:2px solid var(--border);">
            ${node.children.map(ch => renderMegaColumn(ch, depth + 1)).join('')}
          </div>`
        : '';
      return `<div>
        <a href="${node.href}" style="display:block;font-size:${childSize};font-weight:${childWeight};color:${childColor};padding:0.2rem 0;white-space:nowrap;transition:opacity .2s;" onmouseover="this.style.opacity='.6'" onmouseout="this.style.opacity='1'">${node.label}</a>
        ${childrenHTML}
      </div>`;
    }

    const navHTML = navLinks.map(link => {
      const active = (currentPage === link.href || (link.slug_path && location.search.includes(link.slug_path))) ? 'active' : '';
      const hasChildren = link.children && link.children.length > 0;
      let megaMenu = '';
      if (hasChildren) {
        const cols = link.children.map(child => `
          <div style="min-width:130px;">
            <a href="${child.href}" style="display:block;font-size:0.7rem;font-weight:800;color:var(--foreground);text-transform:uppercase;letter-spacing:0.1em;padding:0 0 0.6rem 0;border-bottom:2px solid var(--border);margin-bottom:0.75rem;white-space:nowrap;">${child.label}</a>
            ${child.children && child.children.length
              ? `<div style="display:flex;flex-direction:column;gap:0.5rem;">${child.children.map(sub => renderMegaColumn(sub, 1)).join('')}</div>`
              : ''}
          </div>`).join('');
        megaMenu = `<div class="mega-menu" style="position:absolute;top:calc(100% + 1px);left:50%;transform:translateX(-50%);background:oklch(1 0 0 / 0.98);backdrop-filter:blur(16px);border:1px solid var(--border);border-top:3px solid var(--foreground);border-radius:0 0 1rem 1rem;padding:1.5rem 2rem;display:flex;gap:2.5rem;box-shadow:0 16px 40px -8px rgba(0,0,0,0.15);opacity:0;visibility:hidden;transition:opacity .2s,visibility .2s;min-width:max-content;max-width:80vw;z-index:100;">${cols}</div>`;
      }
      return `<div class="nav-group" style="position:relative;" onmouseenter="const m=this.querySelector('.mega-menu');if(m){m.style.opacity='1';m.style.visibility='visible';}" onmouseleave="const m=this.querySelector('.mega-menu');if(m){m.style.opacity='0';m.style.visibility='hidden';}">
        <a href="${link.href}" class="nav-link ${active}" style="display:flex;align-items:center;gap:0.3rem;">
          ${link.label}
          ${hasChildren ? I.chevronDown(10) : ''}
        </a>
        ${megaMenu}
      </div>`;
    }).join('');

    function renderMobileNode(node, depth) {
      const indent = depth * 1;
      const hasC = node.children && node.children.length > 0;
      const id = `mob-${node.label.replace(/\s/g,'-').toLowerCase()}-${depth}`;
      return `<div style="padding-left:${indent}rem;"><div style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0;border-bottom:1px solid var(--border);"><a href="${node.href}" style="font-size:${depth===0?'0.875':'0.75'}rem;font-weight:${depth===0?'700':'500'};color:var(--foreground);text-transform:${depth===0?'uppercase':'none'};letter-spacing:${depth===0?'0.1em':'0'};">${node.label}</a>${hasC ? `<button onclick="const el=document.getElementById('${id}');el.style.display=el.style.display==='none'?'block':'none';" style="background:none;border:none;padding:0.25rem;color:var(--muted-foreground);cursor:pointer;">${I.chevronDown(12)}</button>` : ''}</div>${hasC ? `<div id="${id}" style="display:none;">${node.children.map(ch => renderMobileNode(ch, depth+1)).join('')}</div>` : ''}</div>`;
    }

    return `
    <header style="position:sticky;top:0;z-index:40;background:oklch(1 0 0 / 0.95);backdrop-filter:blur(8px);border-bottom:1px solid var(--border);">
      <div style="background:var(--primary);color:var(--primary-foreground);text-align:center;padding:0.5rem 1rem;">
        <p id="top-announcement-bar" style="font-size:0.75rem;letter-spacing:0.2em;font-family:'Syne',sans-serif;text-transform:uppercase;margin:0;">${window.PalcusBranding.top_announcement}</p>
      </div>
      <div style="max-width:80rem;margin:0 auto;padding:0 1rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;height:4rem;">
          <button id="mobileMenuBtn" style="background:none;border:none;padding:0.5rem;display:none;" aria-label="Menú" class="mobile-only">${I.menu()}</button>
          <a href="index.html" style="flex-shrink:0;"><img id="main-logo" src="${window.PalcusBranding.url_logo}" alt="${window.PalcusBranding.nombre_tienda}" style="height:3rem;width:auto;"></a>
          <nav class="desktop-nav" style="display:flex;align-items:center;gap:2rem;">${navHTML}</nav>
          <div style="display:flex;align-items:center;gap:0.75rem;">
            <button id="searchBtn" style="background:none;border:none;padding:0.5rem;" aria-label="Buscar">${I.search()}</button>
            <button id="cartBtn" style="background:none;border:none;padding:0.5rem;position:relative;" aria-label="Carrito">${I.shoppingBag()}<span data-cart-count style="position:absolute;top:-2px;right:-2px;background:var(--foreground);color:var(--background);font-size:10px;font-weight:bold;width:1rem;height:1rem;border-radius:9999px;display:none;align-items:center;justify-content:center;">0</span></button>
          </div>
        </div>
      </div>
      <div id="mobileMenu" style="display:none;border-top:1px solid var(--border);background:var(--background);padding:1rem;max-height:80vh;overflow-y:auto;">${navLinks.map(l => renderMobileNode(l, 0)).join('')}</div>
    </header>
    <style>@media (max-width: 1024px) { .desktop-nav { display: none !important; } .mobile-only { display: block !important; } }</style>`;
  }

  function buildFooter() {
    const social = [
      { href: 'https://facebook.com/palcusperu', label: 'Facebook', icon: I.facebook(), handle: '@PalCusPeru' },
      { href: 'https://instagram.com/palcusperu', label: 'Instagram', icon: I.instagram(), handle: '@PalCusPeru' },
      { href: 'https://tiktok.com/@palcusperu', label: 'TikTok', icon: I.tiktok(), handle: '@PalCusPeru' },
    ];
    return `<footer style="background:var(--primary);color:var(--primary-foreground);"><div style="max-width:80rem;margin:0 auto;padding:4rem 1rem;"><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2.5rem;"><div><img id="footer-logo" src="${window.PalcusBranding.url_logo}" alt="${window.PalcusBranding.nombre_tienda}" class="invert" style="height:3rem;width:auto;margin-bottom:1.5rem;"><p style="font-size:0.875rem;opacity:0.7;line-height:1.6;">Moda casual exclusiva para mujer con 100% algodón peruano. Calidad, confort y estilo en cada prenda.</p></div><div><h4 style="font-family:'Syne',sans-serif;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.2em;font-weight:600;margin-bottom:1rem;">Ayuda</h4><ul style="list-style:none;padding:0;margin:0;font-size:0.875rem;opacity:0.7;display:flex;flex-direction:column;gap:0.5rem;"><li><a href="faq.html">Preguntas Frecuentes</a></li><li><a href="devoluciones.html">Devoluciones</a></li><li><a href="libro-reclamaciones.html">Libro de Reclamaciones</a></li></ul></div><div><h4 style="font-family:'Syne',sans-serif;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.2em;font-weight:600;margin-bottom:1rem;">Síguenos</h4><div style="display:flex;flex-direction:column;gap:0.75rem;">${social.map(s => `<a href="${s.href}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:0.75rem;"><span style="width:2.25rem;height:2.25rem;border-radius:9999px;border:1px solid oklch(1 0 0 / 0.3);display:flex;align-items:center;justify-content:center;">${s.icon}</span><span style="font-size:0.875rem;"><span style="opacity:0.7;">${s.label}</span><span style="display:block;font-size:0.75rem;opacity:0.5;">${s.handle}</span></span></a>`).join('')}<a href="https://wa.me/${PHONE}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:0.75rem;margin-top:0.5rem;"><span style="width:2.25rem;height:2.25rem;border-radius:9999px;border:1px solid oklch(1 0 0 / 0.3);background:oklch(1 0 0 / 0.1);display:flex;align-items:center;justify-content:center;">${I.whatsapp(18)}</span><span style="font-size:0.875rem;"><span style="opacity:0.7;">WhatsApp</span><span style="display:block;font-size:0.75rem;opacity:0.5;">Contáctanos</span></span></a></div></div></div><div style="border-top:1px solid oklch(1 0 0 / 0.2);margin-top:3rem;padding-top:2rem;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;"><p style="font-size:0.75rem;opacity:0.5;margin:0;">© ${new Date().getFullYear()} PalCus Perú. Todos los derechos reservados.</p><a href="libro-reclamaciones.html" style="font-size:0.75rem;opacity:0.5;text-decoration:underline;">📋 Libro de Reclamaciones</a></div></div></footer>`;
  }

  function buildFloating() {
    return `<div id="chatPanel" style="display:none;"></div><button id="faqBtn" class="faq-float animate-bounce-slow" aria-label="Preguntas frecuentes">${I.helpCircle(24)}<span class="ping"></span></button><a href="https://wa.me/${PHONE}?text=Hola%20PalCus%20Per%C3%BA!%20Tengo%20una%20consulta" target="_blank" rel="noopener" class="whatsapp-float animate-pulse-soft" aria-label="Contactar por WhatsApp">${I.whatsapp(28)}</a><div id="cartDrawerHost"></div><div id="searchModalHost"></div>`;
  }

  // ---------- Cart Drawer ----------
  let cartOpen = false;
  function renderCart() {
    const host = document.getElementById('cartDrawerHost');
    if (!host || !cartOpen) { if(host) host.innerHTML=''; return; }
    const items = window.PalcusCart.read();
    const itemsHTML = items.length === 0 ? `<div style="text-align:center;padding:4rem 0;">${I.shoppingBag(48)}<p style="color:var(--muted-foreground);font-size:0.875rem;margin-top:1rem;">Tu carrito está vacío</p></div>` : items.map(it => `<div style="display:flex;gap:1rem;"><img src="${U.imageUrl(it.image)}" alt="${it.name}" style="width:5rem;height:6rem;object-fit:cover;"><div style="flex:1;"><h3 style="font-size:0.875rem;font-weight:500;margin:0;">${it.name}</h3><p style="font-size:0.875rem;font-weight:600;margin:0.25rem 0 0;">S/${it.price.toFixed(2)}</p></div></div>`).join('');
    host.innerHTML = `<div class="drawer-overlay" id="drawerOverlay"></div><div class="drawer"><div style="display:flex;align-items:center;justify-content:space-between;padding:1.5rem;border-bottom:1px solid var(--border);"><h2>Carrito</h2><button id="closeCart">${I.x()}</button></div><div style="padding:1.5rem;">${itemsHTML}</div></div>`;
    document.getElementById('drawerOverlay').onclick = closeCart;
    document.getElementById('closeCart').onclick = closeCart;
  }
  function openCart() { cartOpen = true; renderCart(); }
  function closeCart() { cartOpen = false; renderCart(); }

  // ---------- Search Modal ----------
  let searchOpen = false;
  function renderSearch() {
    const host = document.getElementById('searchModalHost');
    if (!host || !searchOpen) { if(host) host.innerHTML=''; return; }
    host.innerHTML = `<div class="search-overlay" id="searchOverlay"></div><div class="search-modal"><div style="display:flex;align-items:center;gap:0.75rem;border-bottom:1px solid var(--border);padding-bottom:1rem;">${I.search(20)}<input id="searchInput" type="text" placeholder="Buscar..." style="flex:1;border:none;outline:none;" autofocus><button id="closeSearch">${I.x()}</button></div><div id="searchResults" style="margin-top:1.5rem;"></div></div>`;
    document.getElementById('searchOverlay').onclick = () => { searchOpen = false; renderSearch(); };
    document.getElementById('closeSearch').onclick = () => { searchOpen = false; renderSearch(); };
  }

  // ---------- Adaptive Icon Engine ----------
  function processIconToWhite(url, callback) {
    if (!url) return;
    const img = new Image();
    img.crossOrigin = "anonymous";
    img.onload = () => {
      const canvas = document.createElement('canvas');
      canvas.width = img.width;
      canvas.height = img.height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0);
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const data = imageData.data;
      for (let i = 0; i < data.length; i += 4) {
        if (data[i+3] > 10) { // Si el pixel no es transparente
          data[i] = 255;   // R -> Blanco
          data[i+1] = 255; // G -> Blanco
          data[i+2] = 255; // B -> Blanco
        }
      }
      ctx.putImageData(imageData, 0, 0);
      callback(canvas.toDataURL());
    };
    img.src = url;
  }

  // ---------- Logic ----------
  function render() {
    const headerHost = document.getElementById('site-header');
    const footerHost = document.getElementById('site-footer');
    const floatHost = document.getElementById('site-floating');
    if (headerHost) headerHost.innerHTML = buildHeader();
    if (footerHost) footerHost.innerHTML = buildFooter();
    if (floatHost) floatHost.innerHTML = buildFloating();

    if (!document.getElementById('global-preloader')) {
      const pre = document.createElement('div');
      pre.id = 'global-preloader';
      pre.innerHTML = `<img src="preloader-icon.png" alt="" class="preloader-img"><div class="preloader-bar"></div>`;
      document.body.prepend(pre);
    }

    document.getElementById('cartBtn')?.addEventListener('click', openCart);
    document.getElementById('searchBtn')?.addEventListener('click', () => { searchOpen = true; renderSearch(); });
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
      const m = document.getElementById('mobileMenu');
      m.style.display = m.style.display === 'block' ? 'none' : 'block';
    });
  }

  async function fetchBranding() {
    try {
      const resp = await fetch(`${API_BRANDING}?v=${new Date().getTime()}`);
      const data = await resp.json();
      window.PalcusBranding = { ...window.PalcusBranding, ...data };
      const logoHeader = document.getElementById('main-logo');
      const logoFooter = document.getElementById('footer-logo');
      const heroImg = document.getElementById('main-hero');
      const topAnn = document.getElementById('top-announcement-bar');
      const preImg = document.querySelector('.preloader-img');

      if (logoHeader && data.url_logo) logoHeader.src = data.url_logo;
      if (logoFooter && data.url_logo) logoFooter.src = data.url_logo;
      if (heroImg && data.url_hero) heroImg.src = data.url_hero;
      if (topAnn && data.top_announcement) topAnn.innerText = data.top_announcement;
      
      if (data.url_icono) {
        // 1. Icono del preloader directo
        if (preImg) preImg.src = data.url_icono;
        
        // 2. Procesar a blanco para la pestaña (Favicon)
        processIconToWhite(data.url_icono, (whiteUrl) => {
           const fav = document.querySelector('link[rel="icon"]');
           if (fav) fav.href = whiteUrl;
        });
      }
    } catch (e) { console.error('Branding error:', e); }
  }

  function init() {
    if (updateNavLinks()) render();
    else render();
    
    fetchBranding();
    
    const retryInterval = setInterval(() => {
      if (navLinks.length > 1) {
        clearInterval(retryInterval);
        return;
      }
      if (updateNavLinks()) {
        render();
        clearInterval(retryInterval);
      }
    }, 800);
    setTimeout(() => clearInterval(retryInterval), 5000);

    setTimeout(() => {
      const p = document.getElementById('global-preloader');
      if(p) { p.classList.add('fade-out'); setTimeout(() => p.remove(), 800); }
    }, 1500);
  }

  window.PalcusLayout = { renderCart, openCart, closeCart };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  window.addEventListener('palcus-data-ready', () => {
    if (updateNavLinks()) render();
    fetchBranding();
  });
})();
