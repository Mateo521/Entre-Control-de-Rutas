<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const peajes = ref([])
const resultados = ref([])
const cargando = ref(false)
const busquedaRealizada = ref(false)

// Objeto reactivo que almacena los criterios de búsqueda
const filtros = ref({
    toll_id: '',
    incident_type: '',
    date_from: '',
    date_to: ''
})

const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        peajes.value = respuesta.data
    } catch (error) {
        toast.error('Error al cargar las estaciones')
    }
}

const ejecutarBusqueda = async () => {
    cargando.value = true
    busquedaRealizada.value = true
    try {
        // Axios convertirá el objeto 'filtros' en parámetros URL de forma automática
        const respuesta = await axios.get('/api/incidents', { params: filtros.value })
        resultados.value = respuesta.data
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
        'accidente_vial': 'Accidente Vial'
    };
    return tipos[tipo] || tipo;
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

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 rounded-xl shadow-sm mb-6 transition-colors">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-1 rounded-t-xl"></div>
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

        <div v-if="busquedaRealizada" class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm transition-colors">
            <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3 flex justify-between items-center">
                <span class="font-['Barlow_Condensed'] font-bold text-[15px] text-slate-900 dark:text-slate-100">Resultados obtenidos</span>
                <span class="text-xs font-semibold text-amber-600 dark:text-amber-500 bg-amber-50 dark:bg-amber-500/10 px-2.5 py-1 rounded-md">{{ resultados.length }} registros</span>
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
                                <div v-if="suceso.dynamic_data && Object.keys(suceso.dynamic_data).length > 0" class="relative group/tooltip">
                                    <span class="inline-flex items-center gap-1 text-[12px] text-amber-600 dark:text-amber-500 cursor-help border-b border-dashed border-amber-500/50">Ver datos operativos</span>
                                    <div class="absolute bottom-full left-0 mb-2 hidden group-hover/tooltip:block w-max max-w-xs bg-slate-900 text-white text-xs rounded-lg p-3 shadow-xl z-10 border border-slate-700">
                                        <div v-for="(valor, clave) in suceso.dynamic_data" :key="clave" class="mb-1 grid grid-cols-[auto_1fr] gap-2">
                                            <span class="font-semibold text-amber-400">{{ clave }}:</span>
                                            <span>{{ typeof valor === 'boolean' ? (valor ? 'Sí' : 'No') : (valor || 'N/A') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span v-else class="text-[12px] text-slate-400 italic">Sin datos</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>