// Configuración de Firebase - PalCus Perú
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
import { getFirestore, collection, getDocs, addDoc, updateDoc, deleteDoc, doc, query, orderBy, limit, increment } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";
import { getAuth, signInWithEmailAndPassword, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

const firebaseConfig = {
  apiKey: "AIzaSyBsfug7JA6sNZKILUoRwJ8LL3L2BflAs0A",
  authDomain: "palcus-peru.firebaseapp.com",
  projectId: "palcus-peru",
  storageBucket: "palcus-peru.firebasestorage.app",
  messagingSenderId: "387253312347",
  appId: "1:387253312347:web:571d1195bad61f3e48187e",
  measurementId: "G-06GF0Y35B2"
};

// Inicializar Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

// Exportar funciones útiles
window.PalcusDb = {
  db,
  auth,
  collection,
  getDocs,
  addDoc,
  updateDoc,
  deleteDoc,
  doc,
  query,
  orderBy,
  limit,
  increment,
  signIn: signInWithEmailAndPassword,
  onAuth: onAuthStateChanged,
  logout: signOut
};

console.log("Firebase conectado correctamente");

export { app, db, auth };
