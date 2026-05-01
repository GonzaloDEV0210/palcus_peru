// Renderizadores de páginas (grids de categoría, detalle de producto)
(function () {
  const U = window.PalcusUtil;

  function productCard(p) {
    const colors = p.colors || [];
    const sizes = p.sizes || [];
    
    // Lógica de Etiquetas Automáticas
    let badgeHTML = '';
    const now = new Date();
    const created = p.createdAt?.toDate ? p.createdAt.toDate() : new Date(p.createdAt || now);
    const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24));
    
    if (diffDays <= 15) {
      badgeHTML = `<div class="product-tag">Lo más Nuevo</div>`;
    } else if ((p.salesCount || 0) > 10) {
      badgeHTML = `<div class="product-tag">Lo más vendido</div>`;
    } else if ((p.viewCount || 0) > 50) {
      badgeHTML = `<div class="product-tag">Tendencia</div>`;
    } else if ((p.stock || 0) > 0 && (p.stock || 0) < 5) {
      badgeHTML = `<div class="product-tag">¡Últimas unidades!</div>`;
    }

    return `
      <a href="producto.html?id=${p.id}" class="product-card">
        <div class="img-wrap" style="position:relative;">
          ${badgeHTML}
          <img src="${U.imageUrl(p.image)}" alt="${p.name}" loading="lazy">
        </div>
        <div style="margin-top:0.75rem;">
          <h3 style="font-size:0.875rem;font-weight:500;margin:0;">${p.name}</h3>
          <p style="font-family:'Syne',sans-serif;font-size:0.875rem;font-weight:bold;margin:0.25rem 0 0;">S/${(p.price || 0).toFixed(2)}</p>
          <div style="display:flex;align-items:center;gap:0.375rem;margin-top:0.5rem;">
            ${colors.map(c => `<span title="${c.name}" style="width:1rem;height:1rem;border-radius:9999px;border:1px solid var(--border);background:${c.hex};"></span>`).join('')}
          </div>
          <p style="font-size:0.625rem;color:var(--muted-foreground);text-transform:uppercase;letter-spacing:0.1em;margin:0.25rem 0 0;">${sizes.join(' · ')}</p>
        </div>
      </a>`;
  }

  function renderGrid(containerId, products) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = products.map(productCard).join('');
  }

  function renderProductDetail() {
    const params = new URLSearchParams(location.search);
    const id = params.get('id');
    const p = U.byId(id);
    const host = document.getElementById('product-detail');
    if (!host) return;
    if (!p) {
      host.innerHTML = '<div style="padding:10rem 2rem;text-align:center;"><p>Producto no encontrado</p><a href="catalogo.html" class="btn-primary">Volver al catálogo</a></div>';
      return;
    }
    window.PalcusUtil.trackView(p.id);
    document.title = `${p.name} — PalCus Perú`;
    let designs = p.designs || [];
    let sizes = p.sizes || [];
    let colors = p.colors || [];
    let selectedSize = null, selectedColor = null, selectedDesign = null, qty = 1;

    function update() {
      const validDesigns = selectedColor ? Object.keys(p.imageMap[selectedColor] || {}) : designs;
      if (selectedColor && selectedDesign && !validDesigns.includes(selectedDesign)) {
        selectedDesign = validDesigns.length > 0 ? validDesigns[0] : null;
      }
      
      let currentImage = p.image;
      if (selectedColor && selectedDesign && p.imageMap[selectedColor] && p.imageMap[selectedColor][selectedDesign]) {
        currentImage = p.imageMap[selectedColor][selectedDesign];
      } else if (selectedColor && validDesigns.length > 0) {
        currentImage = p.imageMap[selectedColor][validDesigns[0]];
      }

      host.innerHTML = `
        <div class="section-padding">
          <div style="max-width:72rem;margin:0 auto;">
            <nav style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:2rem;text-transform:uppercase;letter-spacing:0.1em;">
              <a href="index.html">Inicio</a><span style="margin:0 0.5rem;">/</span><span style="color:var(--foreground);">${p.name}</span>
            </nav>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:3rem;">
              <div style="background:var(--accent);aspect-ratio:3/4;overflow:hidden;">
                <img src="${U.imageUrl(currentImage)}" alt="${p.name}" style="width:100%;height:100%;object-fit:cover;" loading="lazy" decoding="async">
              </div>
              <div style="display:flex;flex-direction:column;justify-content:center;">
                ${p.tag ? `<span style="font-size:0.625rem;text-transform:uppercase;letter-spacing:0.2em;font-family:'Syne',sans-serif;font-weight:600;color:var(--muted-foreground);margin-bottom:0.5rem;">${p.tag}</span>` : ''}
                <h1 class="font-heading" style="font-size:2.25rem;font-weight:800;margin:0;">${p.name}</h1>
                <p class="font-heading" style="font-size:1.5rem;font-weight:bold;margin:0.75rem 0 0;">S/${p.price.toFixed(2)}</p>
                <p style="color:var(--muted-foreground);margin:1rem 0 0;line-height:1.6;">${p.description}</p>
                <div style="margin-top:1.5rem;display:inline-flex;align-items:center;gap:0.5rem;background:var(--accent);padding:0.5rem 1rem;width:fit-content;">
                  <span style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;font-weight:500;">100% Algodón Peruano</span>
                </div>
                <div style="margin-top:2rem;">
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Color${selectedColor ? `: ${selectedColor}` : ''}</p>
                  <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                    ${colors.map(c => `
                      <button class="pick-color" data-color="${c.name}" title="${c.name}" style="position:relative;width:2.5rem;height:2.5rem;border-radius:9999px;border:none;background:${c.hex};cursor:pointer;transition:all 0.2s;${selectedColor===c.name?'outline:2px solid var(--foreground);outline-offset:2px;transform:scale(1.1);':''}">
                        ${selectedColor===c.name?`<span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:${['#f5f5f5','#d4b896','#e8b4b8','#b19cd9','#ffffff','#eab308'].includes(c.hex)?'#1a1a1a':'#fff'};">${window.PalcusIcons.check(16)}</span>`:''}
                      </button>`).join('')}
                  </div>
                </div>
                <div style="margin-top:2rem;">
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Selecciona tu talla</p>
                  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    ${sizes.map(s => `<button class="pick-size" data-size="${s}" style="width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:500;border:1px solid ${selectedSize===s?'var(--primary)':'var(--border)'};background:${selectedSize===s?'var(--primary)':'transparent'};color:${selectedSize===s?'var(--primary-foreground)':'var(--foreground)'};cursor:pointer;">${s}</button>`).join('')}
                  </div>
                </div>
                ${validDesigns && validDesigns.length > 0 ? `
                <div style="margin-top:2rem;">
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Diseño (Pecho)${selectedDesign ? `: ${selectedDesign}` : ''}</p>
                  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    ${validDesigns.map(d => `<button class="pick-design" data-design="${d}" style="padding:0.5rem 1rem;font-size:0.75rem;font-weight:500;border:1px solid ${selectedDesign===d?'var(--primary)':'var(--border)'};background:${selectedDesign===d?'var(--primary)':'transparent'};color:${selectedDesign===d?'var(--primary-foreground)':'var(--foreground)'};cursor:pointer;border-radius:0.25rem;">${d}</button>`).join('')}
                  </div>
                </div>` : ''}
                <div style="margin-top:1.5rem;">
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.5rem;">Disponibilidad</p>
                  <div style="display:flex;align-items:center;gap:0.5rem;">
                    <div style="width:0.5rem;height:0.5rem;border-radius:9999px;background:${(p.stock || 0) > 0 ? '#10b981' : '#ef4444'};"></div>
                    <span style="font-size:0.75rem;font-weight:500;color:${(p.stock || 0) > 0 ? 'var(--foreground)' : '#ef4444'};">
                      ${(p.stock || 0) > 0 ? `${p.stock} unidades disponibles` : 'Agotado'}
                    </span>
                  </div>
                </div>
                <div style="margin-top:2rem;">
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Cantidad</p>
                  <div style="display:flex;align-items:center;gap:0.75rem;width:fit-content;border:1px solid var(--border);">
                    <button id="qd" style="width:2.5rem;height:2.5rem;background:none;border:none;display:flex;align-items:center;justify-content:center;">${window.PalcusIcons.minus(14)}</button>
                    <span style="font-size:0.875rem;font-weight:500;width:2rem;text-align:center;">${qty}</span>
                    <button id="qi" style="width:2.5rem;height:2.5rem;background:none;border:none;display:flex;align-items:center;justify-content:center;">${window.PalcusIcons.plus(14)}</button>
                  </div>
                </div>
                ${(() => {
                  const stock = p.stock || 0;
                  const missing = !selectedSize || !selectedColor || (validDesigns.length > 0 && !selectedDesign);
                  if (stock <= 0) return `<button class="btn-primary" style="margin-top:2rem;background:#999;cursor:not-allowed;" disabled>Agotado</button>`;
                  return `<button id="addCart" class="btn-primary" style="margin-top:2rem;${missing?'opacity:0.4;cursor:not-allowed;':''}" ${missing?'disabled':''}>Agregar al carrito</button>`;
                })()}
                <a href="https://wa.me/${window.PalcusCart.PHONE}?text=${encodeURIComponent(`Hola PalCus Perú! Me interesa: ${p.name} - S/${p.price.toFixed(2)}`)}" target="_blank" rel="noopener" class="btn-outline" style="margin-top:1rem;">Consultar por WhatsApp</a>
              </div>
            </div>
          </div>
        </div>`;
      host.querySelectorAll('.pick-color').forEach(b => b.onclick = () => { selectedColor = b.dataset.color; update(); });
      host.querySelectorAll('.pick-size').forEach(b => b.onclick = () => { selectedSize = b.dataset.size; update(); });
      host.querySelectorAll('.pick-design').forEach(b => b.onclick = () => { selectedDesign = b.dataset.design; update(); });
      host.querySelector('#qd').onclick = () => { qty = Math.max(1, qty - 1); update(); };
      host.querySelector('#qi').onclick = () => { 
        if (qty < (p.stock || 0)) {
          qty = qty + 1; 
          update(); 
        }
      };
      const addBtn = host.querySelector('#addCart');
      if (addBtn && !addBtn.disabled) addBtn.onclick = () => {
        const productForCart = { ...p, image: currentImage };
        window.PalcusCart.add(productForCart, selectedSize, selectedColor, selectedDesign, qty);
        addBtn.innerHTML = `${window.PalcusIcons.check(16)} Agregado`;
        setTimeout(() => window.PalcusLayout.openCart(), 500);
      };
    }
    update();
  }

  window.PalcusRender = { renderGrid, renderProductDetail, productCard };
})();
