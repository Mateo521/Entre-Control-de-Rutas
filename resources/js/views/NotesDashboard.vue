<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const notas = ref([])
const cargando = ref(true)
const vistaActual = ref('pendientes') // Puede ser 'pendientes' o 'archivadas'

const mostrarModalFormulario = ref(false)
const guardando = ref(false)
const modoEdicion = ref(false)
const idEdicion = ref(null)

const formulario = ref({
    title: '',
    description: '',
    due_date: ''
})

const cargarNotas = async () => {
    cargando.value = true
    try {
        const url = vistaActual.value === 'archivadas' ? '/api/notes?archived=true' : '/api/notes'
        const respuesta = await axios.get(url)
        notas.value = respuesta.data
    } catch (error) {
        toast.error('Error al cargar las notas.')
    } finally {
        cargando.value = false
    }
}

const cambiarVista = (vista) => {
    vistaActual.value = vista
    cargarNotas()
}

const abrirModalNuevo = () => {
    modoEdicion.value = false
    idEdicion.value = null
    formulario.value = { title: '', description: '', due_date: '' }
    mostrarModalFormulario.value = true
}

const abrirModalEditar = (nota) => {
    modoEdicion.value = true
    idEdicion.value = nota.id
    // Formateamos la fecha para el input type="date" (YYYY-MM-DD)
    const fechaFormat = nota.due_date ? nota.due_date.split('T')[0] : ''
    
    formulario.value = { 
        title: nota.title, 
        description: nota.description, 
        due_date: fechaFormat 
    }
    mostrarModalFormulario.value = true
}

const cerrarModal = () => {
    mostrarModalFormulario.value = false
}

const guardarNota = async () => {
    if (!formulario.value.title) {
        toast.warning('El título es obligatorio.')
        return
    }

    guardando.value = true
    try {
        if (modoEdicion.value) {
            await axios.put(`/api/notes/${idEdicion.value}`, formulario.value)
            toast.success('Nota actualizada correctamente.')
        } else {
            await axios.post('/api/notes', formulario.value)
            toast.success('Nota creada exitosamente.')
        }
        cerrarModal()
        await cargarNotas()
    } catch (error) {
        toast.error('Ocurrió un error al guardar la nota.')
    } finally {
        guardando.value = false
    }
}

// Cambiar estado del Checkbox (Completada / Pendiente)
const toggleCompletada = async (nota) => {
    try {
        const nuevoEstado = !nota.is_completed
        await axios.patch(`/api/notes/${nota.id}/status`, {
            is_completed: nuevoEstado
        })
        nota.is_completed = nuevoEstado
        if(nuevoEstado) toast.success('Tarea marcada como completada.')
    } catch (error) {
        toast.error('Error al actualizar el estado de la tarea.')
        // Revertir visualmente si falla el backend
        nota.is_completed = !nota.is_completed
    }
}

// Enviar al archivo o restaurar
const archivarNota = async (nota) => {
    try {
        await axios.patch(`/api/notes/${nota.id}/status`, {
            is_archived: true
        })
        toast.info('Nota enviada al archivo.')
        await cargarNotas()
    } catch (error) {
        toast.error('Error al archivar la nota.')
    }
}

const restaurarNota = async (nota) => {
    try {
        await axios.patch(`/api/notes/${nota.id}/status`, {
            is_archived: false
        })
        toast.success('Nota restaurada a pendientes.')
        await cargarNotas()
    } catch (error) {
        toast.error('Error al restaurar la nota.')
    }
}

const eliminarNota = async (id) => {
    const confirmacion = confirm('¿Está seguro de eliminar esta nota permanentemente?')
    if (!confirmacion) return

    try {
        await axios.delete(`/api/notes/${id}`)
        toast.success('Nota eliminada.')
        await cargarNotas()
    } catch (error) {
        toast.error('Error al eliminar la nota.')
    }
}


const formatearFecha = (fechaString) => {
    if (!fechaString) return 'Sin fecha límite'
   
    const soloFecha = fechaString.split('T')[0].split(' ')[0]
    return new Date(soloFecha + 'T12:00:00').toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' })
}

const estadoVencimiento = (fechaString, completada) => {
    if (!fechaString || completada) return 'normal'
    
    const hoy = new Date()
    hoy.setHours(0, 0, 0, 0)
    

    const soloFecha = fechaString.split('T')[0].split(' ')[0]
    const fechaLimite = new Date(soloFecha + 'T12:00:00')
    fechaLimite.setHours(0, 0, 0, 0)

    const diferenciaDias = (fechaLimite - hoy) / (1000 * 60 * 60 * 24)

    if (diferenciaDias < 0) return 'vencida'
    if (diferenciaDias <= 3) return 'alerta'
    return 'normal'
}

onMounted(() => {
    cargarNotas()
})
</script>

<template>
    <div class="h-full flex flex-col relative space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 tracking-wide uppercase">
                    Gestión de Notas y Alertas
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 font-medium">
                    Recordatorios, vencimientos y tareas de mantenimiento preventivo.
                </p>
            </div>
            
            <button @click="abrirModalNuevo" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2 shadow-sm">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nueva Nota
            </button>
        </div>

        <div class="flex border-b border-slate-200 dark:border-white/10">
            <button @click="cambiarVista('pendientes')" 
                :class="vistaActual === 'pendientes' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-sm transition-colors cursor-pointer bg-transparent">
                Notas Pendientes
            </button>
            <button @click="cambiarVista('archivadas')" 
                :class="vistaActual === 'archivadas' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                class="px-6 py-3 border-b-2 font-['Barlow_Condensed'] font-bold uppercase tracking-wider text-sm transition-colors cursor-pointer bg-transparent">
                Archivo Histórico
            </button>
        </div>

        <div v-if="cargando" class="py-12 text-center text-slate-400 text-sm">Cargando información...</div>
        
        <div v-else-if="notas.length === 0" class="py-12 text-center text-slate-400 text-sm border-2 border-dashed border-slate-200 dark:border-white/10 ">
            {{ vistaActual === 'pendientes' ? 'No hay notas pendientes registradas.' : 'El archivo histórico está vacío.' }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="nota in notas" :key="nota.id" 
                :class="['bg-white dark:bg-[#0d1b2a] border  p-5 shadow-sm transition-all flex flex-col', 
                         nota.is_completed ? 'border-emerald-200 dark:border-emerald-900/50 opacity-70' : 'border-slate-200 dark:border-white/10']">
                
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-start gap-3">
                        <div class="mt-1">
                            <input type="checkbox" :checked="nota.is_completed" @change="toggleCompletada(nota)" 
                                class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer" />
                        </div>
                        <div>
                            <h4 :class="['font-bold text-base m-0 leading-tight', nota.is_completed ? 'line-through text-slate-500 dark:text-slate-400' : 'text-slate-800 dark:text-slate-100']">
                                {{ nota.title }}
                            </h4>
                            
                            <div class="mt-1.5 flex items-center gap-1.5">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span :class="[
                                    'text-[10px] font-bold uppercase tracking-wider',
                                    estadoVencimiento(nota.due_date, nota.is_completed) === 'vencida' ? 'text-red-600 dark:text-red-400' : 
                                    estadoVencimiento(nota.due_date, nota.is_completed) === 'alerta' ? 'text-amber-600 dark:text-amber-400' : 
                                    'text-slate-500 dark:text-slate-400'
                                ]">
                                    {{ formatearFecha(nota.due_date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <p :class="['text-sm flex-1 mb-4', nota.is_completed ? 'text-slate-400' : 'text-slate-600 dark:text-slate-300']">
                    {{ nota.description }}
                </p>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-white/5">
                    <button v-if="!nota.is_archived" @click="abrirModalEditar(nota)" class="text-[11px] font-bold uppercase tracking-widest text-indigo-500 hover:text-indigo-700 bg-transparent border-none cursor-pointer p-0">Editar</button>
                    
                    <button v-if="!nota.is_archived" @click="archivarNota(nota)" class="text-[11px] font-bold uppercase tracking-widest text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 bg-transparent border-none cursor-pointer p-0">Archivar</button>
                    <button v-else @click="restaurarNota(nota)" class="text-[11px] font-bold uppercase tracking-widest text-emerald-500 hover:text-emerald-700 bg-transparent border-none cursor-pointer p-0">Restaurar</button>
                    
                    <button @click="eliminarNota(nota.id)" class="text-[11px] font-bold uppercase tracking-widest text-red-500 hover:text-red-700 bg-transparent border-none cursor-pointer p-0">Eliminar</button>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalFormulario" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden ">
                <div class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3 class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">
                        {{ modoEdicion ? 'Editar Nota' : 'Nueva Nota' }}
                    </h3>
                    <button @click="cerrarModal" class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>

                <form @submit.prevent="guardarNota" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Título / Asunto</label>
                        <input v-model="formulario.title" type="text" required class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" placeholder="Ej: Vencimiento VTV Camioneta" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Fecha límite (Opcional)</label>
                        <input v-model="formulario.due_date" type="date" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50 [color-scheme:light] dark:[color-scheme:dark]" />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Detalles / Descripción</label>
                        <textarea v-model="formulario.description" rows="3" class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50 resize-none" placeholder="Especificaciones adicionales..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModal" class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Guardar Nota' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>