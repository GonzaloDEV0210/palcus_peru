import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getFirestore, collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, orderBy, onSnapshot } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

const firebaseConfig = {
  apiKey: "AIzaSyBsfug7JA6sNZKILUoRwJ8LL3L2BflAs0A",
  authDomain: "palcus-peru.firebaseapp.com",
  projectId: "palcus-peru",
  storageBucket: "palcus-peru.firebasestorage.app",
  messagingSenderId: "387253312347",
  appId: "1:387253312347:web:571d1195bad61f3e48187e"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

let currentUploadedImages = [];

// 1. Seguridad: Verificar Sesión
onAuthStateChanged(auth, (user) => {
    if (!user) {
        window.location.href = 'login.html';
    } else {
        document.getElementById('adminBody').classList.remove('hidden');
        initDashboard();
    }
});

document.getElementById('logoutBtn').onclick = () => signOut(auth);

// 2. Inicializar Dashboard
function initDashboard() {
    loadCategories();
    loadProducts();
    initCloudinary();
}

// 3. Gestión de Categorías
function loadCategories() {
    onSnapshot(collection(db, "categories"), (snap) => {
        const list = document.getElementById('categoriesList');
        const select = document.getElementById('prodCategory');
        list.innerHTML = '';
        select.innerHTML = '<option value="">Seleccionar categoría...</option>';
        
        snap.forEach(doc => {
            const cat = doc.data();
            list.innerHTML += `
                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-xl">
                    <div>
                        <p class="font-bold uppercase text-sm">${cat.name}</p>
                        <p class="text-[10px] text-gray-400">Slug: ${cat.slug}</p>
                    </div>
                    <button onclick="deleteCategory('${doc.id}')" class="text-red-500 text-xs font-bold">Eliminar</button>
                </div>
            `;
            select.innerHTML += `<option value="${cat.slug}">${cat.name}</option>`;
        });
    });
}

window.deleteCategory = async (id) => {
    if(confirm('¿Estás seguro de eliminar esta categoría?')) {
        await deleteDoc(doc(db, "categories", id));
    }
};

document.getElementById('categoryForm').onsubmit = async (e) => {
    e.preventDefault();
    const name = document.getElementById('catName').value;
    const slug = document.getElementById('catSlug').value;
    await addDoc(collection(db, "categories"), { name, slug });
    e.target.reset();
    closeModal('categoryModal');
};

// 4. Gestión de Productos
function loadProducts() {
    onSnapshot(query(collection(db, "products"), orderBy("createdAt", "desc")), (snap) => {
        const grid = document.getElementById('productsGrid');
        grid.innerHTML = '';
        
        snap.forEach(docSnap => {
            const p = docSnap.data();
            const id = docSnap.id;
            grid.innerHTML += `
                <div class="card overflow-hidden group">
                    <div class="aspect-[3/4] relative overflow-hidden bg-gray-100">
                        <img src="${p.image.startsWith('http') ? p.image : '../assets/'+p.image}" class="absolute inset-0 w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button onclick="editProduct('${id}')" class="bg-white p-2 rounded-full text-black hover:scale-110 transition-transform">✏️</button>
                            <button onclick="deleteProduct('${id}')" class="bg-white p-2 rounded-full text-red-500 hover:scale-110 transition-transform">🗑️</button>
                        </div>
                    </div>
                    <div class="p-4">
                        <p class="text-[10px] uppercase font-bold text-gray-400">${p.category}</p>
                        <h3 class="font-heading font-bold text-sm truncate">${p.name}</h3>
                        <p class="font-bold text-sm mt-1">S/${p.price.toFixed(2)}</p>
                    </div>
                </div>
            `;
        });
    });
}

window.deleteProduct = async (id) => {
    if(confirm('¿Seguro que deseas eliminar este producto?')) {
        await deleteDoc(doc(db, "products", id));
    }
};

// 5. Cloudinary Widget
function initCloudinary() {
    const myWidget = cloudinary.createUploadWidget({
        cloudName: 'dv7nmkmpm', 
        uploadPreset: 'placus_fotos',
        sources: ['local', 'url', 'camera'],
        multiple: true,
        defaultSource: 'local',
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
            const url = result.info.secure_url;
            currentUploadedImages.push(url);
            renderPreviews();
        }
    });

    document.getElementById("uploadWidgetBtn").addEventListener("click", function(){
        myWidget.open();
    }, false);
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

// 6. Guardar Producto
document.getElementById('productForm').onsubmit = async (e) => {
    e.preventDefault();
    const editId = document.getElementById('editId').value;
    
    const productData = {
        name: document.getElementById('prodName').value,
        price: parseFloat(document.getElementById('prodPrice').value),
        category: document.getElementById('prodCategory').value,
        description: document.getElementById('prodDesc').value,
        image: currentUploadedImages[0] || 'placeholder.jpg',
        gallery: currentUploadedImages,
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
    currentUploadedImages = [];
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
        document.getElementById('prodCategory').value = data.category;
        document.getElementById('prodDesc').value = data.description || '';
        currentUploadedImages = data.gallery || [data.image];
        renderPreviews();
        openModal('productModal');
    }
};

// Exponer funciones de UI
window.openModal = (id) => document.getElementById(id).classList.remove('hidden');
window.closeModal = (id) => { 
    document.getElementById(id).classList.add('hidden'); 
    if(id==='productModal') {
        document.getElementById('productForm').reset();
        document.getElementById('editId').value = '';
        document.getElementById('uploadedImages').innerHTML = '';
        currentUploadedImages = [];
    }
};
window.switchTab = (tab, btn) => {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active', 'text-gray-500'));
    btn.classList.add('active');
};

