<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement } from 'chart.js'
import { Doughnut, Bar } from 'vue-chartjs'

 
ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement)

const cargando = ref(true)
const peajes = ref([])
const kpis = ref({ total_sucesos: 0, sucesos_hoy: 0, total_peajes: 0 })
const ultimosSucesos = ref([])
 
const datosTipos = ref([])
const datosPeajes = ref([])

const cargarDatos = async () => {
    try {
        const [resDashboard, resTolls] = await Promise.all([
            axios.get('/api/dashboard'),
            axios.get('/api/tolls')
        ])

        const db = resDashboard.data
        kpis.value = db.kpis
        datosTipos.value = db.charts.tipos
        datosPeajes.value = db.charts.peajes
        ultimosSucesos.value = db.ultimos_sucesos
        
      
        peajes.value = resTolls.data.data

    } catch (error) {
        toast.error('Error al cargar las métricas del panel')
    } finally {
        cargando.value = false
    }
}


const chartDataTipos = computed(() => {
    const labels = datosTipos.value.map(d => d.incident_type.replace('_', ' ').toUpperCase())
    const data = datosTipos.value.map(d => d.total)
    
    return {
        labels,
        datasets: [{
            data,
            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444', '#8b5cf6', '#64748b'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    }
})

 
const chartDataPeajes = computed(() => {
    const labels = datosPeajes.value.map(d => d.name)
    const data = datosPeajes.value.map(d => d.total)
    
    return {
        labels,
        datasets: [{
            label: 'Sucesos Registrados',
            data,
            backgroundColor: '#f59e0b',
            borderRadius: 4
        }]
    }
})

 
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { labels: { color: '#94a3b8', font: { family: 'Barlow Condensed' } } }
    }
}

const barChartOptions = {
    ...chartOptions,
    scales: {
        y: { grid: { color: '#334155', tickColor: 'transparent' }, ticks: { color: '#94a3b8' } },
        x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { family: 'Barlow Condensed' } } }
    }
}

const formatearFecha = (fechaString) => {
    if (!fechaString) return '';
    return new Date(fechaString).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

onMounted(() => {
    cargarDatos()
})
</script>

<template>
    <div v-if="cargando" class="flex items-center justify-center h-full">
        <span class="text-slate-500 font-['Barlow_Condensed'] tracking-widest uppercase">Generando inteligencia de negocios...</span>
    </div>

    <div v-else>
        <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-6 tracking-wide">
            Panel Principal
        </h2>
        
        <div class="grid grid-cols-4 gap-5 mb-6">
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm  p-5 relative overflow-hidden transition-colors">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-amber-500"></div>
                <div class="text-[11px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-2">Estaciones Activas</div>
                <div class="font-['Barlow_Condensed'] text-[38px] font-extrabold text-slate-900 dark:text-slate-100 leading-none">{{ kpis.total_peajes }}</div>
            </div>
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm  p-5 relative overflow-hidden transition-colors">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-blue-500"></div>
                <div class="text-[11px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-2">Sucesos Hoy</div>
                <div class="font-['Barlow_Condensed'] text-[38px] font-extrabold text-slate-900 dark:text-slate-100 leading-none">{{ kpis.sucesos_hoy }}</div>
            </div>
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm  p-5 relative overflow-hidden transition-colors">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-emerald-500"></div>
                <div class="text-[11px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-2">Total Histórico</div>
                <div class="font-['Barlow_Condensed'] text-[38px] font-extrabold text-slate-900 dark:text-slate-100 leading-none">{{ kpis.total_sucesos }}</div>
            </div>
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm  p-5 relative overflow-hidden transition-colors">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-slate-400"></div>
                <div class="text-[11px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-2">Estado del Sistema</div>
                <div class="font-['Barlow_Condensed'] text-[24px] font-extrabold text-emerald-600 dark:text-emerald-400 leading-none mt-2">OPERATIVO</div>
            </div>
        </div>

        <div class="grid grid-cols-[1fr_2fr] gap-6 mb-6">
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  p-5 shadow-sm transition-colors">
                <h3 class="font-['Barlow_Condensed'] text-[15px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-white/5 pb-2">Clasificación de Sucesos</h3>
                <div class="h-[220px] relative">
                    <Doughnut v-if="datosTipos.length > 0" :data="chartDataTipos" :options="chartOptions" />
                    <div v-else class="absolute inset-0 flex items-center justify-center text-xs text-slate-500 italic">No hay datos suficientes</div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  p-5 shadow-sm transition-colors">
                <h3 class="font-['Barlow_Condensed'] text-[15px] font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-white/5 pb-2">Carga Operativa por Estación (Top 5)</h3>
                <div class="h-[220px] relative">
                    <Bar v-if="datosPeajes.length > 0" :data="chartDataPeajes" :options="barChartOptions" />
                    <div v-else class="absolute inset-0 flex items-center justify-center text-xs text-slate-500 italic">No hay datos suficientes</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-[2fr_1.2fr] gap-6">
            
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  shadow-sm overflow-hidden transition-colors">
                <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3 flex items-center justify-between">
                    <span class="font-['Barlow_Condensed'] font-bold text-[15px] text-slate-900 dark:text-slate-100 uppercase tracking-widest">Auditoría Reciente</span>
                    <router-link to="/panel/sucesos" class="text-[11px] font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 uppercase tracking-wider no-underline">Ver historial</router-link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <tbody>
                            <tr v-if="ultimosSucesos.length === 0">
                                <td class="px-5 py-6 text-center text-slate-500 text-sm">Sin actividad reciente.</td>
                            </tr>
                            <tr v-else v-for="suceso in ultimosSucesos" :key="suceso.id" class="border-b border-slate-100 dark:border-white/5 last:border-0 hover:bg-slate-50 dark:hover:bg-white/5">
                                <td class="px-5 py-3">
                                    <div class="text-[13px] font-semibold text-slate-900 dark:text-slate-100">{{ suceso.toll ? suceso.toll.name : 'N/A' }}</div>
                                    <div class="text-[11px] text-slate-500">{{ formatearFecha(suceso.created_at) }}</div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold tracking-wide uppercase bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300">
                                        {{ suceso.incident_type.replace('_', ' ') }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  shadow-sm overflow-hidden transition-colors">
                <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3 flex items-center justify-between">
                    <span class="font-['Barlow_Condensed'] font-bold text-[15px] text-slate-900 dark:text-slate-100 uppercase tracking-widest">Base Operativa</span>
                    <router-link to="/panel/peajes" class="text-[11px] font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 uppercase tracking-wider no-underline">Gestionar</router-link>
                </div>
                <div class="overflow-y-auto max-h-[260px]">
                    <table class="w-full border-collapse">
                        <tbody>
                            <tr v-if="peajes.length === 0">
                                <td class="px-5 py-6 text-center text-slate-500 text-sm">No hay estaciones configuradas.</td>
                            </tr>
                            <tr v-else v-for="peaje in peajes" :key="peaje.id" class="border-b border-slate-100 dark:border-white/5 last:border-0 hover:bg-slate-50 dark:hover:bg-white/5">
                                <td class="px-5 py-2.5 flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.5)]"></div>
                                    <div>
                                        <div class="text-[13px] font-semibold text-slate-900 dark:text-slate-100">{{ peaje.name }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">{{ peaje.dynamic_schema && peaje.dynamic_schema.inventory_fields ? peaje.dynamic_schema.inventory_fields.length : 0 }} campos requeridos</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</template>