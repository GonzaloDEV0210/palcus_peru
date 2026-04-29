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

  byCategory: (cat) => window.PALCUS_PRODUCTS.filter(p => p.category === cat),
  byId: (id) => window.PALCUS_PRODUCTS.find(p => p.id === id),
  
  // Obtener los más nuevos
  getNewest: (limit = 3) => window.PALCUS_PRODUCTS.slice(0, limit),
  
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
    if (!key) return 'assets/placeholder.jpg';
    if (key.startsWith('http')) return key;
    if (key.includes('.')) return `assets/${key}`;
    return `assets/${key}.jpg`;
  },

  typesByCategory: (cat) => [...new Set(window.PALCUS_PRODUCTS.filter(p => p.category === cat).map(p => p.type))],
};

