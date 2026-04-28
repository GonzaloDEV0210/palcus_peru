// Catálogo de productos PalCus Perú - Exclusivo Polos de Mujer
window.PALCUS_PRODUCTS = [
  // BÁSICOS
  { id: 'polo-basico-blanco', name: 'Polo Básico Blanco', price: 49.90, category: 'basicos', type: 'polos',
    sizes: ['XS','S','M','L','XL'],
    colors: [{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'},{name:'Gris',hex:'#8c8c8c'}],
    image: 'product-tshirt-white',
    description: 'Polo básico blanco de algodón pima peruano 100%. Esencial, suave y combinable con todo.',
    tag: 'Best Seller' },
  { id: 'polo-basico-negro', name: 'Polo Básico Negro', price: 49.90, category: 'basicos', type: 'polos',
    sizes: ['XS','S','M','L','XL'],
    colors: [{name:'Negro',hex:'#1a1a1a'},{name:'Blanco',hex:'#f5f5f5'},{name:'Azul Marino',hex:'#1e3a5f'}],
    image: 'product-polo-mujer',
    description: 'Polo básico negro cuello redondo. Comodidad absoluta para el día a día.' },
  
  // OVERSIZE
  { id: 'polo-oversize-gris', name: 'Polo Oversize Gris', price: 59.90, category: 'oversize', type: 'polos',
    sizes: ['S','M','L'],
    colors: [{name:'Gris',hex:'#8c8c8c'},{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'}],
    image: 'product-polo-gray',
    description: 'Polo oversize en tono gris. Corte holgado y moderno, fabricado con algodón peruano.',
    tag: 'Nuevo' },
  { id: 'polo-oversize-rosa', name: 'Polo Oversize Rosa', price: 59.90, category: 'oversize', type: 'polos',
    sizes: ['S','M','L'],
    colors: [{name:'Rosa',hex:'#e8b4b8'},{name:'Lila',hex:'#b19cd9'}],
    image: 'product-blouse-white',
    description: 'Polo oversize rosa pastel. Perfecto para un look relajado y aesthetic.',
    tag: 'Popular' },

  // ESTAMPADOS
  { id: 'polo-estampado-vintage', name: 'Polo Estampado Vintage', price: 65.90, category: 'estampados', type: 'polos',
    sizes: ['S','M','L','XL'],
    colors: [{name:'Beige',hex:'#d4b896'},{name:'Blanco',hex:'#f5f5f5'}],
    image: 'product-polo-varon',
    description: 'Polo con estampado vintage exclusivo. Calidad premium que no destiñe.' },
  { id: 'polo-estampado-floral', name: 'Polo Estampado Floral', price: 65.90, category: 'estampados', type: 'polos',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'}],
    image: 'product-polo-mujer',
    description: 'Polo con sutil estampado floral. Fresco y elegante para la temporada.',
    tag: 'Nuevo' },

  // DEPORTIVOS
  { id: 'polo-deportivo-dry', name: 'Polo Deportivo Dry', price: 55.90, category: 'deportivos', type: 'polos',
    sizes: ['S','M','L','XL'],
    colors: [{name:'Negro',hex:'#1a1a1a'},{name:'Blanco',hex:'#f5f5f5'},{name:'Rosa',hex:'#e8b4b8'}],
    image: 'product-polo-gray',
    description: 'Polo deportivo ligero y transpirable. Ideal para tus rutinas de ejercicio.' },
  { id: 'bividi-deportivo', name: 'Bividí Deportivo', price: 39.90, category: 'deportivos', type: 'bividis',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'},{name:'Gris',hex:'#8c8c8c'}],
    image: 'product-bividi-mujer',
    description: 'Bividí deportivo ajustado. Movilidad total y confort asegurado.' },
];

window.PALCUS_CATEGORY_LABELS = { basicos: 'Básicos', oversize: 'Oversize', estampados: 'Estampados', deportivos: 'Deportivos' };
window.PALCUS_TYPE_LABELS = { polos:'Polos', bividis:'Bividís' };

window.PalcusUtil = {
  byCategory: (cat) => window.PALCUS_PRODUCTS.filter(p => p.category === cat),
  byId: (id) => window.PALCUS_PRODUCTS.find(p => p.id === id),
  search: (q) => {
    const s = q.toLowerCase();
    return window.PALCUS_PRODUCTS.filter(p =>
      p.name.toLowerCase().includes(s) ||
      p.description.toLowerCase().includes(s) ||
      window.PALCUS_CATEGORY_LABELS[p.category].toLowerCase().includes(s));
  },
  imageUrl: (key) => `assets/${key}.jpg`,
  typesByCategory: (cat) => [...new Set(window.PALCUS_PRODUCTS.filter(p => p.category === cat).map(p => p.type))],
};
