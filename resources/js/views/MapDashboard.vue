<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { toast } from 'vue3-toastify'
import axios from 'axios'

const mapContainer = ref(null)
let map = null
let tileLayer = null

const searchRuta = ref('20')
const searchKm = ref('')
const marcadoresKm = []
let marcadorTemporal = null 

const mostrarModal = ref(false)
const guardando = ref(false)
const puntoFormulario = reactive({ lat: null, lng: null, tipo: '', observaciones: '' })

// Resultado de la búsqueda de progresivas
const resultadoBusqueda = ref(null)

const lightTile = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
const darkTile = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'

// Datos Estáticos de Rutas y Peajes (Coordenadas Reales)
const trazasVialesGeoJSON = {
    "type": "FeatureCollection",
    "features": [
        {
            "type": "Feature",
            "properties": { 
                "nombre": "Corredor Ruta 20 y Ruta 30", 
                "color": "#3b82f6", 
                "peajes": [
                    { nombre: "Peaje Cruz de Piedra", lat: -33.2541131, lng: -66.2270219 },
                    { nombre: "Peaje Perilago", lat: -33.2542911, lng: -66.2124143 },
                    { nombre: "Peaje Ruta 30", lat: -33.3026376, lng: -66.1093417 }
                ] 
            },
            // Geometría visual aproximada para pintar la línea en el mapa
            "geometry": { "type": "LineString", "coordinates": [[-66.3026, -33.3026], [-66.2270, -33.2541], [-66.2124, -33.2542]] }
        },
        {
            "type": "Feature",
            "properties": { 
                "nombre": "Autopista Los Puquios (Ruta Prov. 9)", 
                "color": "#10b981", 
                "peajes": [
                    { nombre: "Peaje Los Puquios", lat: -33.2714449, lng: -66.1965004 }
                ] 
            },
            "geometry": { "type": "LineString", "coordinates": [[-66.2500, -33.3000], [-66.1965, -33.2714], [-66.1000, -33.2000]] }
        },
        {
            "type": "Feature",
            "properties": { 
                "nombre": "Autopista Serranías Puntanas (Ruta Nac. 7)", 
                "color": "#f59e0b", 
                "peajes": [
                    { nombre: "Peaje La Cumbre", lat: -33.3590784, lng: -66.0670751 },
                    { nombre: "Peaje Desaguadero (Isla Este)", lat: -33.4117474, lng: -67.1234507 },
                    { nombre: "Peaje Desaguadero (Isla Oeste)", lat: -33.4128459, lng: -67.1147563 }
                ] 
            },
            "geometry": { "type": "LineString", "coordinates": [[-67.1234, -33.4117], [-66.0670, -33.3590], [-65.5000, -33.5000]] }
        }
    ]
}

// Tablas de referencias kilométricas reales
const referenciasViales = {
    '20': [
        { km: 4, desc: 'Puente Derivador' },
        { km: 5, desc: 'Rodeo del Alto' },
        { km: 7, desc: 'Club La Estrega' },
        { km: 9, desc: 'Ave Fenix' },
        { km: 12, desc: 'Rotonda Cruz de Piedra' },
        { km: 13, desc: 'Peaje Cruz de Piedra' },
        { km: 15, desc: 'Peaje Perilago' },
        { km: 17, desc: 'La Hoya' },
        { km: 19, desc: 'Rotonda La Virgen / El Volcan' },
        { km: 20, desc: 'Puente Los Puquios' },
        { km: 27, desc: 'Cruce Ruta Prov. 30 / Control policial' },
        { km: 33, desc: 'Arroyo y retorno Los Risma' },
        { km: 36.5, desc: 'Puente arroyo La Petra' },
        { km: 38, desc: 'Retorno La Petra' },
        { km: 44.7, desc: 'Ingreso presa Saladillo' },
        { km: 46, desc: 'Puente Rio 5to.' }
    ],
    '9': [
        { km: 8, desc: 'Rotonda Rotary Club San Luis' },
        { km: 10.5, desc: 'Rotonda Malvinas' },
        { km: 11.8, desc: 'Ingreso a San Roque' },
        { km: 13.5, desc: 'Ingreso a Cuchi Corral' },
        { km: 14.6, desc: 'Ingreso a perilago, Dique Cruz de Piedra' },
        { km: 15.1, desc: 'Peaje Los Puquios' },
        { km: 16.5, desc: 'Puente arroyo los p. y escultura del ciclista' },
        { km: 18.4, desc: 'Puente Los Puquios - ingreso al Volcán' },
        { km: 20, desc: 'Rotonda El Alpatacal' },
        { km: 23, desc: 'Retorno arroyo Las Mondinas' },
        { km: 25.8, desc: 'Rotonda El Zorrito / 4 Esquina' },
        { km: 30, desc: 'Retorno - ingreso a Polo Club' },
        { km: 32, desc: 'Arroyo Las Barranquitas / Ingreso El Durazno' },
        { km: 34, desc: 'Retorno - ingreso a Las Barranquitas' },
        { km: 36, desc: 'Ingreso a Barrio Altos Del Trapiche' },
        { km: 37, desc: 'Rotonda El Trapiche - Acceso a La Florida' },
        { km: 39.7, desc: 'Rotonda acceso a la Carolina - 7 Cajones' },
        { km: 41, desc: 'Limite El Trapiche - Rio Grande' },
        { km: 43.4, desc: 'Puente Rio Grande' },
        { km: 45, desc: 'Ingreso a dique Rio Grande - Nogoli' },
        { km: 50, desc: 'Ingreso Monumento a Pringles' },
        { km: 60, desc: 'Paso del Rey - La Arenilla' },
        { km: 66, desc: 'Valle de Pancanta' },
        { km: 79, desc: 'La Carolina' }
    ],
    '7': [
        { km: 715, desc: 'Liborio Luna - Inicio región centro' },
        { km: 731, desc: 'Puente de Fraga' },
        { km: 740, desc: 'Retorno Granville' },
        { km: 742, desc: 'Puente Los Italianos - Ser Beef SA' },
        { km: 751.65, desc: 'Acceso a Dique Paso de Las Carretas' },
        { km: 755.25, desc: 'Eleodoro Lobos' },
        { km: 756.6, desc: 'La Petra' },
        { km: 761, desc: 'Peaje La Cumbre' },
        { km: 765.75, desc: 'Destacamento Policial La Cumbre' },
        { km: 779.7, desc: 'Ingreso a Donovan' },
        { km: 781, desc: 'Puente los Donovan' },
        { km: 782, desc: 'Puente Hospital Ramón Carrillo' },
        { km: 783.5, desc: 'Terrazas del Portezuelo' },
        { km: 785, desc: 'Puente Ruta Prov. Nº 3 (zanjitas)' },
        { km: 788.9, desc: 'Puente Ruta Nac. Nº 146 (Beazley)' },
        { km: 792.5, desc: 'Puente Autódromo' },
        { km: 793, desc: 'Autódromo' },
        { km: 796, desc: 'Escuela Agraria' },
        { km: 798, desc: 'Centro de Disposición Final - Basural' },
        { km: 815.7, desc: 'Puente Balde - Salinas del Bebed.' },
        { km: 832, desc: 'Ingreso a Chosme' },
        { km: 844.2, desc: 'Ingreso Alto Pencoso' },
        { km: 856, desc: 'Ingreso a Jarilla' },
        { km: 862, desc: 'Peaje Desaguadero Isla Este' },
        { km: 863, desc: 'Peaje Desaguadero Isla Oeste' },
        { km: 866, desc: 'Limite Provincia de Mendoza - Arco Desaguadero' }
    ]
}

const renderizarTrazasEstaticas = () => {
    L.geoJSON(trazasVialesGeoJSON, {
        style: (feature) => ({ color: feature.properties.color, weight: 6, opacity: 0.6, lineCap: 'round', lineJoin: 'round' }),
        onEachFeature: (feature, layer) => {
            layer.bindPopup(`<strong class="font-[Barlow_Condensed] text-sm">${feature.properties.nombre}</strong><br>Traza bajo jurisdicción.`);
            if (feature.properties.peajes) {
                feature.properties.peajes.forEach(peaje => {
                    const iconPeaje = L.divIcon({
                        className: 'bg-transparent border-none',
                        html: `<div class="w-7 h-7 bg-white dark:bg-slate-800 rounded-lg border-2 border-[${feature.properties.color}] shadow-lg flex items-center justify-center relative -left-3.5 -top-3.5"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="${feature.properties.color}" stroke-width="2.5"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0110 0v4"></path></svg></div>`,
                        iconSize: [28, 28]
                    });
                    L.marker([peaje.lat, peaje.lng], { icon: iconPeaje }).addTo(map).bindPopup(`<strong class="font-[Barlow_Condensed] text-sm tracking-wide border-b border-slate-200 dark:border-slate-700 pb-1 mb-1 block">${peaje.nombre}</strong><div class="text-[11px] text-slate-500">Estación base operativa</div>`);
                });
            }
        }
    }).addTo(map);
}

const cargarPuntosGuardados = async () => {
    try {
        const respuesta = await axios.get('/api/incidents')
        const sucesos = respuesta.data

        sucesos.forEach(suceso => {
            if (suceso.dynamic_data && suceso.dynamic_data.latitud && suceso.dynamic_data.longitud) {
                const lat = suceso.dynamic_data.latitud
                const lng = suceso.dynamic_data.longitud
                const tipoFormateado = suceso.incident_type.replace('_', ' ').toUpperCase()
                const obs = suceso.dynamic_data.observaciones_mapa || 'Sin detalles extra'

                const iconoFijo = L.divIcon({
                    className: 'bg-transparent border-none',
                    html: `<div class="w-5 h-5 bg-emerald-500 rounded-full border-2 border-white shadow-md"></div>`,
                    iconSize: [20, 20]
                })

                L.marker([lat, lng], { icon: iconoFijo })
                 .addTo(map)
                 .bindPopup(`
                    <strong class="font-[Barlow_Condensed] text-emerald-600 dark:text-emerald-400 uppercase tracking-widest text-xs">${tipoFormateado}</strong>
                    <div class="text-[10px] text-slate-400 mb-1">${new Date(suceso.created_at).toLocaleDateString('es-AR')}</div>
                    <p class="text-xs mt-1 text-slate-600 dark:text-slate-300">${obs}</p>
                 `)
            }
        })
    } catch (error) {
        toast.error('No se pudieron cargar los sucesos históricos del mapa.')
    }
}

const initMap = () => {
    map = L.map(mapContainer.value, { zoomControl: false }).setView([-33.3017, -66.3378], 8)
    const isDark = document.documentElement.classList.contains('dark')
    
    tileLayer = L.tileLayer(isDark ? darkTile : lightTile, {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO', subdomains: 'abcd', maxZoom: 19
    }).addTo(map)

    L.control.zoom({ position: 'bottomright' }).addTo(map)

    renderizarTrazasEstaticas()
    cargarPuntosGuardados()

    map.on('click', (e) => {
        const { lat, lng } = e.latlng
        if (marcadorTemporal) { map.removeLayer(marcadorTemporal) }
        
        const customIcon = L.divIcon({
            className: 'bg-transparent border-none',
            html: `<div class="w-4 h-4 bg-red-500 rounded-full border-2 border-white shadow-[0_0_10px_rgba(239,68,68,0.8)] animate-pulse"></div>`,
            iconSize: [16, 16]
        })

        marcadorTemporal = L.marker([lat, lng], { icon: customIcon }).addTo(map)
        marcadorTemporal.bindPopup(`
            <div class="font-[Barlow_Condensed] text-sm tracking-wide font-bold border-b border-slate-200 dark:border-slate-700 pb-1 mb-2">Punto detectado</div>
            <div class="text-xs mb-3 text-slate-500 dark:text-slate-400">Lat: ${lat.toFixed(5)}<br>Lng: ${lng.toFixed(5)}</div>
            <div class="flex gap-2">
                <button id="btn-eliminar-punto" class="text-[11px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-white/10 text-slate-600 dark:text-slate-300 px-2 py-1.5 rounded w-full hover:bg-slate-200 cursor-pointer border-none transition-colors">Quitar</button>
                <button id="btn-registrar-punto" class="text-[11px] font-bold uppercase tracking-wider bg-amber-500 text-slate-900 px-2 py-1.5 rounded w-full hover:bg-amber-400 cursor-pointer border-none transition-colors">Clasificar</button>
            </div>
        `).openPopup()
    })

    map.on('popupopen', () => {
        const btnEliminar = document.getElementById('btn-eliminar-punto')
        const btnRegistrar = document.getElementById('btn-registrar-punto')
        if (btnEliminar) {
            btnEliminar.onclick = () => {
                if (marcadorTemporal) { map.removeLayer(marcadorTemporal); marcadorTemporal = null; }
            }
        }
        if (btnRegistrar) {
            btnRegistrar.onclick = () => {
                const { lat, lng } = marcadorTemporal.getLatLng()
                puntoFormulario.lat = lat; puntoFormulario.lng = lng; puntoFormulario.tipo = ''; puntoFormulario.observaciones = '';
                mostrarModal.value = true; map.closePopup()
            }
        }
    })

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                const currentIsDark = document.documentElement.classList.contains('dark')
                tileLayer.setUrl(currentIsDark ? darkTile : lightTile)
            }
        })
    })
    observer.observe(document.documentElement, { attributes: true })
}

const buscarKilometro = () => {
    if (!searchRuta.value || searchKm.value === '') {
        toast.warning('Debe ingresar un kilómetro válido.')
        return
    }

    const puntos = referenciasViales[searchRuta.value]
    const km = parseFloat(searchKm.value)

    const exacto = puntos.find(p => p.km === km)
    if (exacto) {
        resultadoBusqueda.value = { tipo: 'exacto', punto: exacto }
        toast.info('Punto kilométrico exacto encontrado.')
        return
    }

    // Algoritmo para encontrar las dos referencias más cercanas
    const puntosOrdenados = [...puntos].sort((a, b) => Math.abs(a.km - km) - Math.abs(b.km - km))
    const cercano1 = puntosOrdenados[0]
    const cercano2 = puntosOrdenados[1]
    const limites = [cercano1, cercano2].sort((a, b) => a.km - b.km)

    resultadoBusqueda.value = { 
        tipo: 'aproximado', 
        anterior: limites[0], 
        posterior: limites[1]
    }
}

const limpiarBusqueda = () => {
    searchKm.value = ''
    resultadoBusqueda.value = null
}

const confirmarPunto = async () => {
    if (!puntoFormulario.tipo) return;
    guardando.value = true;
    try {
        const payload = {
            toll_id: 1, 
            incident_type: puntoFormulario.tipo,
            dynamic_data: { latitud: puntoFormulario.lat, longitud: puntoFormulario.lng, observaciones_mapa: puntoFormulario.observaciones }
        }
        await axios.post('/api/incidents', payload)
        toast.success('Punto georreferenciado guardado en el sistema.')
        
        const iconoFijo = L.divIcon({
            className: 'bg-transparent border-none',
            html: `<div class="w-5 h-5 bg-emerald-500 rounded-full border-2 border-white shadow-md"></div>`,
            iconSize: [20, 20]
        })
        marcadorTemporal.setIcon(iconoFijo)
        const tipoFormateado = puntoFormulario.tipo.replace('_', ' ').toUpperCase()
        
        const fechaActual = new Date().toLocaleDateString('es-AR')
        
        marcadorTemporal.bindPopup(`
            <strong class="font-[Barlow_Condensed] text-emerald-600 dark:text-emerald-400 uppercase tracking-widest text-xs">${tipoFormateado}</strong>
            <div class="text-[10px] text-slate-400 mb-1">${fechaActual}</div>
            <p class="text-xs mt-1 text-slate-600 dark:text-slate-300">${puntoFormulario.observaciones || 'Sin detalles extra'}</p>
        `)
        marcadorTemporal = null; mostrarModal.value = false;
    } catch (error) {
        toast.error('Error de conexión al intentar guardar el punto.')
    } finally {
        guardando.value = false
    }
}

const cerrarModal = () => { mostrarModal.value = false }
onMounted(() => { initMap() })
onBeforeUnmount(() => { if (map) { map.remove() } })
</script>

<template>
    <div class="relative w-full h-[calc(100vh-56px)] flex">
        
        <div class="w-80 bg-white dark:bg-[#0d1b2a] border-r border-slate-200 dark:border-white/10 z-[400] flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.05)] transition-colors">
            <div class="p-5 border-b border-slate-100 dark:border-white/5">
                <h3 class="font-['Barlow_Condensed'] text-[22px] font-extrabold text-slate-900 dark:text-slate-100 m-0 leading-none">Herramientas SIG</h3>
                <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1">Sistema de Información Geográfica</p>
            </div>

            <div class="p-5 flex-1 overflow-y-auto">
                <div class="mb-6">
                    <h4 class="font-['Barlow_Condensed'] text-[13px] font-bold tracking-widest uppercase text-amber-600 dark:text-amber-500 mb-4 border-b border-amber-500/20 pb-2">Localizador por Progresiva</h4>
                    <form @submit.prevent="buscarKilometro" class="space-y-4">
                        <div>
                            <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Corredor Vial</label>
                            <select v-model="searchRuta" class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50">
                                <option value="20">Ruta Nacional 20 / Autopista 20</option>
                                <option value="9">Ruta Prov. 9 (Autopista Puquios)</option>
                                <option value="7">Ruta Nac. 7 (Autopista Serranías)</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Kilómetro (Aprox)</label>
                            <input v-model="searchKm" type="number" step="0.1" placeholder="Ej: 781.5" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50" />
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="limpiarBusqueda" class="w-1/3 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-white/10 font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-2 py-2.5 rounded-lg border border-slate-200 dark:border-white/10 cursor-pointer transition-all">Limpiar</button>
                            <button type="submit" class="w-2/3 bg-slate-800 dark:bg-white/10 text-white hover:bg-slate-700 dark:hover:bg-white/20 font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-4 py-2.5 rounded-lg border-none cursor-pointer transition-all">Localizar Punto</button>
                        </div>
                    </form>
                </div>

                <div v-if="resultadoBusqueda" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-xl p-4 shadow-sm transition-colors mb-6">
                    <h4 class="font-['Barlow_Condensed'] text-[13px] font-bold text-blue-800 dark:text-blue-400 uppercase tracking-widest mb-3 border-b border-blue-200 dark:border-blue-800/50 pb-2">Resultado Espacial</h4>
                    
                    <div v-if="resultadoBusqueda.tipo === 'exacto'" class="text-sm">
                        <div class="text-slate-600 dark:text-slate-300 mb-1 text-[11px] uppercase tracking-widest">Coincidencia en Km {{ resultadoBusqueda.punto.km }}:</div>
                        <strong class="text-blue-900 dark:text-blue-300 block text-sm font-['DM_Sans']">{{ resultadoBusqueda.punto.desc }}</strong>
                    </div>

                    <div v-else class="text-sm">
                        <div class="text-slate-600 dark:text-slate-300 mb-3 leading-relaxed text-xs">
                            El Km {{ searchKm }} se encuentra en el tramo comprendido entre:
                        </div>
                        <ul class="list-none p-0 m-0 space-y-3 relative">
                            <li class="flex items-start gap-2 relative z-10">
                                <span class="bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm">Km {{ resultadoBusqueda.anterior.km }}</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ resultadoBusqueda.anterior.desc }}</span>
                            </li>
                            <li class="pl-4 border-l-2 border-dashed border-blue-300 dark:border-blue-700/50 py-1.5 text-[10px] text-blue-600 dark:text-blue-400 font-bold tracking-widest uppercase ml-3">Punto Reportado</li>
                            <li class="flex items-start gap-2 relative z-10">
                                <span class="bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm">Km {{ resultadoBusqueda.posterior.km }}</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{ resultadoBusqueda.posterior.desc }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h4 class="font-['Barlow_Condensed'] text-[13px] font-bold tracking-widest uppercase text-amber-600 dark:text-amber-500 mb-4 border-b border-amber-500/20 pb-2">Control Georreferenciado</h4>
                    <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-500/20 rounded-lg p-3 text-xs text-amber-800 dark:text-amber-300 leading-relaxed mb-3">
                        <strong>Trazas Activas:</strong> El mapa despliega la jurisdicción operativa de las estaciones base.
                    </div>
                    <div class="bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg p-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Haz clic en el mapa para inicializar un suceso. Selecciona "Quitar" para borrar la coordenada temporal.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 relative">
            <div ref="mapContainer" class="w-full h-full z-[100]"></div>
        </div>
        
        <div v-if="mostrarModal" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity">
            <div class="w-full max-w-sm bg-white dark:bg-[#0d1b2a]  border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden relative">
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-1"></div>
                <div class="px-5 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <h3 class="font-['Barlow_Condensed'] text-[18px] font-bold text-slate-900 dark:text-slate-100 tracking-wide m-0">Clasificar Coordenada</h3>
                </div>

                <form @submit.prevent="confirmarPunto" class="p-5">
                    <div class="mb-4">
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Tipo de Evento</label>
                        <select v-model="puntoFormulario.tipo" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors focus:border-amber-500/50">
                            <option value="" disabled>— Seleccionar clasificación —</option>
                            <option value="accidente_vial">Accidente Vial</option>
                            <option value="animal_ruta">Animal en Ruta</option>
                            <option value="corte_ruta">Corte de Ruta / Manifestación</option>
                            <option value="falla_infraestructura">Falla en Infraestructura</option>
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Observaciones adicionales</label>
                        <textarea v-model="puntoFormulario.observaciones" rows="3" placeholder="Detalles de la ubicación o gravedad..." class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors focus:border-amber-500/50 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" @click="cerrarModal" class="px-4 py-2 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 border-none bg-transparent cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">Cancelar</button>
                        <button type="submit" :disabled="guardando" class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-5 py-2 rounded-lg border-none cursor-pointer inline-flex items-center transition-all hover:bg-amber-400 disabled:opacity-50">
                            {{ guardando ? 'Registrando...' : 'Confirmar Punto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>