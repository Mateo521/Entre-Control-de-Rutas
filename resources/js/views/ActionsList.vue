<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const acciones = ref([])
const cargando = ref(true)

const verArchivados = ref(false)
const mostrarModalArchivar = ref(false)
const accionAArchivar = ref(null)


const mostrarGaleria = ref(false)
const mediosActivos = ref([])
const accionActiva = ref(null)


const paginaActual = ref(1);
const ultimaPagina = ref(1);
const totalRegistros = ref(0);



const mostrarDetalles = ref(false)
const accionDetalle = ref(null)

const cargarAcciones = async (page = 1) => {
    cargando.value = true;
    try {
        const respuesta = await axios.get('/api/actions', {
            params: {
                archived: verArchivados.value ? 1 : 0,
                page: page  
            }
        });

  
        acciones.value = respuesta.data.data;

      
        paginaActual.value = respuesta.data.current_page;
        ultimaPagina.value = respuesta.data.last_page;
        totalRegistros.value = respuesta.data.total;

    } catch (error) {
        toast.error('Error al cargar el historial de acciones');
    } finally {
        cargando.value = false;
    }
}



const cambiarPestana = (estado) => {
    verArchivados.value = estado;
    cargarAcciones(1);
}

const confirmarArchivado = (accion) => {
    accionAArchivar.value = accion
    mostrarModalArchivar.value = true
}

const ejecutarArchivado = async () => {
    if (!accionAArchivar.value) return
    try {
        await axios.delete(`/api/actions/${accionAArchivar.value.id}`)
        toast.success('Reporte movido al archivo histórico')
        mostrarModalArchivar.value = false
        accionAArchivar.value = null
        cargarAcciones()
    } catch (error) {
        toast.error('No se pudo archivar el registro')
    }
}

const formatearFecha = (fechaString) => {
    if (!fechaString) return '';
    return new Date(fechaString).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}


const abrirDetalles = (accion) => {
    accionDetalle.value = accion
    mostrarDetalles.value = true
}
const cerrarDetalles = () => {
    mostrarDetalles.value = false
    accionDetalle.value = null
}


const abrirGaleria = (accion) => {
    accionActiva.value = accion
    mediosActivos.value = accion.media_paths || []
    mostrarGaleria.value = true
}
const cerrarGaleria = () => {
    mostrarGaleria.value = false
    mediosActivos.value = []
    accionActiva.value = null
}

const esImagen = (ruta) => ruta && ruta.match(/\.(jpeg|jpg|gif|png)$/i) != null;
const esVideo = (ruta) => ruta && ruta.match(/\.(mp4|webm|ogg)$/i) != null;

onMounted(() => {
    cargarAcciones()
})
</script>

<template>
    <div class="h-full relative">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Gestión de Acciones
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">
                    Registro de mantenimientos, compras y tareas operativas
                </p>
            </div>

            <div class="flex gap-3">
                <div
                    class="bg-slate-200 dark:bg-[#0a1628] p-1 rounded-lg flex border border-slate-300 dark:border-white/10">
                    <button @click="cambiarPestana(false)"
                        :class="!verArchivados ? 'bg-white dark:bg-white/10 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                        class="px-4 py-1.5 rounded-md text-xs font-bold font-['Barlow_Condensed'] tracking-wider uppercase transition-all cursor-pointer border-none">Activos</button>
                    <button @click="cambiarPestana(true)"
                        :class="verArchivados ? 'bg-slate-800 dark:bg-white/10 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                        class="px-4 py-1.5 rounded-md text-xs font-bold font-['Barlow_Condensed'] tracking-wider uppercase transition-all cursor-pointer border-none">Archivados</button>
                </div>
                <router-link to="/panel/acciones/nuevo"
                    class="bg-emerald-600 text-white font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-emerald-500 hover:-translate-y-px">
                    + Registrar Acción
                </router-link>
            </div>
        </div>

        <div
            class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm dark:shadow-none transition-colors">




            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Fecha</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Ubicación</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Categoría y Tarea</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Descripción</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-emerald-600 dark:text-emerald-500">
                                Evidencia</th>
                            <th
                                class="px-4 py-3 text-right font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">Cargando reportes...
                            </td>
                        </tr>
                        <tr v-else-if="acciones.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">No se encontraron
                                acciones operativas registradas.</td>
                        </tr>

                        <tr v-else v-for="accion in acciones" :key="accion.id"
                            class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-b border-slate-100 dark:border-white/5 group">
                            <td class="px-4 py-3 text-[13px] text-slate-600 dark:text-slate-400 font-medium">
                                {{ formatearFecha(accion.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="accion.toll" class="font-semibold text-slate-900 dark:text-slate-100">{{
                                    accion.toll.name }}</div>
                                <div v-else
                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                                    Región Centro General</div>
                            </td>


                            <td class="px-4 py-3">
                                <div
                                    class="text-[10px] font-bold tracking-widest uppercase text-emerald-600 dark:text-emerald-500 mb-0.5">
                                    {{ accion.category }}</div>
                                <div class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ accion.title }}
                                </div>
                            </td>


                            <td class="px-4 py-3">
                                <button @click="abrirDetalles(accion)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-[11px] font-bold tracking-wider uppercase bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-white/20 transition-colors border-none cursor-pointer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                    Leer Reporte
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="accion.media_paths && accion.media_paths.length > 0"
                                    @click="abrirGaleria(accion)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-[11px] font-bold tracking-wider uppercase bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/30 transition-colors border-none cursor-pointer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    Ver adjuntos ({{ accion.media_paths.length }})
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">No requiere</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <router-link v-if="!verArchivados" :to="`/panel/acciones/editar/${accion.id}`"
                                        class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded transition-colors cursor-pointer"><svg
                                            width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg></router-link>
                                    <button v-if="!verArchivados" @click="confirmarArchivado(accion)"
                                        class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 rounded transition-colors cursor-pointer border-none bg-transparent"><svg
                                            width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round">
                                            <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                            <rect x="1" y="3" width="22" height="5"></rect>
                                            <line x1="10" y1="12" x2="14" y2="12"></line>
                                        </svg></button>
                                    <span v-if="verArchivados"
                                        class="text-[10px] font-bold tracking-widest uppercase text-slate-400 px-2 py-1 bg-slate-100 dark:bg-white/5 rounded border border-slate-200 dark:border-white/10">Solo
                                        Lectura</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="totalRegistros > 0"
                    class="bg-slate-50 dark:bg-[#0d1b2a] border-t border-slate-200 dark:border-white/10 px-5 py-3 flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Mostrando página {{ paginaActual }} de {{ ultimaPagina }} ({{ totalRegistros }} registros
                        totales)
                    </span>
                    <div class="flex gap-2">
                        <button @click="cargarAcciones(paginaActual - 1)" :disabled="paginaActual === 1"
                            class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Anterior
                        </button>
                        <button @click="cargarAcciones(paginaActual + 1)" :disabled="paginaActual === ultimaPagina"
                            class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Siguiente
                        </button>
                    </div>
                </div>


            </div>
        </div>

        <div v-if="mostrarDetalles"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4">
            <div
                class="w-full max-w-2xl bg-white dark:bg-[#0a1628]  shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 h-1"></div>
                <div
                    class="px-6 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between bg-slate-50 dark:bg-[#0d1b2a]">
                    <h3 class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 m-0">
                        {{ accionDetalle?.title }}</h3>
                    <button @click="cerrarDetalles"
                        class="text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer"><svg
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg></button>
                </div>
                <div class="p-6 bg-slate-100 dark:bg-[#0a1628]">
                    <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  p-5 shadow-sm">
                        <p
                            class="text-[13.5px] text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap font-sans m-0">
                            {{ accionDetalle?.description }}</p>
                    </div>
                </div>
                <div
                    class="px-6 py-4 bg-slate-50 dark:bg-[#0d1b2a] border-t border-slate-200 dark:border-white/10 flex justify-end">
                    <button @click="cerrarDetalles"
                        class="px-5 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-white/10 bg-slate-200/50 dark:bg-white/5 cursor-pointer font-['Barlow_Condensed'] uppercase border-none">Cerrar</button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalArchivar"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4">
            <div
                class="w-full max-w-sm bg-white dark:bg-[#0a1628]  shadow-2xl p-6 text-center border border-slate-200 dark:border-white/10">
                <div
                    class="w-12 h-12 bg-red-100 dark:bg-red-500/20 rounded-full flex mx-auto mb-4 items-center justify-center text-red-600 dark:text-red-400">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="21 8 21 21 3 21 3 8"></polyline>
                        <rect x="1" y="3" width="22" height="5"></rect>
                    </svg></div>
                <h3 class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 mb-2">
                    ¿Archivar reporte?</h3>
                <p class="text-sm text-slate-500 mb-6">El registro de mantenimiento va a ser movido al histórico de solo
                    lectura.</p>
                <div class="flex gap-3 justify-center">
                    <button @click="mostrarModalArchivar = false"
                        class="px-5 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border-none cursor-pointer font-['Barlow_Condensed'] uppercase">Cancelar</button>
                    <button @click="ejecutarArchivado"
                        class="bg-red-500 text-white font-['Barlow_Condensed'] text-xs font-bold uppercase px-5 py-2 rounded-lg border-none cursor-pointer hover:bg-red-600">Confirmar</button>
                </div>
            </div>
        </div>

        <div v-if="mostrarGaleria"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4">
            <div
                class="w-full max-w-4xl bg-white dark:bg-[#0a1628] shadow-2xl overflow-hidden flex flex-col max-h-full border border-slate-200 dark:border-white/10 ">
                <div
                    class="px-6 py-4 border-b border-slate-200 dark:border-white/10 flex justify-between bg-slate-50 dark:bg-[#0d1b2a]">
                    <h3 class="font-['Barlow_Condensed'] text-[22px] font-bold text-slate-900 dark:text-slate-100">
                        Evidencia Fotográfica</h3>
                    <button @click="cerrarGaleria"
                        class="text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer"><svg
                            width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg></button>
                </div>
                <div class="p-6 overflow-y-auto bg-slate-100 dark:bg-[#0a1628] flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-for="(ruta, idx) in mediosActivos" :key="idx"
                            class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm flex flex-col ">
                            <div
                                class="aspect-video bg-slate-200 dark:bg-black/40 flex items-center justify-center relative group overflow-hidden">
                                <img v-if="esImagen(ruta)" :src="ruta" class="w-full h-full object-cover" />
                                <video v-else-if="esVideo(ruta)" :src="ruta" controls
                                    class="w-full h-full object-contain bg-black"></video>
                                <a :href="ruta" target="_blank"
                                    class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white no-underline font-['Barlow_Condensed'] text-sm font-bold uppercase z-10">Abrir
                                    original</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>