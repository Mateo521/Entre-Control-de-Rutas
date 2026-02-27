<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const peajes = ref([])
const cargandoPeajes = ref(true)
const guardando = ref(false)


const isOnline = ref(navigator.onLine)

const actualizarEstadoRed = () => {
    isOnline.value = navigator.onLine
}

 
const esEdicion = ref(false)
const idEdicion = ref(null)

const formulario = ref({ toll_id: '', incident_type: '', dynamic_data: {} })
const camposDinamicosActivos = ref([])
 
const archivosAdjuntos = ref({})  
const mediosExistentes = ref({})  
const archivosAEliminar = ref([])  
 
const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        peajes.value = respuesta.data
    } catch (error) {
        toast.error('Error al cargar estaciones')
    } finally {
        cargandoPeajes.value = false
    }
}

const cargarSucesoParaEditar = async (id) => {
    try {
        const respuesta = await axios.get(`/api/incidents/${id}`)
        const suceso = respuesta.data
       
        formulario.value.toll_id = suceso.toll_id
        formulario.value.incident_type = suceso.incident_type
         
        actualizarCamposDinamicos(suceso.toll_id)
       
        if (suceso.dynamic_data) {
            
            formulario.value.dynamic_data = { ...formulario.value.dynamic_data, ...suceso.dynamic_data }
        }

     
        if (suceso.media_paths) {
            mediosExistentes.value = suceso.media_paths
        }

    } catch (error) {
        toast.error('Error al cargar el registro para edición')
        router.push('/panel/sucesos')
    }
}

 
const actualizarCamposDinamicos = (idPeaje) => {
    if (!idPeaje) {
        camposDinamicosActivos.value = []
        return
    }
    const peajeSeleccionado = peajes.value.find(p => p.id === idPeaje)
    if (peajeSeleccionado && peajeSeleccionado.dynamic_schema && peajeSeleccionado.dynamic_schema.inventory_fields) {
        camposDinamicosActivos.value = peajeSeleccionado.dynamic_schema.inventory_fields
    } else {
        camposDinamicosActivos.value = []
    }
}
 
const alCambiarPeaje = () => {
    archivosAdjuntos.value = {} 
    mediosExistentes.value = {}
    archivosAEliminar.value = []
    formulario.value.dynamic_data = {}
    
    actualizarCamposDinamicos(formulario.value.toll_id)
    
    
    camposDinamicosActivos.value.forEach(campo => {
        if (campo.type !== 'multimedia') {
            formulario.value.dynamic_data[campo.name] = (campo.type === 'booleano') ? false : ''
        }
    })
}

 
const procesarArchivo = (event, nombreCampo) => {
    const file = event.target.files[0]
    if (file) archivosAdjuntos.value[nombreCampo] = file
}

const eliminarArchivoExistente = (nombreCampo) => {
    archivosAEliminar.value.push(nombreCampo)
    
    delete mediosExistentes.value[nombreCampo]
}

 
const guardarSuceso = async () => {

    if (!navigator.onLine) {
        toast.error('No tienes conexión a internet. Espera recuperar la señal para enviar la evidencia.');
        return;
    }


    guardando.value = true
    try {
        const formData = new FormData()
        formData.append('toll_id', formulario.value.toll_id)
        formData.append('incident_type', formulario.value.incident_type)
        formData.append('dynamic_data', JSON.stringify(formulario.value.dynamic_data))
        
       
        Object.keys(archivosAdjuntos.value).forEach(campoNombre => {
            formData.append(`media[${campoNombre}]`, archivosAdjuntos.value[campoNombre])
        })

        if (esEdicion.value) {
            formData.append('_method', 'PUT')
            
           
            if (archivosAEliminar.value.length > 0) {
                formData.append('archivos_a_eliminar', JSON.stringify(archivosAEliminar.value))
            }

            await axios.post(`/api/incidents/${idEdicion.value}`, formData)
            toast.success('Registro actualizado y sincronizado')
            router.push('/panel/sucesos')
        } else {
            await axios.post('/api/incidents', formData)
            toast.success('Suceso y archivos registrados correctamente')
            formulario.value.incident_type = ''
            alCambiarPeaje()  
        }
    } catch (error) {
        toast.error('Hubo un error al procesar la solicitud.')
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
        cargarSucesoParaEditar(route.params.id)
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
                <h2 class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    {{ esEdicion ? `Edición de Suceso #${idEdicion}` : 'Registro Operativo' }}
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">Reporte de sucesos en la traza con soporte multimedia</p>
            </div>
            
            <button v-if="esEdicion" @click="router.push('/panel/sucesos')" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-400 bg-slate-200 dark:bg-white/10 hover:bg-slate-300 dark:hover:bg-white/20 transition-colors border-none cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">
                Volver al historial
            </button>
        </div>

        <div class="grid grid-cols-[1fr_360px] gap-6">
            
            <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="bg-slate-50 dark:bg-white/5 border-b border-slate-200 dark:border-white/10 px-5 py-3.5 flex items-center justify-between">
                    <span class="font-['Barlow_Condensed'] font-bold text-[17px] text-slate-900 dark:text-slate-100 tracking-wide">
                        {{ esEdicion ? 'Actualizar datos operativos' : 'Nuevo suceso' }}
                    </span>
                </div>

                <form @submit.prevent="guardarSuceso" class="p-6">
                    <div class="grid grid-cols-2 gap-5 mb-5">
                        <div>
                            <span class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-2">Peaje / Estación</span>
                            <select v-model="formulario.toll_id" @change="alCambiarPeaje" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-amber-500/50 appearance-none cursor-pointer">
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
                                <option value="falla_infraestructura">Falla en Infraestructura</option>
                                <option value="corte_ruta">Corte de Ruta / Manifestación</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="camposDinamicosActivos.length > 0" class="mb-6 p-4  border border-amber-500/30 bg-amber-50 dark:bg-amber-500/5">
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

                                <div v-if="campo.type === 'multimedia'" class="relative">
                                    
                                    <div v-if="mediosExistentes[campo.name]" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-500/30 rounded-md px-3 py-2 flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-widest font-['Barlow_Condensed']">Archivo vinculado</span>
                                        <button type="button" @click="eliminarArchivoExistente(campo.name)" class="text-[10px] font-bold uppercase tracking-wider text-red-500 hover:text-red-700 cursor-pointer bg-transparent border-none">
                                            Quitar
                                        </button>
                                    </div>

                                    <input v-else type="file" @change="procesarArchivo($event, campo.name)" accept="image/*,video/mp4,.pdf" class="w-full bg-white dark:bg-[#0a1628] border border-slate-300 dark:border-white/10 rounded-md px-2 py-1.5 text-xs outline-none focus:border-amber-500/50 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-500/10 dark:file:text-amber-500 cursor-pointer" />
                                
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-white/5 mt-6">
          <button type="submit" :disabled="guardando || !isOnline" class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-[13px] font-bold tracking-wider uppercase px-6 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center transition-all hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed">
    {{ !isOnline ? 'Esperando conexión...' : (guardando ? 'Procesando...' : (esEdicion ? 'Actualizar Suceso' : 'Registrar Suceso')) }}
</button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10  overflow-hidden shadow-sm dark:shadow-none transition-colors h-fit">
                <div class="p-5 flex gap-3">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" class="shrink-0">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <div class="text-[12.5px] text-slate-500 dark:text-slate-400 leading-relaxed">
                        <strong class="text-slate-700 dark:text-slate-200 block mb-1">Auditoría de Modificaciones</strong>
                        {{ esEdicion ? 'Al actualizar un registro operativo, los archivos previos que decidas quitar serán eliminados permanentemente del servidor. Los datos se mantendrán vinculados al usuario creador.' : 'Al seleccionar una estación de peaje, el sistema verificará si el administrador ha requerido campos adicionales (N° de actas, controles de carga, etc.).' }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>