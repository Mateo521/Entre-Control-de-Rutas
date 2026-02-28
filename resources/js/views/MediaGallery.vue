<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const cargando = ref(true)
const evidencias = ref([])
const filtroActivo = ref('todos') // 'todos', 'imagen', 'documento', 'videso' 

const cargarEvidencias = async () => {
    cargando.value = true
    try {
        let paginaActual = 1;
        let ultimaPagina = 1;
        let todosLosSucesos = [];

        // 1. Recorremos todas las páginas que Laravel tenga disponibles
        do {
            const respuesta = await axios.get('/api/incidents', { 
                params: { 
                    per_page: 50, 
                    page: paginaActual // Le decimos qué página específica queremos
                } 
            });
            
            // Sumamos los sucesos de esta página a nuestro arreglo maestro
            todosLosSucesos = todosLosSucesos.concat(respuesta.data.data);
            
            // Laravel guarda el total de páginas en 'last_page' (o meta.last_page en Resources)
            ultimaPagina = respuesta.data.last_page || (respuesta.data.meta && respuesta.data.meta.last_page) || 1;
            
            paginaActual++;
        } while (paginaActual <= ultimaPagina);

        // 2. Ahora procesamos TODOS los sucesos recolectados
        let mediaExtraida = []

        todosLosSucesos.forEach(suceso => {
            if (suceso.media_paths) {
                Object.entries(suceso.media_paths).forEach(([campoNombre, rutas]) => {
                    rutas.forEach(ruta => {
                        const extension = ruta.split('.').pop().toLowerCase()
                        let tipoGeneral = 'documento'

                        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)) {
                            tipoGeneral = 'imagen'
                        } else if (['mp4', 'mov', 'webm'].includes(extension)) {
                            tipoGeneral = 'video'
                        }

                        mediaExtraida.push({
                            id: Math.random().toString(36).substring(7), 
                            url: ruta,
                            tipo: tipoGeneral,
                            extension: extension.toUpperCase(),
                            campo: campoNombre.replace(/_/g, ' '),
                            suceso_id: suceso.id,
                            suceso_tipo: suceso.incident_type.replace(/_/g, ' '),
                            peaje: suceso.toll ? suceso.toll.name : 'Traza',
                            fecha: new Date(suceso.created_at).toLocaleDateString('es-AR')
                        })
                    })
                })
            }
        })

        evidencias.value = mediaExtraida
    } catch (error) {
        toast.error('Error al cargar la galería de evidencias.')
    } finally {
        cargando.value = false
    }
}

// Filtro computado para los botones
const evidenciasFiltradas = computed(() => {
    if (filtroActivo.value === 'todos') return evidencias.value
    return evidencias.value.filter(item => item.tipo === filtroActivo.value)
})

onMounted(() => {
    cargarEvidencias()
})
</script>

<template>
    <div class="h-full flex flex-col">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Portafolio de evidencias
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">
                    Galería de archivos adjuntos y reportes
                </p>
            </div>

            <div class="flex bg-white dark:bg-[#0a1628] p-1 rounded-lg border border-slate-200 dark:border-white/10 shadow-sm w-max">
                <button @click="filtroActivo = 'todos'" :class="filtroActivo === 'todos' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Todo
                </button>
                <button @click="filtroActivo = 'imagen'" :class="filtroActivo === 'imagen' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Fotos
                </button>
                <button @click="filtroActivo = 'documento'" :class="filtroActivo === 'documento' ? 'bg-amber-500 text-[#0d1b2a] shadow' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'" class="px-4 py-1.5 rounded-md text-xs font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer">
                    Documentos
                </button>
            </div>
        </div>

        <div v-if="cargando" class="flex-1 flex items-center justify-center text-slate-400 text-sm font-medium">
            Analizando y cargando archivos...
        </div>

        <div v-else-if="evidenciasFiltradas.length === 0" class="flex-1 flex items-center justify-center text-slate-400 text-sm font-medium border-2 border-dashed border-slate-200 dark:border-white/10 rounded-xl">
            No se encontraron evidencias para este filtro.
        </div>

        <div v-else class="columns-1 sm:columns-2 md:columns-3 lg:columns-4 gap-5 space-y-5 pb-10">
            
            <div v-for="item in evidenciasFiltradas" :key="item.id" class="break-inside-avoid relative group  overflow-hidden shadow-sm border border-slate-200 dark:border-white/10 bg-white dark:bg-[#0d1b2a] hover:shadow-lg transition-all hover:-translate-y-1">
                
                <div v-if="item.tipo === 'imagen'" class="relative">
                    <img :src="item.url" class="w-full h-auto object-cover" loading="lazy" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-4">
                        <span class="text-amber-400 text-[10px] font-bold tracking-widest uppercase">{{ item.peaje }}</span>
                        <strong class="text-white text-sm font-['Barlow_Condensed'] uppercase tracking-wide leading-tight">{{ item.suceso_tipo }}</strong>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-slate-300 text-xs">{{ item.fecha }}</span>
                            <a :href="item.url" target="_blank" class="bg-white/20 hover:bg-amber-500 hover:text-black text-white p-1.5 rounded transition-colors backdrop-blur-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div v-else-if="item.tipo === 'documento'" class="p-6 flex flex-col items-center justify-center text-center bg-slate-50 dark:bg-white/5 h-48 relative">
                    <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center mb-3">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">{{ item.extension }} Document</span>
                    <strong class="text-sm font-['Barlow_Condensed'] text-slate-800 dark:text-slate-100 uppercase tracking-wide px-2">{{ item.suceso_tipo }}</strong>
                    
                    <div class="absolute inset-0 bg-black/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                        <a :href="item.url" target="_blank" class="bg-amber-500 text-[#0d1b2a] font-bold text-xs px-4 py-2 rounded-lg font-['Barlow_Condensed'] uppercase tracking-wider no-underline hover:bg-amber-400">
                            Abrir Archivo
                        </a>
                    </div>
                </div>

                <div v-if="item.tipo === 'documento'" class="p-3 border-t border-slate-100 dark:border-white/5 bg-white dark:bg-[#0d1b2a] flex justify-between items-center">
                    <span class="text-[10px] text-slate-500 truncate mr-2">Ref: #{{ item.suceso_id }} - {{ item.peaje }}</span>
                    <span class="text-[10px] text-slate-400">{{ item.fecha }}</span>
                </div>

            </div>
        </div>
    </div>
</template>