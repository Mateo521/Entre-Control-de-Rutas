<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const peajes = ref([])
const inventarioBacheo = ref([])
const materialConsumido = ref([])
const historialBacheo = ref([])

const cargandoInventario = ref(true)
const cargandoHistorial = ref(true)
const guardando = ref(false)

const formulario = ref({
    title: '',
    description: '',
    toll_id: '' 
})

 
const archivosAdjuntos = ref([])
const mostrarVisor = ref(false)
const evidenciasSeleccionadas = ref([])

 
const mostrarModalEditar = ref(false)
const guardandoEdicion = ref(false)
const idEdicion = ref(null)
const formEditar = ref({
    title: '',
    description: ''
})

 
const vistaActual = ref('activos')
const paginacion = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})

const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        peajes.value = respuesta.data.data || respuesta.data
    } catch (error) {
        toast.error('Error al cargar la lista de peajes.')
    }
}

const cargarInventarioEspecializado = async () => {
    cargandoInventario.value = true
    try {
        const respuesta = await axios.get('/api/inventory', {
            params: { no_paginate: true } 
        })
        inventarioBacheo.value = respuesta.data.filter(item => item.category.toLowerCase().includes('bacheo'))
    } catch (error) {
        toast.error('Error al cargar el stock de materiales de bacheo.')
    } finally {
        cargandoInventario.value = false
    }
}

watch(() => formulario.value.toll_id, (nuevoTollId) => {
    if (!nuevoTollId) {
        materialConsumido.value = []
        return
    }
    
    const materialesDelPeaje = inventarioBacheo.value.filter(item => item.toll_id === nuevoTollId)
    
    materialConsumido.value = materialesDelPeaje.map(item => ({
        inventory_item_id: item.id,
        name: item.name,
        unit_measure: item.unit_measure,
        current_stock: item.current_stock,
        quantity: 0  
    }))
})

 
const cargarHistorial = async (page = 1) => {
    cargandoHistorial.value = true
    try {
        const respuesta = await axios.get('/api/actions', {
            params: { 
                page: page,
                archived: vistaActual.value === 'archivados'
            }
        })
        
        const todosLosTrabajos = respuesta.data.data || respuesta.data
        historialBacheo.value = todosLosTrabajos.filter(trabajo => trabajo.category === 'Mantenimiento Vial - Bacheo')
        
        if (respuesta.data.current_page) {
            paginacion.value = {
                current_page: respuesta.data.current_page,
                last_page: respuesta.data.last_page,
                total: respuesta.data.total
            }
        }
    } catch (error) {
        console.error('Error al cargar el historial:', error)
    } finally {
        cargandoHistorial.value = false
    }
}

const cambiarVista = (vista) => {
    vistaActual.value = vista
    cargarHistorial(1)
}

const procesarArchivo = (event) => {
    const files = Array.from(event.target.files)
    archivosAdjuntos.value.push(...files)
    event.target.value = ''
}

const quitarArchivo = (index) => {
    archivosAdjuntos.value.splice(index, 1)
}

const abrirVisor = (archivos) => {
    evidenciasSeleccionadas.value = archivos || []
    mostrarVisor.value = true
}

const cerrarVisor = () => {
    mostrarVisor.value = false
    evidenciasSeleccionadas.value = []
}

const esImagen = (ruta) => {
    const extension = ruta.split('.').pop().toLowerCase()
    return ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)
}

const registrarTrabajoBacheo = async () => {
    if (!formulario.value.toll_id) {
        toast.warning('Debe seleccionar el peaje al que corresponde el trabajo.')
        return
    }
    if (!formulario.value.title || !formulario.value.description) {
        toast.warning('Debe completar el título y la descripción del trabajo.')
        return
    }

    guardando.value = true
    try {
        const formData = new FormData()
        formData.append('category', 'Mantenimiento Vial - Bacheo')
        formData.append('title', formulario.value.title)
        formData.append('description', formulario.value.description)
        formData.append('toll_id', formulario.value.toll_id)
 
        const materialesUtilizados = materialConsumido.value
            .filter(m => m.quantity > 0)
            .map(m => ({
                inventory_item_id: m.inventory_item_id,
                quantity: parseFloat(m.quantity)
            }))

        if (materialesUtilizados.length > 0) {
            formData.append('materiales', JSON.stringify(materialesUtilizados))
        }

        archivosAdjuntos.value.forEach(file => {
            formData.append('media[]', file)
        })
 
        await axios.post('/api/actions', formData)
        toast.success('Parte de bacheo registrado y stock actualizado.')
  
        formulario.value = { title: '', description: '', toll_id: '' }
        archivosAdjuntos.value = []
        
        await cargarInventarioEspecializado()  
        await cargarHistorial(1)  
        
    } catch (error) {
        toast.error('Ocurrió un error al registrar el parte de trabajo.')
    } finally {
        guardando.value = false
    }
}

const abrirModalEditar = (trabajo) => {
    idEdicion.value = trabajo.id
    formEditar.value = { title: trabajo.title, description: trabajo.description }
    archivosAdjuntos.value = []
    mostrarModalEditar.value = true
}

const cerrarModalEditar = () => {
    mostrarModalEditar.value = false
    idEdicion.value = null
    formEditar.value = { title: '', description: '' }
    archivosAdjuntos.value = []
}

const guardarEdicion = async () => {
    guardandoEdicion.value = true
    try {
        const formData = new FormData()
        formData.append('title', formEditar.value.title)
        formData.append('description', formEditar.value.description)
        formData.append('_method', 'PUT')

        archivosAdjuntos.value.forEach(file => {
            formData.append('media[]', file)
        })

        await axios.post(`/api/actions/${idEdicion.value}`, formData)
        
        toast.success('Registro actualizado correctamente.')
        cerrarModalEditar()
        await cargarHistorial(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al actualizar el registro.')
    } finally {
        guardandoEdicion.value = false
    }
}

 
const archivarTrabajo = async (id) => {
    const confirmacion = confirm('¿Desea enviar este registro al archivo histórico?')
    if (!confirmacion) return

    try {
        await axios.delete(`/api/actions/${id}`)
        toast.info('Registro archivado exitosamente.')
        await cargarHistorial(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al archivar el registro.')
    }
}

const restaurarTrabajo = async (id) => {
    try {
        await axios.post(`/api/actions/${id}/restore`)
        toast.success('Registro restaurado a la vista principal.')
        await cargarHistorial(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al restaurar el registro.')
    }
}

const formatearFecha = (fecha) => {
    if(!fecha) return '';
    return new Date(fecha).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

onMounted(() => {
    cargarPeajes()
    cargarInventarioEspecializado()
    cargarHistorial()
})
</script>

<template>
    <div class="h-full flex flex-col relative space-y-6">
        <div>
            <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 tracking-wide uppercase">
                Cuadrilla de bacheo
            </h2>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 font-medium">
                Gestión de asfalto en frío y registro de reparaciones por sucursal.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 p-5 shadow-sm ">
                    <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-4 border-b border-slate-100 dark:border-white/5 pb-2">
                        Stock Disponible (Bacheo)
                    </h3>
                    
                    <div v-if="!formulario.toll_id" class="text-sm text-amber-600 dark:text-amber-400 font-medium p-3 bg-amber-50 dark:bg-amber-500/10 rounded border border-amber-200 dark:border-amber-500/20">
                        Seleccione un peaje en el formulario para visualizar el stock.
                    </div>
                    <div v-else-if="cargandoInventario" class="text-sm text-slate-500">Cargando materiales...</div>
                    <div v-else-if="materialConsumido.length === 0" class="text-sm text-slate-500 italic">Esta sucursal no posee insumos de bacheo registrados.</div>
                    <div v-else class="space-y-4">
                        <div v-for="item in materialConsumido" :key="item.inventory_item_id" class="flex justify-between items-center bg-slate-50 dark:bg-white/5 p-3 rounded-lg border border-slate-200 dark:border-white/5">
                            <div>
                                <span class="block text-sm font-bold text-slate-700 dark:text-slate-300">{{ item.name }}</span>
                                <span class="text-[11px] text-slate-500 uppercase tracking-widest">{{ item.unit_measure }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-mono text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ item.current_stock }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 p-6 shadow-sm ">
                    <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-5 border-b border-slate-100 dark:border-white/5 pb-2">
                        Registrar Nuevo Bacheo
                    </h3>

                    <form @submit.prevent="registrarTrabajoBacheo" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-1.5">
                                    1. Sucursal / Peaje afectado
                                </label>
                                <select v-model="formulario.toll_id" required class="w-full bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                                    <option value="" disabled selected>Seleccione el peaje...</option>
                                    <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">{{ peaje.name }}</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">2. Ubicación / Referencia</label>
                                <input v-model="formulario.title" type="text" required :disabled="!formulario.toll_id"
                                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 disabled:opacity-50" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">3. Detalle del trabajo realizado</label>
                                <textarea v-model="formulario.description" rows="3" required :disabled="!formulario.toll_id"
                                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 resize-none disabled:opacity-50"></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                    4. Fotos de evidencia (Opcional)
                                </label>
                                <input id="bacheo-file" type="file" multiple @change="procesarArchivo" accept="image/*,.pdf" class="hidden" />
                                <label for="bacheo-file" :class="{'opacity-50 cursor-not-allowed': !formulario.toll_id, 'cursor-pointer hover:border-indigo-500/50 hover:bg-slate-100 dark:hover:bg-white/5': formulario.toll_id}"
                                    class="w-full flex flex-col items-center justify-center gap-2 bg-slate-50 dark:bg-[#0a1628] border border-dashed border-slate-300 dark:border-white/20 px-3 py-6 text-xs font-semibold text-slate-500 transition-all rounded-lg">
                                    <div class="bg-indigo-100 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 p-2 rounded-full">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                    </div>
                                    <span>Subir fotos del bache (Antes / Después)</span>
                                </label>

                                <div v-if="archivosAdjuntos.length > 0" class="flex flex-col gap-1.5 mt-3">
                                    <div v-for="(archivo, i) in archivosAdjuntos" :key="i" class="bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-md px-3 py-2 flex items-center justify-between shadow-sm">
                                        <span class="text-[11px] font-medium text-indigo-700 dark:text-indigo-300 truncate pr-2">{{ archivo.name }}</span>
                                        <button type="button" @click="quitarArchivo(i)" class="text-[10px] font-bold uppercase tracking-wider text-red-500 hover:text-red-700 cursor-pointer bg-transparent border-none">Quitar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="materialConsumido.length > 0" class="mt-6 bg-slate-50 dark:bg-white/5 p-4 rounded-lg border border-slate-200 dark:border-white/10">
                            <label class="block text-[12px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-3">
                                5. Material Consumido en este arreglo
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-for="(mat, index) in materialConsumido" :key="index" class="flex flex-col">
                                    <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">{{ mat.name }} ({{ mat.unit_measure }})</span>
                                    <input v-model="mat.quantity" type="number" step="0.01" min="0" :max="mat.current_stock"
                                        class="w-full bg-white dark:bg-black/40 border border-slate-300 dark:border-white/20 rounded px-3 py-2 text-slate-900 dark:text-white text-sm outline-none focus:border-emerald-500" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4">
                            <button type="submit" :disabled="guardando || !formulario.toll_id" 
                                class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-6 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50 shadow-sm">
                                {{ guardando ? 'Procesando...' : 'Guardar y Descontar Stock' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm mt-6  flex-1 flex flex-col">
            <div class="flex border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5">
                <button @click="cambiarVista('activos')" 
                    :class="vistaActual === 'activos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                    class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-[15px] transition-colors cursor-pointer bg-transparent">
                    Historial Activo
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
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest">Ubicación</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest">Detalle</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Evidencia</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody v-if="cargandoHistorial">
                        <tr><td colspan="5" class="p-6 text-center text-slate-400 text-sm">Cargando historial...</td></tr>
                    </tbody>
                    <tbody v-else-if="historialBacheo.length === 0">
                        <tr><td colspan="5" class="p-6 text-center text-slate-400 text-sm">No hay registros en esta vista.</td></tr>
                    </tbody>
                    <tbody v-else>
                        <tr v-for="trabajo in historialBacheo" :key="trabajo.id" class="border-b border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 dark:text-slate-400">
                                {{ formatearFecha(trabajo.created_at) }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-800 dark:text-slate-200">
                                {{ trabajo.title }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                                {{ trabajo.description }}
                            </td>
                            <td class="px-6 py-4">
                                <button v-if="trabajo.media_paths && trabajo.media_paths.length > 0" @click="abrirVisor(trabajo.media_paths)" class="text-[12px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors flex items-center gap-1 cursor-pointer bg-transparent border-none p-0">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                    Ver {{ trabajo.media_paths.length }} Archivo(s)
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">Sin fotos</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <template v-if="vistaActual === 'activos'">
                                        <button @click="abrirModalEditar(trabajo)" class="text-[11px] font-bold uppercase tracking-widest text-indigo-500 hover:text-indigo-700 bg-transparent border-none cursor-pointer p-0">Editar</button>
                                        <button @click="archivarTrabajo(trabajo.id)" class="text-[11px] font-bold uppercase tracking-widest text-slate-500 hover:text-slate-700 bg-transparent border-none cursor-pointer p-0">Archivar</button>
                                    </template>
                                    <template v-else>
                                        <button @click="restaurarTrabajo(trabajo.id)" class="text-[11px] font-bold uppercase tracking-widest text-emerald-500 hover:text-emerald-700 bg-transparent border-none cursor-pointer p-0">Restaurar</button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="paginacion.total > 0 && !cargandoHistorial" class="px-6 py-4 flex items-center justify-between bg-slate-50 dark:bg-white/5 border-t border-slate-200 dark:border-white/10 mt-auto">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Página <strong>{{ paginacion.current_page }}</strong> de <strong>{{ paginacion.last_page }}</strong>
                </span>
                <div class="flex items-center gap-2">
                    <button @click="cargarHistorial(paginacion.current_page - 1)" :disabled="paginacion.current_page === 1" class="px-3 py-1 text-xs font-bold text-slate-600 border border-slate-300 rounded hover:bg-white disabled:opacity-50 cursor-pointer bg-transparent">Anterior</button>
                    <button @click="cargarHistorial(paginacion.current_page + 1)" :disabled="paginacion.current_page === paginacion.last_page" class="px-3 py-1 text-xs font-bold text-slate-600 border border-slate-300 rounded hover:bg-white disabled:opacity-50 cursor-pointer bg-transparent">Siguiente</button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalEditar" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-lg shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden ">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">Editar Registro de Bacheo</h3>
                    <button @click="cerrarModalEditar" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="guardarEdicion" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Ubicación / Referencia</label>
                        <input v-model="formEditar.title" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Detalle del trabajo realizado</label>
                        <textarea v-model="formEditar.description" rows="3" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50 resize-none"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">
                            Anexar nuevas fotos (Las anteriores se conservan)
                        </label>
                        <input id="edit-bacheo-file" type="file" multiple @change="procesarArchivo" accept="image/*,.pdf" class="hidden" />
                        <label for="edit-bacheo-file" class="w-full flex flex-col items-center justify-center gap-2 bg-slate-50 dark:bg-[#0a1628] border border-dashed border-slate-300 dark:border-white/20 px-3 py-4 text-xs font-semibold text-slate-500 cursor-pointer rounded-lg hover:border-indigo-500/50 transition-all">
                            <span>Seleccionar archivos para subir...</span>
                        </label>
                        <div v-if="archivosAdjuntos.length > 0" class="flex flex-col gap-1.5 mt-3">
                            <div v-for="(archivo, i) in archivosAdjuntos" :key="i" class="bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-md px-3 py-2 flex items-center justify-between">
                                <span class="text-[11px] font-medium text-indigo-700 dark:text-indigo-300 truncate pr-2">{{ archivo.name }}</span>
                                <button type="button" @click="quitarArchivo(i)" class="text-[10px] font-bold text-red-500 bg-transparent border-none cursor-pointer uppercase">Quitar</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 p-3 rounded">
                        <strong>Nota:</strong> La edición de materiales consumidos no está permitida por políticas de auditoría de inventario.
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="cerrarModalEditar" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardandoEdicion" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50 shadow-sm">
                            {{ guardandoEdicion ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="mostrarVisor" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/80 dark:bg-[#0a1628]/90 backdrop-blur-md p-4 transition-opacity">
            <div class="w-full max-w-4xl bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl flex flex-col max-h-[90vh]  overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide m-0">Evidencia Fotográfica</h3>
                    <button @click="cerrarVisor" class="text-slate-500 hover:text-red-500 bg-transparent border-none cursor-pointer"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                </div>
                <div class="p-6 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 bg-slate-100 dark:bg-[#0a1628]">
                    <div v-for="(archivo, index) in evidenciasSeleccionadas" :key="index" class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg overflow-hidden shadow-sm group relative">
                        <div v-if="esImagen(archivo)" class="aspect-video w-full bg-slate-200 dark:bg-black/50 relative">
                            <img :src="archivo" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><a :href="archivo" target="_blank" class="bg-amber-500 text-black px-4 py-2 rounded font-bold uppercase text-sm no-underline hover:bg-amber-400">Ampliar Foto</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>