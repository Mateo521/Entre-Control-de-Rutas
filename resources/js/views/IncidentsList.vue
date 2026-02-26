<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const sucesos = ref([])
const cargando = ref(true)

const cargarSucesos = async () => {
    try {
        const respuesta = await axios.get('/api/incidents')
        sucesos.value = respuesta.data
    } catch (error) {
        console.error("Error:", error)
        toast.error('Error al cargar la lista de sucesos')
    } finally {
        cargando.value = false
    }
}

// Función para formatear fechas de PostgreSQL a formato latino
const formatearFecha = (fechaString) => {
    if (!fechaString) return '';
    const fecha = new Date(fechaString);
    return fecha.toLocaleString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

// Función para traducir los tipos de incidentes técnicos a texto amigable
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
    cargarSucesos()
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">Historial Operativo</h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">Consulta de sucesos registrados en la traza</p>
            </div>
            
            <router-link to="/panel/sucesos/nuevo" class="bg-slate-800 dark:bg-white/10 text-white font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-slate-700 dark:hover:bg-white/20 hover:-translate-y-px">
                + Reportar Suceso
            </router-link>
        </div>

        <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Fecha y Hora</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Estación / Peaje</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Tipo de Suceso</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Datos Adicionales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">Cargando base de datos...</td>
                        </tr>
                        <tr v-else-if="sucesos.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500 text-sm">No se han registrado sucesos operativos aún.</td>
                        </tr>
                        
                        <tr v-else v-for="suceso in sucesos" :key="suceso.id" class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-b border-slate-100 dark:border-white/5 group">
                            
                            <td class="px-4 py-3 text-[13px] text-slate-600 dark:text-slate-400 font-medium">
                                {{ formatearFecha(suceso.created_at) }}
                            </td>
                            
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ suceso.toll ? suceso.toll.name : 'Estación no definida' }}
                                </div>
                            </td>
                            
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-[11.5px] font-bold tracking-wide uppercase bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                                    {{ traducirTipo(suceso.incident_type) }}
                                </span>
                            </td>
                            
                            <td class="px-4 py-3">
                                <div v-if="suceso.dynamic_data && Object.keys(suceso.dynamic_data).length > 0" class="relative group/tooltip">
                                    <span class="inline-flex items-center gap-1 text-[12px] text-amber-600 dark:text-amber-500 cursor-help border-b border-dashed border-amber-500/50">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                        Ver detalles adjuntos ({{ Object.keys(suceso.dynamic_data).length }})
                                    </span>
                                    
                                    <div class="absolute bottom-full left-0 mb-2 hidden group-hover/tooltip:block w-max max-w-xs bg-slate-900 dark:bg-black text-white text-xs rounded-lg p-3 shadow-xl z-10 border border-slate-700">
                                        <div v-for="(valor, clave) in suceso.dynamic_data" :key="clave" class="mb-1 last:mb-0 grid grid-cols-[auto_1fr] gap-2 items-start">
                                            <span class="font-semibold text-amber-400 opacity-80">{{ clave }}:</span>
                                            <span class="break-words">
                                                {{ typeof valor === 'boolean' ? (valor ? 'Sí' : 'No') : (valor || 'N/A') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <span v-else class="text-[12px] text-slate-400 italic">
                                    Sin datos extra
                                </span>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>