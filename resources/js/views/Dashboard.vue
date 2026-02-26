<script setup>
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const handleLogout = async () => {
    try {

        await axios.post('/api/logout');
    } catch (error) {
        console.error("Error al cerrar sesión en el servidor", error);
    } finally {
        localStorage.removeItem('access_token');
        router.push('/');
    }
}
</script>
<template>
    <div>
        <h2 class="font-['Barlow_Condensed'] text-[24px] font-extrabold text-slate-900 dark:text-slate-100 mb-4 tracking-wide">Panel de Control</h2>
        
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 shadow-sm dark:shadow-none p-5 relative overflow-hidden transition-colors rounded-xl">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-amber-500"></div>
                <div class="text-[11px] text-slate-500 tracking-[0.08em] font-semibold uppercase mb-2">Peajes activos</div>
                <div class="font-['Barlow_Condensed'] text-[38px] font-extrabold text-slate-900 dark:text-slate-100 leading-none">7</div>
            </div>
            </div>

        <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl p-10 text-center text-slate-500 dark:text-slate-400">
            <p>Bienvenido al sistema. Navega usando el menú lateral.</p>
        </div>
    </div>
</template>