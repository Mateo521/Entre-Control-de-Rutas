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

const mostrarModalEditar = ref(false)
const guardandoEdicion = ref(false)
const idEdicion = ref(null)
const formEditar = ref({
    title: '',
    description: ''
})

 
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
            params: { page: page }
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
 
        await axios.post('/api/actions', formData)
        toast.success('Parte de bacheo registrado y stock actualizado.')
  
        formulario.value = { title: '', description: '', toll_id: '' }
        
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
    mostrarModalEditar.value = true
}

const cerrarModalEditar = () => {
    mostrarModalEditar.value = false
    idEdicion.value = null
    formEditar.value = { title: '', description: '' }
}

const guardarEdicion = async () => {
    guardandoEdicion.value = true
    try {
        await axios.put(`/api/actions/${idEdicion.value}`, {
            title: formEditar.value.title,
            description: formEditar.value.description
        })
        toast.success('Registro actualizado correctamente.')
        cerrarModalEditar()
        await cargarHistorial(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al actualizar el registro.')
    } finally {
        guardandoEdicion.value = false
    }
}

const eliminarTrabajo = async (id) => {
    const confirmacion = confirm('¿Desea eliminar este registro del historial? Nota: Esto no restaurará el stock consumido.')
    if (!confirmacion) return

    try {
        await axios.delete(`/api/actions/${id}`)
        toast.success('Registro eliminado exitosamente.')
        await cargarHistorial(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al eliminar el registro.')
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
                <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  p-5 shadow-sm">
                    <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-4 border-b border-slate-100 dark:border-white/5 pb-2">
                        Stock Disponible (Bacheo)
                    </h3>
                    
                    <div v-if="!formulario.toll_id" class="text-sm text-amber-600 dark:text-amber-400 font-medium p-3 bg-amber-50 dark:bg-amber-500/10 rounded border border-amber-200 dark:border-amber-500/20">
                        Seleccione un peaje en el formulario para visualizar el stock correspondiente a esa sucursal.
                    </div>
                    
                    <div v-else-if="cargandoInventario" class="text-sm text-slate-500">Cargando materiales...</div>
                    
                    <div v-else-if="materialConsumido.length === 0" class="text-sm text-slate-500 italic">
                        Esta sucursal no posee insumos de bacheo registrados en su inventario.
                    </div>
                    
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
                <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  p-6 shadow-sm">
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
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">2. Ubicación / Referencia (Ej: Ruta 20 Km 14)</label>
                                <input v-model="formulario.title" type="text" required :disabled="!formulario.toll_id"
                                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 disabled:opacity-50" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">3. Detalle del trabajo realizado</label>
                                <textarea v-model="formulario.description" rows="3" required :disabled="!formulario.toll_id"
                                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 resize-none disabled:opacity-50"
                                    placeholder="Dimensiones del bache, personal involucrado..."></textarea>
                            </div>
                        </div>

                        <div v-if="materialConsumido.length > 0" class="mt-6 bg-slate-50 dark:bg-white/5 p-4 rounded-lg border border-slate-200 dark:border-white/10">
                            <label class="block text-[12px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-3">
                                4. Material Consumido en este arreglo
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
                                class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-6 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                                {{ guardando ? 'Registrando y descontando...' : 'Guardar y Descontar Stock' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm mt-6">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5">
                <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide m-0">
                    Historial de trabajos
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-white/10 bg-white dark:bg-black/20">
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest">Ubicación</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest">Detalle del trabajo</th>
                            <th class="px-6 py-3 font-['Barlow_Condensed'] text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody v-if="cargandoHistorial">
                        <tr><td colspan="4" class="p-6 text-center text-slate-400 text-sm">Cargando historial...</td></tr>
                    </tbody>
                    <tbody v-else-if="historialBacheo.length === 0">
                        <tr><td colspan="4" class="p-6 text-center text-slate-400 text-sm">Aún no se han registrado trabajos de bacheo.</td></tr>
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
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="abrirModalEditar(trabajo)" class="text-indigo-500 hover:text-indigo-700 transition-colors bg-transparent border-none cursor-pointer p-0" title="Editar registro">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </button>
                                    <button @click="eliminarTrabajo(trabajo.id)" class="text-red-500 hover:text-red-700 transition-colors bg-transparent border-none cursor-pointer p-0" title="Eliminar registro">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div v-if="paginacion.total > 0" class="px-6 py-4 flex items-center justify-between bg-slate-50 dark:bg-white/5 border-t border-slate-200 dark:border-white/10">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Página <strong>{{ paginacion.current_page }}</strong> de <strong>{{ paginacion.last_page }}</strong>
                </span>
                <div class="flex items-center gap-2">
                    <button @click="cargarHistorial(paginacion.current_page - 1)" :disabled="paginacion.current_page === 1"
                        class="px-3 py-1 text-xs font-bold text-slate-600 border border-slate-300 rounded hover:bg-white disabled:opacity-50 cursor-pointer">
                        Anterior
                    </button>
                    <button @click="cargarHistorial(paginacion.current_page + 1)" :disabled="paginacion.current_page === paginacion.last_page"
                        class="px-3 py-1 text-xs font-bold text-slate-600 border border-slate-300 rounded hover:bg-white disabled:opacity-50 cursor-pointer">
                        Siguiente
                    </button>
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
                        <textarea v-model="formEditar.description" rows="4" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50 resize-none"></textarea>
                    </div>
                    
                    <div class="text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 p-3 rounded">
                        <strong>Nota:</strong> La edición de los materiales consumidos no está permitida para mantener la integridad de la auditoría de inventario.
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalEditar" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardandoEdicion" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardandoEdicion ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>