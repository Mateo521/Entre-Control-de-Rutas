<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const peajes = ref([])
const cargando = ref(true)

const mostrarModal = ref(false)
const guardando = ref(false)

const esEdicion = ref(false)
const idEdicion = ref(null)

const nuevoPeaje = reactive({
    name: '',
    camposDinámicos: []
})

 
const fotoSeleccionada = ref(null)
const previewFoto = ref(null)
const fileInput = ref(null)

const paginaActual = ref(1)
const ultimaPagina = ref(1)
const totalRegistros = ref(0)

const cargarPeajes = async (page = 1) => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/tolls', {
            params: { page: page }
        })

        peajes.value = respuesta.data.data
        paginaActual.value = respuesta.data.current_page
        ultimaPagina.value = respuesta.data.last_page
        totalRegistros.value = respuesta.data.total
    } catch (error) {
        toast.error('Error al cargar los peajes')
    } finally {
        cargando.value = false
    }
}

const abrirModal = (peaje = null) => {
     
    fotoSeleccionada.value = null
    previewFoto.value = null

    if (peaje && peaje.id) {
        esEdicion.value = true
        idEdicion.value = peaje.id
        nuevoPeaje.name = peaje.name

        
        nuevoPeaje.camposDinámicos = peaje.dynamic_schema && peaje.dynamic_schema.inventory_fields
            ? peaje.dynamic_schema.inventory_fields.map(c => ({
                ...c,
                label: c.label || c.name.replace(/_/g, ' ')  
            }))
            : []
        
        
        if (peaje.image_path) {
            previewFoto.value = peaje.image_path
        }

    } else {
        esEdicion.value = false
        idEdicion.value = null
        nuevoPeaje.name = ''
        nuevoPeaje.camposDinámicos = []
    }

    mostrarModal.value = true
}

const cerrarModal = () => {
    mostrarModal.value = false
}

const agregarCampo = () => {
 
    nuevoPeaje.camposDinámicos.push({ label: '', name: '', type: 'texto' })
}

const quitarCampo = (index) => {
    nuevoPeaje.camposDinámicos.splice(index, 1)
}

 
const seleccionarFoto = (event) => {
    const file = event.target.files[0]
    if (file) {
        fotoSeleccionada.value = file
        previewFoto.value = URL.createObjectURL(file)  
    }
}

const guardarPeaje = async () => {
    if (!nuevoPeaje.name) return;

    guardando.value = true;

    try {
  
        const camposFormateados = nuevoPeaje.camposDinámicos.map(c => {
            const internalName = c.name || (c.label ? c.label.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '') : 'campo');
            return {
                label: c.label,
                name: internalName,
                type: c.type
            }
        })

        const payload = {
            name: nuevoPeaje.name,
            dynamic_schema: {
                inventory_fields: camposFormateados
            }
        }

        let peajeIdOperado = null;

        
        if (esEdicion.value) {
            await axios.put(`/api/tolls/${idEdicion.value}`, payload)
            peajeIdOperado = idEdicion.value;
            toast.success('Configuración de peaje actualizada.')
        } else {
            const respuestaPost = await axios.post('/api/tolls', payload)
            peajeIdOperado = respuestaPost.data.data.id;  
            toast.success('Peaje registrado.')
        }

       
        if (fotoSeleccionada.value && peajeIdOperado) {
            const formData = new FormData();
            formData.append('image', fotoSeleccionada.value);

            await axios.post(`/api/tolls/${peajeIdOperado}/image`, formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json'  
                }
            });
            toast.success('Foto de fachada guardada.');
        }

        cerrarModal()
        cargarPeajes(paginaActual.value)  

    } catch (error) {
        console.error("Error al guardar:", error)
        toast.error("Ocurrió un error al guardar la configuración.")
    } finally {
        guardando.value = false
    }
}

onMounted(() => {
    cargarPeajes()
})
</script>

<template>
    <div class="relative h-full">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Gestión de peajes
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0">
                    Administración y configuración de campos personalizados por peaje
                </p>
            </div>

            <button @click="abrirModal(null)"
                class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-2.5 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-amber-400 hover:-translate-y-px shadow-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Nuevo peaje
            </button>
        </div>

        <div
            class="bg-white dark:bg-white/5 border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-amber-500/5 dark:bg-amber-500/10 border-b border-amber-500/20">
                            <th
                                class="px-4 py-2.5 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                ID</th>
                            <th
                                class="px-4 py-2.5 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                Nombre</th>
                            <th
                                class="px-4 py-2.5 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                Fachada</th>
                            <th
                                class="px-4 py-2.5 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                Campos Dinámicos</th>
                            <th
                                class="px-4 py-2.5 text-left font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                Estado</th>
                            <th
                                class="px-4 py-2.5 text-right font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-amber-600 dark:text-amber-500">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="cargando">
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500 text-sm font-medium">Cargando
                                peajes desde la base de datos...</td>
                        </tr>
                        <tr v-else-if="peajes.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">No hay peajes
                                registrados aún. Haz clic en "Nuevo peaje".</td>
                        </tr>
                        <tr v-else v-for="peaje in peajes" :key="peaje.id"
                            class="hover:bg-black/5 dark:hover:bg-white/5 transition-colors border-b border-slate-100 dark:border-white/5">
                            <td class="px-4 py-3 text-[13.5px] font-semibold text-slate-900 dark:text-slate-100">#{{
                                peaje.id }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ peaje.name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <img v-if="peaje.image_path" :src="peaje.image_path" class="w-12 h-8 object-cover rounded shadow-sm border border-slate-200 dark:border-white/10" />
                                <span v-else class="text-[10px] text-slate-400 italic">Sin foto</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-[13px] text-slate-700 dark:text-slate-300 font-medium">
                                    {{ peaje.dynamic_schema && peaje.dynamic_schema.inventory_fields ?
                                        peaje.dynamic_schema.inventory_fields.length : 0 }}
                                    <span class="text-slate-500 dark:text-slate-500 font-normal">campos
                                        personalizados</span>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wider uppercase transition-all bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full inline-block bg-emerald-400 shadow-[0_0_6px_#34d399]"></span>
                                    Activo
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button @click="abrirModal(peaje)"
                                    class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-500/20 rounded transition-colors cursor-pointer border-none bg-transparent inline-flex items-center justify-center">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="totalRegistros > 0"
                    class="bg-slate-50 dark:bg-[#0d1b2a] border-t border-slate-200 dark:border-white/10 px-5 py-3 flex items-center justify-between">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Mostrando página {{ paginaActual }} de {{ ultimaPagina }} ({{ totalRegistros }} registros
                        totales)
                    </span>
                    <div class="flex gap-2">
                        <button @click="cargarPeajes(paginaActual - 1)" :disabled="paginaActual === 1"
                            class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Anterior
                        </button>
                        <button @click="cargarPeajes(paginaActual + 1)" :disabled="paginaActual === ultimaPagina"
                            class="px-3 py-1.5 rounded-md text-xs font-bold uppercase font-['Barlow_Condensed'] tracking-wider border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModal"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 dark:bg-[#0a1628]/80 backdrop-blur-sm transition-opacity p-4">
            <div
                class="w-full max-w-lg bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden relative">

                <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-1"></div>

                <div class="px-6 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <h3
                        class="font-['Barlow_Condensed'] text-[20px] font-bold text-slate-900 dark:text-slate-100 tracking-wide m-0">
                        {{ esEdicion ? 'Configurar estación de peaje' : 'Registrar nuevo peaje' }}
                    </h3>
                    <button @click="cerrarModal"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors cursor-pointer bg-transparent border-none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarPeaje" class="p-6">
                    
                    <div class="mb-5">
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">
                            foto de fachada (opcional)
                        </label>
                        <div class="relative w-full h-36 rounded-lg border-2 border-dashed border-slate-300 dark:border-white/20 bg-slate-50 dark:bg-white/5 overflow-hidden flex items-center justify-center group hover:border-amber-500/50 transition-colors cursor-pointer" @click="$refs.fileInput.click()">
                            <img v-if="previewFoto" :src="previewFoto" class="w-full h-full object-cover absolute inset-0 z-0 group-hover:opacity-50 transition-opacity" />
                            
                            <div v-if="!previewFoto" class="text-center z-10 flex flex-col items-center">
                                <svg class="w-8 h-8 text-slate-400 mb-2 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 group-hover:text-amber-500">Haz clic para subir imagen</span>
                                <span class="text-[10px] text-slate-400 mt-1">Se comprimirá automáticamente</span>
                            </div>

                            <div v-if="previewFoto" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                <span class="text-white text-xs font-bold uppercase tracking-wider bg-black/60 px-3 py-1.5 rounded shadow-lg">Cambiar foto</span>
                            </div>

                            <input ref="fileInput" type="file" accept="image/jpeg, image/png, image/webp" class="hidden" @change="seleccionarFoto" />
                        </div>
                    </div>

                    <div class="mb-5">
                        <label
                            class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Nombre
                            del Peaje</label>
                        <input v-model="nuevoPeaje.name" type="text" required placeholder="Ej: Peaje La Carolina"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-amber-500/50" />
                    </div>

                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <label
                                class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block">Campos
                                Operativos Adicionales (JSON)</label>
                            <button type="button" @click="agregarCampo"
                                class="text-[11px] font-semibold text-amber-600 dark:text-amber-500 hover:text-amber-700 bg-amber-50 dark:bg-amber-500/10 px-2 py-1 rounded cursor-pointer border-none transition-colors">
                                + Agregar campo
                            </button>
                        </div>

                        <div v-if="nuevoPeaje.camposDinámicos.length === 0"
                            class="text-[12px] text-slate-400 dark:text-slate-500 italic p-3 border border-dashed border-slate-300 dark:border-white/10 rounded-lg text-center bg-slate-50/50 dark:bg-transparent">
                            No se solicitarán campos adicionales en los sucesos de este peaje.
                        </div>

                        <div v-else class="space-y-2 max-h-[160px] overflow-y-auto pr-1">
                            <div v-for="(campo, index) in nuevoPeaje.camposDinámicos" :key="index"
                                class="flex gap-2 items-center bg-slate-50 dark:bg-white/5 p-2 rounded-lg border border-slate-200 dark:border-white/5">
                                <input v-model="campo.label" type="text"
                                    placeholder="Nombre visible (Ej: Estado de Barreras)" required
                                    class="flex-1 bg-transparent border-none text-slate-900 dark:text-white text-xs outline-none focus:ring-0 placeholder:text-slate-400" />
                                <select v-model="campo.type"
                                    class="bg-white dark:bg-[#0a1628] border border-slate-200 dark:border-white/10 rounded text-xs px-2 py-1 text-slate-700 dark:text-slate-300 outline-none">
                                    <option value="texto">Texto</option>
                                    <option value="numero">Número</option>
                                    <option value="booleano">Sí/No</option>
                                    <option value="multimedia">Archivo / fotos</option>
                                </select>
                                <button type="button" @click="quitarCampo(index)"
                                    class="text-red-400 hover:text-red-600 p-1 cursor-pointer bg-transparent border-none">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6l-1 14H6L5 6"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end pt-2 border-t border-slate-100 dark:border-white/5">
                        <button type="button" @click="cerrarModal"
                            class="px-4 py-2 rounded-lg text-[13px] font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 transition-colors cursor-pointer border-none bg-transparent font-['Barlow_Condensed'] uppercase tracking-wider">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardando"
                            class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-[13px] font-bold tracking-wider uppercase px-6 py-2 rounded-lg border-none cursor-pointer inline-flex items-center transition-all hover:bg-amber-400 disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : (esEdicion ? 'Actualizar Cambios' : 'Confirmar y Crear') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>