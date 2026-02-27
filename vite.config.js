import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'; // <-- 1. IMPORTACIÓN RESTAURADA
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),  
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            outDir: 'public', 
            buildBase: '/',
            manifest: {
                name: 'Ente Control de Rutas',
                short_name: 'ECR',
                description: 'Sistema Operativo de Auditoría Vial',
                theme_color: '#f59e0b',
                background_color: '#0d1b2a',
                display: 'standalone', 
                icons: [
                    {
                        src: '/logo-192.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: '/logo-512.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
                cleanupOutdatedCaches: true
            }
        })
    ],
});