<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const inventario = ref([])
const cargando = ref(true)
const mostrarModal = ref(false)
const guardando = ref(false)

 
const formOP = ref({
    inventory_item_id: '',
    quantity: '',
    reference_document: '',
    description: ''
})

const cargarInventario = async () => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/inventory')
        inventario.value = respuesta.data
    } catch (error) {
        toast.error('Error al cargar el stock de inventario.')
        console.error(error)
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
        
        toast.success('Orden de Provisión (OP) registrada exitosamente.')
        mostrarModal.value = false
        
       
        formOP.value = { inventory_item_id: '', quantity: '', reference_document: '', description: '' }
        
       
        await cargarInventario()
    } catch (error) {
        toast.error('Error al registrar la Orden de Provisión.')
    } finally {
        guardando.value = false
    }
}

const cerrarModal = () => {
    mostrarModal.value = false
    formOP.value = { inventory_item_id: '', quantity: '', reference_document: '', description: '' }
}

onMounted(() => {
    cargarInventario()
})
</script>

<template>
    <div class="h-full flex flex-col relative">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Control de inventario
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 uppercase tracking-widest font-bold">
                    Stock y Órdenes de Provisión (OP)
                </p>
            </div>

            <button @click="mostrarModal = true"
                class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-5 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2 shadow-lg shadow-amber-500/20">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Cargar OP (Ingreso)
            </button>
        </div>

        <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 rounded-xl shadow-sm overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Material / Insumo</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Categoría</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">Stock Actual</th>
                            <th class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Estado</th>
                        </tr>
                    </thead>
                    <tbody v-if="cargando">
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400 text-sm">Cargando inventario...</td>
                        </tr>
                    </tbody>
                    <tbody v-else-if="inventario.length === 0">
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400 text-sm">No hay ítems registrados en el sistema.</td>
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
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="mostrarModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-md rounded-xl shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">Ingresar Material (OP)</h3>
                    <button @click="cerrarModal" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="registrarIngresoOP" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Material a Ingresar</label>
                        <select v-model="formOP.inventory_item_id" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                            <option value="" disabled selected>Seleccione el insumo...</option>
                            <option v-for="item in inventario" :key="item.id" :value="item.id">
                                {{ item.name }} ({{ item.unit_measure }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Cantidad</label>
                            <input v-model="formOP.quantity" type="number" step="0.01" min="0.01" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                                placeholder="Ej: 50" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">N° de OP / Remito</label>
                            <input v-model="formOP.reference_document" type="text" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                                placeholder="Ej: OP-2026-45" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Observaciones</label>
                        <textarea v-model="formOP.description" rows="2"
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50 resize-none"
                            placeholder="Detalles del proveedor o entrega..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardando" class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Confirmar Ingreso' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>