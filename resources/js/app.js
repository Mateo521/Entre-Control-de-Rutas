import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// 1. Importar la librería y sus estilos
import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const app = createApp(App);

app.use(router);

// 2. Configurar el comportamiento global de los Toasts
app.use(Vue3Toastify, {
    autoClose: 3000, // Se cierra a los 3 segundos
    position: 'top-right',
    theme: 'colored', // Aplica un fondo sólido según el tipo (éxito, error)
    clearOnUrlChange: false,
});

app.mount('#app');