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

const lightTile = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
const darkTile = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'

// Datos Estáticos de Rutas
const trazasVialesGeoJSON = {
    "type": "FeatureCollection",
    "features": [
        {
            "type": "Feature",
            "properties": { "nombre": "Corredor Ruta 20", "color": "#3b82f6", "peajes": [{ nombre: "Peaje Cruz de Piedra", lat: -33.2500, lng: -66.2300 }] },
            "geometry": { "type": "LineString", "coordinates": [[-66.3378, -33.3017], [-66.2800, -33.2800], [-66.2300, -33.2500], [-66.1800, -33.2000]] }
        },
        {
            "type": "Feature",
            "properties": { "nombre": "Corredor Ruta 9", "color": "#10b981", "peajes": [{ nombre: "Peaje La Florida", lat: -33.1200, lng: -66.0500 }] },
            "geometry": { "type": "LineString", "coordinates": [[-66.2300, -33.2500], [-66.1500, -33.1800], [-66.0500, -33.1200], [-65.9500, -33.0500]] }
        },
        {
            "type": "Feature",
            "properties": { "nombre": "Autopista Serranías Puntanas", "color": "#f59e0b", "peajes": [{ nombre: "Peaje Desaguadero", lat: -33.3980, lng: -68.2750 }, { nombre: "Peaje Justo Daract", lat: -33.8600, lng: -65.1800 }] },
            "geometry": { "type": "LineString", "coordinates": [[-68.2750, -33.3980], [-66.3378, -33.3017], [-65.1800, -33.8600]] }
        }
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

// NUEVA FUNCIÓN: Descargar y renderizar los puntos de la base de datos
const cargarPuntosGuardados = async () => {
    try {
        const respuesta = await axios.get('/api/incidents')
        const sucesos = respuesta.data

        sucesos.forEach(suceso => {
            // Filtramos solo aquellos sucesos que tengan latitud y longitud guardadas
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

                // Dibujamos el marcador real desde la base de datos
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
        console.error("Error al cargar puntos históricos:", error)
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

    // Llamamos a la API después de dibujar el mapa base
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
    if (!searchKm.value) return
    marcadoresKm.forEach(m => map.removeLayer(m))
    const mockLat = -33.3017 + (searchKm.value * 0.0005)
    const mockLng = -66.3378 + (searchKm.value * 0.0005)
    const dotKm = L.circleMarker([mockLat, mockLng], {
        radius: 7, fillColor: "#ef4444", color: "#ffffff", weight: 2, opacity: 1, fillOpacity: 0.9
    }).addTo(map)
    dotKm.bindPopup(`<strong class="font-[Barlow_Condensed]">Ruta ${searchRuta.value} - Km ${searchKm.value}</strong><br>Punto aproximado calculado.`).openPopup()
    map.flyTo([mockLat, mockLng], 13, { duration: 1.5 })
    marcadoresKm.push(dotKm)
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
        
        // Agregamos la fecha actual para que coincida con el estilo de los cargados desde la BD
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
                <div class="mb-8">
                    <h4 class="font-['Barlow_Condensed'] text-[13px] font-bold tracking-widest uppercase text-amber-600 dark:text-amber-500 mb-4 border-b border-amber-500/20 pb-2">Localizador por Progresiva</h4>
                    <form @submit.prevent="buscarKilometro" class="space-y-4">
                        <div>
                            <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Corredor Vial</label>
                            <select v-model="searchRuta" class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50">
                                <option value="20">Ruta Nacional 20</option>
                                <option value="7">Ruta Nacional 7</option>
                                <option value="9">Ruta Provincial 9</option>
                            </select>
                        </div>
                        <div>
                            <label class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Kilómetro (Aprox)</label>
                            <input v-model="searchKm" type="number" step="0.1" placeholder="Ej: 145.5" required class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50" />
                        </div>
                        <button type="submit" class="w-full bg-slate-800 dark:bg-white/10 text-white hover:bg-slate-700 dark:hover:bg-white/20 font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-4 py-2.5 rounded-lg border-none cursor-pointer transition-all">
                            Localizar en mapa
                        </button>
                    </form>
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
            <div class="w-full max-w-sm bg-white dark:bg-[#0d1b2a] rounded-2xl border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden relative">
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