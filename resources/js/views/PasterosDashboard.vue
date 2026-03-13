<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const trabajos = ref([])
const cargando = ref(true)
const mostrarModal = ref(false)
const guardando = ref(false)

const formulario = ref({
    company_name: '',
    work_type: '',
    location: '',
    description: '',
    status: 'En Progreso'
})

const archivosAdjuntos = ref([])

const cargarTrabajos = async () => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/contractors')
        trabajos.value = respuesta.data
    } catch (error) {
        toast.error('Error al cargar el historial de trabajos tercerizados.')
    } finally {
        cargando.value = false
    }
}

const abrirModal = () => {
    formulario.value = { company_name: '', work_type: '', location: '', description: '', status: 'En Progreso' }
    archivosAdjuntos.value = []
    mostrarModal.value = true
}

const cerrarModal = () => {
    mostrarModal.value = false
}

const procesarArchivo = (event) => {
    const files = Array.from(event.target.files)
    archivosAdjuntos.value.push(...files)
    event.target.value = ''
}

const quitarArchivo = (index) => {
    archivosAdjuntos.value.splice(index, 1)
}

const mostrarVisor = ref(false)
const evidenciasSeleccionadas = ref([])

const abrirVisor = (archivos) => {
    evidenciasSeleccionadas.value = archivos
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


const guardarTrabajo = async () => {
    guardando.value = true
    try {
        const formData = new FormData()
        formData.append('company_name', formulario.value.company_name)
        formData.append('work_type', formulario.value.work_type)
        formData.append('location', formulario.value.location)
        formData.append('description', formulario.value.description)
        formData.append('status', formulario.value.status)

        archivosAdjuntos.value.forEach(file => {
            formData.append('media[]', file)
        })

        await axios.post('/api/contractors', formData)
        toast.success('Trabajo tercerizado registrado correctamente.')

        cerrarModal()
        await cargarTrabajos()
    } catch (error) {
        toast.error('Ocurrió un error al guardar el registro.')
    } finally {
        guardando.value = false
    }
}

const formatearFecha = (fechaString) => {
    if (!fechaString) return '';
    return new Date(fechaString).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

onMounted(() => {
    cargarTrabajos()
})
</script>

<template>
    <div class="h-full relative">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide uppercase">
                    Empresas Contratistas
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 font-medium">
                    Auditoría de corte de pasto, poda, pintura y mantenimiento externo.
                </p>
            </div>

            <button @click="abrirModal"
                class="bg-indigo-600 text-white font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-indigo-500 hover:-translate-y-px shadow-sm">
                + Registrar Certificación
            </button>
        </div>

        <div
            class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Fecha</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Empresa</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Tipo / Ubicación</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">
                                Estado</th>
                            <th
                                class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-indigo-600 dark:text-indigo-400">
                                Evidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">Cargando registros...
                            </td>
                        </tr>
                        <tr v-else-if="trabajos.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-sm">No hay trabajos
                                tercerizados registrados.</td>
                        </tr>
                        <tr v-else v-for="trabajo in trabajos" :key="trabajo.id"
                            class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-b border-slate-100 dark:border-white/5">
                            <td class="px-4 py-3 text-[13px] text-slate-600 dark:text-slate-400 font-medium">
                                {{ formatearFecha(trabajo.created_at) }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">
                                {{ trabajo.company_name }}
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    class="text-[10px] font-bold tracking-widest uppercase text-indigo-600 dark:text-indigo-400 mb-0.5">
                                    {{ trabajo.work_type }}</div>
                                <div class="text-sm text-slate-700 dark:text-slate-300">{{ trabajo.location }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="trabajo.status === 'En Progreso'"
                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30">En
                                    Progreso</span>
                                <span v-else-if="trabajo.status === 'Finalizado'"
                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30">Finalizado</span>
                                <span v-else
                                    class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">Certificado
                                    (Pago)</span>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="trabajo.media_paths && trabajo.media_paths.length > 0"
                                    @click="abrirVisor(trabajo.media_paths)"
                                    class="text-[12px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors flex items-center gap-1 cursor-pointer bg-transparent border-none p-0">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48">
                                        </path>
                                    </svg>
                                    Ver {{ trabajo.media_paths.length }} Archivo(s)
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">Sin fotos</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="mostrarModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 dark:bg-[#0a1628]/80 backdrop-blur-sm transition-opacity p-4">
            <div
                class="w-full max-w-2xl bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden relative">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-1"></div>

                <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <h3
                        class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 tracking-wide m-0 uppercase">
                        Auditoría de Contratista
                    </h3>
                    <button @click="cerrarModal"
                        class="text-slate-400 hover:text-red-500 transition-colors cursor-pointer bg-transparent border-none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarTrabajo" class="p-6 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Razón
                                social (Pastero/Empresa)</label>
                            <input v-model="formulario.company_name" type="text" required
                                placeholder="Ej: Servicios Viales SRL"
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50" />
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tipo
                                de tarea</label>
                            <select v-model="formulario.work_type" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50 appearance-none">
                                <option value="" disabled>Seleccione...</option>
                                <option value="Corte de pasto">Corte de pasto / Desmalezado</option>
                                <option value="Poda correctiva">Poda correctiva</option>
                                <option value="Pintura">Pintura</option>
                                <option value="Mantenimiento general">Mantenimiento general</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tramo
                            / Ubicación exacta</label>
                        <input v-model="formulario.location" type="text" required
                            placeholder="Ej: Autopista 55, del Km 750 al 780"
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50" />
                    </div>

                    <div class="mb-5">
                        <label
                            class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Estado
                            del trabajo</label>
                        <select v-model="formulario.status" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50 appearance-none">
                            <option value="En progreso">En progreso (Ejecutando)</option>
                            <option value="Finalizado">Finalizado (Pendiente de revisión)</option>
                            <option value="Certificado para pago">Certificado para pago (Aprobado)</option>
                        </select>
                    </div>

                    <div class="mb-5">
                        <label
                            class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Observaciones</label>
                        <textarea v-model="formulario.description" rows="3"
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50 resize-y"
                            placeholder="Detalles sobre el avance, herramientas usadas o justificación de demoras..."></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Fotos
                            de certificación (Antes / Después)</label>
                        <input id="pastero-file" type="file" multiple @change="procesarArchivo" accept="image/*,.pdf"
                            class="hidden" />
                        <label for="pastero-file"
                            class="w-full flex flex-col items-center justify-center gap-2 bg-slate-50 dark:bg-[#0a1628] border border-dashed border-slate-300 dark:border-white/20 px-3 py-6 text-xs font-semibold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 hover:border-indigo-500/50 cursor-pointer transition-all group rounded-lg">
                            <div
                                class="bg-indigo-100 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 p-2 rounded-full group-hover:scale-110 transition-transform">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <span>Subir fotos o documentos de inspección</span>
                        </label>

                        <div v-if="archivosAdjuntos.length > 0" class="flex flex-col gap-1.5 mt-3">
                            <div v-for="(archivo, i) in archivosAdjuntos" :key="i"
                                class="bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-md px-3 py-2 flex items-center justify-between shadow-sm">
                                <span
                                    class="text-[11px] font-medium text-indigo-700 dark:text-indigo-300 truncate pr-2">{{
                                        archivo.name }}</span>
                                <button type="button" @click="quitarArchivo(i)"
                                    class="text-[10px] font-bold uppercase tracking-wider text-red-500 hover:text-red-700 cursor-pointer bg-transparent border-none">Quitar</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end pt-5 mt-4 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="cerrarModal"
                            class="px-4 py-2 rounded-lg text-[13px] font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors cursor-pointer border-none bg-transparent font-['Barlow_Condensed'] uppercase tracking-wider">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardando"
                            class="bg-indigo-600 text-white font-['Barlow_Condensed'] text-[13px] font-bold tracking-wider uppercase px-6 py-2 rounded-lg border-none cursor-pointer transition-all hover:bg-indigo-500 disabled:opacity-50">
                            {{ guardando ? 'Procesando...' : 'Guardar y Certificar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div v-if="mostrarVisor"
        class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/80 dark:bg-[#0a1628]/90 backdrop-blur-md p-4 transition-opacity">
        <div
            class="w-full max-w-4xl bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl flex flex-col max-h-[90vh]">
            <div
                class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-white/5">
                <h3
                    class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide m-0">
                    Evidencia Fotográfica / Documental
                </h3>
                <button @click="cerrarVisor"
                    class="text-slate-500 hover:text-red-500 transition-colors cursor-pointer bg-transparent border-none">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div
                class="p-6 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 bg-slate-100 dark:bg-[#0a1628]">
                <div v-for="(archivo, index) in evidenciasSeleccionadas" :key="index"
                    class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg overflow-hidden shadow-sm group relative">

                    <div v-if="esImagen(archivo)" class="aspect-video w-full bg-slate-200 dark:bg-black/50 relative">
                        <img :src="archivo" class="w-full h-full object-cover" />
                        <div
                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                            <a :href="archivo" target="_blank"
                                class="bg-amber-500 text-black px-4 py-2 rounded font-['Barlow_Condensed'] font-bold tracking-wider uppercase text-sm no-underline hover:bg-amber-400">Ampliar
                                Foto</a>
                        </div>
                    </div>

                    <div v-else
                        class="aspect-video w-full flex flex-col items-center justify-center bg-slate-50 dark:bg-black/20 p-4 text-center relative">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" class="text-red-500 mb-2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span
                            class="text-xs font-bold text-slate-500 uppercase tracking-widest break-all line-clamp-2">Documento
                            PDF</span>
                        <div
                            class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                            <a :href="archivo" target="_blank"
                                class="bg-indigo-500 text-white px-4 py-2 rounded font-['Barlow_Condensed'] font-bold tracking-wider uppercase text-sm no-underline hover:bg-indigo-400">Ver
                                Archivo</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>