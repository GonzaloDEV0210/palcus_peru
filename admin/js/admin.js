// Panel Administrativo PalCus Perú - Versión Pro
import { app } from '../../js/db.js';
import { getFirestore, collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, orderBy } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

const db = getFirestore(app);
const auth = getAuth(app);

let currentUploadedImages = [];
let selectedColors = [];
let selectedSizes = [];

// Iconos para el panel
window.PalcusIcons = {
    edit: (s=20) => `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>`,
    trash: (s=20) => `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>`
};

// 1. Seguridad: Verificar Sesión
onAuthStateChanged(auth, (user) => {
    if (!user) {
        window.location.href = 'login.html';
    } else {
        document.getElementById('adminBody').classList.remove('hidden');
        init();
    }
});

// 2. Inicialización
async function init() {
    loadCategories();
    loadProducts();
    initUploadWidget();
}

// 3. Gestión de Categorías
async function loadCategories() {
    const q = query(collection(db, "categories"), orderBy("name", "asc"));
    const snap = await getDocs(q);
    const list = document.getElementById('categoriesList');
    const select = document.getElementById('prodCategory');
    
    list.innerHTML = '';
    select.innerHTML = '<option value="">Selecciona una categoría</option>';
    
    snap.forEach(doc => {
        const cat = doc.data();
        list.innerHTML += `
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                <div>
                    <p class="font-bold uppercase text-sm">${cat.name}</p>
                    <p class="text-[10px] text-gray-400">Slug: ${cat.slug}</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="editCategory('${doc.id}', '${cat.name}', '${cat.slug}')" class="text-blue-500 text-xs font-bold">Editar</button>
                    <button onclick="deleteCategory('${doc.id}')" class="text-red-500 text-xs font-bold">Eliminar</button>
                </div>
            </div>
        `;
        select.innerHTML += `<option value="${cat.slug}">${cat.name}</option>`;
    });
}

window.deleteCategory = async (id) => {
    if(confirm('¿Estás seguro de eliminar esta categoría?')) {
        await deleteDoc(doc(db, "categories", id));
        loadCategories();
    }
};

document.getElementById('categoryForm').onsubmit = async (e) => {
    e.preventDefault();
    const name = document.getElementById('catName').value;
    const slug = document.getElementById('catSlug').value;
    const editId = document.getElementById('editCatId').value;
    
    if (editId) {
        await updateDoc(doc(db, "categories", editId), { name, slug });
    } else {
        await addDoc(collection(db, "categories"), { name, slug });
    }
    
    document.getElementById('categoryForm').reset();
    document.getElementById('editCatId').value = '';
    loadCategories();
};

window.editCategory = (id, name, slug) => {
    document.getElementById('catName').value = name;
    document.getElementById('catSlug').value = slug;
    document.getElementById('editCatId').value = id;
    document.getElementById('catName').focus();
};

// 4. Gestión de Productos
async function loadProducts() {
    const q = collection(db, "products");
    const snap = await getDocs(q);
    const list = document.getElementById('productsGrid');
    list.innerHTML = '';
    
    snap.forEach(d => {
        const p = d.data();
        list.innerHTML += `
            <div class="bg-white p-4 rounded-2xl border border-gray-100 flex gap-4 items-center">
                <img src="${p.image}" class="w-16 h-20 object-cover rounded-lg bg-gray-50">
                <div class="flex-1">
                    <p class="font-bold text-sm">${p.name}</p>
                    <p class="text-xs text-gray-400">${p.category} · S/${p.price.toFixed(2)}</p>
                    <p class="text-[10px] mt-1 ${p.stock < 5 ? 'text-red-500 font-bold' : 'text-gray-400'}">Stock: ${p.stock}</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="editProduct('${d.id}')" class="p-2 hover:bg-gray-50 rounded-lg text-blue-500">${window.PalcusIcons.edit(18)}</button>
                    <button onclick="deleteProduct('${d.id}')" class="p-2 hover:bg-gray-50 rounded-lg text-red-500">${window.PalcusIcons.trash(18)}</button>
                </div>
            </div>
        `;
    });
}

window.deleteProduct = async (id) => {
    if(confirm('¿Eliminar este producto?')) {
        await deleteDoc(doc(db, "products", id));
        loadProducts();
    }
};

// 5. Cloudinary Widget
function initUploadWidget() {
    const myWidget = cloudinary.createUploadWidget({
        cloudName: 'dv7nmkmpm', 
        uploadPreset: 'palcus_fotos',
        folder: 'palcus_assets',
        clientAllowedFormats: ["webp", "jpg", "png"],
        maxFileSize: 2000000,
        styles: {
            palette: {
                window: "#FFFFFF",
                windowBorder: "#90A0B3",
                tabIcon: "#000000",
                menuIcons: "#5A616A",
                textDark: "#000000",
                textLight: "#FFFFFF",
                link: "#000000",
                action: "#000000",
                inactiveTabIcon: "#0E1111",
                error: "#F44235",
                inProgress: "#0078FF",
                complete: "#20B832",
                sourceBg: "#E4EBF1"
            }
        }
    }, (error, result) => { 
        if (!error && result && result.event === "success") { 
            currentUploadedImages.push(result.info.secure_url);
            renderPreviews();
        }
    });

    document.getElementById("uploadWidgetBtn").addEventListener("click", () => myWidget.open(), false);
}

function renderPreviews() {
    const container = document.getElementById('uploadedImages');
    container.innerHTML = currentUploadedImages.map((url, i) => `
        <div class="relative w-20 h-24 rounded-lg overflow-hidden border">
            <img src="${url}" class="w-full h-full object-cover">
            <button type="button" onclick="removeImage(${i})" class="absolute top-0 right-0 bg-red-500 text-white text-[8px] p-1">X</button>
        </div>
    `).join('');
}

window.removeImage = (i) => {
    currentUploadedImages.splice(i, 1);
    renderPreviews();
};

// 6. Manejo de Variantes (Tallas y Colores)
window.toggleSize = (size) => {
    const idx = selectedSizes.indexOf(size);
    if (idx > -1) selectedSizes.splice(idx, 1);
    else selectedSizes.push(size);
    renderSizes();
};

function renderSizes() {
    document.querySelectorAll('.size-pill').forEach(btn => {
        if (selectedSizes.includes(btn.dataset.size)) btn.classList.add('active');
        else btn.classList.remove('active');
    });
}

window.quickAddColor = (name, hex) => {
    if (selectedColors.some(c => c.hex === hex)) return;
    selectedColors.push({ name, hex });
    renderColors();
};

window.addColor = () => {
    const picker = document.getElementById('colorPicker');
    const hexInput = document.getElementById('colorHex');
    const nameInput = document.getElementById('colorName');
    
    let hex = hexInput.value.trim() || picker.value;
    if (hex && !hex.startsWith('#')) hex = '#' + hex;
    
    if (!/^#[0-9A-F]{6}$/i.test(hex)) return alert('Código HEX inválido');
    const name = nameInput.value.trim();
    if (!name) return alert('Ingresa un nombre');
    
    selectedColors.push({ name, hex });
    renderColors();
    nameInput.value = '';
    hexInput.value = '';
};

window.removeColor = (index) => {
    selectedColors.splice(index, 1);
    renderColors();
};

function renderColors() {
    const list = document.getElementById('selectedColorList');
    list.innerHTML = selectedColors.map((c, i) => `
        <div class="tag-color">
            <span style="background: ${c.hex}"></span>
            ${c.name}
            <button type="button" onclick="removeColor(${i})">×</button>
        </div>
    `).join('');
}

window.initColorSync = () => {
    const picker = document.getElementById('colorPicker');
    const hexInput = document.getElementById('colorHex');
    if (!picker || !hexInput) return;
    picker.oninput = () => hexInput.value = picker.value.toUpperCase();
    const updateFromHex = () => {
        let val = hexInput.value.trim();
        if (val.length > 0 && !val.startsWith('#')) val = '#' + val;
        if (/^#[0-9A-F]{6}$/i.test(val)) picker.value = val;
    };
    hexInput.oninput = updateFromHex;
};

// 7. Guardar Producto
document.getElementById('productForm').onsubmit = async (e) => {
    e.preventDefault();
    const editId = document.getElementById('editId').value;
    
    const productData = {
        name: document.getElementById('prodName').value,
        price: parseFloat(document.getElementById('prodPrice').value),
        stock: parseInt(document.getElementById('prodStock').value),
        category: document.getElementById('prodCategory').value,
        description: document.getElementById('prodDesc').value,
        image: currentUploadedImages[0] || 'https://res.cloudinary.com/dv7nmkmpm/image/upload/palcus_assets/icon_logo.png',
        gallery: currentUploadedImages,
        sizes: selectedSizes,
        colors: selectedColors,
        type: 'polos',
        updatedAt: new Date(),
        active: true
    };

    if (!editId) {
        productData.createdAt = new Date();
        productData.salesCount = 0;
        await addDoc(collection(db, "products"), productData);
    } else {
        await updateDoc(doc(db, "products", editId), productData);
    }

    closeModal('productModal');
    loadProducts();
};

window.editProduct = async (id) => {
    const snap = await getDocs(query(collection(db, "products")));
    let data;
    snap.forEach(d => { if(d.id === id) data = d.data(); });
    
    if (data) {
        document.getElementById('modalTitle').innerText = 'Editar Producto';
        document.getElementById('editId').value = id;
        document.getElementById('prodName').value = data.name;
        document.getElementById('prodPrice').value = data.price;
        document.getElementById('prodStock').value = data.stock || 0;
        document.getElementById('prodCategory').value = data.category;
        document.getElementById('prodDesc').value = data.description || '';
        
        selectedSizes = data.sizes || [];
        renderSizes();
        selectedColors = data.colors || [];
        renderColors();

        currentUploadedImages = data.gallery || [data.image];
        renderPreviews();
        openModal('productModal');
    }
};

// UI Functions
window.openModal = (id) => {
    document.getElementById(id).classList.remove('hidden');
    if (id === 'productModal') initColorSync();
};

window.closeModal = (id) => { 
    document.getElementById(id).classList.add('hidden'); 
    if(id==='productModal') {
        document.getElementById('productForm').reset();
        document.getElementById('editId').value = '';
        selectedSizes = [];
        renderSizes();
        selectedColors = [];
        renderColors();
        currentUploadedImages = [];
        renderPreviews();
    }
};

window.switchTab = (tab, btn) => {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    btn.classList.add('active');
};

window.logout = () => signOut(auth);

// Vincular botones de logout
document.getElementById('logoutBtn').onclick = window.logout;
document.getElementById('logoutBtnMobile').onclick = window.logout;
