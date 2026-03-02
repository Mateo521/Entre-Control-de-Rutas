<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const peajes = ref([])
const cargandoPeajes = ref(true)
const guardando = ref(false)

const isOnline = ref(navigator.onLine)
const actualizarEstadoRed = () => { isOnline.value = navigator.onLine }

const esEdicion = ref(false)
const idEdicion = ref(null)


const formulario = ref({ toll_id: '', category: '', title: '', description: '' })


 
const archivosAdjuntos = ref([])
const mediosExistentes = ref([])
const archivosAEliminar = ref([])

const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        peajes.value = respuesta.data.data
    } catch (error) {
        toast.error('Error al cargar la lista de ubicaciones')
    } finally {
        cargandoPeajes.value = false
    }
}

const cargarAccionParaEditar = async (id) => {
    try {
        const respuesta = await axios.get(`/api/actions/${id}`)
        const accion = respuesta.data

        formulario.value.toll_id = accion.toll_id || ''
        formulario.value.category = accion.category || ''
        formulario.value.title = accion.title
        formulario.value.description = accion.description

        if (accion.media_paths && accion.media_paths.length > 0) {
            mediosExistentes.value = accion.media_paths
        }
    } catch (error) {
        toast.error('Error al cargar el reporte para su edición')
        router.push('/panel/acciones')
    }
}
 
const procesarArchivo = (event) => {
    const files = Array.from(event.target.files)
    archivosAdjuntos.value.push(...files)
    event.target.value = ''  
}

const quitarArchivoNuevo = (index) => {
    archivosAdjuntos.value.splice(index, 1)
}

const eliminarArchivoExistente = (ruta) => {
    archivosAEliminar.value.push(ruta)
    mediosExistentes.value = mediosExistentes.value.filter(r => r !== ruta)
}

const guardarAccion = async () => {
    if (!navigator.onLine) {
        toast.error('No tienes conexión a internet. Espera recuperar la señal para guardar el reporte.');
        return;
    }

    guardando.value = true
    try {
        const formData = new FormData()

        if (formulario.value.toll_id) {
            formData.append('toll_id', formulario.value.toll_id)
        }
        formData.append('category', formulario.value.category)
        formData.append('title', formulario.value.title)
        formData.append('description', formulario.value.description)

        archivosAdjuntos.value.forEach(file => {
            formData.append('media[]', file)
        })

        if (esEdicion.value) {
            formData.append('_method', 'PUT')
            if (archivosAEliminar.value.length > 0) {
                formData.append('archivos_a_eliminar', JSON.stringify(archivosAEliminar.value))
            }
            await axios.post(`/api/actions/${idEdicion.value}`, formData)
            toast.success('Reporte de acción actualizado')
            router.push('/panel/acciones')
        } else {
            await axios.post('/api/actions', formData)
            toast.success('Acción registrada')

            formulario.value.title = ''
            formulario.value.description = ''
            archivosAdjuntos.value = []
        }
    } catch (error) {
        toast.error('Hubo un error al procesar el reporte de acción.')
    } finally {
        guardando.value = false
    }
}

onMounted(async () => {
    window.addEventListener('online', actualizarEstadoRed)
    window.addEventListener('offline', actualizarEstadoRed)

    await cargarPeajes()

    if (route.params.id) {
        esEdicion.value = true
        idEdicion.value = route.params.id
        cargarAccionParaEditar(route.params.id)
    }
})

onUnmounted(() => {
    window.removeEventListener('online', actualizarEstadoRed)
    window.removeEventListener('offline', actualizarEstadoRed)
})
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    {{ esEdicion ? `Edición de acción #${idEdicion}` : 'Nueva acción operativa' }}
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">Documentación de trabajos, mantenimientos
                    y adquisiciones</p>
            </div>
            <button v-if="esEdicion" @click="router.push('/panel/acciones')"
                class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-200 dark:bg-white/10 hover:bg-slate-300 dark:hover:bg-white/20 transition-colors border-none cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">
                Volver al historial
            </button>
        </div>

        <div class="grid grid-cols-[1fr_360px] gap-6">
            <div
                class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm dark:shadow-none transition-colors h-fit">
                <div
                    class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3.5 flex items-center justify-between">
                    <span
                        class="font-['Barlow_Condensed'] font-bold text-[17px] text-slate-900 dark:text-slate-100 tracking-wide">
                        Detalles del Informe
                    </span>
                </div>

                <form @submit.prevent="guardarAccion" class="p-6">
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div class="col-span-1">
                            <span
                                class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Ubicación
                                de la tarea</span>
                            <select v-model="formulario.toll_id"
                                class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-emerald-500/50 appearance-none cursor-pointer">
                                <option value="">Región Centro (General)</option>
                                <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">{{ peaje.name }}
                                </option>
                            </select>
                        </div>



                        <div class="col-span-1 flex gap-4">
                            <div class="w-1/2">
                                <span
                                    class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Categoría</span>
                                <select v-model="formulario.category" required
                                    class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-emerald-500/50 appearance-none cursor-pointer">
                                    <option value="" disabled selected>— Seleccionar —</option>
                                    <option value="Mantenimiento Vial">Mantenimiento Vial</option>
                                    <option value="Infraestructura Edilicia">Infraestructura Edilicia</option>
                                    <option value="Equipamiento y Tecnología">Equipamiento y Tecnología</option>
                                    <option value="Recursos Humanos y Seguridad">R.R.H.H y Seguridad</option>
                                </select>
                            </div>
                            <div class="w-1/2">
                                <span
                                    class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Título
                                    de la Acción</span>
                                <input v-model="formulario.title" type="text" required
                                    placeholder="Ej: Bacheo en Ruta 20"
                                    class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-emerald-500/50" />
                            </div>
                        </div>



                    </div>

                    <div class="mb-6">
                        <span
                            class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Descripción
                            Detallada</span>
                        <textarea v-model="formulario.description" required rows="6"
                            placeholder="Detalla los trabajos realizados, materiales, cuadrillas involucradas..."
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-emerald-500/50 resize-y"></textarea>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-white/5">
                        <button type="submit" :disabled="guardando || !isOnline"
                            class="bg-emerald-600 text-white font-['Barlow_Condensed'] text-[13px] font-bold tracking-wider uppercase px-6 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center transition-all hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ !isOnline ? 'Esperando conexión...' : (guardando ? 'Procesando...' : (esEdicion ?
                                'Actualizar Reporte' : 'Registrar Acción')) }}
                        </button>
                    </div>
                </form>
            </div>

            <div
                class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm dark:shadow-none transition-colors h-fit">
                <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3.5">
                    <span
                        class="font-['Barlow_Condensed'] font-bold text-[15px] text-slate-900 dark:text-slate-100 tracking-wide">
                        Portafolio de Evidencia
                    </span>
                </div>

                <div class="p-5">
                    <p class="text-[12px] text-slate-500 dark:text-slate-400 mb-4 leading-relaxed">
                        Adjuntá fotos de los mantenimientos hechos, facturas de compras o documentos de
                        respaldo.
                    </p>

                    <input id="accion-file-upload" type="file" multiple @change="procesarArchivo"
                        accept="image/*,video/mp4,.pdf" class="hidden" />
                    <label for="accion-file-upload"
                        class="w-full flex flex-col items-center justify-center gap-2 bg-slate-50 dark:bg-[#0a1628] border border-dashed border-slate-300 dark:border-white/20  px-3 py-6 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 hover:border-emerald-500/50 cursor-pointer transition-all group mb-4">
                        <div
                            class="bg-emerald-100 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 p-2 rounded-full group-hover:scale-110 transition-transform">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                        </div>
                        <span>Subir evidencia documental</span>
                    </label>

                    <div v-if="archivosAdjuntos.length > 0" class="flex flex-col gap-1.5">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Archivos
                            listos para subir:</span>
                        <div v-for="(archivo, i) in archivosAdjuntos" :key="i"
                            class="bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-md px-3 py-2 flex items-center justify-between shadow-sm">
                            <span
                                class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400 truncate pr-2">{{
                                archivo.name }}</span>
                            <button type="button" @click="quitarArchivoNuevo(i)"
                                class="text-[10px] font-bold uppercase tracking-wider text-red-500 hover:text-red-700 cursor-pointer bg-transparent border-none">Quitar</button>
                        </div>
                    </div>

                    <div v-if="mediosExistentes.length > 0"
                        class="flex flex-col gap-1.5 mt-4 pt-4 border-t border-slate-200 dark:border-white/10">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Ya guardados
                            en servidor:</span>
                        <div v-for="(ruta, i) in mediosExistentes" :key="'existente-' + i"
                            class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-500/30 rounded-md px-3 py-2 flex items-center justify-between shadow-sm">
                            <span
                                class="text-[11px] text-blue-700 dark:text-blue-400 font-['Barlow_Condensed'] uppercase tracking-wider font-bold">Documento
                                #{{ i + 1 }}</span>
                            <button type="button" @click="eliminarArchivoExistente(ruta)"
                                class="text-[10px] font-bold uppercase tracking-wider text-red-500 hover:text-red-700 cursor-pointer bg-transparent border-none">Eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>