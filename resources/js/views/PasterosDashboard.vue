<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const trabajos = ref([])
const cargando = ref(true)
const mostrarModal = ref(false)
const guardando = ref(false)

const vistaActual = ref('activos') // 'activos' o 'archivados'
const modoEdicion = ref(false)
const idEdicion = ref(null)

const paginacion = ref({ current_page: 1, last_page: 1, total: 0 })

const formulario = ref({
    company_name: '',
    work_type: '',
    location: '',
    description: '',
    status: 'En progreso'
})

const archivosAdjuntos = ref([])

 
const tiposTarea = ref([])
const mostrarModalNuevoTipo = ref(false)
const nuevoTipoNombre = ref('')
const guardandoTipo = ref(false)

const cargarTiposTarea = async () => {
    try {
        const respuesta = await axios.get('/api/work-types')
        tiposTarea.value = respuesta.data
    } catch (error) {
        console.error('Error al cargar tipos de tarea')
    }
}

const abrirModalNuevoTipo = () => {
    nuevoTipoNombre.value = ''
    mostrarModalNuevoTipo.value = true
}

const cerrarModalNuevoTipo = () => {
    mostrarModalNuevoTipo.value = false
}

const guardarNuevoTipo = async () => {
    if (!nuevoTipoNombre.value) return
    guardandoTipo.value = true
    try {
        const respuesta = await axios.post('/api/work-types', { name: nuevoTipoNombre.value })
        tiposTarea.value.push(respuesta.data)
        formulario.value.work_type = respuesta.data.name
        cerrarModalNuevoTipo()
    } catch (error) {
        toast.error('Error al guardar. Puede que el tipo ya exista.')
    } finally {
        guardandoTipo.value = false
    }
}

 

const cargarTrabajos = async (page = 1) => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/contractors', {
            params: { 
                page: page,
                archived: vistaActual.value === 'archivados'
            }
        })
        
        trabajos.value = respuesta.data.data || []
        
        paginacion.value = {
            current_page: respuesta.data.current_page,
            last_page: respuesta.data.last_page,
            total: respuesta.data.total
        }
    } catch (error) {
        toast.error('Error al cargar el historial.')
    } finally {
        cargando.value = false
    }
}

const cambiarVista = (vista) => {
    vistaActual.value = vista
    cargarTrabajos(1)
}

const abrirModalNuevo = () => {
    modoEdicion.value = false
    idEdicion.value = null
    formulario.value = { company_name: '', work_type: '', location: '', description: '', status: 'En progreso' }
    archivosAdjuntos.value = []
    mostrarModal.value = true
}

const abrirModalEditar = (trabajo) => {
    modoEdicion.value = true
    idEdicion.value = trabajo.id
    formulario.value = { 
        company_name: trabajo.company_name, 
        work_type: trabajo.work_type, 
        location: trabajo.location, 
        description: trabajo.description || '', 
        status: trabajo.status 
    }
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

        if (modoEdicion.value) {
         
            formData.append('_method', 'PUT')
            await axios.post(`/api/contractors/${idEdicion.value}`, formData)
            toast.success('Registro actualizado y archivos anexados correctamente.')
        } else {
            await axios.post('/api/contractors', formData)
            toast.success('Trabajo tercerizado registrado correctamente.')
        }

        cerrarModal()
        await cargarTrabajos(paginacion.value.current_page)
    } catch (error) {
        toast.error('Ocurrió un error al guardar el registro.')
    } finally {
        guardando.value = false
    }
}

const archivarTrabajo = async (id) => {
    const confirmar = confirm('¿Desea enviar este registro al archivo histórico?')
    if (!confirmar) return

    try {
        await axios.delete(`/api/contractors/${id}`)
        toast.info('Registro archivado exitosamente.')
        await cargarTrabajos(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al archivar el registro.')
    }
}

const restaurarTrabajo = async (id) => {
    try {
        await axios.post(`/api/contractors/${id}/restore`)
        toast.success('Registro restaurado a la vista principal.')
        await cargarTrabajos(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al restaurar el registro.')
    }
}

const formatearFecha = (fechaString) => {
    if (!fechaString) return '';
    return new Date(fechaString).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

onMounted(() => {
    cargarTiposTarea()
    cargarTrabajos()
})
</script>

<template>
    <div class="h-full relative flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide uppercase">
                    Empresas Contratistas
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 font-medium">
                    Auditoría de servicios y certificación de avances.
                </p>
            </div>
            <button @click="abrirModalNuevo"
                class="bg-indigo-600 text-white font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-indigo-500 hover:-translate-y-px shadow-sm">
                + Registrar Certificación
            </button>
        </div>

        <div class="flex border-b border-slate-200 dark:border-white/10">
            <button @click="cambiarVista('activos')" 
                :class="vistaActual === 'activos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-sm transition-colors cursor-pointer bg-transparent">
                Trabajos Activos
            </button>
            <button @click="cambiarVista('archivados')" 
                :class="vistaActual === 'archivados' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-sm transition-colors cursor-pointer bg-transparent">
                Archivo Histórico
            </button>
        </div>

        <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm dark:shadow-none flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10">
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Fecha</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Empresa</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Tipo / Ubicación</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Estado</th>
                            <th class="px-4 py-3 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-indigo-600 dark:text-indigo-400">Evidencia</th>
                            <th class="px-4 py-3 text-right font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">Cargando registros...</td>
                        </tr>
                        <tr v-else-if="trabajos.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">No hay trabajos registrados en esta vista.</td>
                        </tr>
                        <tr v-else v-for="trabajo in trabajos" :key="trabajo.id" class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-b border-slate-100 dark:border-white/5">
                            <td class="px-4 py-3 text-[13px] text-slate-600 dark:text-slate-400 font-medium">{{ formatearFecha(trabajo.created_at) }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100">{{ trabajo.company_name }}</td>
                            <td class="px-4 py-3">
                                <div class="text-[10px] font-bold tracking-widest uppercase text-indigo-600 dark:text-indigo-400 mb-0.5">{{ trabajo.work_type }}</div>
                                <div class="text-sm text-slate-700 dark:text-slate-300">{{ trabajo.location }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="trabajo.status === 'En progreso'" class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30">En Progreso</span>
                                <span v-else-if="trabajo.status === 'Finalizado'" class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30">Finalizado</span>
                                <span v-else class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">Certificado (Pago)</span>
                            </td>
                            <td class="px-4 py-3">
                                <button v-if="trabajo.media_paths && trabajo.media_paths.length > 0" @click="abrirVisor(trabajo.media_paths)" class="text-[12px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors flex items-center gap-1 cursor-pointer bg-transparent border-none p-0">
                                    Ver {{ trabajo.media_paths.length }} Archivo(s)
                                </button>
                                <span v-else class="text-[12px] text-slate-400 italic">Sin fotos</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <template v-if="!trabajo.deleted_at">
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

            <div v-if="paginacion.total > 0 && !cargando" class="px-6 py-4 flex items-center justify-between bg-slate-50 dark:bg-white/5 border-t border-slate-200 dark:border-white/10 mt-auto">
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Página <strong>{{ paginacion.current_page }}</strong> de <strong>{{ paginacion.last_page }}</strong>
                </span>
                <div class="flex items-center gap-2">
                    <button @click="cargarTrabajos(paginacion.current_page - 1)" :disabled="paginacion.current_page === 1" class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-white/20 rounded hover:bg-white disabled:opacity-50 cursor-pointer bg-transparent">Anterior</button>
                    <button @click="cargarTrabajos(paginacion.current_page + 1)" :disabled="paginacion.current_page === paginacion.last_page" class="px-3 py-1.5 text-xs font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-white/20 rounded hover:bg-white disabled:opacity-50 cursor-pointer bg-transparent">Siguiente</button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 dark:bg-[#0a1628]/80 backdrop-blur-sm transition-opacity p-4">
            <div class="w-full max-w-2xl bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden relative">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-1"></div>
                <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <h3 class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 tracking-wide m-0 uppercase">
                        {{ modoEdicion ? 'Actualizar Certificación' : 'Auditoría de Contratista' }}
                    </h3>
                    <button @click="cerrarModal" class="text-slate-400 hover:text-red-500 transition-colors cursor-pointer bg-transparent border-none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="guardarTrabajo" class="p-6 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Razón social</label>
                            <input v-model="formulario.company_name" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50" />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tipo de tarea</label>
                            <div class="flex items-center gap-2">
                                <select v-model="formulario.work_type" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50 appearance-none">
                                    <option value="" disabled>Seleccione...</option>
                                    <option v-for="tipo in tiposTarea" :key="tipo.id" :value="tipo.name">{{ tipo.name }}</option>
                                </select>
                                <button type="button" @click="abrirModalNuevoTipo" class="bg-indigo-100 text-indigo-600 px-3.5 py-2.5 rounded-lg font-bold border-none cursor-pointer">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tramo / Ubicación exacta</label>
                        <input v-model="formulario.location" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50" />
                    </div>

                    <div class="mb-5">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Estado del trabajo</label>
                        <select v-model="formulario.status" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50 appearance-none">
                            <option value="En progreso">En progreso (Ejecutando)</option>
                            <option value="Finalizado">Finalizado (Pendiente de revisión)</option>
                            <option value="Certificado para pago">Certificado para pago (Aprobado)</option>
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Observaciones</label>
                        <textarea v-model="formulario.description" rows="3" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50 resize-y"></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                            {{ modoEdicion ? 'Anexar nuevas fotos (Las anteriores se conservan)' : 'Fotos de certificación' }}
                        </label>
                        <input id="pastero-file" type="file" multiple @change="procesarArchivo" accept="image/*,.pdf" class="hidden" />
                        <label for="pastero-file" class="w-full flex flex-col items-center justify-center gap-2 bg-slate-50 dark:bg-[#0a1628] border border-dashed border-slate-300 dark:border-white/20 px-3 py-6 text-xs font-semibold text-slate-500 cursor-pointer rounded-lg hover:border-indigo-500/50">
                            <span>Seleccionar archivos para subir...</span>
                        </label>

                        <div v-if="archivosAdjuntos.length > 0" class="flex flex-col gap-1.5 mt-3">
                            <div v-for="(archivo, i) in archivosAdjuntos" :key="i" class="bg-indigo-50 border border-indigo-200 rounded-md px-3 py-2 flex items-center justify-between">
                                <span class="text-[11px] font-medium text-indigo-700 truncate pr-2">{{ archivo.name }}</span>
                                <button type="button" @click="quitarArchivo(i)" class="text-[10px] font-bold text-red-500 bg-transparent border-none cursor-pointer">Quitar</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end pt-5 mt-4 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="cerrarModal" class="px-4 py-2 rounded-lg text-[13px] font-bold text-slate-500 cursor-pointer border-none bg-transparent font-['Barlow_Condensed'] uppercase tracking-wider">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-indigo-600 text-white font-['Barlow_Condensed'] text-[13px] font-bold tracking-wider uppercase px-6 py-2 rounded-lg border-none cursor-pointer transition-all disabled:opacity-50">
                            {{ guardando ? 'Procesando...' : (modoEdicion ? 'Actualizar Registro' : 'Guardar y Certificar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="mostrarVisor" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-900/80 dark:bg-[#0a1628]/90 backdrop-blur-md p-4 transition-opacity">
            <div class="w-full max-w-4xl bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl flex flex-col max-h-[90vh]">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide m-0">Evidencia Fotográfica / Documental</h3>
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

        <div v-if="mostrarModalNuevoTipo" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-900/60 dark:bg-[#0a1628]/80 backdrop-blur-sm p-4">
            <div class="w-full max-w-sm bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl rounded-xl overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-lg font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wide m-0">Nuevo Tipo de Tarea</h3>
                    <button @click="cerrarModalNuevoTipo" class="text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                </div>
                <form @submit.prevent="guardarNuevoTipo" class="p-5 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Nombre de la Categoría</label>
                        <input v-model="nuevoTipoNombre" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-sm text-slate-900 dark:text-white outline-none focus:border-indigo-500/50" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="cerrarModalNuevoTipo" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-500 border-none bg-transparent uppercase cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardandoTipo" class="bg-indigo-600 text-white text-xs font-bold tracking-wider uppercase px-5 py-2 rounded-lg border-none cursor-pointer disabled:opacity-50">{{ guardandoTipo ? 'Guardando...' : 'Añadir' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>