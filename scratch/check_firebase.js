const { initializeApp } = require('firebase/app');
const { getFirestore, collection, getDocs } = require('firebase/firestore');

const firebaseConfig = {
  apiKey: "AIzaSyBsfug7JA6sNZKILUoRwJ8LL3L2BflAs0A",
  authDomain: "palcus-peru.firebaseapp.com",
  projectId: "palcus-peru",
  storageBucket: "palcus-peru.firebasestorage.app",
  messagingSenderId: "387253312347",
  appId: "1:387253312347:web:571d1195bad61f3e48187e",
  measurementId: "G-06GF0Y35B2"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

async function checkData() {
    console.log("--- Verificando Colección 'products' ---");
    const snap = await getDocs(collection(db, "products"));
    console.log(`Total productos encontrados: ${snap.size}`);
    snap.forEach(doc => {
        console.log(`ID: ${doc.id} | Nombre: ${doc.data().name} | Cat: ${doc.data().category}`);
    });

    console.log("\n--- Verificando Colección 'categories' ---");
    const catSnap = await getDocs(collection(db, "categories"));
    console.log(`Total categorías encontradas: ${catSnap.size}`);
    catSnap.forEach(doc => {
        console.log(`ID: ${doc.id} | Nombre: ${doc.data().name}`);
    });
}

checkData().catch(console.error);
