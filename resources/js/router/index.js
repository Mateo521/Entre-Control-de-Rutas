import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import MainLayout from '../layouts/MainLayout.vue';
import Dashboard from '../views/Dashboard.vue';
import Tolls from '../views/Tolls.vue';
import Incidents from '../views/Incidents.vue';
import IncidentsList from '../views/IncidentsList.vue';
import AdvancedSearch from '../views/AdvancedSearch.vue';
import MapDashboard from '../views/MapDashboard.vue';

const routes = [
    {
        path: '/',
        name: 'Login',
        component: Login,
        meta: { requiresGuest: true }
    },
    {
        // El layout es la ruta principal /panel
        path: '/panel',
        component: MainLayout,
        meta: { requiresAuth: true },
        // Sus "hijos" se inyectarán en la etiqueta <router-view> del layout
        children: [
            {
                path: '', // Coincide con /panel exactamente
                name: 'Dashboard',
                component: Dashboard
            },
            {
                path: 'peajes', // Coincide con /panel/peajes
                name: 'Tolls',
                component: Tolls
            },
            {
                path: 'sucesos',
                name: 'IncidentsList',
                component: IncidentsList
            },
            {
                path: 'sucesos/nuevo',
                name: 'IncidentsCreate',
                component: Incidents
            },
            {
                path: 'busqueda',
                name: 'AdvancedSearch',
                component: AdvancedSearch
            },
            {
                path: 'mapa',
                name: 'MapDashboard',
                component: MapDashboard
            }
        ]
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('access_token');
    if (to.meta.requiresAuth && !token) { next('/'); }
    else if (to.meta.requiresGuest && token) { next('/panel'); }
    else { next(); }
});

export default router;