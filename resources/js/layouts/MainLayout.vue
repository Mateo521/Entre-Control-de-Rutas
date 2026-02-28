<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()


const isOnline = ref(navigator.onLine)
const peajes = ref([])

const actualizarEstadoRed = () => {
    isOnline.value = navigator.onLine
}

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


const horaActual = ref('')


const actualizarReloj = () => {
    const ahora = new Date()
    horaActual.value = ahora.toLocaleTimeString('es-AR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    })
}

let intervaloReloj = null


const obtenerDatosRuta = (nombre) => {
    const nombreLower = nombre.toLowerCase();
    if (nombreLower.includes('cumbre') || nombreLower.includes('desaguadero')) {
        return { ruta: 'Ruta Nac. 7', colorText: 'text-amber-400', bgGradiente: 'from-amber-900/90' };
    }
    if (nombreLower.includes('puquios')) {
        return { ruta: 'Ruta Prov. 9', colorText: 'text-emerald-400', bgGradiente: 'from-emerald-900/90' };
    }
    if (nombreLower.includes('piedra') || nombreLower.includes('perilago')) {
        return { ruta: 'Ruta Prov. 20', colorText: 'text-blue-400', bgGradiente: 'from-blue-900/90' };
    }
    if (nombreLower.includes('30')) {
        return { ruta: 'Ruta Prov. 30', colorText: 'text-purple-400', bgGradiente: 'from-purple-900/90' };
    }
    return { ruta: 'Ruta Provincial', colorText: 'text-slate-400', bgGradiente: 'from-black/80' };
}


const cargarPeajesBase = async () => {
    try {
        const respuesta = await axios.get('/api/tolls', {
            params: { per_page: 50 }
        })

        peajes.value = respuesta.data.data
    } catch (error) {
        console.error('Error al cargar peajes en el layout:', error)
    }
}



const footerDesplegado = ref(false)


const enfocarPeajeYcerrar = (nombre) => {
    footerDesplegado.value = false;
    router.push({ path: '/panel/mapa', query: { focus: nombre } });
}


onMounted(() => {
    actualizarReloj()
    intervaloReloj = setInterval(actualizarReloj, 1000)
    window.addEventListener('online', actualizarEstadoRed)
    window.addEventListener('offline', actualizarEstadoRed)
    cargarPeajesBase()
})

onUnmounted(() => {
    window.removeEventListener('online', actualizarEstadoRed)
    window.removeEventListener('offline', actualizarEstadoRed)
    if (intervaloReloj) clearInterval(intervaloReloj)
})
</script>

<template>
    <div
        class="flex min-h-screen bg-[#f0f4f8] dark:bg-[#0a1628] bg-[linear-gradient(rgba(0,0,0,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.04)_1px,transparent_1px)] dark:bg-[linear-gradient(rgba(245,158,11,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(245,158,11,0.04)_1px,transparent_1px)] bg-[size:40px_40px] transition-colors">

        <div v-if="!isOnline"
            class="fixed top-0 left-0 w-full bg-red-600 text-white text-xs font-bold font-['Barlow_Condensed'] tracking-widest uppercase py-1.5 flex justify-center items-center z-[10000] shadow-md">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                class="mr-2">
                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path>
                <path d="M14 14a3.53 3.53 0 0 0-5-5"></path>
                <path d="M17.5 17.5a8.5 8.5 0 0 0-11-11"></path>
                <path d="M21 21a13.5 13.5 0 0 0-18-18"></path>
                <line x1="2" y1="2" x2="22" y2="22"></line>
            </svg>
            Sin conexión al servidor central. Funciones limitadas.
        </div>

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
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5"><svg
                        width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>Panel principal</router-link>

                <router-link to="/panel/peajes"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5"><svg
                        width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>Gestión de peajes</router-link>

                <router-link to="/panel/sucesos"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5"><svg
                        width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>Registro de sucesos</router-link>

                <router-link to="/panel/acciones"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path
                            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                        </path>
                    </svg>
                    Gestión de acciones
                </router-link>


                <router-link to="/panel/busqueda"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5"><svg
                        width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="11" y1="8" x2="11" y2="14"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>Búsqueda avanzada</router-link>


                <router-link to="/panel/evidencias"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    Galería de adjuntos
                </router-link>

                <router-link to="/panel/mapa"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5"><svg
                        width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>Mapa</router-link>



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
                    </svg>Cerrar sesión
                </a>
            </nav>



            <div
                class="p-4 border-t border-black/5 dark:border-white/5 flex items-center gap-2.5 absolute bottom-0 w-full bg-white dark:bg-[#0d1b2a]">
                <div
                    class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center shrink-0 font-['Barlow_Condensed'] font-bold text-amber-500 text-sm">
                    AD
                </div>

                <div class="flex-1">
                    <div class="text-[13px] font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                        Administrador
                    </div>
                    <div class="flex justify-between items-center mt-0.5">
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[110px]">
                            admin@enterutas.gov.ar
                        </div>

                        <div
                            class="text-[11px] font-mono font-bold text-amber-600 dark:text-amber-500/80 bg-amber-500/5 px-1.5 rounded border border-amber-500/10">
                            {{ horaActual }}
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex-1 flex flex-col "> <!-- pt-[24px] -->
            <div
                class="h-14 bg-white/95 dark:bg-[#0d1b2a]/95 border-b border-amber-500/15 dark:border-amber-500/10 backdrop-blur-md flex items-center justify-between px-6 sticky top-0 z-40 transition-colors">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider uppercase transition-all bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                        <span
                            class="w-1.5 h-1.5 rounded-full inline-block bg-emerald-400 shadow-[0_0_6px_#34d399]"></span>
                        Sistema operativo
                    </span>
                </div>
            </div>







            <div class="flex-1 overflow-y-auto flex flex-col bg-slate-100 dark:bg-[#0a1628]">


                <div :class="[
                    'flex-1 flex flex-col bg-slate-100 dark:bg-[#0a1628] relative',
                    $route.name === 'MapDashboard' ? 'overflow-hidden' : 'overflow-y-auto'
                ]">

                    <div :class="[
                        'flex-1',
                        $route.name !== 'MapDashboard' ? 'p-6' : ''
                    ]">
                        <router-view></router-view>
                    </div>

                    <div :class="[
                        $route.name === 'MapDashboard'
                            ? 'absolute bottom-0 left-0 right-0 z-[1000] bg-white/95 dark:bg-[#0d1b2a]/95 backdrop-blur-md shadow-[0_-10px_30px_rgba(0,0,0,0.3)] transition-transform duration-500 ease-in-out border-t border-amber-500/30 px-6 pb-6 pt-2'
                            : 'px-6 pb-6 mt-auto',
                        $route.name === 'MapDashboard' && !footerDesplegado ? 'translate-y-full' : 'translate-y-0'
                    ]">

                        <button v-if="$route.name === 'MapDashboard'" @click="footerDesplegado = !footerDesplegado"
                            class="absolute -top-10 left-1/2 -translate-x-1/2 h-10 px-8 bg-white/95 dark:bg-[#0d1b2a]/95 backdrop-blur-md border border-b-0 border-amber-500/30 rounded-t-xl flex items-center justify-center gap-2 cursor-pointer transition-colors hover:bg-amber-500/10 shadow-[0_-4px_10px_rgba(0,0,0,0.1)]">
                            <span
                                class="font-['Barlow_Condensed'] text-[12px] font-bold tracking-[0.15em] uppercase text-amber-600 dark:text-amber-500">
                                {{ footerDesplegado ? 'Ocultar Estaciones' : 'Ver Estaciones' }}
                            </span>
                            <svg :class="footerDesplegado ? '' : 'rotate-180'"
                                class="w-4 h-4 text-amber-500 transition-transform duration-300" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>


                        <div
                            :class="['pt-4 border-slate-200 dark:border-white/10', $route.name !== 'MapDashboard' ? 'border-t' : '']">
                            <div class="flex items-center justify-between mb-4">
                                <h4
                                    class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.15em] uppercase text-slate-500 dark:text-slate-400 m-0">
                                    Infraestructura operativa - Estaciones
                                </h4>
                                <span
                                    class="text-[10px] text-slate-400 dark:text-slate-500 font-['Barlow_Condensed'] tracking-widest uppercase">
                                    Ente Control de Rutas
                                </span>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                                <div v-for="peaje in peajes" :key="peaje.id" @click="enfocarPeajeYcerrar(peaje.name)"
                                    class="group relative cursor-pointer rounded-lg overflow-hidden border border-slate-200 dark:border-white/10 shadow-sm bg-slate-200 dark:bg-slate-800 aspect-video">

                                    <img :src="peaje.image_path || `https://placehold.co/400x250/1e293b/f59e0b?text=${peaje.name.split(' ').pop()}`"
                                        onerror="this.src='https://placehold.co/600x400?text=Cargando...'"
                                        class="w-full h-full object-cover transition-transform duration-500 " />

                                    <div
                                        :class="`absolute inset-0 bg-gradient-to-t ${obtenerDatosRuta(peaje.name).bgGradiente} via-black/20 to-transparent flex items-end p-2.5`">
                                        <div>
                                            <div
                                                class="text-white font-['Barlow_Condensed'] text-[12px] font-bold tracking-wide leading-tight truncate max-w-[120px]">
                                                {{ peaje.name.replace('Peaje ', '') }}
                                            </div>
                                            <div
                                                :class="`${obtenerDatosRuta(peaje.name).colorText} text-[9px] font-bold tracking-widest uppercase mt-0.5`">
                                                {{ obtenerDatosRuta(peaje.name).ruta }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>


            </div>



        </div>
    </div>
</template>