<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const inventario = ref([])
const cargando = ref(true)


const mostrarModalOP = ref(false)
const mostrarModalNuevoInsumo = ref(false)
const mostrarModalEditar = ref(false)  
const guardando = ref(false)
const idEdicion = ref(null)  


const formOP = ref({
    inventory_item_id: '',
    quantity: '',
    reference_document: '',
    description: ''
})


const formNuevoInsumo = ref({
    name: '', category: '', unit_measure: '', alert_threshold: 10
})

const formEditarInsumo = ref({
    name: '', category: '', unit_measure: '', alert_threshold: 10
})

const cargarInventario = async () => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/inventory')
        inventario.value = respuesta.data
    } catch (error) {
        toast.error('Error al cargar el catálogo de inventario.')
    } finally {
        cargando.value = false
    }
}


const registrarIngresoOP = async () => {
    guardando.value = true
    try {
        await axios.post('/api/inventory/movements', {
            inventory_item_id: formOP.value.inventory_item_id,
            quantity: formOP.value.quantity,
            reference_document: formOP.value.reference_document,
            description: formOP.value.description,
            type: 'in'  
        })
        toast.success('Orden de Provisión registrada exitosamente.')
        cerrarModalOP()
        await cargarInventario()
    } catch (error) {
        toast.error('Error al registrar el ingreso.')
    } finally {
        guardando.value = false
    }
}


const registrarNuevoInsumo = async () => {
    guardando.value = true
    try {
        await axios.post('/api/inventory', {
            name: formNuevoInsumo.value.name,
            category: formNuevoInsumo.value.category,
            unit_measure: formNuevoInsumo.value.unit_measure,
            alert_threshold: formNuevoInsumo.value.alert_threshold,
            current_stock: 0
        })
        toast.success('Nuevo insumo agregado al catálogo.')
        cerrarModalNuevoInsumo()
        await cargarInventario()
    } catch (error) {
        toast.error('Error al crear el insumo.')
    } finally {
        guardando.value = false
    }
}


const abrirModalEditar = (item) => {
    idEdicion.value = item.id
    formEditarInsumo.value = {
        name: item.name,
        category: item.category,
        unit_measure: item.unit_measure,
        alert_threshold: item.alert_threshold
    }
    mostrarModalEditar.value = true
}


const guardarEdicionInsumo = async () => {
    guardando.value = true
    try {
        await axios.put(`/api/inventory/${idEdicion.value}`, formEditarInsumo.value)
        toast.success('Insumo actualizado correctamente.')
        cerrarModalEditar()
        await cargarInventario()
    } catch (error) {
        toast.error('Error al actualizar el insumo.')
    } finally {
        guardando.value = false
    }
}


const eliminarInsumo = async (id) => {
    const confirmacion = confirm('¿Está seguro de que desea eliminar este insumo del catálogo? Esta acción no se puede deshacer.')
    if (!confirmacion) return

    try {
        await axios.delete(`/api/inventory/${id}`)
        toast.success('Insumo eliminado del catálogo exitosamente.')
        await cargarInventario()
    } catch (error) {
      
        toast.error('Error al eliminar. Es posible que el insumo ya tenga movimientos históricos registrados.')
    }
}

const cerrarModalOP = () => {
    mostrarModalOP.value = false
    formOP.value = { inventory_item_id: '', quantity: '', reference_document: '', description: '' }
}

const cerrarModalNuevoInsumo = () => {
    mostrarModalNuevoInsumo.value = false
    formNuevoInsumo.value = { name: '', category: '', unit_measure: '', alert_threshold: 10 }
}

const cerrarModalEditar = () => {
    mostrarModalEditar.value = false
    idEdicion.value = null
    formEditarInsumo.value = { name: '', category: '', unit_measure: '', alert_threshold: 10 }
}

onMounted(() => {
    cargarInventario()
})
</script>

<template>
    <div class="flex flex-col relative h-full">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Control de inventario
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 uppercase tracking-widest font-bold">
                    Catálogo y Órdenes de Provisión (OP)
                </p>
            </div>

            <div class="flex gap-3">
                <button @click="mostrarModalNuevoInsumo = true"
                    class="bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/20 text-slate-800 dark:text-white px-4 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    Nuevo Insumo
                </button>

                <button @click="mostrarModalOP = true"
                    class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-5 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2 shadow-lg shadow-amber-500/20">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Cargar OP (Ingreso)
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Material / Insumo</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Categoría</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Stock Actual</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Estado</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody v-if="cargando">
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-sm">Cargando inventario...</td>
                        </tr>
                    </tbody>
                    <tbody v-else-if="inventario.length === 0">
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 text-sm">El catálogo está vacío. Registre un nuevo insumo para comenzar.</td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr v-for="item in inventario" :key="item.id" class="border-b border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                            <td class="p-4">
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ item.name }}</span>
                            </td>
                            <td class="p-4">
                                <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 font-medium">
                                    {{ item.category }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <span class="font-mono text-base font-bold text-slate-900 dark:text-amber-400">
                                    {{ item.current_stock }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 ml-1 uppercase">{{ item.unit_measure }}</span>
                            </td>
                            <td class="p-4">
                                <span v-if="parseFloat(item.current_stock) <= parseFloat(item.alert_threshold)" 
                                      class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-500/10 px-2 py-1 rounded uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> Stock Bajo
                                </span>
                                <span v-else 
                                      class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Normal
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="abrirModalEditar(item)" class="p-1.5 text-slate-400 hover:text-indigo-500 transition-colors bg-transparent border-none cursor-pointer" title="Editar Insumo">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <button @click="eliminarInsumo(item.id)" class="p-1.5 text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer" title="Eliminar Insumo">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="mostrarModalOP" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">Ingresar Material (OP)</h3>
                    <button @click="cerrarModalOP" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="registrarIngresoOP" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Material a Ingresar</label>
                        <select v-model="formOP.inventory_item_id" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                            <option value="" disabled selected>Seleccione el insumo...</option>
                            <option v-for="item in inventario" :key="item.id" :value="item.id">{{ item.name }} ({{ item.unit_measure }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Cantidad</label>
                            <input v-model="formOP.quantity" type="number" step="0.01" min="0.01" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50" placeholder="Ej: 50" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">N° de OP / Remito</label>
                            <input v-model="formOP.reference_document" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50" placeholder="Ej: OP-2026-45" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Observaciones</label>
                        <textarea v-model="formOP.description" rows="2" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50 resize-none" placeholder="Detalles del proveedor o entrega..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalOP" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Confirmar Ingreso' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="mostrarModalNuevoInsumo" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">Registrar Nuevo Insumo</h3>
                    <button @click="cerrarModalNuevoInsumo" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="registrarNuevoInsumo" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Nombre del Insumo</label>
                        <input v-model="formNuevoInsumo.name" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50" placeholder="Ej: Pintura Vial Amarilla" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Categoría</label>
                            <select v-model="formNuevoInsumo.category" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                                <option value="" disabled selected>Seleccione...</option>
                                <option value="Bacheo">Bacheo</option>
                                <option value="Señalización">Señalización</option>
                                <option value="Seguridad">Seguridad Vial</option>
                                <option value="Mantenimiento">Mantenimiento General</option>
                                <option value="Herramientas">Herramientas</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Unidad de Medida</label>
                            <select v-model="formNuevoInsumo.unit_measure" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                                <option value="" disabled selected>Seleccione...</option>
                                <option value="Litros">Litros</option>
                                <option value="Kilos">Kilos</option>
                                <option value="Bolsas">Bolsas</option>
                                <option value="Tambores">Tambores</option>
                                <option value="Unidades">Unidades</option>
                                <option value="Metros">Metros</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Alerta de Stock Bajo (Límite)</label>
                        <input v-model="formNuevoInsumo.alert_threshold" type="number" min="1" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50" placeholder="Ej: 10" />
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalNuevoInsumo" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-slate-800 dark:bg-white text-white dark:text-black px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Guardar en Catálogo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="mostrarModalEditar" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">Editar Insumo</h3>
                    <button @click="cerrarModalEditar" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="guardarEdicionInsumo" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Nombre del Insumo</label>
                        <input v-model="formEditarInsumo.name" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Categoría</label>
                            <select v-model="formEditarInsumo.category" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50">
                                <option value="Bacheo">Bacheo</option>
                                <option value="Señalización">Señalización</option>
                                <option value="Seguridad">Seguridad Vial</option>
                                <option value="Mantenimiento">Mantenimiento General</option>
                                <option value="Herramientas">Herramientas</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Unidad de Medida</label>
                            <select v-model="formEditarInsumo.unit_measure" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50">
                                <option value="Litros">Litros</option>
                                <option value="Kilos">Kilos</option>
                                <option value="Bolsas">Bolsas</option>
                                <option value="Tambores">Tambores</option>
                                <option value="Unidades">Unidades</option>
                                <option value="Metros">Metros</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Alerta de Stock Bajo</label>
                        <input v-model="formEditarInsumo.alert_threshold" type="number" min="1" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" />
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalEditar" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>