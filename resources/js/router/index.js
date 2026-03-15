import { createRouter, createWebHistory } from "vue-router";
import Login from "../views/Login.vue";
import MainLayout from "../layouts/MainLayout.vue";
import Dashboard from "../views/Dashboard.vue";
import Tolls from "../views/Tolls.vue";
import Incidents from "../views/Incidents.vue";
import IncidentsList from "../views/IncidentsList.vue";
import AdvancedSearch from "../views/AdvancedSearch.vue";
import MapDashboard from "../views/MapDashboard.vue";
import MediaGallery from "../views/MediaGallery.vue";
import InventoryDashboard from "../views/InventoryDashboard.vue";
import BacheoDashboard from "../views/BacheoDashboard.vue";
import PasterosDashboard from "../views/PasterosDashboard.vue";
import NotesDashboard from "../views/NotesDashboard.vue";
const routes = [
    {
        path: "/",
        name: "Login",
        component: Login,
        meta: { requiresGuest: true },
    },
    {
        path: "/panel",
        component: MainLayout,
        meta: { requiresAuth: true },

        children: [
            {
                path: "",
                name: "Dashboard",
                component: Dashboard,
            },
            {
                path: "peajes",
                name: "Tolls",
                component: Tolls,
            },
            {
                path: "sucesos",
                name: "IncidentsList",
                component: IncidentsList,
            },
            {
                path: "sucesos/nuevo",
                name: "IncidentsCreate",
                component: Incidents,
            },
            {
                path: "busqueda",
                name: "AdvancedSearch",
                component: AdvancedSearch,
            },
            {
                path: "mapa",
                name: "MapDashboard",
                component: MapDashboard,
            },
            {
                path: "sucesos/editar/:id",
                name: "IncidentsEdit",
                component: Incidents,
            },
            {
                path: "acciones",
                name: "ActionsList",
                component: () => import("../views/ActionsList.vue"),
            },
            {
                path: "acciones/nuevo",
                name: "ActionsCreate",
                component: () => import("../views/Actions.vue"),
            },
            {
                path: "acciones/editar/:id",
                name: "ActionsEdit",
                component: () => import("../views/Actions.vue"),
            },
            {
                path: "inventario",
                name: "InventoryDashboard",
                component: InventoryDashboard,
            },
            {
                path: "bacheo",
                name: "BacheoDashboard",
                component: BacheoDashboard,
            },
            {
                path: "evidencias",
                name: "MediaGallery",
                component: MediaGallery,
            },
            {
                path: "pasteros",
                name: "PasterosDashboard",
                component: PasterosDashboard,
            },
             {
                path: "notas",
                name: "NotessDashboard",
                component: NotesDashboard,
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from) => {
    const isAuthenticated = localStorage.getItem("access_token");

    if (to.meta.requiresAuth && !isAuthenticated) {
        return "/login";
    }

    return true;
});

export default router;
