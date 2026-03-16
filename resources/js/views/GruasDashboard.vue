<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const registros = ref([])
const cargando = ref(true)

 
const listaGruas = ['Grúa Provincial', 'Grúa Desaguadero', 'Grúa La Cumbre']

 
const vistaActual = ref('activos')  
const paginacion = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})

const filtros = ref({
    search: '',
    tow_truck: ''
})

 
const mostrarModalFormulario = ref(false)
const guardando = ref(false)
const modoEdicion = ref(false)
const idEdicion = ref(null)

const formulario = ref({
    tow_truck: '',
    license_plate: '',
    location: '',
    reason: '',
    observations: ''
})

 

const cargarRegistros = async (page = 1) => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/tow-services', {
            params: {
                page: page,
                search: filtros.value.search,
                tow_truck: filtros.value.tow_truck,
                archived: vistaActual.value === 'archivados'  
            }
        })
        
        registros.value = respuesta.data.data
        
        paginacion.value = {
            current_page: respuesta.data.current_page,
            last_page: respuesta.data.last_page,
            total: respuesta.data.total
        }
    } catch (error) {
        toast.error('Error al cargar el historial de acarreos.')
    } finally {
        cargando.value = false
    }
}

 
watch(() => filtros.value.tow_truck, () => {
    cargarRegistros(1)
})

const buscarPorTexto = () => {
    cargarRegistros(1)
}

const limpiarFiltros = () => {
    filtros.value = { search: '', tow_truck: '' }
    cargarRegistros(1)
}

const cambiarVista = (vista) => {
    vistaActual.value = vista
    cargarRegistros(1)
}

 

const abrirModalNuevo = () => {
    modoEdicion.value = false
    idEdicion.value = null
    formulario.value = { tow_truck: '', license_plate: '', location: '', reason: '', observations: '' }
    mostrarModalFormulario.value = true
}

const abrirModalEditar = (registro) => {
    modoEdicion.value = true
    idEdicion.value = registro.id
    formulario.value = { 
        tow_truck: registro.tow_truck, 
        license_plate: registro.license_plate, 
        location: registro.location, 
        reason: registro.reason, 
        observations: registro.observations || ''
    }
    mostrarModalFormulario.value = true
}

const cerrarModal = () => {
    mostrarModalFormulario.value = false
}

const guardarRegistro = async () => {
    guardando.value = true
    try {
        if (modoEdicion.value) {
            await axios.put(`/api/tow-services/${idEdicion.value}`, formulario.value)
            toast.success('Registro actualizado correctamente.')
        } else {
            await axios.post('/api/tow-services', formulario.value)
            toast.success('Acarreo registrado exitosamente.')
        }
        cerrarModal()
        await cargarRegistros(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al guardar. Verifique los datos ingresados.')
    } finally {
        guardando.value = false
    }
}


const archivarRegistro = async (id) => {
    const confirmacion = confirm('¿Está seguro de archivar este registro? Se moverá al historial documental.')
    if (!confirmacion) return

    try {
        await axios.delete(`/api/tow-services/${id}`) 
        toast.info('Registro archivado exitosamente.')
        await cargarRegistros(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al intentar archivar el registro.')
    }
}

 
const restaurarRegistro = async (id) => {
    try {
        await axios.post(`/api/tow-services/${id}/restore`)
        toast.success('Registro restaurado a la vista principal.')
        await cargarRegistros(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al restaurar el registro.')
    }
}

const formatearFecha = (fecha) => {
    if(!fecha) return '';
    return new Date(fecha).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(() => {
    cargarRegistros()
})
</script>

<template>
    <div class="flex flex-col relative h-full space-y-6">
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 tracking-wide uppercase">
                    Auditoría de Grúas
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 font-medium">
                    Control de acarreos y facturación de flota tercerizada.
                </p>
            </div>

            <button @click="abrirModalNuevo" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" viewBox="0 0 92.983 92.983" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g fill="currentColor">
                        <path d="M48.633,44.708l-0.887,4.268l-3.397-0.708L45.239,44L48.633,44.708z"/>
                        <path d="M92.983,49.379l-4.464-0.936l-3.933-11.97l-25.388-5.351l-8.427,9.367l-12.594-2.647l-1.591,7.55l-1.541-0.323l-0.767,3.643c-0.236,0.119-0.49,0.205-0.775,0.205c-0.95,0-1.724-0.771-1.724-1.726c0-0.952,0.776-1.728,1.724-1.728h0.544V33.768l0.191-0.165l-4.573-6.332L1.137,51.222H0v10.279h17.859c0.654,2.413,2.842,4.206,5.457,4.206c2.616,0,4.807-1.793,5.461-4.206h5.305v-3.107h1.395v-4.356h-1.395v-2.812H13.694l19.264-16.523v9.727c-1.292,0.252-2.269,1.403-2.269,2.767c0,1.55,1.261,2.814,2.815,2.814c0.561,0,1.054-0.206,1.493-0.488l0.696,0.144l-0.562,2.676l8.859,1.861c-0.04,2.66,1.803,5.064,4.5,5.634c0.388,0.083,0.782,0.124,1.178,0.124c2.294,0,4.334-1.41,5.21-3.459l16.433,3.454c-0.023,1.122,0.292,2.227,0.921,3.185c0.831,1.268,2.104,2.14,3.584,2.449c0.388,0.082,0.782,0.123,1.177,0.123c2.293,0,4.332-1.409,5.208-3.458l7.729,1.624L92.983,49.379z M20.824,37.122h7.641l-7.641,6.556V37.122z M19.735,43.702h-7.937l7.937-6.662V43.702z M20.929,36.031l7.489-6.281v6.281H20.929z M10.569,44.732v6.49H2.837L10.569,44.732z M23.32,63.527c-1.925,0-3.495-1.569-3.495-3.495c0-1.932,1.567-3.499,3.495-3.499c1.929,0,3.497,1.567,3.497,3.499C26.817,61.958,25.247,63.527,23.32,63.527z M31.906,59.325h-2.976c-0.353-2.797-2.719-4.973-5.604-4.973c-2.887,0-5.253,2.176-5.603,4.973H2.191v-5.922h29.722L31.906,59.325L31.906,59.325z M11.66,51.222V44.79h7.865l-7.498,6.432H11.66z M29.505,36.227v-7.328l3.268,4.528L29.505,36.227z M53.083,54.998c-0.388,1.848-2.274,3.096-4.137,2.698c-1.889-0.396-3.099-2.258-2.702-4.14c0.341-1.608,1.777-2.776,3.419-2.776c0.241,0,0.485,0.025,0.726,0.073c0.914,0.191,1.696,0.729,2.203,1.51C53.103,53.15,53.277,54.083,53.083,54.998z M80.41,60.752c-0.388,1.844-2.265,3.089-4.14,2.688c-0.917-0.191-1.702-0.726-2.208-1.506c-0.512-0.787-0.689-1.715-0.493-2.635c0.338-1.607,1.774-2.775,3.415-2.775c0.244,0,0.485,0.029,0.728,0.077c0.912,0.191,1.697,0.728,2.206,1.511C80.426,58.905,80.604,59.835,80.41,60.752z M82.664,60.11c0.016-1.12-0.297-2.222-0.924-3.178c-0.826-1.275-2.101-2.146-3.581-2.457c-2.655-0.545-5.347,0.94-6.392,3.342l-16.429-3.455c0.019-1.118-0.296-2.223-0.921-3.185c-0.831-1.269-2.104-2.14-3.586-2.45c-2.646-0.56-5.345,0.933-6.384,3.344l-6.734-1.419l2.151-10.222l11.687,2.463l8.424-9.366l22.927,4.826l3.938,11.971l3.559,0.744l-2.153,10.224L82.664,60.11z M81.868,42.155l2.429,7.391l-3.813-0.806L81.868,42.155z M70.042,37.867L80.1,39.978l-1.752,8.318l-10.053-2.114L70.042,37.867z M60.761,35.91l7.15,1.509l-1.752,8.312l-11.962-2.515L60.761,35.91z"/>
                    </g>
                </svg>
                Registrar Acarreo
            </button>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 rounded-xl p-4 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Buscar Patente o Ubicación</label>
                <div class="relative">
                    <input v-model="filtros.search" @keyup.enter="buscarPorTexto" type="text" placeholder="Ej: AB123CD o Ruta 20..." 
                        class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg pl-3 pr-10 py-2 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500" />
                    <button v-if="filtros.search" @click="filtros.search = ''; buscarPorTexto()" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer">×</button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por Móvil / Grúa</label>
                <select v-model="filtros.tow_truck" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                    <option value="">Todas las Grúas</option>
                    <option v-for="grua in listaGruas" :key="grua" :value="grua">{{ grua }}</option>
                </select>
            </div>

            <div class="flex justify-end">
                <button @click="limpiarFiltros" class="text-[11px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white uppercase tracking-widest bg-transparent border-none cursor-pointer">
                    Limpiar Filtros
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm rounded-xl overflow-hidden flex-1 flex flex-col">
            
            <div class="flex border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5">
                <button @click="cambiarVista('activos')" 
                    :class="vistaActual === 'activos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-[15px] transition-colors cursor-pointer bg-transparent">
                    Acarreos Activos
                </button>
                <button @click="cambiarVista('archivados')" 
                    :class="vistaActual === 'archivados' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-[15px] transition-colors cursor-pointer bg-transparent">
                    Archivo Documental
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-white/10 bg-white dark:bg-black/20">
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Fecha y Hora</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Móvil Asignado</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Patente Remolcada</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Ubicación / Motivo</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody v-if="cargando">
                        <tr><td colspan="5" class="p-8 text-center text-slate-400 text-sm">Cargando registros de auditoría...</td></tr>
                    </tbody>
                    <tbody v-else-if="registros.length === 0">
                        <tr><td colspan="5" class="p-8 text-center text-slate-400 text-sm border-2 border-dashed border-slate-200 dark:border-white/10 m-4 rounded-lg">No hay registros en esta vista.</td></tr>
                    </tbody>
                    <tbody v-else>
                        <tr v-for="item in registros" :key="item.id" class="border-b border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="p-4">
                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ formatearFecha(item.created_at) }}</span>
                            </td>
                            <td class="p-4">
                                <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest bg-indigo-50 dark:bg-indigo-500/10 px-2.5 py-1 rounded">
                                    {{ item.tow_truck }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="inline-block border-2 border-slate-800 dark:border-slate-300 rounded px-2 py-0.5 bg-white dark:bg-slate-800">
                                    <span class="font-mono text-base font-bold text-slate-900 dark:text-slate-100 tracking-wider">
                                        {{ item.license_plate }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ item.location }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ item.reason }}</div>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <template v-if="vistaActual === 'activos'">
                                        <button @click="abrirModalEditar(item)" class="text-[11px] font-bold uppercase tracking-widest text-indigo-500 hover:text-indigo-700 bg-transparent border-none cursor-pointer p-0">Editar</button>
                                        <button @click="archivarRegistro(item.id)" class="text-[11px] font-bold uppercase tracking-widest text-slate-500 hover:text-slate-700 bg-transparent border-none cursor-pointer p-0">Archivar</button>
                                    </template>
                                    <template v-else>
                                        <button @click="restaurarRegistro(item.id)" class="text-[11px] font-bold uppercase tracking-widest text-emerald-500 hover:text-emerald-700 bg-transparent border-none cursor-pointer p-0">Restaurar</button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="paginacion.total > 0 && !cargando" class="px-6 py-4 flex items-center justify-between bg-slate-50 dark:bg-white/5 border-t border-slate-200 dark:border-white/10 mt-auto">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Página <strong>{{ paginacion.current_page }}</strong> de <strong>{{ paginacion.last_page }}</strong> ({{ paginacion.total }} acarreos)
                </span>
                <div class="flex items-center gap-2">
                    <button @click="cargarRegistros(paginacion.current_page - 1)" :disabled="paginacion.current_page === 1"
                        class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-white/20 rounded hover:bg-white dark:hover:bg-black/20 disabled:opacity-50 cursor-pointer transition-colors bg-transparent">
                        Anterior
                    </button>
                    <button @click="cargarRegistros(paginacion.current_page + 1)" :disabled="paginacion.current_page === paginacion.last_page"
                        class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-white/20 rounded hover:bg-white dark:hover:bg-black/20 disabled:opacity-50 cursor-pointer transition-colors bg-transparent">
                        Siguiente
                    </button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalFormulario" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-lg shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden rounded-xl">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">
                        {{ modoEdicion ? 'Editar Acarreo' : 'Registrar Nuevo Acarreo' }}
                    </h3>
                    <button @click="cerrarModal" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="guardarRegistro" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Móvil / Grúa Asignada</label>
                            <select v-model="formulario.tow_truck" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50">
                                <option value="" disabled selected>Seleccione grúa...</option>
                                <option v-for="grua in listaGruas" :key="grua" :value="grua">{{ grua }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Patente Remolcada</label>
                            <input v-model="formulario.license_plate" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50 uppercase font-mono tracking-wider" placeholder="Ej: AB123CD" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Ubicación de Carga</label>
                        <input v-model="formulario.location" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" placeholder="Ej: Ruta 7 Km 780" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Motivo del Acarreo</label>
                        <input v-model="formulario.reason" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" placeholder="Ej: Desperfecto mecánico, Accidente..." />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Observaciones Adicionales (Opcional)</label>
                        <textarea v-model="formulario.observations" rows="2" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50 resize-none" placeholder="Destino de descarga, condiciones del vehículo..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Guardar Acarreo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>