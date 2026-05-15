// Catálogo dinámico PalCus Perú
window.PALCUS_PRODUCTS = [];
window.PALCUS_CATEGORY_LABELS = {};
window.PALCUS_TYPE_LABELS = { polos:'Polos', bividis:'Bividís' };

window.PalcusDbReady = false;

window.PalcusUtil = {
  // Carga inicial desde API MySQL
  init: async () => {
    try {
      const resp = await fetch('admin/api/get_catalogo.php');
      const res = await resp.json();
      
      if (res.success) {
        window.PALCUS_PRODUCTS         = res.products;
        window.PALCUS_CATEGORY_LABELS   = res.categories;
        window.PALCUS_CATEGORIES_LIST   = res.categories_list || [];
        window.PalcusDbReady = true;
        console.log("Datos cargados desde MySQL:", window.PALCUS_PRODUCTS.length, "productos");
        window.dispatchEvent(new CustomEvent('palcus-data-ready'));
      }
    } catch (error) {
      console.error("Error cargando datos:", error);
    }
  },

  byCategory: (cat) => {
    const s = (cat || "").toLowerCase();
    return window.PALCUS_PRODUCTS.filter(p => {
      const pCat = (p.category || "").toLowerCase();
      // Coincidencia exacta o que empiece con el path (ej: hombre/manga-corta empieza con hombre)
      return pCat === s || pCat.startsWith(s + '/');
    });
  },
  byId: (id) => {
    if (!id) return null;
    const searchId = String(id).trim();
    return window.PALCUS_PRODUCTS.find(p => String(p.id).trim() == searchId);
  },
  trackView: (id) => { console.log("Vista trackeada para el producto:", id); },
  
  getNewest: (limit = 3) => {
    const now = new Date();
    return window.PALCUS_PRODUCTS.filter(p => {
      const created = new Date(p.createdAt || now);
      const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24));
      return diffDays <= 30; // 30 días para productos nuevos
    }).slice(0, limit);
  },
  
  getBestSellers: (limit = 4) => [...window.PALCUS_PRODUCTS]
    .sort((a, b) => (b.salesCount || 0) - (a.salesCount || 0))
    .slice(0, limit),

  search: (q) => {
    const s = q.toLowerCase();
    return window.PALCUS_PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(s) ||
      p.description.toLowerCase().includes(s) ||
      (window.PALCUS_CATEGORY_LABELS[p.category] || "").toLowerCase().includes(s));
  },

  imageUrl: (key) => {
    if (!key || key === 'placeholder.jpg') return 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png';
    if (key.startsWith('http')) return key;
    return `https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/${key}`;
  },

  typesByCategory: (cat) => [...new Set(window.PALCUS_PRODUCTS.filter(p => p.category === cat).map(p => p.type))]
};

