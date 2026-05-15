// Renderizadores de páginas (grids de categoría, detalle de producto)
(function () {
  const U = window.PalcusUtil;
  const PLACEHOLDER = 'https://res.cloudinary.com/dv7nmkmpm/image/upload/v1778354037/vjypdweg16udzxoptdxz.png';

  /* ── Extraer imágenes de variaciones (JSON o string) ── */
  function extractImages(p) {
    const varImages = [];
    (p.variations || []).forEach(v => {
      if (v.gallery && Array.isArray(v.gallery)) varImages.push(...v.gallery);
      else if (v.image) varImages.push(v.image);
      else if (v.imagen_url) {
        try {
          const parsed = JSON.parse(v.imagen_url);
          if (Array.isArray(parsed)) varImages.push(...parsed);
          else varImages.push(v.imagen_url);
        } catch(e) { varImages.push(v.imagen_url); }
      }
    });
    const all = [...new Set([p.image, ...varImages, ...(p.gallery||[])])].filter(
      img => img && typeof img === 'string' && img.startsWith('http')
    );
    return all.length ? all : [PLACEHOLDER];
  }

  /* ── TARJETA DE PRODUCTO ── */
  function productCard(p) {
    const colors = p.colors || [];
    const sizes  = p.sizes  || [];
    const imgs   = extractImages(p);
    const now    = new Date();
    const diffDays = Math.floor((now - new Date(p.createdAt||now)) / 86400000);
    let badge = '';
    if (diffDays <= 15)              badge = `<div class="product-tag" style="z-index:10;">Lo más Nuevo</div>`;
    else if ((p.stock||0) < 5 && (p.stock||0) > 0) badge = `<div class="product-tag" style="z-index:10;">Últimas unidades</div>`;

    return `
      <a href="producto.html?id=${p.id}" class="product-card" data-product-id="${p.id}">
        <div class="img-wrap">
          ${badge}
          <div class="product-slider-container">
            ${imgs.map((img,i) => `<img src="${U.imageUrl(img)}" class="slider-img ${i===0?'active':''}"
              style="opacity:${i===0?1:0};z-index:${i===0?2:1};transition:opacity 1.5s ease;position:absolute;inset:0;width:100%;height:100%;object-fit:cover;"
              alt="${p.name}" loading="lazy">`).join('')}
          </div>
        </div>
        <div style="padding:.875rem .25rem .5rem;">
          <h3 style="font-size:.8125rem;font-weight:400;margin:0;color:var(--foreground);letter-spacing:.01em;">${p.name}</h3>
          <p style="font-size:.8125rem;font-weight:600;margin:.3rem 0 0;color:var(--foreground);">S/${(p.price||0).toFixed(2)}</p>
          <div style="display:flex;gap:.35rem;margin-top:.6rem;flex-wrap:wrap;">
            ${colors.map(c=>`<span title="${c.name}" style="width:10px;height:10px;border-radius:50%;background:${c.hex};border:1px solid rgba(0,0,0,.15);flex-shrink:0;"></span>`).join('')}
          </div>
        </div>
      </a>`;
  }

  function renderGrid(containerId, products) {
    const el = document.getElementById(containerId);
    if (el) el.innerHTML = products.map(productCard).join('');
  }

  /* ══════════════════════════════════════════════════════
     DETALLE DE PRODUCTO — Inspiración NET-A-PORTER / COS
  ══════════════════════════════════════════════════════ */
  function renderProductDetail() {
    const params = new URLSearchParams(location.search);
    const id = params.get('id');
    const p  = U.byId(id);
    const host = document.getElementById('product-detail');
    if (!host) return;

    if (!p) {
      host.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;min-height:60vh;flex-direction:column;gap:1.5rem;text-align:center;padding:2rem;">
        <p style="font-size:1rem;color:var(--muted-foreground);">Este producto no está disponible.</p>
        <a href="catalogo.html" class="btn-primary" style="font-size:.8rem;">Ver catálogo</a>
      </div>`;
      return;
    }

    U.trackView(p.id);
    document.title = `${p.name} — PalCus Perú`;

    const allImgs  = extractImages(p);
    const sizes    = p.sizes  || [];
    const colors   = p.colors || [];
    const designs  = p.designs || [];

    let selSize = null, selColor = null, selDesign = null, qty = 1;

    const CSS = `
      <style>
        #pd-outer{max-width:1020px;margin:0 auto;padding:3rem 2rem 5rem;}
        #pd-wrap{display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:start;}
        @media(max-width:760px){#pd-wrap{grid-template-columns:1fr;gap:2rem;}}
        #pd-gallery{align-self:start;}
        #pd-main-img{width:100%;aspect-ratio:3/4;object-fit:cover;display:block;border-radius:4px;background:var(--accent);}
        .pd-thumbs{display:flex;gap:.5rem;margin-top:.6rem;flex-wrap:wrap;}
        .pd-thumb{width:4rem;height:4rem;object-fit:cover;cursor:pointer;border-radius:3px;opacity:.5;transition:opacity .2s,border-color .2s;border:1.5px solid transparent;flex-shrink:0;}
        .pd-thumb.active{opacity:1;border-color:var(--foreground);}
        .pd-thumb:hover:not(.active){opacity:.8;}
        #pd-info{padding-top:.5rem;align-self:start;}
        .pd-label{font-size:.625rem;font-weight:700;text-transform:uppercase;letter-spacing:.18em;color:var(--muted-foreground);margin:0 0 .5rem;}
        .pd-section{margin-bottom:2rem;}
        .pd-swatch{width:1.5rem;height:1.5rem;border-radius:50%;cursor:pointer;border:1.5px solid transparent;transition:border-color .2s,transform .2s;flex-shrink:0;}
        .pd-swatch.sel{border-color:var(--foreground);transform:scale(1.15);}
        .pd-swatch:hover:not(.sel){border-color:var(--muted-foreground);}
        .pd-pill{display:inline-flex;align-items:center;height:2rem;padding:0 .875rem;font-size:.75rem;font-weight:500;letter-spacing:.04em;cursor:pointer;border:1px solid var(--border);transition:all .15s;background:transparent;color:var(--foreground);}
        .pd-pill.sel{background:var(--foreground);color:var(--background);}
        .pd-pill:hover:not(.sel){border-color:var(--foreground);}
        .pd-cta{display:flex;align-items:center;justify-content:center;width:100%;height:2.875rem;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;border:none;cursor:pointer;background:var(--foreground);color:var(--background);transition:opacity .2s;}
        .pd-cta:hover{opacity:.82;}
        .pd-cta.ghost{background:transparent;border:1px solid var(--border);color:var(--foreground);text-decoration:none;margin-top:.5rem;}
        .pd-cta.ghost:hover{border-color:var(--foreground);}
        .pd-qty{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;}
        .pd-qty button{background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--foreground);padding:.25rem .5rem;opacity:.5;transition:opacity .2s;}
        .pd-qty button:hover{opacity:1;}
        .pd-qty span{font-size:.875rem;font-weight:600;min-width:1.5rem;text-align:center;}
        .pd-divider{border:none;border-top:1px solid var(--border);margin:1.75rem 0;}
        .pd-acc-btn{all:unset;width:100%;display:flex;justify-content:space-between;align-items:center;padding:.875rem 0;cursor:pointer;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.14em;box-sizing:border-box;}
        .pd-acc-body{overflow:hidden;max-height:0;transition:max-height .3s ease;font-size:.8125rem;line-height:1.75;color:var(--muted-foreground);}
        @keyframes pdShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
        .pd-err{animation:pdShake .3s ease;}
      </style>`;

    function getStock() {
      if (!selSize && !selColor) return p.stock || 0;
      return (p.variations||[]).filter(v=>(!selColor||v.color===selColor)&&(!selSize||v.talla===selSize))
                               .reduce((s,v)=>s+parseInt(v.stock||0),0);
    }

    function getActiveImg() {
      if (selColor && selDesign && p.imageMap?.[selColor]?.[selDesign]) return p.imageMap[selColor][selDesign];
      if (selColor && p.imageMap?.[selColor]) { const k=Object.keys(p.imageMap[selColor])[0]; if(k) return p.imageMap[selColor][k]; }
      return allImgs[0];
    }

    function render() {
      const stock    = getStock();
      const activeImg = getActiveImg();
      const validDesigns = selColor && p.imageMap ? Object.keys(p.imageMap[selColor]||{}) : designs;
      if (selColor && validDesigns.length && !selDesign) selDesign = validDesigns[0];

      const stockLine = stock > 0
        ? `<span style="font-size:.7rem;color:#16a34a;font-weight:600;text-transform:uppercase;letter-spacing:.1em;">${stock} disponibles</span>`
        : `<span style="font-size:.7rem;color:#dc2626;font-weight:600;text-transform:uppercase;letter-spacing:.1em;">Sin stock</span>`;

      host.innerHTML = CSS + `
        <div id="pd-outer">
        <div id="pd-wrap">

          <!-- GALERÍA -->
          <div id="pd-gallery">
            <img id="pd-main-img" src="${U.imageUrl(activeImg)}" alt="${p.name}">
            ${allImgs.length > 1 ? `
            <div class="pd-thumbs">
              ${allImgs.map(img => `
                <img class="pd-thumb ${img===activeImg?'active':''}"
                     src="${U.imageUrl(img)}" data-img="${img}" alt="${p.name}" loading="lazy">
              `).join('')}
            </div>` : ''}
          </div>

          <!-- INFO -->
          <div id="pd-info">

            <!-- Breadcrumb -->
            <p style="font-size:.625rem;text-transform:uppercase;letter-spacing:.18em;color:var(--muted-foreground);margin:0 0 2.5rem;">
              <a href="index.html" style="color:inherit;text-decoration:none;">Inicio</a>
              &nbsp;/&nbsp;${p.name}
            </p>

            <!-- Nombre -->
            <h1 style="font-size:1.375rem;font-weight:500;letter-spacing:-.01em;line-height:1.25;margin:0 0 1rem;">${p.name}</h1>

            <!-- Precio + stock -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;">
              <span style="font-size:1.125rem;font-weight:600;">S/&nbsp;${p.price.toFixed(2)}</span>
              ${stockLine}
            </div>

            <hr class="pd-divider" style="margin-top:0;">

            <!-- Color -->
            ${colors.length ? `
            <div class="pd-section" id="pd-color-sec">
              <p class="pd-label">Color${selColor?` — ${selColor}`:''}</p>
              <div style="display:flex;gap:.625rem;flex-wrap:wrap;">
                ${colors.map(c=>`<button class="pd-swatch ${selColor===c.name?'sel':''}" data-color="${c.name}"
                  style="background:${c.hex};" title="${c.name}"></button>`).join('')}
              </div>
            </div>` : ''}

            <!-- Talla -->
            ${sizes.length ? `
            <div class="pd-section" id="pd-size-sec">
              <p class="pd-label">Talla</p>
              <div style="display:flex;gap:.375rem;flex-wrap:wrap;">
                ${sizes.map(s=>`<button class="pd-pill ${selSize===s?'sel':''}" data-size="${s}">${s}</button>`).join('')}
              </div>
            </div>` : ''}

            <!-- Cantidad -->
            <div class="pd-section">
              <p class="pd-label">Cantidad</p>
              <div class="pd-qty">
                <button id="pd-qd">−</button>
                <span id="pd-qty">${qty}</span>
                <button id="pd-qi">+</button>
              </div>
            </div>

            <!-- CTA -->
            <button id="pd-add" class="pd-cta">
              ${stock > 0 ? 'Agregar al carrito' : 'Sin disponibilidad'}
            </button>
            <a href="https://wa.me/${window.PalcusCart.PHONE}?text=${encodeURIComponent(`Hola PalCus! Me interesa: ${p.name} — S/${p.price.toFixed(2)}`)}"
               target="_blank" rel="noopener" class="pd-cta ghost">
              ${window.PalcusIcons.whatsapp(14)}&nbsp;&nbsp;Consultar por WhatsApp
            </a>

            <hr class="pd-divider">

            <!-- Combinaciones -->
            <div class="pd-section">
              <p class="pd-label" style="margin-bottom:.75rem;">Combínalo con</p>
              <p style="font-size:.8rem;color:var(--muted-foreground);line-height:1.8;margin:0;">
                Jeans &nbsp;·&nbsp; Faldas &nbsp;·&nbsp; Shorts &nbsp;·&nbsp; Joggers
              </p>
            </div>

            <!-- Accordions -->
            ${p.features?`
            <div style="border-top:1px solid var(--border);">
              <button class="pd-acc-btn" data-target="feat">Características <span>+</span></button>
              <div class="pd-acc-body" id="acc-feat"><div style="padding-bottom:1.25rem;">${p.features.replace(/\n/g,'<br>')}</div></div>
            </div>`:``}
            ${p.modelInfo?`
            <div style="border-top:1px solid var(--border);">
              <button class="pd-acc-btn" data-target="model">Información del modelo <span>+</span></button>
              <div class="pd-acc-body" id="acc-model"><div style="padding-bottom:1.25rem;">${p.modelInfo.replace(/\n/g,'<br>')}</div></div>
            </div>`:``}

          </div>
        </div><!-- pd-wrap -->
        </div><!-- pd-outer -->`;

      /* — EVENTOS — */
      host.querySelectorAll('.pd-thumb').forEach(img => img.onclick = () => {
        host.querySelector('#pd-main-img').src = U.imageUrl(img.dataset.img);
        host.querySelectorAll('.pd-thumb').forEach(x => x.classList.remove('active'));
        img.classList.add('active');
      });
      host.querySelectorAll('.pd-swatch').forEach(b=>b.onclick=()=>{ selColor=b.dataset.color; render(); });
      host.querySelectorAll('.pd-pill'  ).forEach(b=>b.onclick=()=>{ selSize =b.dataset.size;  render(); });
      host.querySelector('#pd-qd').onclick=()=>{ qty=Math.max(1,qty-1); host.querySelector('#pd-qty').textContent=qty; };
      host.querySelector('#pd-qi').onclick=()=>{ qty++; host.querySelector('#pd-qty').textContent=qty; };

      host.querySelector('#pd-add').onclick=()=>{
        if (stock<=0) return;
        if (!selColor && colors.length) {
          const s=document.getElementById('pd-color-sec'); s.classList.add('pd-err'); s.scrollIntoView({block:'center',behavior:'smooth'}); setTimeout(()=>s.classList.remove('pd-err'),350); return;
        }
        if (!selSize && sizes.length) {
          const s=document.getElementById('pd-size-sec'); s.classList.add('pd-err'); s.scrollIntoView({block:'center',behavior:'smooth'}); setTimeout(()=>s.classList.remove('pd-err'),350); return;
        }
        window.PalcusCart.add({...p,image:getActiveImg()}, selSize, selColor, selDesign, qty);
        const btn=host.querySelector('#pd-add');
        btn.textContent='✓  Agregado';
        setTimeout(()=>{ btn.textContent='Agregar al carrito'; window.PalcusLayout.openCart(); },600);
      };

      host.querySelectorAll('.pd-acc-btn').forEach(btn=>{
        const body=document.getElementById('acc-'+btn.dataset.target);
        const ico=btn.querySelector('span');
        btn.onclick=()=>{
          const open=body.style.maxHeight&&body.style.maxHeight!=='0px';
          body.style.maxHeight=open?'0px':body.scrollHeight+'px';
          ico.textContent=open?'+':'−';
        };
      });
    }

    render();
  }

  /* Auto-Slider tarjetas */
  setInterval(()=>{
    document.querySelectorAll('.product-slider-container').forEach(c=>{
      const imgs=c.querySelectorAll('.slider-img');
      if(imgs.length<=1)return;
      let ai=Array.from(imgs).findIndex(i=>i.classList.contains('active'));
      if(ai===-1)ai=0;
      imgs[ai].classList.remove('active');imgs[ai].style.opacity='0';imgs[ai].style.zIndex='1';
      const ni=(ai+1)%imgs.length;
      imgs[ni].classList.add('active');imgs[ni].style.opacity='1';imgs[ni].style.zIndex='2';
    });
  },3500);

  window.PalcusRender={renderGrid,renderProductDetail,productCard};
})();
