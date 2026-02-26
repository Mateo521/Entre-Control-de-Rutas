<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
// Variables Reactivas
const peajes = ref([])
const cargandoPeajes = ref(true)
const guardando = ref(false)

// Objeto que almacena el formulario completo
const formulario = ref({
    toll_id: '',
    incident_type: '',
    // Aquí se guardarán las respuestas a los campos dinámicos
    dynamic_data: {} 
})

// Esta variable guardará la estructura JSON del peaje que esté seleccionado en ese momento
const camposDinamicosActivos = ref([])

// 1. Cargar peajes al iniciar la pantalla
const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        peajes.value = respuesta.data
    } catch (error) {
        console.error("Error al obtener peajes:", error)
    } finally {
        cargandoPeajes.value = false
    }
}

// 2. Observador: Escucha cada vez que el usuario cambia el selector de "Peaje"
watch(() => formulario.value.toll_id, (nuevoIdPeaje) => {
    // Si no hay peaje seleccionado, borramos los campos
    if (!nuevoIdPeaje) {
        camposDinamicosActivos.value = []
        formulario.value.dynamic_data = {}
        return
    }

    // Buscamos el peaje completo en nuestro array de peajes
    const peajeSeleccionado = peajes.value.find(p => p.id === nuevoIdPeaje)
    
    // Si tiene un esquema dinámico configurado, lo activamos
    if (peajeSeleccionado && peajeSeleccionado.dynamic_schema && peajeSeleccionado.dynamic_schema.inventory_fields) {
        camposDinamicosActivos.value = peajeSeleccionado.dynamic_schema.inventory_fields
        
        // Limpiamos los datos anteriores y preparamos las claves vacías
        formulario.value.dynamic_data = {}
        camposDinamicosActivos.value.forEach(campo => {
            formulario.value.dynamic_data[campo.name] = (campo.type === 'booleano') ? false : ''
        })
    } else {
        camposDinamicosActivos.value = []
        formulario.value.dynamic_data = {}
    }
})

 
const guardarSuceso = async () => {
    guardando.value = true
    try {
        await axios.post('/api/incidents', formulario.value)
        
        // Reemplazamos el alert
        toast.success('Suceso registrado y guardado correctamente');
        
        // Limpiamos el formulario tras guardar
        formulario.value.incident_type = ''
        formulario.value.dynamic_data = {}
        
    } catch (error) {
        console.error("Error al guardar suceso:", error)
        // Reemplazamos el alert
        toast.error('Hubo un error crítico al guardar el suceso.');
    } finally {
        guardando.value = false
    }
}

onMounted(() => {
    cargarPeajes()
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">Registro Operativo</h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">Reporte de sucesos en la traza</p>
            </div>
        </div>

        <div class="grid grid-cols-[1fr_360px] gap-6">
            
            <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3.5 flex items-center justify-between">
                    <span class="font-['Barlow_Condensed'] font-bold text-[17px] text-slate-900 dark:text-slate-100 tracking-wide">Nuevo suceso</span>
                </div>

                <form @submit.prevent="guardarSuceso" class="p-6">
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div>
                            <span class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Peaje / Estación</span>
                            <select v-model="formulario.toll_id" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-amber-500/50 appearance-none cursor-pointer">
                                <option value="" disabled selected>— Seleccioná la estación —</option>
                                <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">
                                    {{ peaje.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <span class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Tipo de Suceso</span>
                            <select v-model="formulario.incident_type" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-amber-500/50 appearance-none cursor-pointer">
                                <option value="" disabled selected>— Clasificación —</option>
                                <option value="pesaje_excedido">Pesaje excedido</option>
                                <option value="fuga_peaje">Fuga de peaje</option>
                                <option value="animal_ruta">Animal en ruta</option>
                                <option value="accidente_vial">Accidente vial</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="camposDinamicosActivos.length > 0" class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-50 dark:bg-amber-500/5">
                        <h4 class="font-['Barlow_Condensed'] text-xs font-bold tracking-widest uppercase text-amber-600 dark:text-amber-500 mb-4 flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path></svg>
                            Requerimientos Operativos de la Estación
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div v-for="(campo, index) in camposDinamicosActivos" :key="index">
                                <span class="font-['DM_Sans'] text-xs font-medium text-slate-600 dark:text-slate-300 block mb-1.5">{{ campo.name }}</span>
                                
                                <input v-if="campo.type === 'texto'" v-model="formulario.dynamic_data[campo.name]" type="text" class="w-full bg-white dark:bg-[#0a1628] border border-slate-300 dark:border-white/10 rounded-md px-3 py-2 text-xs outline-none focus:border-amber-500/50" />
                                
                                <input v-if="campo.type === 'numero'" v-model="formulario.dynamic_data[campo.name]" type="number" step="0.01" class="w-full bg-white dark:bg-[#0a1628] border border-slate-300 dark:border-white/10 rounded-md px-3 py-2 text-xs outline-none focus:border-amber-500/50" />
                                
                                <div v-if="campo.type === 'booleano'" class="flex items-center mt-1">
                                    <input type="checkbox" v-model="formulario.dynamic_data[campo.name]" class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                                    <span class="ml-2 text-xs text-slate-600 dark:text-slate-400">Confirmar</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-white/5 mt-6">
                        <button type="submit" :disabled="guardando" class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-[13px] font-bold tracking-wider uppercase px-6 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center transition-all hover:bg-amber-400 disabled:opacity-50">
                            {{ guardando ? 'Registrando...' : 'Registrar Suceso' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-none transition-colors h-fit">
                <div class="p-5 flex gap-3">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" class="shrink-0">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div class="text-[12.5px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        <strong class="text-slate-700 dark:text-slate-200 block mb-1">Formularios Dinámicos Activos</strong>
                        Al seleccionar una estación de peaje, el sistema verificará si el administrador ha requerido campos adicionales (N° de actas, controles de carga, etc.) para esa ubicación específica.
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>