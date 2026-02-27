<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'


const sucesos = ref([])
const cargando = ref(true)
const verArchivados = ref(false)
const filtroEstado = ref('')


const paginaActual = ref(1)
const ultimaPagina = ref(1)
const totalRegistros = ref(0)


const mostrarDetalles = ref(false)
const detallesActivos = ref({})
const sucesoDetalle = ref(null)


const sucesoAArchivar = ref(null)
const mostrarModalArchivar = ref(false)


const sucesoActivo = ref(null)
const mediosActivos = ref({})
const mostrarGaleria = ref(false)


const cargarSucesos = async (page = 1) => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/incidents', {
            params: {
                status: filtroEstado.value, 
                archived: verArchivados.value ? 1 : 0,
                page: page
            }
        })

    
        sucesos.value = respuesta.data.data

     
        paginaActual.value = respuesta.data.current_page
        ultimaPagina.value = respuesta.data.last_page
        totalRegistros.value = respuesta.data.total
    } catch (error) {
        toast.error('Error al cargar los sucesos')
    } finally {
        cargando.value = false
    }
}

const cambiarPestana = (estado) => {
    verArchivados.value = estado
    cargarSucesos(1)
}


const confirmarArchivado = (suceso) => {
    sucesoAArchivar.value = suceso
    mostrarModalArchivar.value = true
}

const ejecutarArchivado = async () => {
    if (!sucesoAArchivar.value) return

    try {
        await axios.delete(`/api/incidents/${sucesoAArchivar.value.id}`)
        toast.success('Suceso transferido al archivo histórico')
        mostrarModalArchivar.value = false
        sucesoAArchivar.value = null
        cargarSucesos(1)
    } catch (error) {
        toast.error('No se pudo archivar el registro')
    }
}


const formatearFecha = (fechaString) => {
    if (!fechaString) return '';
    const fecha = new Date(fechaString);
    return fecha.toLocaleString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

const traducirTipo = (tipo) => {
    const tipos = {
        'pesaje_excedido': 'Pesaje Excedido',
        'fuga_peaje': 'Fuga de Peaje',
        'animal_ruta': 'Animal en Ruta',
        'accidente_vial': 'Accidente Vial',
        'falla_infraestructura': 'Falla en Infraestructura'
    };
    return tipos[tipo] || tipo;
}


const abrirGaleria = (suceso) => {
    sucesoActivo.value = suceso
    let medios = suceso.media_paths || {}
 
    for (let key in medios) {
        if (!Array.isArray(medios[key])) medios[key] = [medios[key]];
    }
    mediosActivos.value = medios
    mostrarGaleria.value = true
}

const cerrarGaleria = () => {
    mostrarGaleria.value = false
    mediosActivos.value = {}
    sucesoActivo.value = null
}

const esImagen = (ruta) => {
    if (!ruta) return false;
    return ruta.match(/\.(jpeg|jpg|gif|png)$/i) != null;
}

const esVideo = (ruta) => {
    if (!ruta) return false;
    return ruta.match(/\.(mp4|webm|ogg)$/i) != null;
}


const abrirDetalles = (suceso) => {
    sucesoDetalle.value = suceso
    detallesActivos.value = suceso.dynamic_data || {}
    mostrarDetalles.value = true
}

const cerrarDetalles = () => {
    mostrarDetalles.value = false
    detallesActivos.value = {}
    sucesoDetalle.value = null
}

onMounted(() => {
    cargarSucesos(1)
})
</script>

<template>
    <div class="h-full relative">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Historial Operativo
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">
                    Consulta de sucesos y auditoría de evidencia física
                </p>
            </div>

            <div class="flex gap-3">
                <div
                    class="bg-slate-200 dark:bg-[#0a1628] p-1 rounded-lg flex border border-slate-300 dark:border-white/10">
                    <button @click="cambiarPestana(false)"
                        :class="!verArchivados ? 'bg-white dark:bg-white/10 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                        class="px-4 py-1.5 rounded-md text-xs font-bold font-['Barlow_Condensed'] tracking-wider uppercase transition-all cursor-pointer border-none">
                        Activos
                    </button>
                    <button @click="cambiarPestana(true)"
                        :class="verArchivados ? 'bg-slate-800 dark:bg-white/10 text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                        class="px-4 py-1.5 rounded-md text-xs font-bold font-['Barlow_Condensed'] tracking-wider uppercase transition-all cursor-pointer border-none">
                        Archivados
                    </button>
                </div>

                <router-link to="/panel/sucesos/nuevo"
                    class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-amber-400 hover:-translate-y-px">
                    + Reportar Suceso
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
                                Fecha y Hora</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Estación</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Tipo de Suceso</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Datos Operativos</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                Evidencia</th>
                            <th
                                class="px-4 py-3 text-right font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">Cargando base de
                                datos...</td>
                        </tr>
                        <tr v-else-if="sucesos.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">No se encontraron
                                sucesos en esta categoría.</td>
                        </tr>

                        <tr v-else v-for="suceso in sucesos" :key="suceso.id"
                            class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-b border-slate-100 dark:border-white/5 group">

                            <td class="px-4 py-3 text-[13px] text-slate-600 dark:text-slate-400 font-medium">
                                {{ formatearFecha(suceso.created_at) }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ suceso.toll ? suceso.toll.name : 'Estación no definida' }}
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-md text-[11.5px] font-bold tracking-wide uppercase bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                                    {{ traducirTipo(suceso.incident_type) }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <button v-if="suceso.dynamic_data && Object.keys(suceso.dynamic_data).length > 0"
                                    @click="abrirDetalles(suceso)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-[11px] font-bold tracking-wider uppercase bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-500/30 transition-colors border-none cursor-pointer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    Ver detalles ({{ Object.keys(suceso.dynamic_data).length }})
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">Sin datos</span>
                            </td>

                            <td class="px-4 py-3">
                                <button v-if="suceso.media_paths && Object.keys(suceso.media_paths).length > 0"
                                    @click="abrirGaleria(suceso)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-[11px] font-bold tracking-wider uppercase bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-500/30 transition-colors border-none cursor-pointer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    Ver adjuntos
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">No requiere</span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">

                                    <router-link v-if="!verArchivados" :to="`/panel/sucesos/editar/${suceso.id}`"
                                        class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded transition-colors cursor-pointer">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </router-link>

                                    <button v-if="!verArchivados" @click="confirmarArchivado(suceso)"
                                        class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 rounded transition-colors cursor-pointer border-none bg-transparent">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <polyline points="21 8 21 21 3 21 3 8"></polyline>
                                            <rect x="1" y="3" width="22" height="5"></rect>
                                            <line x1="10" y1="12" x2="14" y2="12"></line>
                                        </svg>
                                    </button>

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
                        <button @click="cargarSucesos(paginaActual - 1)" :disabled="paginaActual === 1"
                            class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Anterior
                        </button>
                        <button @click="cargarSucesos(paginaActual + 1)" :disabled="paginaActual === ultimaPagina"
                            class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Siguiente
                        </button>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="mostrarModalArchivar"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm p-4">
            <div
                class="w-full max-w-sm bg-white dark:bg-[#0a1628]  shadow-2xl overflow-hidden border border-slate-200 dark:border-white/10 text-center p-6">
                <div
                    class="w-12 h-12 bg-red-100 dark:bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600 dark:text-red-400">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="21 8 21 21 3 21 3 8"></polyline>
                        <rect x="1" y="3" width="22" height="5"></rect>
                        <line x1="10" y1="12" x2="14" y2="12"></line>
                    </svg>
                </div>
                <h3 class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 mb-2">
                    ¿Archivar registro?</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">El suceso #{{ sucesoAArchivar?.id }} será
                    movido a la tabla de archivados. Pasará a estado de Solo Lectura y no podrá ser modificado en el
                    futuro.</p>

                <div class="flex gap-3 justify-center">
                    <button @click="mostrarModalArchivar = false"
                        class="px-5 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 transition-colors border-none cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">Cancelar</button>
                    <button @click="ejecutarArchivado"
                        class="bg-red-500 text-white font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-5 py-2 rounded-lg border-none cursor-pointer hover:bg-red-600 transition-colors">Confirmar
                        Archivo</button>
                </div>
            </div>
        </div>

        <div v-if="mostrarGaleria"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm transition-opacity p-4">
            <div
                class="w-full max-w-4xl bg-white dark:bg-[#0a1628] shadow-2xl overflow-hidden flex flex-col max-h-full border border-slate-200 dark:border-white/10">
                <div
                    class="px-6 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between bg-slate-50 dark:bg-[#0d1b2a]">
                    <div>
                        <h3
                            class="font-['Barlow_Condensed'] text-[22px] font-extrabold text-slate-900 dark:text-slate-100 tracking-wide m-0">
                            Portafolio de Evidencia</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">
                            <span class="text-amber-600 dark:text-amber-500 font-bold">{{
                                traducirTipo(sucesoActivo?.incident_type) }}</span>
                            · Reportado el {{ formatearFecha(sucesoActivo?.created_at) }}
                        </p>
                    </div>
                    <button @click="cerrarGaleria"
                        class="text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer transition-colors p-1">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto bg-slate-100 dark:bg-[#0a1628] flex-1">


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <template v-for="(rutas, nombreCampo) in mediosActivos" :key="nombreCampo">
                            <div v-for="(ruta, idx) in rutas" :key="`${nombreCampo}-${idx}`"
                                class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm flex flex-col ">
                                <div
                                    class="px-4 py-2.5 bg-slate-50 dark:bg-[#0d1b2a] border-b border-slate-200 dark:border-white/10 flex items-center justify-between">
                                    <span
                                        class="text-[11px] font-bold text-slate-600 dark:text-slate-400 uppercase tracking-widest font-['Barlow_Condensed']">
                                        {{ nombreCampo.replace('_', ' ') }} {{ rutas.length > 1 ? `#${idx + 1}` : '' }}
                                    </span>
                                </div>
                                <div
                                    class="aspect-video bg-slate-200 dark:bg-black/40 flex items-center justify-center relative group overflow-hidden">
                                    <img v-if="esImagen(ruta)" :src="ruta"
                                        class="w-full h-full object-cover bg-black/5" />
                                    <video v-else-if="esVideo(ruta)" :src="ruta" controls
                                        class="w-full h-full object-contain bg-black"></video>
                                    <div v-else class="flex flex-col items-center gap-3 p-6 text-center">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="1.5" class="text-slate-400">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        <span class="text-sm text-slate-500">Documento no previsualizable</span>
                                    </div>
                                    <a :href="ruta" target="_blank"
                                        class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white no-underline font-['Barlow_Condensed'] tracking-widest text-sm font-bold uppercase z-10">
                                        Abrir archivo original
                                    </a>
                                </div>
                            </div>
                        </template>



                    </div>


                </div>
            </div>
        </div>

        <div v-if="mostrarDetalles"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm transition-opacity p-4">
            <div
                class="w-full max-w-md bg-white dark:bg-[#0a1628] shadow-2xl overflow-hidden flex flex-col border border-slate-200 dark:border-white/10">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-1"></div>
                <div
                    class="px-6 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between bg-slate-50 dark:bg-[#0d1b2a]">
                    <div>
                        <h3
                            class="font-['Barlow_Condensed'] text-[20px] font-extrabold text-slate-900 dark:text-slate-100 tracking-wide m-0">
                            Requerimientos Operativos</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Auditoría de campos
                            dinámicos</p>
                    </div>
                    <button @click="cerrarDetalles"
                        class="text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer transition-colors p-1">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <div class="p-6 bg-slate-100 dark:bg-[#0a1628]">
                    <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  p-4 shadow-sm">
                        <div v-for="(valor, clave) in detallesActivos" :key="clave"
                            class="mb-3 last:mb-0 grid grid-cols-[1fr_1.5fr] gap-4 items-center border-b border-slate-100 dark:border-white/5 pb-3 last:border-0 last:pb-0">
                            <span
                                class="text-[12px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest font-['Barlow_Condensed']">{{
                                    clave.replace('_', ' ') }}</span>
                            <span
                                class="text-sm font-medium text-slate-900 dark:text-slate-100 break-words bg-slate-50 dark:bg-black/20 px-3 py-1.5 rounded-md border border-slate-100 dark:border-white/5">
                                {{ typeof valor === 'boolean' ? (valor ? 'Confirmado' : 'Rechazado') : (valor || 'N/A')
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="px-6 py-4 bg-slate-50 dark:bg-[#0d1b2a] border-t border-slate-200 dark:border-white/10 flex justify-end">
                    <button @click="cerrarDetalles"
                        class="px-5 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-white/10 transition-colors border-none bg-slate-200/50 dark:bg-white/5 cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">Cerrar
                        panel</button>
                </div>
            </div>
        </div>

    </div>
</template>