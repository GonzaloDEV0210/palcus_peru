// Catálogo de productos PalCus Perú
window.PALCUS_PRODUCTS = [
  // MUJERES
  { id: 'casaca-negra-mujer', name: 'Casaca Casual Negra', price: 149.90, category: 'mujeres', type: 'casacas',
    sizes: ['XS','S','M','L','XL'],
    colors: [{name:'Negro',hex:'#1a1a1a'},{name:'Gris Oscuro',hex:'#4a4a4a'},{name:'Beige',hex:'#d4b896'}],
    image: 'product-casaca-mujer',
    description: 'Casaca casual negra confeccionada con algodón peruano premium. Perfecta para el día a día.',
    tag: 'Nuevo' },
  { id: 'polo-gris-mujer', name: 'Polo Casual Gris', price: 59.90, category: 'mujeres', type: 'polos',
    sizes: ['XS','S','M','L','XL'],
    colors: [{name:'Gris',hex:'#8c8c8c'},{name:'Blanco',hex:'#f5f5f5'},{name:'Rosa',hex:'#e8b4b8'},{name:'Negro',hex:'#1a1a1a'}],
    image: 'product-polo-mujer',
    description: 'Polo casual gris de algodón peruano 100%. Suave, cómodo y versátil.',
    tag: 'Best Seller' },
  { id: 'jean-mujer', name: 'Jean Casual Mujer', price: 119.90, category: 'mujeres', type: 'pantalones',
    sizes: ['S','M','L','XL'],
    colors: [{name:'Azul Clásico',hex:'#4a6fa5'},{name:'Azul Oscuro',hex:'#2c3e6b'},{name:'Negro',hex:'#1a1a1a'}],
    image: 'product-jean-mujer',
    description: 'Jean casual de corte recto. Tela resistente con mezcla de algodón peruano.' },
  { id: 'bividi-blanca-mujer', name: 'Bividí Blanca Mujer', price: 39.90, category: 'mujeres', type: 'bividis',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'},{name:'Beige',hex:'#d4b896'}],
    image: 'product-bividi-mujer',
    description: 'Bividí básica blanca de algodón peruano. Esencial para cualquier outfit casual.' },
  // VARONES
  { id: 'casaca-bomber-varon', name: 'Casaca Bomber Negra', price: 159.90, category: 'varones', type: 'casacas',
    sizes: ['S','M','L','XL','XXL'],
    colors: [{name:'Negro',hex:'#1a1a1a'},{name:'Verde Militar',hex:'#556b2f'},{name:'Azul Marino',hex:'#1e3a5f'}],
    image: 'product-casaca-varon',
    description: 'Casaca bomber negra de algodón peruano. Estilo urbano y máxima comodidad.',
    tag: 'Popular' },
  { id: 'polo-blanco-varon', name: 'Polo Clásico Blanco', price: 59.90, category: 'varones', type: 'polos',
    sizes: ['S','M','L','XL','XXL'],
    colors: [{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'},{name:'Gris',hex:'#8c8c8c'},{name:'Azul Marino',hex:'#1e3a5f'}],
    image: 'product-polo-varon',
    description: 'Polo clásico blanco de algodón pima peruano. Básico premium para tu día a día.',
    tag: 'Nuevo' },
  { id: 'jean-slim-varon', name: 'Jean Slim Fit', price: 129.90, category: 'varones', type: 'pantalones',
    sizes: ['S','M','L','XL'],
    colors: [{name:'Azul Clásico',hex:'#4a6fa5'},{name:'Negro',hex:'#1a1a1a'},{name:'Gris',hex:'#6b6b6b'}],
    image: 'product-jean-varon',
    description: 'Jean slim fit con mezcla de algodón peruano. Comodidad y estilo en cada paso.' },
  { id: 'bividi-gris-varon', name: 'Bividí Gris Varón', price: 34.90, category: 'varones', type: 'bividis',
    sizes: ['S','M','L','XL','XXL'],
    colors: [{name:'Gris',hex:'#8c8c8c'},{name:'Blanco',hex:'#f5f5f5'},{name:'Negro',hex:'#1a1a1a'}],
    image: 'product-bividi-varon',
    description: 'Bividí gris de algodón peruano. Fresca, cómoda y perfecta para el verano.' },
  // NIÑOS
  { id: 'casaca-hoodie-nino', name: 'Casaca Hoodie Niño', price: 89.90, category: 'ninos', type: 'casacas',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Azul',hex:'#4a90d9'},{name:'Rojo',hex:'#c0392b'},{name:'Verde',hex:'#27ae60'}],
    image: 'product-casaca-nino',
    description: 'Casaca hoodie colorida para niños. Algodón peruano suave y resistente.',
    tag: 'Nuevo' },
  { id: 'jean-casual-nino', name: 'Jean Casual Niño', price: 69.90, category: 'ninos', type: 'pantalones',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Azul Clásico',hex:'#4a6fa5'},{name:'Azul Oscuro',hex:'#2c3e6b'}],
    image: 'product-jean-nino',
    description: 'Jean casual para niños. Resistente y cómodo para el día a día.',
    tag: 'Popular' },
  // NIÑAS
  { id: 'casaca-rosa-nina', name: 'Casaca Rosa Niña', price: 84.90, category: 'ninas', type: 'casacas',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Rosa',hex:'#e8b4b8'},{name:'Lila',hex:'#b19cd9'},{name:'Blanco',hex:'#f5f5f5'}],
    image: 'product-casaca-nina',
    description: 'Casaca rosa con capucha para niñas. Algodón peruano suave y abrigador.',
    tag: 'Nuevo' },
  { id: 'legging-nina', name: 'Legging Básico Niña', price: 44.90, category: 'ninas', type: 'leggings',
    sizes: ['XS','S','M','L'],
    colors: [{name:'Negro',hex:'#1a1a1a'},{name:'Rosa',hex:'#e8b4b8'},{name:'Gris',hex:'#8c8c8c'}],
    image: 'product-legging-nina',
    description: 'Legging básico para niñas. 100% algodón peruano, cómodo y elástico.' },
];

window.PALCUS_CATEGORY_LABELS = { mujeres: 'Mujeres', varones: 'Varones', ninos: 'Niños', ninas: 'Niñas' };
window.PALCUS_TYPE_LABELS = { polos:'Polos', casacas:'Casacas', pantalones:'Pantalones', shorts:'Shorts', bividis:'Bividís', leggings:'Leggings' };

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
