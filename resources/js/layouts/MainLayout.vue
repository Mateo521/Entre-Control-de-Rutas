<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify';
import axios from 'axios'

const router = useRouter()
//const toast = useToast();
const isOnline = ref(navigator.onLine)
const peajes = ref([])
const horaActual = ref('')
let intervaloReloj = null
const footerDesplegado = ref(false)


const sidebarAbierto = ref(false)

const cerrarMenuMovil = () => {
    sidebarAbierto.value = false
}

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

const actualizarReloj = () => {
    const ahora = new Date()
    horaActual.value = ahora.toLocaleTimeString('es-AR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    })
}


const revisarAlertas = async () => {
    try {
        const respuesta = await axios.get('/api/notes/alerts');
        const alertas = respuesta.data;

        if (alertas && alertas.length > 0) {
            alertas.forEach(alerta => {

                toast.warning(`EVENTO PRÓXIMO: ${alerta.title}`, {
                    autoClose: 8000,
                    position: 'top-right',

                });
            });
        }
    } catch (error) {
        console.error('Error al verificar alertas:', error);
    }
}

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

        cargarClimaPeajes();
    } catch (error) {
        console.error('Error al cargar peajes en el layout:', error)
    }
}

const enfocarPeajeYcerrar = (nombre) => {
    footerDesplegado.value = false;
    router.push({ path: '/panel/mapa', query: { focus: nombre } });
}

const climasPeajes = ref({});



const coordenadasRespaldo = {
    'Peaje Cruz de Piedra': { lat: -33.2541131, lng: -66.2270219 },
    'Peaje Perilago': { lat: -33.2542911, lng: -66.2124143 },
    'Peaje Los Puquios': { lat: -33.2435, lng: -66.1558 },
    'Peaje La Cumbre': { lat: -33.3590784, lng: -66.0670751 },
    'Peaje Desaguadero Oeste': { lat: -33.4117474, lng: -67.1234507 },
    'Peaje Desaguadero Este': { lat: -33.4128459, lng: -67.1147563 },
    'Peaje Ruta 30': { lat: -33.1585, lng: -64.9542 }
};

const cargarClimaPeajes = async () => {
    if (!isOnline.value || peajes.value.length === 0) return;


    for (const peaje of peajes.value) {
        const lat = peaje.lat || peaje.latitude || (coordenadasRespaldo[peaje.name] ? coordenadasRespaldo[peaje.name].lat : null);
        const lng = peaje.lng || peaje.longitude || (coordenadasRespaldo[peaje.name] ? coordenadasRespaldo[peaje.name].lng : null);

        if (!lat || !lng) {
            console.warn(`Coordenadas no encontradas para: ${peaje.name}`);
            continue;
        }

        try {
            const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}&current_weather=true`;
            const respuesta = await axios.get(url);

            climasPeajes.value = {
                ...climasPeajes.value,
                [peaje.id]: {
                    temperatura: Math.round(respuesta.data.current_weather.temperature)
                }
            };
        } catch (error) {
            console.error(`Error al cargar clima para ${peaje.name}`);
        }
    }
};


const grupoRuta20 = computed(() => {
    return {
        cruzPiedra: peajes.value.find(p => p.name.includes('Cruz de Piedra')),
        perilago: peajes.value.find(p => p.name.includes('Perilago'))
    };
});


const grupoDesaguadero = computed(() => {
    const este = peajes.value.find(p => p.name.includes('Desaguadero Este'));
    const oeste = peajes.value.find(p => p.name.includes('Desaguadero Oeste'));
    return [este, oeste].filter(Boolean);
});


const otrosPeajes = computed(() => {
    return peajes.value.filter(p =>
        !p.name.includes('Cruz de Piedra') &&
        !p.name.includes('Perilago') &&
        !p.name.includes('Desaguadero')
    );
});


onMounted(() => {
    actualizarReloj()
    intervaloReloj = setInterval(actualizarReloj, 1000)
    window.addEventListener('online', actualizarEstadoRed)
    window.addEventListener('offline', actualizarEstadoRed)
    cargarPeajesBase()
    revisarAlertas();
})

onUnmounted(() => {
    window.removeEventListener('online', actualizarEstadoRed)
    window.removeEventListener('offline', actualizarEstadoRed)
    if (intervaloReloj) clearInterval(intervaloReloj)
})
</script>

<template>
    <div
        class="flex min-h-screen bg-[#f0f4f8] dark:bg-[#0a1628] bg-[linear-gradient(rgba(0,0,0,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(0,0,0,0.04)_1px,transparent_1px)] dark:bg-[linear-gradient(rgba(245,158,11,0.04)_1px,transparent_1px),linear-gradient(90deg,rgba(245,158,11,0.04)_1px,transparent_1px)] bg-[size:40px_40px] transition-colors relative">

        <div v-if="!isOnline"
            class="fixed top-0 left-0 w-full gap-1  bg-red-600 text-white text-xs font-bold font-['Barlow_Condensed'] tracking-widest uppercase py-1.5 flex justify-center items-center z-[10000] shadow-md">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 8a16 16 0 0 1 20 0"></path>
                <path d="M5 12a11 11 0 0 1 14 0"></path>
                <path d="M8.5 16a6 6 0 0 1 7 0"></path>
                <circle cx="12" cy="20" r="1.5" fill="currentColor" stroke="none"></circle>
            </svg>
            Sin conexión a internet. Funciones limitadas.
        </div>

        <div v-if="sidebarAbierto" @click="cerrarMenuMovil"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[1001] md:hidden transition-opacity">
        </div>

        <div :class="[
            'w-[240px] min-h-screen bg-white dark:bg-[#0d1b2a] border-r border-amber-500/20 dark:border-amber-500/10 shrink-0 transition-transform duration-300 ease-in-out shadow-[2px_0_12px_rgba(0,0,0,0.06)] dark:shadow-none',
            'fixed inset-y-0 left-0 z-[1002] md:relative md:translate-x-0',
            sidebarAbierto ? 'translate-x-0' : '-translate-x-full'
        ]">
            <div
                class="py-4.5 px-5 border-b border-amber-500/20 dark:border-amber-500/10 flex items-center justify-between gap-2.5">
                <div class="flex items-center gap-2.5">
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

                <button @click="cerrarMenuMovil"
                    class="md:hidden text-slate-400 hover:text-amber-500 bg-transparent border-none p-1 cursor-pointer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <nav class="pt-8 pb-24 overflow-y-auto h-[calc(100vh-80px)]">
                <div
                    class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.15em] uppercase text-slate-400 dark:text-slate-500 px-5 pt-5 pb-1.5 transition-colors">
                    General</div>

                <router-link to="/panel" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <rect x="3" y="3" width="7" height="7" />
                        <rect x="14" y="3" width="7" height="7" />
                        <rect x="14" y="14" width="7" height="7" />
                        <rect x="3" y="14" width="7" height="7" />
                    </svg>Panel principal
                </router-link>

                <router-link to="/panel/peajes" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0118 0z" />
                        <circle cx="12" cy="10" r="3" />
                    </svg>Gestión de peajes
                </router-link>

                <router-link to="/panel/sucesos" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>Registro de sucesos
                </router-link>

                <router-link to="/panel/acciones" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <path
                            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                        </path>
                    </svg>Gestión de acciones
                </router-link>

                <router-link to="/panel/busqueda" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="11" y1="8" x2="11" y2="14"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>Búsqueda avanzada
                </router-link>

                <router-link to="/panel/evidencias" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>Galería de adjuntos
                </router-link>

                <router-link to="/panel/inventario" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>Inventario
                </router-link>

                <router-link to="/panel/bacheo" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                        fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                            d="M7.58209 8.96025 9.8136 11.1917l-1.61782 1.6178c-1.08305-.1811-2.23623.1454-3.07364.9828-1.1208 1.1208-1.32697 2.8069-.62368 4.1363.14842.2806.42122.474.73509.5213.06726.0101.1347.0133.20136.0098-.00351.0666-.00036.1341.00977.2013.04724.3139.24069.5867.52125.7351 1.32944.7033 3.01552.4971 4.13627-.6237.8375-.8374 1.1639-1.9906.9829-3.0736l4.8107-4.8108c1.0831.1811 2.2363-.1454 3.0737-.9828 1.1208-1.1208 1.3269-2.80688.6237-4.13632-.1485-.28056-.4213-.474-.7351-.52125-.0673-.01012-.1347-.01327-.2014-.00977.0035-.06666.0004-.13409-.0098-.20136-.0472-.31386-.2406-.58666-.5212-.73508-1.3294-.70329-3.0155-.49713-4.1363.62367-.8374.83741-1.1639 1.9906-.9828 3.07365l-1.7788 1.77875-2.23152-2.23148-1.41419 1.41424Zm1.31056-3.1394c-.04235-.32684-.24303-.61183-.53647-.76186l-1.98183-1.0133c-.38619-.19746-.85564-.12345-1.16234.18326l-.86321.8632c-.3067.3067-.38072.77616-.18326 1.16235l1.0133 1.98182c.15004.29345.43503.49412.76187.53647l1.1127.14418c.3076.03985.61628-.06528.8356-.28461l.86321-.8632c.21932-.21932.32446-.52801.2846-.83561l-.14417-1.1127ZM19.4448 16.4052l-3.1186-3.1187c-.7811-.781-2.0474-.781-2.8285 0l-.1719.172c-.7811.781-.7811 2.0474 0 2.8284l3.1186 3.1187c.7811.781 2.0474.781 2.8285 0l.1719-.172c.7811-.781.7811-2.0474 0-2.8284Z" />
                    </svg>
                    Cuadrilla de bacheo
                </router-link>

                <router-link to="/panel/pasteros" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.83892 12.4543s1.24988-3.08822-.21626-5.29004C8.15656 4.96245 4.58671 4.10885 4.39794 4.2436c-.18877.13476-1.11807 3.32546.34803 5.52727 1.4661 2.20183 5.09295 2.68343 5.09295 2.68343Zm0 0C10.3389 13.4543 12 15 12 18v2c0-2-.4304-3.4188 2.0696-5.9188m0 0s-.4894-2.7888 1.1206-4.35788c1.6101-1.56907 4.4903-1.54682 4.6701-1.28428.1798.26254.4317 2.84376-1.0809 4.31786-1.61 1.5691-4.7098 1.3243-4.7098 1.3243Z" />
                    </svg>

                    Empresas privadas
                </router-link>

                <router-link to="/panel/gruas" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg class="w-6 h-6" viewBox="0 0 92.983 92.983" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <g fill="currentColor">
                            <path d="M48.633,44.708l-0.887,4.268l-3.397-0.708L45.239,44L48.633,44.708z" />
                            <path
                                d="M92.983,49.379l-4.464-0.936l-3.933-11.97l-25.388-5.351l-8.427,9.367l-12.594-2.647l-1.591,7.55l-1.541-0.323l-0.767,3.643c-0.236,0.119-0.49,0.205-0.775,0.205c-0.95,0-1.724-0.771-1.724-1.726c0-0.952,0.776-1.728,1.724-1.728h0.544V33.768l0.191-0.165l-4.573-6.332L1.137,51.222H0v10.279h17.859c0.654,2.413,2.842,4.206,5.457,4.206c2.616,0,4.807-1.793,5.461-4.206h5.305v-3.107h1.395v-4.356h-1.395v-2.812H13.694l19.264-16.523v9.727c-1.292,0.252-2.269,1.403-2.269,2.767c0,1.55,1.261,2.814,2.815,2.814c0.561,0,1.054-0.206,1.493-0.488l0.696,0.144l-0.562,2.676l8.859,1.861c-0.04,2.66,1.803,5.064,4.5,5.634c0.388,0.083,0.782,0.124,1.178,0.124c2.294,0,4.334-1.41,5.21-3.459l16.433,3.454c-0.023,1.122,0.292,2.227,0.921,3.185c0.831,1.268,2.104,2.14,3.584,2.449c0.388,0.082,0.782,0.123,1.177,0.123c2.293,0,4.332-1.409,5.208-3.458l7.729,1.624L92.983,49.379z M20.824,37.122h7.641l-7.641,6.556V37.122z M19.735,43.702h-7.937l7.937-6.662V43.702z M20.929,36.031l7.489-6.281v6.281H20.929z M10.569,44.732v6.49H2.837L10.569,44.732z M23.32,63.527c-1.925,0-3.495-1.569-3.495-3.495c0-1.932,1.567-3.499,3.495-3.499c1.929,0,3.497,1.567,3.497,3.499C26.817,61.958,25.247,63.527,23.32,63.527z M31.906,59.325h-2.976c-0.353-2.797-2.719-4.973-5.604-4.973c-2.887,0-5.253,2.176-5.603,4.973H2.191v-5.922h29.722L31.906,59.325L31.906,59.325z M11.66,51.222V44.79h7.865l-7.498,6.432H11.66z M29.505,36.227v-7.328l3.268,4.528L29.505,36.227z M53.083,54.998c-0.388,1.848-2.274,3.096-4.137,2.698c-1.889-0.396-3.099-2.258-2.702-4.14c0.341-1.608,1.777-2.776,3.419-2.776c0.241,0,0.485,0.025,0.726,0.073c0.914,0.191,1.696,0.729,2.203,1.51C53.103,53.15,53.277,54.083,53.083,54.998z M80.41,60.752c-0.388,1.844-2.265,3.089-4.14,2.688c-0.917-0.191-1.702-0.726-2.208-1.506c-0.512-0.787-0.689-1.715-0.493-2.635c0.338-1.607,1.774-2.775,3.415-2.775c0.244,0,0.485,0.029,0.728,0.077c0.912,0.191,1.697,0.728,2.206,1.511C80.426,58.905,80.604,59.835,80.41,60.752z M82.664,60.11c0.016-1.12-0.297-2.222-0.924-3.178c-0.826-1.275-2.101-2.146-3.581-2.457c-2.655-0.545-5.347,0.94-6.392,3.342l-16.429-3.455c0.019-1.118-0.296-2.223-0.921-3.185c-0.831-1.269-2.104-2.14-3.586-2.45c-2.646-0.56-5.345,0.933-6.384,3.344l-6.734-1.419l2.151-10.222l11.687,2.463l8.424-9.366l22.927,4.826l3.938,11.971l3.559,0.744l-2.153,10.224L82.664,60.11z M81.868,42.155l2.429,7.391l-3.813-0.806L81.868,42.155z M70.042,37.867L80.1,39.978l-1.752,8.318l-10.053-2.114L70.042,37.867z M60.761,35.91l7.15,1.509l-1.752,8.312l-11.962-2.515L60.761,35.91z" />
                        </g>
                    </svg>


                    Grúas
                </router-link>

                <router-link to="/panel/notas" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg class="w-4  h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                        fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 4h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3m0 3h6m-3 5h3m-6 0h.01M12 16h3m-6 0h.01M10 3v4h4V3h-4Z" />
                    </svg>


                    Notas
                </router-link>



                <router-link to="/panel/mapa" @click="cerrarMenuMovil"
                    active-class="border-amber-500 text-amber-600 dark:text-amber-500 bg-amber-500/10"
                    class="flex items-center gap-2.5 px-5 py-2.5 text-[13.5px] font-medium transition-all no-underline border-l-[3px] border-transparent text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>Mapa
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
                    </svg>Cerrar sesión
                </a>
            </nav>

            <div
                class="p-4 border-t border-black/5 dark:border-white/5 flex items-center gap-2.5 absolute bottom-0 w-full bg-white dark:bg-[#0d1b2a] z-10">
                <div
                    class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center shrink-0 font-['Barlow_Condensed'] font-bold text-amber-500 text-sm">
                    AD</div>
                <div class="flex-1">
                    <div class="text-[13px] font-semibold text-slate-900 dark:text-slate-100 leading-tight">
                        Administrador</div>
                    <div class="flex justify-between items-center mt-0.5">
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[100px]">
                            admin@enterutas.gov.ar</div>
                        <div
                            class="text-[11px] font-mono font-bold text-amber-600 dark:text-amber-500/80 bg-amber-500/5 px-1 rounded border border-amber-500/10">
                            {{ horaActual }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col min-w-0">
            <div
                class="h-14 bg-white/95 dark:bg-[#0d1b2a]/95 border-b border-amber-500/15 dark:border-amber-500/10 backdrop-blur-md flex items-center justify-between px-4 md:px-6 sticky top-0 z-40 transition-colors">
                <div class="flex items-center gap-3">

                    <button @click="sidebarAbierto = true"
                        class="md:hidden p-1.5 -ml-2 text-slate-500 hover:text-amber-500 bg-transparent border-none cursor-pointer">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>

                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider uppercase transition-all bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                        <span
                            class="w-1.5 h-1.5 rounded-full inline-block bg-emerald-400 shadow-[0_0_6px_#34d399]"></span>
                        Sistema operativo
                    </span>
                </div>
            </div>

            <div
                :class="['flex-1 flex flex-col bg-slate-100 dark:bg-[#0a1628] relative', $route.name === 'MapDashboard' ? 'overflow-hidden' : 'overflow-y-auto']">

                <div :class="['flex-1', $route.name !== 'MapDashboard' ? 'p-4 md:p-6' : '']">
                    <router-view></router-view>
                </div>




                <div :class="[
                    $route.name === 'MapDashboard'
                        ? 'absolute bottom-0 left-0 right-0 z-[1050] bg-white/95 dark:bg-[#0d1b2a]/95 backdrop-blur-md shadow-[0_-10px_30px_rgba(0,0,0,0.3)] transition-transform duration-500 ease-in-out border-t border-amber-500/30 px-4 md:px-6 pb-4 pt-2'
                        : 'px-4 md:px-6 pb-6 mt-auto',
                    $route.name === 'MapDashboard' && !footerDesplegado ? 'translate-y-full' : 'translate-y-0'
                ]">

                    <button v-if="$route.name === 'MapDashboard'" @click="footerDesplegado = !footerDesplegado"
                        class="absolute -top-10 left-1/2 -translate-x-1/2 h-10 px-6 md:px-8 bg-white/95 dark:bg-[#0d1b2a]/95 backdrop-blur-md border border-b-0 border-amber-500/30  flex items-center justify-center gap-2 cursor-pointer transition-colors hover:bg-amber-500/10 shadow-[0_-4px_10px_rgba(0,0,0,0.1)]">
                        <span
                            class="font-['Barlow_Condensed'] text-[12px] font-bold tracking-[0.15em] uppercase text-amber-600 dark:text-amber-500">
                            {{ footerDesplegado ? 'Ocultar peajes' : 'Ver peajes' }}
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

                        <div
                            class="flex gap-4 overflow-x-auto snap-x snap-mandatory pt-1 w-full [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">

                            <div v-if="grupoRuta20.cruzPiedra || grupoRuta20.perilago"
                                class="flex flex-1 min-w-[320px] gap-2 shrink-0 snap-center bg-slate-100 dark:bg-white/5 p-2  border border-slate-200 dark:border-white/10">
                                <div v-if="grupoRuta20.cruzPiedra"
                                    @click="enfocarPeajeYcerrar(grupoRuta20.cruzPiedra.name)"
                                    class="flex-[3] transition-transform duration-300 relative z-10 hover:-translate-y-1 group cursor-pointer rounded-lg overflow-hidden border-2 border-amber-500/50 shadow-md bg-slate-200 dark:bg-slate-800 aspect-video md:aspect-auto md:h-28">
                                    <img :src="grupoRuta20.cruzPiedra.image_path || '/data/600x400.svg'"
                                        onerror="this.src='/data/600x400.svg'"
                                        class="w-full h-full object-cover transition-transform duration-500" />

                                    <div v-if="climasPeajes[grupoRuta20.cruzPiedra.id]"
                                        class="absolute top-2 right-2 bg-black/60 backdrop-blur-md border border-white/10 px-2 py-1 rounded-md flex items-center gap-1.5 shadow-lg z-20">
                                        <span class="text-white font-mono text-[10px] font-bold">{{
                                            climasPeajes[grupoRuta20.cruzPiedra.id].temperatura }}°C</span>
                                    </div>

                                    <div
                                        :class="`absolute inset-0 bg-gradient-to-t ${obtenerDatosRuta(grupoRuta20.cruzPiedra.name).bgGradiente} via-black/20 to-transparent flex items-end p-2.5`">
                                        <div>
                                            <div
                                                class="text-white font-['Barlow_Condensed'] text-[15px] font-bold tracking-wide leading-tight truncate">
                                                {{ grupoRuta20.cruzPiedra.name.replace('Peaje ', '') }}
                                            </div>
                                            <div
                                                class="text-amber-400 text-[9px] font-bold tracking-widest uppercase mt-0.5">
                                                Estación Central</div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="grupoRuta20.perilago" @click="enfocarPeajeYcerrar(grupoRuta20.perilago.name)"
                                    class="flex-[2] transition-transform duration-300 relative z-10 hover:-translate-y-1 group cursor-pointer rounded-lg overflow-hidden border border-slate-300 dark:border-white/20 shadow-sm bg-slate-200 dark:bg-slate-800 aspect-video md:aspect-auto md:h-28 opacity-90">
                                    <img :src="grupoRuta20.perilago.image_path || '/data/600x400.svg'"
                                        onerror="this.src='/data/600x400.svg'"
                                        class="w-full h-full object-cover transition-transform duration-500 grayscale-[30%]" />

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent flex items-end p-2.5">
                                        <div>
                                            <div
                                                class="text-slate-200 font-['Barlow_Condensed'] text-[14px] font-bold tracking-wide leading-tight truncate">
                                                {{ grupoRuta20.perilago.name.replace('Peaje ', '') }}
                                            </div>
                                            <div
                                                class="text-slate-400 text-[9px] font-bold tracking-widest uppercase mt-0.5">
                                                Dependencia</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="grupoDesaguadero.length > 0"
                                class="flex flex-1 min-w-[320px] gap-2 shrink-0 snap-center bg-slate-100 dark:bg-white/5 p-2  border border-slate-200 dark:border-white/10">
                                <div v-for="peaje in grupoDesaguadero" :key="peaje.id"
                                    @click="enfocarPeajeYcerrar(peaje.name)"
                                    class="flex-1 transition-transform duration-300 relative z-10 hover:-translate-y-1 group cursor-pointer rounded-lg overflow-hidden border border-slate-200 dark:border-white/20 shadow-sm bg-slate-200 dark:bg-slate-800 aspect-video md:aspect-auto md:h-28">
                                    <img :src="peaje.image_path || '/data/600x400.svg'"
                                        onerror="this.src='/data/600x400.svg'"
                                        class="w-full h-full object-cover transition-transform duration-500" />

                                    <div v-if="climasPeajes[peaje.id]"
                                        class="absolute top-2 right-2 bg-black/60 backdrop-blur-md border border-white/10 px-2 py-1 rounded-md flex items-center gap-1.5 shadow-lg z-20">
                                        <span class="text-white font-mono text-[10px] font-bold">{{
                                            climasPeajes[peaje.id].temperatura }}°C</span>
                                    </div>

                                    <div
                                        :class="`absolute inset-0 bg-gradient-to-t ${obtenerDatosRuta(peaje.name).bgGradiente} via-black/20 to-transparent flex items-end p-2.5`">
                                        <div>
                                            <div
                                                class="text-white font-['Barlow_Condensed'] text-[15px] font-bold tracking-wide leading-tight truncate">
                                                {{ peaje.name.replace('Peaje Desaguadero', 'Desag.') }}
                                            </div>
                                            <div
                                                :class="`${obtenerDatosRuta(peaje.name).colorText} text-[9px] font-bold tracking-widest uppercase mt-0.5`">
                                                {{ obtenerDatosRuta(peaje.name).ruta }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-for="peaje in otrosPeajes" :key="peaje.id" @click="enfocarPeajeYcerrar(peaje.name)"
                                class="flex-1 min-w-[180px] shrink-0 snap-center transition-transform duration-300 relative z-10 hover:-translate-y-1 group cursor-pointer rounded-lg overflow-hidden border border-slate-200 dark:border-white/10 shadow-sm bg-slate-200 dark:bg-slate-800 aspect-video md:aspect-auto md:h-28">

                                <img :src="peaje.image_path || '/data/600x400.svg'"
                                    onerror="this.src='/data/600x400.svg'"
                                    class="w-full h-full object-cover transition-transform duration-500" />

                                <div v-if="climasPeajes[peaje.id]"
                                    class="absolute top-2 right-2 bg-black/60 backdrop-blur-md border border-white/10 px-2 py-1 rounded-md flex items-center gap-1.5 shadow-lg z-20">
                                    <span class="text-white font-mono text-[10px] font-bold">{{
                                        climasPeajes[peaje.id].temperatura }}°C</span>
                                </div>

                                <div
                                    :class="`absolute inset-0 bg-gradient-to-t ${obtenerDatosRuta(peaje.name).bgGradiente} via-black/20 to-transparent flex items-end p-2.5`">
                                    <div>
                                        <div
                                            class="text-white font-['Barlow_Condensed'] text-[15px] font-bold tracking-wide leading-tight truncate">
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
</template>