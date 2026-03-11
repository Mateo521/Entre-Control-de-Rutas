<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
 
const inventarioBacheo = ref([])
const cargandoInventario = ref(true)
 
const guardando = ref(false)
const formulario = ref({
    title: '',
    description: '',
    toll_id: '' 
})
 
const materialConsumido = ref([])

const cargarInventarioEspecializado = async () => {
    cargandoInventario.value = true
    try {
        const respuesta = await axios.get('/api/inventory')
       
        inventarioBacheo.value = respuesta.data.filter(item => item.category === 'Bacheo')
         
        materialConsumido.value = inventarioBacheo.value.map(item => ({
            inventory_item_id: item.id,
            name: item.name,
            unit_measure: item.unit_measure,
            current_stock: item.current_stock,
            quantity: 0  
        }))
    } catch (error) {
        toast.error('Error al cargar el stock de materiales de bacheo.')
        console.error(error)
    } finally {
        cargandoInventario.value = false
    }
}

const registrarTrabajoBacheo = async () => {
 
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
        
        if (formulario.value.toll_id) {
            formData.append('toll_id', formulario.value.toll_id)
        }
 
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
        
    } catch (error) {
        toast.error('Ocurrió un error al registrar el parte de trabajo.')
        console.error(error)
    } finally {
        guardando.value = false
    }
}

onMounted(() => {
    cargarInventarioEspecializado()
})
</script>

<template>
    <div class="h-full flex flex-col relative">
        <div class="mb-6">
            <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 tracking-wide uppercase">
                Módulo: Cuadrilla de Bacheo
            </h2>
            <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 font-medium">
                Gestión de asfalto en frío y registro de reparaciones en la traza vial.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 rounded-xl p-5 shadow-sm">
                    <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-4 border-b border-slate-100 dark:border-white/5 pb-2">
                        Stock Disponible
                    </h3>
                    
                    <div v-if="cargandoInventario" class="text-sm text-slate-500">Cargando materiales...</div>
                    
                    <div v-else class="space-y-4">
                        <div v-for="item in inventarioBacheo" :key="item.id" class="flex justify-between items-center bg-slate-50 dark:bg-white/5 p-3 rounded-lg border border-slate-200 dark:border-white/5">
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
                <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 rounded-xl p-6 shadow-sm">
                    <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-5 border-b border-slate-100 dark:border-white/5 pb-2">
                        Registrar Nuevo Bacheo
                    </h3>

                    <form @submit.prevent="registrarTrabajoBacheo" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Ubicación / Referencia (Ej: Ruta 20 Km 14)</label>
                                <input v-model="formulario.title" type="text" required
                                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500" />
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Detalle del trabajo realizado</label>
                                <textarea v-model="formulario.description" rows="3" required
                                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500 resize-none"
                                    placeholder="Dimensiones del bache, personal involucrado..."></textarea>
                            </div>
                        </div>

                        <div class="mt-6 bg-slate-50 dark:bg-white/5 p-4 rounded-lg border border-slate-200 dark:border-white/10">
                            <label class="block text-[12px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest mb-3">
                                Material Consumido en este arreglo
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
                            <button type="submit" :disabled="guardando" 
                                class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-6 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                                {{ guardando ? 'Registrando y descontando...' : 'Guardar y Descontar Stock' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>