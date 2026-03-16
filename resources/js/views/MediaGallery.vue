<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import ImageLightbox from '@/components/ImageLightbox.vue'
const cargando = ref(true)
const evidencias = ref([])
const filtroActivo = ref('todos') // 'todos', 'imagen', 'documento', 'video' 


const paginaActual = ref(1)
const hayMasPaginas = ref(true)
const observadorRef = ref(null)
let observador = null
const urlProcesadas = new Set()



const mostrarLightbox = ref(false)
const imagenSeleccionada = ref('')
const imagenesRelacionadas = ref([])

const abrirLightboxRelacionado = (itemClickeado) => {
   
    const relacionadas = evidencias.value.filter(evidencia => 
        evidencia.tipo === 'imagen' && 
        evidencia.suceso_id === itemClickeado.suceso_id &&
        evidencia.suceso_tipo === itemClickeado.suceso_tipo
    )
    
   
    imagenesRelacionadas.value = relacionadas.map(img => ({
        url: img.url,
        title: img.suceso_tipo,        
        subtitle: img.peaje,           
        date: img.fecha,             
        badge: img.campo               
    }))
    
  
    imagenSeleccionada.value = itemClickeado.url
    mostrarLightbox.value = true
}

const esImagen = (ruta) => {
    if (!ruta) return false;
    const ext = ruta.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg'].includes(ext);
};

const esVideo = (ruta) => {
    if (!ruta) return false;
    const ext = ruta.split('.').pop().toLowerCase();
    return ['mp4', 'mov', 'webm', 'ogg', 'avi', 'mkv'].includes(ext);
};

const esDocumento = (ruta) => {
    if (!ruta) return false;
    return !esImagen(ruta) && !esVideo(ruta);
};


const formatearMedia = (ruta, id, tipoSuceso, ubicacion, fechaOriginal, etiquetaCampo) => {
    const extension = ruta.split('.').pop().toLowerCase();
    let tipoGeneral = 'documento';

    if (esImagen(ruta)) {
        tipoGeneral = 'imagen';
    } else if (esVideo(ruta)) {
        tipoGeneral = 'video';
    }

    return {
        id: Math.random().toString(36).substring(7),
        url: ruta,
        tipo: tipoGeneral,
        extension: extension.toUpperCase(),
        campo: etiquetaCampo.replace(/_/g, ' '),
        suceso_id: id,
        suceso_tipo: tipoSuceso.replace(/_/g, ' '),
        peaje: ubicacion,
        fecha: new Date(fechaOriginal).toLocaleDateString('es-AR'),
        fechaIso: fechaOriginal
    };
};

const cargandoMas = ref(false)
const cargarSiguientePagina = async () => {
    if ((cargando.value && paginaActual.value > 1) || cargandoMas.value) return;

    if (paginaActual.value > 1) {
        cargandoMas.value = true;
    }

    try {

        const [resIncidents, resContractors, resActions] = await Promise.all([
            axios.get('/api/incidents', { params: { page: paginaActual.value, per_page: 15 } }).catch(() => ({ data: { data: [], last_page: 1 } })),
            axios.get('/api/contractors', { params: { page: paginaActual.value, per_page: 15 } }).catch(() => ({ data: { data: [], last_page: 1 } })),
            axios.get('/api/actions', { params: { page: paginaActual.value, per_page: 15 } }).catch(() => ({ data: { data: [], last_page: 1 } }))
        ]);


        const dataIncidents = Array.isArray(resIncidents.data) ? resIncidents.data : (resIncidents.data.data || []);
        const dataContractors = Array.isArray(resContractors.data) ? resContractors.data : (resContractors.data.data || []);
        const dataActions = Array.isArray(resActions.data) ? resActions.data : (resActions.data.data || []);

        let nuevaMedia = [];


        dataIncidents.forEach(suceso => {
            if (suceso.media_paths) {
                Object.entries(suceso.media_paths).forEach(([campo, rutas]) => {
                    rutas.forEach(ruta => {
                        if (!urlProcesadas.has(ruta)) {
                            urlProcesadas.add(ruta);
                            nuevaMedia.push(formatearMedia(ruta, suceso.id, suceso.incident_type, suceso.toll?.name || 'Traza', suceso.created_at, campo));
                        }
                    });
                });
            }
        });


        dataContractors.forEach(trabajo => {
            if (trabajo.media_paths && Array.isArray(trabajo.media_paths)) {
                trabajo.media_paths.forEach(ruta => {
                    if (!urlProcesadas.has(ruta)) {
                        urlProcesadas.add(ruta);
                        nuevaMedia.push(formatearMedia(ruta, trabajo.id, trabajo.work_type, trabajo.company_name, trabajo.created_at, 'Certificación'));
                    }
                });
            }
        });


        dataActions.forEach(accion => {
            if (accion.media_paths && Array.isArray(accion.media_paths)) {
                accion.media_paths.forEach(ruta => {
                    if (!urlProcesadas.has(ruta)) {
                        urlProcesadas.add(ruta);

                        const nombreSuceso = accion.category === 'Mantenimiento Vial - Bacheo'
                            ? 'Reparación de Bacheo'
                            : accion.category;

                        nuevaMedia.push(formatearMedia(
                            ruta,
                            accion.id,
                            nombreSuceso,
                            accion.toll?.name || 'Traza General',
                            accion.created_at,
                            'Evidencia de Reparación'
                        ));
                    }
                });
            }
        });

        evidencias.value.push(...nuevaMedia);


        evidencias.value.sort((a, b) => new Date(b.fechaIso) - new Date(a.fechaIso));


        const maxPaginasIncidents = resIncidents.data.last_page || 1;
        const maxPaginasActions = resActions.data.last_page || 1;
        const maxPaginasContractors = resContractors.data.last_page || 1;

        const paginaMaximaGlobal = Math.max(maxPaginasIncidents, maxPaginasActions, maxPaginasContractors);

        if (paginaActual.value >= paginaMaximaGlobal) {
            hayMasPaginas.value = false;
        } else {
            paginaActual.value++;
        }

    } catch (error) {
        toast.error('Error al cargar la galería de evidencias unificada.');
        console.error(error);
    } finally {
        cargando.value = false;
        cargandoMas.value = false;
    }
}

const evidenciasFiltradas = computed(() => {
    if (filtroActivo.value === 'todos') return evidencias.value
    return evidencias.value.filter(item => item.tipo === filtroActivo.value)
})

onMounted(() => {

    observador = new IntersectionObserver((entradas) => {
        if (entradas[0].isIntersecting && !cargando.value && hayMasPaginas.value) {
            cargarSiguientePagina();
        }
    }, { rootMargin: '100px' });



    cargarSiguientePagina().then(() => {


        if (observadorRef.value) {
            observador.observe(observadorRef.value);
        }
    });
})

onUnmounted(() => {
    if (observador && observadorRef.value) {
        observador.unobserve(observadorRef.value);
    }
})
</script>

<template>
    <div class="h-full flex flex-col">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Portafolio de evidencias
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">
                    Galería de archivos adjuntos y reportes
                </p>
            </div>

            <div
                class="flex bg-white dark:bg-[#0a1628] p-1 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm w-max">
                <button @click="filtroActivo = 'todos'"
                    :class="filtroActivo === 'todos' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Todo
                </button>
                <button @click="filtroActivo = 'imagen'"
                    :class="filtroActivo === 'imagen' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Fotos
                </button>


                <button @click="filtroActivo = 'documento'"
                    :class="filtroActivo === 'documento' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Documentos
                </button>

                <button @click="filtroActivo = 'video'"
                    :class="filtroActivo === 'video' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Videos
                </button>

            </div>
        </div>


        <div v-if="cargando && paginaActual === 1"
            class="flex-1 flex items-center justify-center text-slate-400 text-sm font-medium">
            Analizando y cargando archivos...
        </div>

        <div v-else-if="evidenciasFiltradas.length === 0"
            class="flex-1 flex items-center justify-center text-slate-400 text-sm font-medium border-2 border-dashed border-slate-200 dark:border-white/10 ">
            No se encontraron evidencias para este filtro.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 pb-4 items-start">

            <div v-for="item in evidenciasFiltradas" :key="item.id"
                class="break-inside-avoid relative group overflow-hidden shadow-sm border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0d1b2a] hover:shadow-lg transition-all hover:-translate-y-1">

                <div v-if="item.tipo === 'imagen'" class="relative">
                    <img :src="item.url" class="w-full h-auto object-cover" loading="lazy" />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                        <span class="text-amber-400 text-[10px] font-bold tracking-widest uppercase">{{ item.peaje
                            }}</span>
                        <strong
                            class="text-white text-sm font-['Barlow_Condensed'] uppercase tracking-wide leading-tight">{{
                            item.suceso_tipo }}</strong>

                        <div class="flex items-center justify-between mt-2">
                            <span class="text-slate-300 text-xs">{{ item.fecha }}</span>

                            <button type="button" @click="abrirLightboxRelacionado(item)"
                                class="bg-amber-500 hover:bg-amber-400 text-black text-xs font-bold px-3 py-1.5 rounded transition-colors backdrop-blur-sm cursor-pointer border-none uppercase tracking-wider shadow-lg flex items-center gap-1">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                                </svg>
                                Ampliar
                            </button>
                        </div>
                    </div>
                </div>



                <div v-else-if="item.tipo === 'video'" class="relative flex flex-col bg-black">
                    <video :src="item.url" controls preload="metadata"
                        class="w-full h-auto max-h-78 object-contain"></video>

                    <div
                        class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent p-3 pointer-events-none text-left">
                        <strong class="text-white text-xs font-['Barlow_Condensed'] uppercase tracking-wide">{{
                            item.suceso_tipo }}</strong>
                    </div>
                </div>

                <div v-else-if="item.tipo === 'documento'"
                    class="p-6 flex flex-col items-center justify-center text-center bg-slate-50 dark:bg-white/5 h-48 relative">
                    <div
                        class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center mb-3">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Tipo de doc: {{
                        item.extension }} </span>
                    <strong
                        class="text-sm font-['Barlow_Condensed'] text-slate-800 dark:text-slate-100 uppercase tracking-wide px-2">{{
                            item.suceso_tipo }}</strong>

                    <div
                        class="absolute inset-0 bg-black/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                        <a :href="item.url" target="_blank"
                            class="bg-amber-500 text-[#0d1b2a] font-bold text-xs px-4 py-2 rounded-lg font-['Barlow_Condensed'] uppercase tracking-wider no-underline hover:bg-amber-400">
                            Abrir Archivo
                        </a>
                    </div>
                </div>

                <div v-if="['documento', 'video'].includes(item.tipo)"
                    class="p-3 border-t border-slate-100 dark:border-white/5 bg-white dark:bg-[#0d1b2a] flex justify-between items-center text-left">
                    <span class="text-[10px] text-slate-500 truncate mr-2">Ref: #{{ item.suceso_id }} - {{ item.peaje
                    }}</span>
                    <span class="text-[10px] text-slate-400">{{ item.fecha }}</span>
                </div>

            </div>
        </div>

        <div ref="observadorRef" class="w-full h-16 flex items-center justify-center mt-6 mb-10">
            <div v-if="cargandoMas" class="flex flex-col items-center gap-2">
                <svg class="animate-spin h-6 w-6 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span class="text-slate-400 text-xs font-bold uppercase tracking-widest font-['Barlow_Condensed']">
                    Cargando historial anterior...
                </span>
            </div>

            <div v-else-if="!hayMasPaginas && evidencias.length > 0"
                class="text-slate-500 text-[11px] font-bold uppercase tracking-widest bg-slate-100 dark:bg-white/5 px-4 py-2 rounded-full border border-slate-200 dark:border-white/10">
                Fin del archivo fotográfico
            </div>
        </div>
    </div>

    <div ref="observadorRef" class="w-full h-16 flex items-center justify-center mt-6 mb-10">
    </div>

    <ImageLightbox :mostrar="mostrarLightbox" :imagenes="imagenesRelacionadas" :imagenInicial="imagenSeleccionada"
        @cerrar="mostrarLightbox = false" />


</template>