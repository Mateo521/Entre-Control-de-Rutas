import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue'; // 1. Importamos el nuevo componente

const routes = [
    {
        path: '/',
        name: 'Login',
        component: Login
    },
    {
        path: '/panel',            // 2. Definimos la URL
        name: 'Dashboard',
        component: Dashboard       // 3. Le asignamos el componente
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;