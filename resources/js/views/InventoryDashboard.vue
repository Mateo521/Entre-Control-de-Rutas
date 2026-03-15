<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import { toast } from 'vue3-toastify'

const inventario = ref([])
const peajes = ref([])
const cargando = ref(true)

// --- NUEVO: ESTADO DE PAGINACIÓN Y FILTROS ---
const paginacion = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})

const filtros = ref({
    search: '',
    toll_id: '',
    category: ''
})

const categorias = ['Bacheo', 'Señalización', 'Guardarrail', 'Mantenimiento', 'Herramientas']
// ----------------------------------------------

const mostrarModalOP = ref(false)
const mostrarModalNuevoInsumo = ref(false)
const mostrarModalEditar = ref(false)
const guardando = ref(false)
const idEdicion = ref(null)

const formOP = ref({ inventory_item_id: '', quantity: '', reference_document: '', description: '' })
const formNuevoInsumo = ref({ name: '', category: '', unit_measure: '', alert_threshold: 10, toll_id: '' })
const formEditarInsumo = ref({ name: '', category: '', unit_measure: '', alert_threshold: 10, toll_id: '' })

const cargarPeajes = async () => {
    try {
        const respuesta = await axios.get('/api/tolls')
        peajes.value = respuesta.data.data || respuesta.data
    } catch (error) {
        toast.error('Error al cargar la lista de peajes.')
    }
}

// --- NUEVO: FUNCIÓN DE CARGA OPTIMIZADA CON PARÁMETROS ---
const cargarInventario = async (page = 1) => {
    cargando.value = true
    try {
        const respuesta = await axios.get('/api/inventory', {
            params: {
                page: page,
                search: filtros.value.search,
                toll_id: filtros.value.toll_id,
                category: filtros.value.category
            }
        })

        // Laravel paginate() devuelve los datos dentro de 'data'
        inventario.value = respuesta.data.data

        // Guardamos la información de la paginación para dibujar los botones
        paginacion.value = {
            current_page: respuesta.data.current_page,
            last_page: respuesta.data.last_page,
            total: respuesta.data.total
        }
    } catch (error) {
        toast.error('Error al cargar el catálogo de inventario.')
    } finally {
        cargando.value = false
    }
}

// Reactividad: Si el usuario cambia un filtro (Peaje o Categoría), recargamos desde la página 1
watch([() => filtros.value.toll_id, () => filtros.value.category], () => {
    cargarInventario(1)
})

// Función para el buscador de texto (se dispara al presionar Enter o limpiar)
const buscarPorTexto = () => {
    cargarInventario(1)
}

const limpiarFiltros = () => {
    filtros.value = { search: '', toll_id: '', category: '' }
    cargarInventario(1)
}

// --- RESTO DE LAS FUNCIONES CRUD ORIGINALES ---
const registrarIngresoOP = async () => {
    guardando.value = true
    try {
        await axios.post('/api/inventory/movements', {
            inventory_item_id: formOP.value.inventory_item_id,
            quantity: formOP.value.quantity,
            reference_document: formOP.value.reference_document,
            description: formOP.value.description,
            type: 'in'
        })
        toast.success('Orden de Provisión registrada exitosamente.')
        cerrarModalOP()
        await cargarInventario(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al registrar el ingreso.')
    } finally {
        guardando.value = false
    }
}

const registrarNuevoInsumo = async () => {
    guardando.value = true
    try {
        await axios.post('/api/inventory', {
            name: formNuevoInsumo.value.name,
            category: formNuevoInsumo.value.category,
            unit_measure: formNuevoInsumo.value.unit_measure,
            alert_threshold: formNuevoInsumo.value.alert_threshold,
            toll_id: formNuevoInsumo.value.toll_id,
            current_stock: 0
        })
        toast.success('Nuevo insumo agregado al catálogo.')
        cerrarModalNuevoInsumo()
        await cargarInventario(1) // Volvemos a la página 1 para ver el nuevo
    } catch (error) {
        toast.error('Error al crear el insumo.')
    } finally {
        guardando.value = false
    }
}

const abrirModalEditar = (item) => {
    idEdicion.value = item.id
    formEditarInsumo.value = {
        name: item.name,
        category: item.category,
        unit_measure: item.unit_measure,
        alert_threshold: item.alert_threshold,
        toll_id: item.toll_id
    }
    mostrarModalEditar.value = true
}

const guardarEdicionInsumo = async () => {
    guardando.value = true
    try {
        await axios.put(`/api/inventory/${idEdicion.value}`, formEditarInsumo.value)
        toast.success('Insumo actualizado correctamente.')
        cerrarModalEditar()
        await cargarInventario(paginacion.value.current_page)
    } catch (error) {
        toast.error('Error al actualizar el insumo.')
    } finally {
        guardando.value = false
    }
}

const eliminarInsumo = async (id) => {
    const confirmacion = confirm('¿Está seguro de eliminar este insumo? Esta acción no se puede deshacer.')
    if (!confirmacion) return

    try {
        await axios.delete(`/api/inventory/${id}`)
        toast.success('Insumo eliminado exitosamente.')
        await cargarInventario(paginacion.value.current_page)
    } catch (error) {
        toast.error('No se pudo eliminar. Puede tener movimientos históricos.')
    }
}

const cerrarModalOP = () => {
    mostrarModalOP.value = false
    formOP.value = { inventory_item_id: '', quantity: '', reference_document: '', description: '' }
}

const cerrarModalNuevoInsumo = () => {
    mostrarModalNuevoInsumo.value = false
    formNuevoInsumo.value = { name: '', category: '', unit_measure: '', alert_threshold: 10, toll_id: '' }
}

const cerrarModalEditar = () => {
    mostrarModalEditar.value = false
    idEdicion.value = null
    formEditarInsumo.value = { name: '', category: '', unit_measure: '', alert_threshold: 10, toll_id: '' }
}

onMounted(() => {
    cargarPeajes()
    cargarInventario()
})
</script>

<template>
    <div class="flex flex-col relative h-full">
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2
                    class="font-['Barlow_Condensed'] text-[28px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-0.5 tracking-wide">
                    Control de inventario por Peaje
                </h2>
                <p class="text-[13px] text-slate-500 dark:text-slate-400 m-0 uppercase tracking-widest font-bold">
                    Catálogo y Órdenes de Provisión (OP)
                </p>
            </div>

            <div class="flex gap-3">
                <button @click="mostrarModalNuevoInsumo = true"
                    class="bg-slate-200 hover:bg-slate-300 dark:bg-white/10 dark:hover:bg-white/20 text-slate-800 dark:text-white px-4 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2">
                    Nuevo Insumo
                </button>

                <button @click="mostrarModalOP = true"
                    class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-5 py-2.5 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer flex items-center gap-2 shadow-lg shadow-amber-500/20">
                    Cargar OP (Ingreso)
                </button>
            </div>
        </div>

        <div
            class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10  p-4 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Buscar
                    Insumo</label>
                <div class="relative">
                    <input v-model="filtros.search" @keyup.enter="buscarPorTexto" type="text"
                        placeholder="Ej: Pintura amarilla..."
                        class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg pl-3 pr-10 py-2 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500" />
                    <button v-if="filtros.search" @click="filtros.search = ''; buscarPorTexto()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 bg-transparent border-none cursor-pointer">×</button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por
                    Peaje</label>
                <select v-model="filtros.toll_id"
                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                    <option value="">Todos los Peajes</option>
                    <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">{{ peaje.name }}</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Filtrar por
                    Categoría</label>
                <select v-model="filtros.category"
                    class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500">
                    <option value="">Todas las Categorías</option>
                    <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
                </select>
            </div>

            <div class="flex justify-end">
                <button @click="limpiarFiltros"
                    class="text-[11px] font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white uppercase tracking-widest bg-transparent border-none cursor-pointer">
                    Limpiar Filtros
                </button>
            </div>
        </div>

        <div
            class="bg-white dark:bg-[#0d1b2a] border-x border-slate-200 dark:border-white/10 shadow-sm overflow-x-auto flex-1">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-white/5 border-y border-slate-200 dark:border-white/10">
                        <th
                            class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            Peaje / Sucursal</th>
                        <th
                            class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            Material / Insumo</th>
                        <th
                            class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            Categoría</th>
                        <th
                            class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">
                            Stock Actual</th>
                        <th
                            class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
                            Estado</th>
                        <th
                            class="p-4 font-['Barlow_Condensed'] text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest text-right">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody v-if="cargando">
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 text-sm">Cargando inventario...</td>
                    </tr>
                </tbody>
                <tbody v-else-if="inventario.length === 0">
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 text-sm">No se encontraron resultados para
                            los filtros seleccionados.</td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr v-for="item in inventario" :key="item.id"
                        class="border-b border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                        <td class="p-4">
                            <span
                                class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest bg-indigo-50 dark:bg-indigo-500/10 px-2 py-1 rounded">
                                {{ item.toll ? item.toll.name : 'Sin asignar' }}
                            </span>
                        </td>
                        <td class="p-4"><span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ item.name
                                }}</span></td>
                        <td class="p-4"><span
                                class="text-xs px-2.5 py-1 rounded-full bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 font-medium">{{
                                item.category }}</span></td>
                        <td class="p-4 text-right">
                            <span class="font-mono text-base font-bold text-slate-900 dark:text-amber-400">{{
                                item.current_stock }}</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 ml-1 uppercase">{{ item.unit_measure
                                }}</span>
                        </td>
                        <td class="p-4">
                            <span v-if="parseFloat(item.current_stock) <= parseFloat(item.alert_threshold)"
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-500/10 px-2 py-1 rounded uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> Stock Bajo
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Normal
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="abrirModalEditar(item)"
                                    class="p-1.5 text-slate-400 hover:text-indigo-500 transition-colors bg-transparent border-none cursor-pointer">Editar</button>
                                <button @click="eliminarInsumo(item.id)"
                                    class="p-1.5 text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer">Borrar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="paginacion.total > 0"
            class="bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 rounded-b-xl p-4 shadow-sm flex items-center justify-between">
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Mostrando página <strong>{{ paginacion.current_page }}</strong> de <strong>{{ paginacion.last_page
                    }}</strong> ({{ paginacion.total }} ítems en total)
            </span>
            <div class="flex items-center gap-2">
                <button @click="cargarInventario(paginacion.current_page - 1)" :disabled="paginacion.current_page === 1"
                    class="px-3 py-1.5 text-sm font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-white/20 rounded hover:bg-slate-50 dark:hover:bg-white/5 disabled:opacity-30 disabled:cursor-not-allowed bg-transparent cursor-pointer transition-colors">
                    Anterior
                </button>
                <button @click="cargarInventario(paginacion.current_page + 1)"
                    :disabled="paginacion.current_page === paginacion.last_page"
                    class="px-3 py-1.5 text-sm font-bold text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-white/20 rounded hover:bg-slate-50 dark:hover:bg-white/5 disabled:opacity-30 disabled:cursor-not-allowed bg-transparent cursor-pointer transition-colors">
                    Siguiente
                </button>
            </div>
        </div>

        <div v-if="mostrarModalOP"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div
                class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden ">
                <div
                    class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3
                        class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">
                        Ingresar Material (OP)</h3>
                    <button @click="cerrarModalOP"
                        class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="registrarIngresoOP" class="p-6 space-y-4">
                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Material
                            y Peaje a Ingresar</label>
                        <select v-model="formOP.inventory_item_id" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                            <option value="" disabled selected>Seleccione el insumo de destino...</option>
                            <option v-for="item in inventario" :key="item.id" :value="item.id">
                                {{ item.name }} ({{ item.unit_measure }}) - {{ item.toll ? item.toll.name : 'Sin Peaje'
                                }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Cantidad</label>
                            <input v-model="formOP.quantity" type="number" step="0.01" min="0.01" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                                placeholder="Ej: 50" />
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">N°
                                de OP / Remito</label>
                            <input v-model="formOP.reference_document" type="text" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                                placeholder="Ej: OP-2026-45" />
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Observaciones</label>
                        <textarea v-model="formOP.description" rows="2"
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50 resize-none"
                            placeholder="Detalles del proveedor..."></textarea>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalOP"
                            class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando"
                            class="bg-amber-500 hover:bg-amber-400 text-[#0d1b2a] px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Confirmar Ingreso' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="mostrarModalNuevoInsumo"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div
                class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden ">
                <div
                    class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3
                        class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">
                        Registrar Nuevo Insumo</h3>
                    <button @click="cerrarModalNuevoInsumo"
                        class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="registrarNuevoInsumo" class="p-6 space-y-4">
                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Peaje
                            / Sucursal Asignada</label>
                        <select v-model="formNuevoInsumo.toll_id" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                            <option value="" disabled selected>Seleccione el peaje...</option>
                            <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">{{ peaje.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Nombre
                            del Insumo</label>
                        <input v-model="formNuevoInsumo.name" type="text" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                            placeholder="Ej: Pintura Vial Amarilla" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Categoría</label>
                            <select v-model="formNuevoInsumo.category" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                                <option value="" disabled selected>Seleccione...</option>
                                <option value="Bacheo">Bacheo</option>
                                <option value="Señalización">Señalización</option>
                                <option value="Seguridad">Seguridad Vial</option>
                                <option value="Mantenimiento">Mantenimiento General</option>
                                <option value="Herramientas">Herramientas</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Unidad
                                de Medida</label>
                            <select v-model="formNuevoInsumo.unit_measure" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50">
                                <option value="" disabled selected>Seleccione...</option>
                                <option value="Litros">Litros</option>
                                <option value="Kilos">Kilos</option>
                                <option value="Bolsas">Bolsas</option>
                                <option value="Tambores">Tambores</option>
                                <option value="Unidades">Unidades</option>
                                <option value="Metros">Metros</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Alerta
                            de Stock Bajo (Límite)</label>
                        <input v-model="formNuevoInsumo.alert_threshold" type="number" min="1" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                            placeholder="Ej: 10" />
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalNuevoInsumo"
                            class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando"
                            class="bg-slate-800 dark:bg-white text-white dark:text-black px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Guardar en Catálogo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="mostrarModalEditar"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div
                class="bg-white dark:bg-[#0d1b2a] w-full max-w-md shadow-2xl border border-slate-200 dark:border-white/10 overflow-hidden ">
                <div
                    class="p-5 border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-slate-50 dark:bg-white/5">
                    <h3
                        class="font-['Barlow_Condensed'] text-xl font-bold text-slate-900 dark:text-white uppercase tracking-wide m-0">
                        Editar Insumo</h3>
                    <button @click="cerrarModalEditar"
                        class="text-slate-400 hover:text-red-500 transition-colors bg-transparent border-none cursor-pointer p-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="guardarEdicionInsumo" class="p-6 space-y-4">
                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Cambiar
                            Peaje Asignado</label>
                        <select v-model="formEditarInsumo.toll_id" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50">
                            <option v-for="peaje in peajes" :key="peaje.id" :value="peaje.id">{{ peaje.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Nombre
                            del Insumo</label>
                        <input v-model="formEditarInsumo.name" type="text" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Categoría</label>
                            <select v-model="formEditarInsumo.category" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50">
                                <option value="Bacheo">Bacheo</option>
                                <option value="Señalización">Señalización</option>
                                <option value="Seguridad">Seguridad Vial</option>
                                <option value="Mantenimiento">Mantenimiento General</option>
                                <option value="Herramientas">Herramientas</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Unidad
                                de Medida</label>
                            <select v-model="formEditarInsumo.unit_measure" required
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50">
                                <option value="Litros">Litros</option>
                                <option value="Kilos">Kilos</option>
                                <option value="Bolsas">Bolsas</option>
                                <option value="Tambores">Tambores</option>
                                <option value="Unidades">Unidades</option>
                                <option value="Metros">Metros</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1.5">Alerta
                            de Stock Bajo</label>
                        <input v-model="formEditarInsumo.alert_threshold" type="number" min="1" required
                            class="w-full bg-slate-50 dark:bg-black/20 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-indigo-500/50" />
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="cerrarModalEditar"
                            class="px-4 py-2 text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors bg-transparent border-none cursor-pointer">Cancelar</button>
                        <button type="submit" :disabled="guardando"
                            class="bg-indigo-600 hover:bg-indigo-500 text-white px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider font-['Barlow_Condensed'] transition-all border-none cursor-pointer disabled:opacity-50">
                            {{ guardando ? 'Guardando...' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>