<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const peajes = ref([])
const resultados = ref([])
const cargando = ref(false)
const busquedaRealizada = ref(false)

const filtros = ref({
    toll_id: '',
    incident_type: '',
    date_from: '',
    date_to: ''
})

const mostrarDetalles = ref(false)
const detallesActivos = ref({})
const sucesoDetalle = ref(null)

const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        
        peajes.value = respuesta.data.data 
    } catch (error) {
        toast.error('Error al cargar las estaciones')
    }
}
 
const paginaActual = ref(1)
const ultimaPagina = ref(1)
const totalRegistros = ref(0)
 
const ejecutarBusqueda = async (page = 1) => {
    cargando.value = true
    busquedaRealizada.value = true
    try {
         
        const parametrosDeBusqueda = { ...filtros.value, page: page }
        
        const respuesta = await axios.get('/api/incidents', { params: parametrosDeBusqueda })
        
        
        resultados.value = respuesta.data.data
        
        
        paginaActual.value = respuesta.data.current_page
        ultimaPagina.value = respuesta.data.last_page
        totalRegistros.value = respuesta.data.total
    } catch (error) {
        toast.error('Error al ejecutar la búsqueda cruzada')
    } finally {
        cargando.value = false
    }
}

 
const limpiarFiltros = () => {
    filtros.value = { toll_id: '', incident_type: '', date_from: '', date_to: '' }
    resultados.value = []
    busquedaRealizada.value = false
    totalRegistros.value = 0
    paginaActual.value = 1
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
        'falla_infraestructura': 'Falla en Infraestructura',
        'corte_ruta': 'Corte de Ruta / Manifestación'
    };
    return tipos[tipo] || tipo;
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

 
const exportarExcel = () => {
    if (resultados.value.length === 0) {
        toast.warning('No hay datos para exportar');
        return;
    }
 
    let csvContent = "Fecha y Hora,Estacion,Clasificacion,Datos Operativos Adicionales\n";
 
    resultados.value.forEach(suceso => {
        const fecha = formatearFecha(suceso.created_at).replace(',', ' -');  
        const estacion = suceso.toll ? suceso.toll.name : 'No definida';
        const tipo = traducirTipo(suceso.incident_type);
         
        let datosExtra = "Sin datos";
        if (suceso.dynamic_data && Object.keys(suceso.dynamic_data).length > 0) {
            datosExtra = Object.entries(suceso.dynamic_data)
                .map(([key, value]) => {
                    const cleanKey = key.replace('_', ' ').toUpperCase();
                    const cleanVal = typeof value === 'boolean' ? (value ? 'Si' : 'No') : value;
                    return `${cleanKey}: ${cleanVal}`;
                })
                .join(' | ');
        }
 
        const fila = `"${fecha}","${estacion}","${tipo}","${datosExtra}"\n`;
        csvContent += fila;
    });
 
    const blob = new Blob(["\ufeff", csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
     
    const link = document.createElement("a");
    const fechaDescarga = new Date().toISOString().split('T')[0]; // Ejj: 2026-02-26
    link.setAttribute("href", url);
    link.setAttribute("download", `Auditoria_Rutas_${fechaDescarga}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    toast.success('Reporte descargado');
}

onMounted(() => {
    cargarPeajes()
})
</script>

<template>
    <div>
        <div class="mb-6">
            <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">Búsqueda Avanzada</h2>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">Filtros cruzados para auditoría de pesajes y sucesos</p>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  shadow-sm mb-6 transition-colors">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-1 "></div>
            <form @submit.prevent="ejecutarBusqueda" class="p-5">
                <div class="grid grid-cols-4 gap-4 mb-5">
                    <div>
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Estación</label>
                        <select v-model="filtros.toll_id" class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50">
                            <option value="">Todas las estaciones</option>
                            <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">{{ peaje.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Clasificación</label>
                        <select v-model="filtros.incident_type" class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50">
                            <option value="">Todos los sucesos</option>
                            <option value="pesaje_excedido">Pesajes Excedidos</option>
                            <option value="fuga_peaje">Fugas de Peaje</option>
                            <option value="accidente_vial">Accidentes Viales</option>
                            <option value="falla_infraestructura">Fallas en Infraestructura</option>
                            <option value="corte_ruta">Corte de Ruta / Manifestación</option>
                        </select>
                    </div>

                    <div>
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Desde la fecha</label>
                        <input type="date" v-model="filtros.date_from" class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50 [color-scheme:light] dark:[color-scheme:dark]" />
                    </div>

                    <div>
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Hasta la fecha</label>
                        <input type="date" v-model="filtros.date_to" class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50 [color-scheme:light] dark:[color-scheme:dark]" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-white/5">
                    <button type="button" @click="limpiarFiltros" class="px-5 py-2 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors border-none bg-transparent font-['Barlow_Condensed'] uppercase tracking-wider cursor-pointer">
                        Limpiar Filtros
                    </button>
                    <button type="submit" :disabled="cargando" class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-6 py-2 rounded-lg border-none cursor-pointer inline-flex items-center transition-all hover:bg-amber-400 disabled:opacity-50">
                        {{ cargando ? 'Consultando...' : 'Aplicar Filtros' }}
                    </button>
                </div>
            </form>
        </div>

        <div v-if="busquedaRealizada" class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm transition-colors">
            <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <span class="font-['Barlow_Condensed'] font-bold text-[15px] text-slate-900 dark:text-slate-100">Resultados obtenidos</span>
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-500 bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 rounded-md">{{ resultados.length }} registros</span>
                </div>
                
                <button v-if="resultados.length > 0" @click="exportarExcel" class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/30 px-3 py-1.5 rounded-md text-[11px] font-bold tracking-wider uppercase transition-colors border-none cursor-pointer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Exportar Excel
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-white/10">
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Fecha y Hora</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Estación</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Clasificación</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Auditoría JSON</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500 text-sm">Procesando consulta...</td>
                        </tr>
                        <tr v-else-if="resultados.length === 0">
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500 text-sm">No se encontraron registros que coincidan con los filtros aplicados.</td>
                        </tr>
                        <tr v-else v-for="suceso in resultados" :key="suceso.id" class="hover:bg-black/5 dark:hover:bg-white/5 border-b border-slate-100 dark:border-white/5 group">
                            <td class="px-4 py-3 text-[13px] text-slate-600 dark:text-slate-400 font-medium">{{ formatearFecha(suceso.created_at) }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ suceso.toll ? suceso.toll.name : 'N/A' }}</td>
                            <td class="px-4 py-3"><span class="inline-flex px-2 py-1 rounded-md text-[11px] font-bold uppercase bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">{{ traducirTipo(suceso.incident_type) }}</span></td>
                            <td class="px-4 py-3">
                                <button v-if="suceso.dynamic_data && Object.keys(suceso.dynamic_data).length > 0" 
                                        @click="abrirDetalles(suceso)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded text-[11px] font-bold tracking-wider uppercase bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-500/30 transition-colors border-none cursor-pointer">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                    Ver más ({{ Object.keys(suceso.dynamic_data).length }})
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">Sin datos</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="totalRegistros > 0" class="bg-slate-50 dark:bg-[#0d1b2a] border-t border-slate-200 dark:border-white/10 px-5 py-3 flex items-center justify-between">
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Mostrando página {{ paginaActual }} de {{ ultimaPagina }} ({{ totalRegistros }} registros totales)
                </span>
                <div class="flex gap-2">
                    <button 
                        type="button"
                        @click="ejecutarBusqueda(paginaActual - 1)" 
                        :disabled="paginaActual === 1"
                        class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Anterior
                    </button>
                    <button 
                        type="button"
                        @click="ejecutarBusqueda(paginaActual + 1)" 
                        :disabled="paginaActual === ultimaPagina"
                        class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        Siguiente
                    </button>
                </div>
            </div>

            </div>
        </div>

        <div v-if="mostrarDetalles" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/90 backdrop-blur-sm transition-opacity p-4">
            <div class="w-full max-w-md bg-white dark:bg-[#0a1628]  shadow-2xl overflow-hidden flex flex-col border border-slate-200 dark:border-white/10">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-1"></div>
                <div class="px-6 py-4 border-b border-slate-200 dark:border-white/10 flex items-center justify-between bg-slate-50 dark:bg-[#0d1b2a]">
                    <div>
                        <h3 class="font-['Barlow_Condensed'] text-[20px] font-extrabold text-slate-900 dark:text-slate-100 tracking-wide m-0">Requerimientos Operativos</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Auditoría de campos dinámicos</p>
                    </div>
                    <button @click="cerrarDetalles" class="text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer transition-colors p-1"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                </div>
                <div class="p-6 bg-slate-100 dark:bg-[#0a1628]">
                    <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  p-4 shadow-sm">
                        <div v-for="(valor, clave) in detallesActivos" :key="clave" class="mb-3 last:mb-0 grid grid-cols-[1fr_1.5fr] gap-4 items-center border-b border-slate-100 dark:border-white/5 pb-3 last:border-0 last:pb-0">
                            <span class="text-[12px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest font-['Barlow_Condensed']">{{ clave.replace('_', ' ') }}</span>
                            <span class="text-sm font-medium text-slate-900 dark:text-slate-100 break-words bg-slate-50 dark:bg-black/20 px-3 py-1.5 rounded-md border border-slate-100 dark:border-white/5">
                                {{ typeof valor === 'boolean' ? (valor ? 'Confirmado' : 'Rechazado') : (valor || 'N/A') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-slate-50 dark:bg-[#0d1b2a] border-t border-slate-200 dark:border-white/10 flex justify-end">
                    <button @click="cerrarDetalles" class="px-5 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-white/10 transition-colors border-none bg-slate-200/50 dark:bg-white/5 cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">Cerrar</button>
                </div>
            </div>
        </div>

    </div>
</template>