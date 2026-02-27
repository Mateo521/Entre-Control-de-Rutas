import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';

import Vue3Toastify from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

// 1. Configuración de Cabeceras
// Obliga al backend de Laravel a devolver respuestas JSON en lugar de intentar redirecciones web (evita el error 500 Route login not defined)
axios.defaults.headers.common['Accept'] = 'application/json';

// 2. Interceptor de Solicitudes (Request)
// Antes de que cualquier petición salga hacia el servidor, extrae el token del almacenamiento local y lo adjunta en la cabecera de autorización.
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// 3. Interceptor de Respuestas (Response)
// Escucha todas las respuestas del servidor. Si detecta un error 401 (No Autorizado / Token expirado), limpia la sesión y expulsa al usuario a la pantalla de login.
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            localStorage.removeItem('access_token');
            router.push('/');
        }
        return Promise.reject(error);
    }
);

const app = createApp(App);

app.use(router);

app.use(Vue3Toastify, {
    autoClose: 3000,  
    position: 'top-right',
    theme: 'colored',  
    clearOnUrlChange: false,
});

app.mount('#app');