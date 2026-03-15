<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import { Chart as ChartJS, ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, LineElement, PointElement, LineController } from 'chart.js'
import { Doughnut, Bar, Line } from 'vue-chartjs'


import { useRouter } from 'vue-router'

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, LineElement, PointElement, LineController)

const cargando = ref(true)
const router = useRouter()
const busquedaGlobal = ref('')
const datosTendencia = ref([])
const kpis = ref({ sucesos_hoy: 0, total_sucesos: 0, obras_activas: 0, alertas_stock: 0, tareas_pendientes: 0 })
const datosTipos = ref([])
const datosPeajes = ref([])

const ultimosSucesos = ref([])
const stockCritico = ref([])
const proximasNotas = ref([])

const buscarEnSistema = () => {
    if (busquedaGlobal.value.trim() !== '') {
        router.push({ path: '/panel/busqueda', query: { q: busquedaGlobal.value } })
    }
}

const cargarDatos = async () => {
    try {
        const respuesta = await axios.get('/api/dashboard')
        const db = respuesta.data

        kpis.value = db.kpis
        datosTipos.value = db.charts.tipos
        datosTendencia.value = db.charts.tendencia
        datosPeajes.value = db.charts.peajes
        ultimosSucesos.value = db.ultimos_sucesos
        stockCritico.value = db.stock_critico
        proximasNotas.value = db.proximas_notas

    } catch (error) {
        toast.error('Error al cargar las métricas del Centro de Comando')
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
            label: 'Sucesos Históricos',
            data,
            backgroundColor: '#f59e0b',
            borderRadius: 4
        }]
    }
})

const chartDataTendencia = computed(() => {

    const labels = datosTendencia.value.map(d => {
        const fecha = new Date(d.fecha + 'T12:00:00');
        return fecha.toLocaleDateString('es-AR', { day: '2-digit', month: 'short' });
    })
    const data = datosTendencia.value.map(d => d.total)

    return {
        labels,
        datasets: [{
            label: 'Incidentes Diarios',
            data,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#0d1b2a',
            pointBorderColor: '#3b82f6',
            pointRadius: 3,
            tension: 0.3,
            fill: true
        }]
    }
})

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#94a3b8', font: { family: 'Barlow Condensed' } } } }
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

    const soloFecha = fechaString.split('T')[0].split(' ')[0];
    return new Date(soloFecha + 'T12:00:00').toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

const formatearFechaCorta = (fechaString) => {
    if (!fechaString) return '';

    const soloFecha = fechaString.split('T')[0].split(' ')[0];
    const fecha = new Date(soloFecha + 'T12:00:00');
    return fecha.toLocaleDateString('es-AR', { day: '2-digit', month: 'short' });
}
onMounted(() => {
    cargarDatos()
})
</script>

<template>
    <div v-if="cargando" class="flex items-center justify-center h-full">
        <span class="text-slate-500 font-['Barlow_Condensed'] tracking-widest uppercase animate-pulse">
            Sincronizando Centro de Comando...
        </span>
    </div>

    <div v-else class="space-y-6">
        <h2
            class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 tracking-wide uppercase">
            Centro de Comando General
        </h2>

        <form @submit.prevent="buscarEnSistema" class="relative w-full">
            <input v-model="busquedaGlobal" type="text"
                placeholder="Buscar patentes, incidentes, OP o remitos en todo el sistema..."
                class="w-full bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 h-12 pl-10 pr-4 py-2.5 text-sm text-slate-700 dark:text-slate-200 outline-none focus:border-amber-500/50 shadow-sm transition-colors " />
            <svg class="absolute left-3.5 top-[15px] text-slate-400 w-4 h-4" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <button type="submit" class="hidden"></button>
        </form>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm p-4 relative overflow-hidden ">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-blue-500"></div>
                <div
                    class="text-[10px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-1">
                    Sucesos Hoy</div>
                <div
                    class="font-['Barlow_Condensed'] text-3xl font-extrabold text-slate-900 dark:text-slate-100 leading-none">
                    {{ kpis.sucesos_hoy }}</div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm p-4 relative overflow-hidden ">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-indigo-500"></div>
                <div
                    class="text-[10px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-1">
                    Obras Activas</div>
                <div
                    class="font-['Barlow_Condensed'] text-3xl font-extrabold text-slate-900 dark:text-slate-100 leading-none">
                    {{ kpis.obras_activas }}</div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm p-4 relative overflow-hidden ">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-red-500"></div>
                <div
                    class="text-[10px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-1">
                    Alertas Inventario</div>
                <div
                    class="font-['Barlow_Condensed'] text-3xl font-extrabold text-red-600 dark:text-red-400 leading-none">
                    {{ kpis.alertas_stock }}</div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm p-4 relative overflow-hidden ">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-amber-500"></div>
                <div
                    class="text-[10px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-1">
                    Tareas Pendientes</div>
                <div
                    class="font-['Barlow_Condensed'] text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-none">
                    {{ kpis.tareas_pendientes }}</div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm p-4 relative overflow-hidden  col-span-2 md:col-span-1">
                <div class="absolute inset-y-0 left-0 w-[3px] bg-emerald-500"></div>
                <div
                    class="text-[10px] text-slate-500 tracking-[0.08em] font-bold font-['Barlow_Condensed'] uppercase mb-1">
                    Estado Sistema</div>
                <div
                    class="font-['Barlow_Condensed'] text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 leading-none mt-1.5">
                    OPERATIVO</div>
            </div>
        </div>


        <div class="grid grid-cols-1 lg:grid-cols-[1fr_2fr] gap-6">
            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 p-5 shadow-sm ">
                <h3
                    class="font-['Barlow_Condensed'] text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-white/5 pb-2">
                    Clasificación de Sucesos
                </h3>
                <div class="h-[220px] relative">
                    <Doughnut v-if="datosTipos.length > 0" :data="chartDataTipos" :options="chartOptions" />
                    <div v-else class="absolute inset-0 flex items-center justify-center text-xs text-slate-500 italic">
                        Sin datos</div>
                </div>
            </div>

            <div class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 p-5 shadow-sm ">
                <h3
                    class="font-['Barlow_Condensed'] text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-white/5 pb-2">
                    Carga Operativa por Estación
                </h3>
                <div class="h-[220px] relative">
                    <Bar v-if="datosPeajes.length > 0" :data="chartDataPeajes" :options="barChartOptions" />
                    <div v-else class="absolute inset-0 flex items-center justify-center text-xs text-slate-500 italic">
                        Sin datos</div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 col-span-2 dark:border-white/10 p-5 shadow-sm  mb-6">
                <div class="flex justify-between items-center mb-4 border-b border-slate-100 dark:border-white/5 pb-2">
                    <h3
                        class="font-['Barlow_Condensed'] text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-widest m-0">
                        Evolución de Sucesos (Últimos 30 Días)
                    </h3>
                </div>
                <div class="h-[250px] relative">
                    <Line v-if="datosTendencia.length > 0" :data="chartDataTendencia" :options="barChartOptions" />
                    <div v-else class="absolute inset-0 flex items-center justify-center text-xs text-slate-500 italic">
                        Recopilando datos históricos...</div>
                </div>
            </div>
        </div>



        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-sm  overflow-hidden flex flex-col h-full">
                <div
                    class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-4 py-3 flex justify-between items-center">
                    <span
                        class="font-['Barlow_Condensed'] font-bold text-sm text-slate-900 dark:text-slate-100 uppercase tracking-widest">Auditoría
                        Reciente</span>
                    <router-link to="/panel/sucesos"
                        class="text-[10px] font-bold text-blue-500 hover:text-blue-400 uppercase no-underline">Ver
                        Todos</router-link>
                </div>
                <div class="flex-1 p-0">
                    <div v-if="ultimosSucesos.length === 0" class="p-5 text-center text-xs text-slate-500">Sin
                        actividad.</div>
                    <div v-else v-for="suceso in ultimosSucesos" :key="suceso.id"
                        class="border-b border-slate-100 dark:border-white/5 last:border-0 p-3 hover:bg-slate-50 dark:hover:bg-white/5 flex justify-between items-center">
                        <div>
                            <div class="text-[12px] font-bold text-slate-800 dark:text-slate-200">{{ suceso.toll ?
                                suceso.toll.name : 'Ruta' }}</div>
                            <div class="text-[10px] text-slate-500">{{ formatearFecha(suceso.created_at) }}</div>
                        </div>
                        <span
                            class="text-[9px] font-bold tracking-wide uppercase bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 px-2 py-1 rounded">
                            {{ suceso.incident_type.replace('_', ' ') }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-red-200 dark:border-red-900/30 shadow-sm  overflow-hidden flex flex-col h-full">
                <div
                    class="bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-900/30 px-4 py-3 flex justify-between items-center">
                    <span
                        class="font-['Barlow_Condensed'] font-bold text-sm text-red-700 dark:text-red-400 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span> Stock Crítico
                    </span>
                    <router-link to="/panel/inventario"
                        class="text-[10px] font-bold text-red-500 hover:text-red-400 uppercase no-underline">Abastecer</router-link>
                </div>
                <div class="flex-1 p-0">
                    <div v-if="stockCritico.length === 0" class="p-5 text-center text-xs text-slate-500">Inventario en
                        niveles óptimos.</div>
                    <div v-else v-for="item in stockCritico" :key="item.id"
                        class="border-b border-slate-100 dark:border-white/5 last:border-0 p-3 hover:bg-slate-50 dark:hover:bg-white/5 flex justify-between items-center">
                        <div class="overflow-hidden pr-2">
                            <div class="text-[12px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ item.name
                                }}</div>
                            <div class="text-[10px] text-slate-500">{{ item.toll ? item.toll.name : 'General' }}</div>
                        </div>
                        <div class="text-right whitespace-nowrap">
                            <div class="text-[14px] font-bold font-mono text-red-600 dark:text-red-400">{{
                                item.current_stock }}</div>
                            <div class="text-[9px] text-slate-400 uppercase">{{ item.unit_measure }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-[#0d1b2a] border border-amber-200 dark:border-amber-900/30 shadow-sm  overflow-hidden flex flex-col h-full">
                <div
                    class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-100 dark:border-amber-900/30 px-4 py-3 flex justify-between items-center">
                    <span
                        class="font-['Barlow_Condensed'] font-bold text-sm text-amber-700 dark:text-amber-400 uppercase tracking-widest">Próximas
                        Tareas</span>
                    <router-link to="/panel/notas"
                        class="text-[10px] font-bold text-amber-600 hover:text-amber-500 uppercase no-underline">Ver
                        Notas</router-link>
                </div>
                <div class="flex-1 p-0">
                    <div v-if="proximasNotas.length === 0" class="p-5 text-center text-xs text-slate-500">Sin eventos
                        próximos.</div>
                    <div v-else v-for="nota in proximasNotas" :key="nota.id"
                        class="border-b border-slate-100 dark:border-white/5 last:border-0 p-3 hover:bg-slate-50 dark:hover:bg-white/5 flex justify-between items-center gap-2">
                        <div class="flex-1 overflow-hidden">
                            <div class="text-[12px] font-bold text-slate-800 dark:text-slate-200 truncate">{{ nota.title
                                }}</div>
                        </div>
                        <span
                            class="text-[10px] font-bold tracking-wide uppercase bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 px-2 py-1 rounded whitespace-nowrap">
                            Vence: {{ formatearFechaCorta(nota.due_date) }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>