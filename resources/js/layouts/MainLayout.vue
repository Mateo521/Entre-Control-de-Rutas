<script setup>
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

const handleLogout = async () => {
    try {
        await axios.post('/api/logout');
    } catch (error) {
        console.error(error);
    } finally {
        localStorage.removeItem('access_token');
        router.push('/');
    }
}
</script>

<template>
    <div
        class="flex min-h-screen bg-[#f0f4f8] dark:bg-[#0a1628] bg-[linear-gradient(rgba(0,0,0,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.04)_1px,transparent_1px)] dark:bg-[linear-gradient(rgba(245,158,11,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(245,158,11,0.04)_1px,transparent_1px)] bg-[size:40px_40px] transition-colors">

        <div
            class="w-[240px] min-h-screen bg-white dark:bg-[#0d1b2a] border-r border-amber-500/20 dark:border-amber-500/10 shrink-0 transition-colors shadow-[2px_0_12px_rgba(0,0,0,0.06)] dark:shadow-none relative">
            <div class="py-4.5 px-5 border-b border-amber-500/20 dark:border-amber-500/10 flex items-center gap-2.5">
                <div
                    class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.8"
                        stroke-linecap="round">
                        <path d="M3 17l2-2h14l2 2" />
                        <path d="M5 17V9a2 2 0 012-2h10a2 2 0 012 2v8" />
                        <circle cx="7.5" cy="17.5" r="1.5" />
                        <circle cx="16.5" cy="17.5" r="1.5" />
                    </svg>
                </div>
                <div>
                    <div
                        class="font-['Barlow_Condensed'] font-extrabold text-base text-slate-900 dark:text-slate-100 leading-none">
                        ECR Sistema</div>
                    <div class="text-[10px] text-slate-400 dark:text-slate-500 tracking-wider mt-0.5">Región centro
                    </div>
                </div>
            </div>

            <nav class="pt-12 pb-24">
                <div
                    class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.15em] uppercase text-slate-400 dark:text-slate-500 px-5 pt-5 pb-1.5 transition-colors">
                    General</div>

                <router-link to="/panel"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>
                    Panel principal
                </router-link>

                <router-link to="/panel/peajes"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>
                    Gestión de Peajes
                </router-link>

                <router-link to="/panel/sucesos"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Registro de Sucesos
                </router-link>

                <router-link to="/panel/busqueda"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="11" y1="8" x2="11" y2="14"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    Búsqueda Avanzada
                </router-link>

                <router-link to="/panel/mapa"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                    Mapa Interactivo
                </router-link>


                <div
                    class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.15em] uppercase text-slate-400 dark:text-slate-500 px-5 pt-5 pb-1.5 transition-colors mt-2">
                    Sistema</div>
                <a @click="handleLogout"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-red-400 dark:text-red-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-500/10 cursor-pointer">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Cerrar sesión
                </a>
            </nav>

            <div
                class="p-4 border-t border-black/5 dark:border-white/5 flex items-center gap-2.5 absolute bottom-0 w-full bg-white dark:bg-[#0d1b2a]">
                <div
                    class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center shrink-0 font-['Barlow_Condensed'] font-bold text-amber-500 text-sm">
                    AD</div>
                <div>
                    <div class="text-[13px] font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                        Administrador</div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500">admin@enterutas.gov.ar</div>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col">
            <div
                class="h-14 bg-white/95 dark:bg-[#0d1b2a]/95 border-b border-amber-500/15 dark:border-amber-500/10 backdrop-blur-md flex items-center justify-between px-6 sticky top-0 z-40 transition-colors">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider uppercase transition-all bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                        <span
                            class="w-1.5 h-1.5 rounded-full inline-block bg-emerald-400 shadow-[0_0_6px_#34d399]"></span>
                        Sistema Operativo
                    </span>
                </div>
            </div>

            <div :class="[
                'flex-1 overflow-y-auto',
                $route.name !== 'MapDashboard' ? 'p-6' : ''
            ]">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>