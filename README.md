# PalCus Perú — Sitio estático

Versión 100% HTML + Tailwind (CDN) + JavaScript vanilla.

## Estructura

```
palcus-peru-static/
├── index.html              ← Home
├── mujeres.html / varones.html / ninos.html / ninas.html
├── producto.html?id=...    ← Detalle de producto
├── faq.html
├── devoluciones.html
├── libro-reclamaciones.html
├── css/styles.css          ← Tokens de diseño + animaciones
├── js/
│   ├── products.js         ← Catálogo (datos)
│   ├── icons.js            ← SVGs (WhatsApp, Lucide, redes)
│   ├── cart.js             ← Carrito + código de compra (localStorage)
│   ├── render.js           ← Render de grids y detalle de producto
│   └── layout.js           ← Header, Footer, Drawer, Modal y FAQ flotante
└── assets/                 ← Imágenes
```

## Cómo abrirlo

Abre `index.html` directamente en el navegador, o mejor con un servidor local:

```bash
# Python 3
python3 -m http.server 8000
# Node
npx serve .
```

Luego visita http://localhost:8000

## Características incluidas

- **Diseño**: Monocromático (blanco/negro/gris), fuentes Syne + DM Sans.
- **Carrito**: Persistente con `localStorage`, drawer lateral animado.
- **Código de compra**: Cada pedido genera un código único `PC-XXXXXX-YYYY` que se incluye en el mensaje de WhatsApp y se puede copiar.
- **WhatsApp**: Icono oficial en todos los lugares (carrito, footer, botón flotante, detalle).
- **Botón FAQ flotante**: Encima del de WhatsApp, abre un mini-chat con respuestas rápidas.
- **Búsqueda**: Modal con búsqueda en vivo del catálogo.
- **Navegación**: Menús desplegables por categoría con animaciones de subrayado.
- **SEO**: Meta tags y Open Graph por página.

## Personalización

- **Teléfono WhatsApp**: cambia `PHONE` en `js/cart.js` y `js/layout.js` (51999999999).
- **Productos**: edita `js/products.js`.
- **Colores/fuentes**: ajusta los tokens en `:root` de `css/styles.css`.
