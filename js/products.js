// Catálogo dinámico PalCus Perú - Conectado a Firebase
window.PALCUS_PRODUCTS = [];
window.PALCUS_CATEGORY_LABELS = {};
window.PALCUS_TYPE_LABELS = { polos:'Polos', bividis:'Bividís' };

window.PalcusDbReady = false;

window.PalcusUtil = {
  // Carga inicial desde Firebase
  init: async () => {
    const { db, collection, getDocs, query, orderBy } = window.PalcusDb;
    
    try {
      // 1. Cargar Categorías
      const catSnap = await getDocs(collection(db, "categories"));
      catSnap.forEach(doc => {
        const data = doc.data();
        window.PALCUS_CATEGORY_LABELS[data.slug] = data.name;
      });

      // 2. Cargar Productos
      const prodQuery = query(collection(db, "products"), orderBy("createdAt", "desc"));
      const prodSnap = await getDocs(prodQuery);
      window.PALCUS_PRODUCTS = [];
      prodSnap.forEach(doc => {
        window.PALCUS_PRODUCTS.push({ id: doc.id, ...doc.data() });
      });

      window.PalcusDbReady = true;
      console.log("Datos cargados desde Firestore:", window.PALCUS_PRODUCTS.length, "productos");
      
      // Disparar evento para que la interfaz sepa que ya puede renderizar
      window.dispatchEvent(new CustomEvent('palcus-data-ready'));
    } catch (error) {
      console.error("Error cargando datos:", error);
    }
  },

  byCategory: (cat) => {
    const s = (cat || "").toLowerCase();
    return window.PALCUS_PRODUCTS.filter(p => (p.category || "").toLowerCase() === s);
  },
  byId: (id) => window.PALCUS_PRODUCTS.find(p => p.id === id),
  
  // Obtener los más nuevos
  // Obtener solo lo que tenga menos de 15 días
  getNewest: (limit = 3) => {
    const now = new Date();
    return window.PALCUS_PRODUCTS.filter(p => {
      const created = p.createdAt?.toDate ? p.createdAt.toDate() : new Date(p.createdAt || now);
      const diffDays = Math.floor((now - created) / (1000 * 60 * 60 * 24));
      return diffDays <= 15;
    }).slice(0, limit);
  },
  
  // Obtener los más vendidos (ordenados por salesCount)
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

  typesByCategory: (cat) => [...new Set(window.PALCUS_PRODUCTS.filter(p => p.category === cat).map(p => p.type))],
  
  // Rastrear vista de producto
  trackView: async (id) => {
    const { db, doc, updateDoc, increment } = window.PalcusDb;
    try {
      await updateDoc(doc(db, "products", id), {
        viewCount: increment(1)
      });
    } catch(e) { console.error("Error tracking view", e); }
  }
};

