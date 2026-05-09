// Renderizadores de páginas (grids de categoría, detalle de producto)
(function () {
  const U = window.PalcusUtil;

  function productCard(p) {
    const colors = p.colors || [];
    const sizes = p.sizes || [];
    
    // Obtener todas las imágenes únicas de variaciones
    const varImages = [...new Set((p.variations || []).map(v => v.imagen_url).filter(img => !!img))];
    const allImages = [...new Set([p.image, ...varImages])].filter(img => !!img);
    
    // Si no hay ninguna imagen, usar placeholder
    if (allImages.length === 0) allImages.push('https://via.placeholder.com/800x1000?text=PalCus');

    // Lógica de Etiquetas Automáticas
    let badgeHTML = '';
    const now = new Date();
    const created = new Date(p.createdAt || now);
    const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24));
    
    if (diffDays <= 15) {
      badgeHTML = `<div class="product-tag" style="z-index:10;">Lo más Nuevo</div>`;
    } else if ((p.salesCount || 0) > 10) {
      badgeHTML = `<div class="product-tag" style="z-index:10;">Lo más vendido</div>`;
    } else if ((p.viewCount || 0) > 50) {
      badgeHTML = `<div class="product-tag" style="z-index:10;">Tendencia</div>`;
    } else if ((p.stock || 0) > 0 && (p.stock || 0) < 5) {
      badgeHTML = `<div class="product-tag" style="z-index:10;">¡Últimas unidades!</div>`;
    }

    return `
      <a href="producto.html?id=${p.id}" class="product-card" data-product-id="${p.id}">
        <div class="img-wrap">
          ${badgeHTML}
          <div class="product-slider-container">
            ${allImages.map((img, idx) => `
              <img src="${U.imageUrl(img)}" 
                   class="slider-img ${idx === 0 ? 'active' : ''}" 
                   style="opacity:${idx === 0 ? '1' : '0'}; z-index:${idx === 0 ? '2' : '1'}; transition: opacity 1.5s ease-in-out; position:absolute; inset:0; width:100%; height:100%; object-fit:cover;"
                   alt="${p.name}" 
                   loading="lazy">
            `).join('')}
          </div>
        </div>
        <div style="margin-top:1rem;padding:0 0.5rem 0.5rem;">
          <h3 style="font-size:0.875rem;font-weight:600;margin:0;color:var(--foreground);">${p.name}</h3>
          <p class="font-heading" style="font-size:1rem;font-weight:bold;margin:0.35rem 0 0;color:var(--foreground);">S/${(p.price || 0).toFixed(2)}</p>
          <div style="display:flex;align-items:center;gap:0.375rem;margin-top:0.75rem;">
            ${colors.map(c => `<span title="${c.name}" style="width:0.75rem;height:0.75rem;border-radius:9999px;border:1px solid var(--border);background:${c.hex};"></span>`).join('')}
          </div>
          <p style="font-size:0.625rem;color:var(--muted-foreground);text-transform:uppercase;letter-spacing:0.15em;margin:0.5rem 0 0;font-family:'Syne',sans-serif;font-weight:600;">${sizes.join(' · ')}</p>
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
      console.warn("Producto no encontrado. ID buscado:", id, "Productos disponibles:", window.PALCUS_PRODUCTS.length);
      host.innerHTML = `<div style="padding:10rem 2rem;text-align:center;">
        <p class="font-heading" style="font-size:1.5rem;font-weight:bold;margin-bottom:1rem;">Producto no encontrado</p>
        <p style="color:var(--muted-foreground);margin-bottom:2rem;">Lo sentimos, el producto con ID <b>${id || 'nulo'}</b> no está disponible o no existe.</p>
        <a href="catalogo.html" class="btn-primary">Volver al catálogo</a>
      </div>`;
      return;
    }
    window.PalcusUtil.trackView(p.id);
    document.title = `${p.name} — PalCus Perú`;
    let designs = p.designs || [];
    let sizes = p.sizes || [];
    let colors = p.colors || [];
    let selectedSize = null, selectedColor = null, selectedDesign = null, qty = 1;

    function update() {
      const validDesigns = (selectedColor && p.imageMap) ? Object.keys(p.imageMap[selectedColor] || {}) : (designs || []);
      if (selectedColor && selectedDesign && !validDesigns.includes(selectedDesign)) {
        selectedDesign = validDesigns.length > 0 ? validDesigns[0] : null;
      }
      
      // Preparar galería (principal + secundarias + todas las de variaciones)
      const varImages = [];
      if (p.imageMap) {
        Object.values(p.imageMap).forEach(designMap => {
          Object.values(designMap).forEach(img => {
            if (img) varImages.push(img);
          });
        });
      }
      const fullGallery = Array.from(new Set([
        ...(p.image ? [p.image] : []),
        ...(p.gallery || []),
        ...varImages
      ]));

      // Determinar imagen principal
      let currentImage = p.image || varImages[0] || 'https://via.placeholder.com/800x1000?text=PalCus';
      if (selectedColor && selectedDesign && p.imageMap?.[selectedColor]?.[selectedDesign]) {
        currentImage = p.imageMap[selectedColor][selectedDesign];
      } else if (selectedColor && p.imageMap?.[selectedColor]) {
        const firstDesign = Object.keys(p.imageMap[selectedColor])[0];
        if (firstDesign) currentImage = p.imageMap[selectedColor][firstDesign];
      }
      
      let currentStock = p.stock;
      if (selectedSize || selectedColor || selectedDesign) {
        const filtered = p.variations.filter(v => 
          (!selectedSize || v.talla === selectedSize) &&
          (!selectedColor || v.color === selectedColor) &&
          (!selectedDesign || v.diseno === selectedDesign)
        );
        currentStock = filtered.reduce((acc, v) => acc + parseInt(v.stock), 0);
      }

      host.innerHTML = `
        <div class="section-padding">
          <div style="max-width:72rem;margin:0 auto;">
            <nav style="font-size:0.75rem;color:var(--muted-foreground);margin-bottom:2rem;text-transform:uppercase;letter-spacing:0.1em;">
              <a href="index.html">Inicio</a><span style="margin:0 0.5rem;">/</span><span style="color:var(--foreground);">${p.name}</span>
            </nav>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:3rem;">
              <div style="display:flex;flex-direction:column;gap:1rem;">
                <div style="background:var(--accent);aspect-ratio:3/4;overflow:hidden;border:1px solid var(--border);">
                  <img id="mainImg" src="${U.imageUrl(currentImage)}" alt="${p.name}" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                </div>
                <div style="display:grid;grid-template-columns:repeat(5, 1fr);gap:0.5rem;">
                  ${fullGallery.map(img => `
                    <div class="thumb ${img === currentImage ? 'active' : ''}" data-img="${img}" style="aspect-ratio:1/1;cursor:pointer;border:1px solid ${img === currentImage ? 'var(--foreground)' : 'var(--border)'};overflow:hidden;opacity:${img === currentImage ? '1' : '0.6'};transition:all 0.2s;">
                      <img src="${U.imageUrl(img)}" style="width:100%;height:100%;object-fit:cover;">
                    </div>
                  `).join('')}
                </div>
              </div>
              <div style="display:flex;flex-direction:column;justify-content:center;">
                ${p.tag ? `<span style="font-size:0.625rem;text-transform:uppercase;letter-spacing:0.2em;font-family:'Syne',sans-serif;font-weight:600;color:var(--muted-foreground);margin-bottom:0.5rem;">${p.tag}</span>` : ''}
                <h1 class="font-heading" style="font-size:2.25rem;font-weight:800;margin:0;">${p.name}</h1>
                <p class="font-heading" style="font-size:1.5rem;font-weight:bold;margin:0.75rem 0 0;">S/${p.price.toFixed(2)}</p>
                
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
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Talla${selectedSize ? `: ${selectedSize}` : ''}</p>
                  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    ${sizes.map(s => `<button class="pick-size" data-size="${s}" style="width:3rem;height:3rem;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:500;border:1px solid ${selectedSize===s?'var(--primary)':'var(--border)'};background:${selectedSize===s?'var(--primary)':'transparent'};color:${selectedSize===s?'var(--primary-foreground)':'var(--foreground)'};cursor:pointer;">${s}</button>`).join('')}
                  </div>
                </div>

                ${validDesigns && validDesigns.length > 0 ? `
                <div style="margin-top:2rem;">
                  <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Diseño${selectedDesign ? `: ${selectedDesign}` : ''}</p>
                  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    ${validDesigns.map(d => `<button class="pick-design" data-design="${d}" style="padding:0.5rem 1rem;font-size:0.75rem;font-weight:500;border:1px solid ${selectedDesign===d?'var(--primary)':'var(--border)'};background:${selectedDesign===d?'var(--primary)':'transparent'};color:${selectedDesign===d?'var(--primary-foreground)':'var(--foreground)'};cursor:pointer;border-radius:0.25rem;">${d}</button>`).join('')}
                  </div>
                </div>` : ''}

                <div style="margin-top:1.5rem;padding:1rem;background:var(--accent);border-radius:0.5rem;">
                  <div style="display:flex;align-items:center;gap:0.5rem;">
                    <div style="width:0.5rem;height:0.5rem;border-radius:9999px;background:${currentStock > 0 ? '#10b981' : '#ef4444'};"></div>
                    <span style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:${currentStock > 0 ? 'var(--foreground)' : '#ef4444'};">
                      ${currentStock > 0 ? `${currentStock} disponibles` : 'Agotado'}
                    </span>
                  </div>
                </div>

                <div style="margin-top:2rem;display:flex;gap:1rem;align-items:flex-end;">
                  <div style="flex:1;">
                    <p style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.15em;font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.75rem;">Cantidad</p>
                    <div style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--border);height:3rem;padding:0 0.5rem;">
                      <button id="qd" style="background:none;border:none;cursor:pointer;padding:0.5rem;">${window.PalcusIcons.minus(14)}</button>
                      <span style="font-size:1rem;font-weight:600;min-width:2rem;text-align:center;">${qty}</span>
                      <button id="qi" style="background:none;border:none;cursor:pointer;padding:0.5rem;">${window.PalcusIcons.plus(14)}</button>
                    </div>
                  </div>
                  <div style="flex:2;">
                    ${(() => {
                      const stock = currentStock;
                      const missing = !selectedSize || !selectedColor || (validDesigns.length > 0 && !selectedDesign);
                      if (stock <= 0) return `<button class="btn-primary" style="width:100%;height:3rem;background:#999;cursor:not-allowed;" disabled>Agotado</button>`;
                      return `<button id="addCart" class="btn-primary" style="width:100%;height:3rem;${missing?'opacity:0.4;cursor:not-allowed;':''}" ${missing?'disabled':''}>Agregar al carrito</button>`;
                    })()}
                  </div>
                </div>
                <a href="https://wa.me/${window.PalcusCart.PHONE}?text=${encodeURIComponent(`Hola PalCus Perú! Me interesa: ${p.name} - S/${p.price.toFixed(2)}`)}" target="_blank" rel="noopener" class="btn-outline" style="margin-top:1rem;height:3rem;display:flex;align-items:center;justify-content:center;">Consultar por WhatsApp</a>

                <!-- Accordions -->
                <div style="margin-top:3rem;border-top:1px solid var(--border);">
                  ${p.features ? `
                  <div class="accordion" style="border-bottom:1px solid var(--border);">
                    <button class="accordion-trigger" style="width:100%;padding:1.25rem 0;display:flex;align-items:center;justify-content:space-between;background:none;border:none;cursor:pointer;font-family:'Syne',sans-serif;font-weight:700;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em;">
                      Descripción y Características
                      <span class="icon" style="transition:transform 0.3s;">${window.PalcusIcons.chevronDown(16)}</span>
                    </button>
                    <div class="accordion-content" style="max-height:0;overflow:hidden;transition:all 0.3s ease-out;font-size:0.9375rem;color:var(--muted-foreground);line-height:1.6;">
                      <div style="padding-bottom:1.25rem;">${p.features.replace(/\n/g, '<br>')}</div>
                    </div>
                  </div>` : ''}
                  
                  ${p.modelInfo ? `
                  <div class="accordion" style="border-bottom:1px solid var(--border);">
                    <button class="accordion-trigger" style="width:100%;padding:1.25rem 0;display:flex;align-items:center;justify-content:space-between;background:none;border:none;cursor:pointer;font-family:'Syne',sans-serif;font-weight:700;font-size:0.875rem;text-transform:uppercase;letter-spacing:0.1em;">
                      Información del Modelo
                      <span class="icon" style="transition:transform 0.3s;">${window.PalcusIcons.chevronDown(16)}</span>
                    </button>
                    <div class="accordion-content" style="max-height:0;overflow:hidden;transition:all 0.3s ease-out;font-size:0.9375rem;color:var(--muted-foreground);line-height:1.6;">
                      <div style="padding-bottom:1.25rem;">${p.modelInfo.replace(/\n/g, '<br>')}</div>
                    </div>
                  </div>` : ''}
                </div>

              </div>
            </div>
          </div>
        </div>`;
      
      // Eventos
      host.querySelectorAll('.accordion-trigger').forEach(btn => btn.onclick = () => {
        const content = btn.nextElementSibling;
        const icon = btn.querySelector('.icon');
        const isOpen = content.style.maxHeight !== '0px' && content.style.maxHeight !== '';
        
        host.querySelectorAll('.accordion-content').forEach(c => c.style.maxHeight = '0px');
        host.querySelectorAll('.accordion-trigger .icon').forEach(i => i.style.transform = 'rotate(0deg)');

        if (!isOpen) {
          content.style.maxHeight = content.scrollHeight + 'px';
          icon.style.transform = 'rotate(180deg)';
        }
      });
      host.querySelectorAll('.thumb').forEach(t => t.onclick = () => {
        host.querySelector('#mainImg').src = U.imageUrl(t.dataset.img);
        host.querySelectorAll('.thumb').forEach(thumb => {
           thumb.style.opacity = '0.6';
           thumb.style.borderColor = 'var(--border)';
        });
        t.style.opacity = '1';
        t.style.borderColor = 'var(--foreground)';
      });

      host.querySelectorAll('.pick-color').forEach(b => b.onclick = () => { 
        selectedColor = b.dataset.color; 
        update(); 
      });
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

  // --- Lógica Global de Auto-Slider para Cards ---
  // Cicla las imágenes de los productos que tienen varios colores/fotos
  setInterval(() => {
    document.querySelectorAll('.product-slider-container').forEach(container => {
      const images = container.querySelectorAll('.slider-img');
      if (images.length <= 1) return;
      
      let activeIdx = Array.from(images).findIndex(img => img.classList.contains('active'));
      if (activeIdx === -1) activeIdx = 0;

      // Desactivar actual
      images[activeIdx].classList.remove('active');
      images[activeIdx].style.opacity = '0';
      images[activeIdx].style.zIndex = '1';
      
      // Activar siguiente
      let nextIdx = (activeIdx + 1) % images.length;
      images[nextIdx].classList.add('active');
      images[nextIdx].style.opacity = '1';
      images[nextIdx].style.zIndex = '2';
    });
  }, 3500); // Un poco más rápido para que se note el efecto

  window.PalcusRender = { renderGrid, renderProductDetail, productCard };
})();
