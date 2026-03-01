<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, computed, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { toast } from 'vue3-toastify'
import axios from 'axios'

const mapContainer = ref(null)
let map = null
let tileLayer = null

const searchRuta = ref('20')
const searchKm = ref('')
let marcadorTemporal = null

const marcadoresPeajesGenerados = {}
const marcadoresBusqueda = []

const capasDisponibles = {
    estandar: {
        nombre: 'Base adaptativa (carto)',
        url: null
    },
    satelite: {
        nombre: 'Satélite',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
    },
    calles: {
        nombre: 'Calles',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}'
    },
    topografico: {
        nombre: 'Relieve topográfico',

        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}'
    },
    gris: {
        nombre: 'Urbano claro',
        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}'
    }
};


const tipoMapa = ref('estandar'); // 'estandar' o 'satelite' y mas
const menuMapasAbierto = ref(false);
const seleccionarCapa = (llave) => {
    tipoMapa.value = llave;
    menuMapasAbierto.value = false;
    actualizarCapaMapa();
};

const actualizarCapaMapa = () => {
    let limiteZoom = 19;

    if (tipoMapa.value === 'estandar') {
        const isDark = document.documentElement.classList.contains('dark');
        tileLayer.setUrl(isDark ? darkTile : lightTile);
    } else {
        tileLayer.setUrl(capasDisponibles[tipoMapa.value].url);

        if (tipoMapa.value === 'satelite') {
            limiteZoom = 17;
        }
        else if (tipoMapa.value === 'gris') {
            limiteZoom = 16;
        }
    }

    map.setMaxZoom(limiteZoom);
    tileLayer.options.maxZoom = limiteZoom;

    if (map.getZoom() > limiteZoom) {
        map.setZoom(limiteZoom);
    }
};

const alternarTipoMapa = () => {
    tipoMapa.value = tipoMapa.value === 'estandar' ? 'satelite' : 'estandar';
    actualizarCapaMapa();
};

const mostrarModal = ref(false)
const guardando = ref(false)
const puntoFormulario = reactive({ lat: null, lng: null, tipo: '', observaciones: '' })

const resultadoBusqueda = ref(null)

const lightTile = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
const darkTile = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'


// Algoritmo de Chaikin
const suavizarCoordenadas = (coordenadasCrudas, iteraciones = 3) => {
    if (coordenadasCrudas.length <= 2) return coordenadasCrudas;

    let lineaSuavizada = [...coordenadasCrudas];

    for (let i = 0; i < iteraciones; i++) {
        const nuevaLinea = [];


        nuevaLinea.push(lineaSuavizada[0]);

        for (let j = 0; j < lineaSuavizada.length - 1; j++) {
            const p0 = lineaSuavizada[j];
            const p1 = lineaSuavizada[j + 1];


            const q = [
                0.75 * p0[0] + 0.25 * p1[0], // long
                0.75 * p0[1] + 0.25 * p1[1]  // lat
            ];

            const r = [
                0.25 * p0[0] + 0.75 * p1[0],
                0.25 * p0[1] + 0.75 * p1[1]
            ];

            nuevaLinea.push(q);
            nuevaLinea.push(r);
        }


        nuevaLinea.push(lineaSuavizada[lineaSuavizada.length - 1]);


        lineaSuavizada = nuevaLinea;
    }

    return lineaSuavizada;
}


const sanLuisBounds = L.latLngBounds(
    [-36.0, -67.5], // Esquina sur-oeste
    [-31.8, -64.5]  // Esquina norte-este
);

const dibujarLimitesProvinciales = async () => {
    try {

        const respuesta = await axios.get('/data/san_luis_limites.geojson');
        const limitesGeoJSON = respuesta.data;

        L.geoJSON(limitesGeoJSON, {
            style: {
                color: "#f59e0b",
                weight: 2,
                opacity: 0.5,
                fillColor: "#f59e0b",
                fillOpacity: 0.05,
                dashArray: '5, 10'
            },
            interactive: false
        }).addTo(map);

    } catch (error) {
        console.warn('No se pudo cargar el archivo de límites de la provincia:', error);
    }
}


const trazasVialesGeoJSON = {
    "type": "FeatureCollection",
    "features": [
        {
            "type": "Feature",
            "properties": {
                "id": "20",
                "nombre": "Autopista Prov. 20",
                "color": "#3b82f6",
                "peajes": [
                    { nombre: "Peaje Cruz de Piedra", lat: -33.2541131, lng: -66.2270219, imagen: "/img/peajes/cruz-piedra.jpeg" },
                    { nombre: "Peaje Perilago", lat: -33.2542911, lng: -66.2124143, imagen: "/img/peajes/perilago.jpeg" }
                ]
            },
            "geometry": {
                "type": "LineString",
                "coordinates": [
                    [-66.3007985, -33.2941849], // Km 4 (Derivador)
                    [-66.2884821, -33.2944079], // Rodeo del Alto
                    [-66.2870306, -33.2946200],
                    [-66.2799964, -33.2969535],
                    [-66.2772227, -33.2972261],
                    [-66.27677, -33.29721],
                    [-66.27268, -33.29616],

                    [-66.27062, -33.29552],
                    [-66.2685864, -33.2945745], // Km 7 (Estrega)
                    [-66.2637178, -33.2920447],
                    [-66.2545769, -33.2878294],
                    [-66.2497884, -33.2847671], // Km 9 (Ave Fenix)
                    [-66.2425691, -33.2804371],
                    [-66.2365943, -33.2768961],
                    [-66.2355086, -33.2761918],
                    [-66.2308586, -33.2648545],
                    [-66.2297909, -33.2638394], // Km 12 (Rotonda C.P.)
                    [-66.2272913, -33.2587553],
                    [-66.22708, -33.25423],
                    [-66.22684, -33.25293],
                    [-66.2260722, -33.2519519], // Peaje Cruz de Piedra
                    [-66.22341, -33.25135],
                    [-66.2211567, -33.2516617],
                    [-66.2175249, -33.2534633],
                    [-66.21576, -33.25400],
                    [-66.2147619, -33.2547688],
                    [-66.21425, -33.25499],
                    [-66.21308, -33.25452],
                    [-66.2124120, -33.2542878], // Km 15 (Peaje Perilago)
                    [-66.21232, -33.25387],
                    [-66.21237, -33.25334],
                    [-66.21283, -33.25278],
                    [-66.21322, -33.25153],
                    [-66.2130937, -33.2513059],
                    [-66.21229, -33.25090],
                    [-66.2085661, -33.2518942],
                    [-66.20208, -33.25400],
                    [-66.2012797, -33.2542032],
                    [-66.19923, -33.25376],
                    [-66.19671, -33.25370],
                    [-66.1955336, -33.2533267],
                    [-66.19418, -33.25155],
                    [-66.1935003, -33.2512997],
                    [-66.19263, -33.25150],
                    [-66.1915335, -33.2509587], // Km 17 (La Hoya)
                    [-66.1899151, -33.2512703],
                    [-66.1878093, -33.2529043],
                    [-66.1821855, -33.2510010],
                    [-66.1777938, -33.2515680],
                    [-66.1751547, -33.2530479], // Km 19 (Rotonda Virgen)
                    [-66.1758317, -33.2560138],
                    [-66.1748790, -33.2588567],
                    [-66.17225, -33.26137],

                    [-66.1698524, -33.2624330], // Km 20 (Puente Puquios)
                    [-66.1683674, -33.2627598],
                    [-66.16550, -33.26404],
                    [-66.1640798, -33.2657315],
                    [-66.1623082, -33.2707589],
                    [-66.16144, -33.27335],
                    [-66.1587576, -33.2752965],
                    [-66.15613, -33.27542],

                    [-66.1410199, -33.2721132],
                    [-66.1307046, -33.2710583],
                    [-66.1078299, -33.2698150], // Km 27 (Cruce Rp30)
                    [-66.0744213, -33.2669278],
                    [-66.0675449, -33.2676800],
                    [-66.0452015, -33.2672577],
                    [-66.0431169, -33.2664050], // Km 33 (Arroyo y Retorno)
                    [-66.0388586, -33.2644799],
                    [-66.0071684, -33.2574904], // Km 36.5 (Puente Arroyo)
                    [-66.0020352, -33.2561069],
                    [-65.9964764, -33.2524878], // Km 38 (Retorno La Petra)
                    [-65.9916789, -33.2498757],
                    [-65.9909967, -33.2494978],
                    [-65.9254676, -33.2423537], // Km 44.7 (Presa Saladillo)
                    [-65.9205906, -33.2427678],
                    [-65.9044023, -33.2403134],
                    [-65.8970534, -33.2407010],
                    [-65.8955251, -33.2415744]  // Km 46 (Puente Rio 5to)
                ]
            }
        },
        {
            "type": "Feature",
            "properties": {
                "id": "30",
                "nombre": "Ruta Prov. 30",
                "color": "#8b5cf6",
                "peajes": [
                    { nombre: "Peaje Ruta 30", lat: -33.3026376, lng: -66.1093417, imagen: "/img/peajes/ruta-30.jpeg" },
                ]
            },
            "geometry": {
                "type": "LineString",
                "coordinates": [
                    [-66.1080016, -33.2697162], // Empalme R20
                    [-66.10278, -33.27679],
                    [-66.1026610, -33.2769976],
                    [-66.10267, -33.27726],
                    [-66.10360, -33.28127],
                    [-66.1093506, -33.3026396], // Km 13.8 (Peaje R30)
                    [-66.1144070, -33.3205677], // Km 16
                    [-66.1189336, -33.3371463], // Km 18
                    [-66.1195209, -33.3392576],
                    [-66.1199308, -33.3439209]  // Empalme R7
                ]
            }
        },
        {
            "type": "Feature",
            "properties": {
                "nombre": "Autopista Los Puquios (Ruta Prov. 9)",
                "color": "#10b981",
                "peajes": [
                    { nombre: "Peaje Los Puquios", lat: -33.2714449, lng: -66.1965004, imagen: "/img/peajes/los-puquios.jpeg" },
                ]
            },

            "geometry": {
                "type": "LineString",
                "coordinates": [
                    [-66.2596378, -33.3039744], [-66.2575399, -33.3028488], [-66.2517099, -33.2994305], [-66.2474000, -33.2936754], [-66.2447753, -33.2917852], [-66.2429016, -33.2899865], [-66.2419404, -33.2893605], [-66.2362907, -33.2839886], [-66.2337810, -33.2828107], [-66.2251270, -33.2772967], [-66.2229570, -33.2759428], [-66.2222975, -33.2751606], [-66.2207873, -33.2744490], [-66.2183962, -33.2741117], [-66.2168694, -33.2746321], [-66.2156328, -33.2752692], [-66.2130840, -33.2750318], [-66.2119968, -33.2735457], [-66.2115001, -33.2722249], [-66.2101791, -33.2709984], [-66.2066197, -33.2698770], [-66.2036138, -33.2711648], [-66.2012375, -33.2714657], [-66.2006259, -33.2709067], [-66.1995541, -33.2704274], [-66.1978459, -33.2706217], [-66.1964652, -33.2714234], [-66.1940288, -33.2727363], [-66.1921863, -33.2725770], [-66.1887023, -33.2732907], [-66.1842140, -33.2741965], [-66.1808986, -33.2751313], [-66.1795281, -33.2740136], [-66.1791507, -33.2711448], [-66.1760090, -33.2687124], [-66.1723870, -33.2635906], [-66.1698745, -33.2624352], [-66.1645067, -33.2594697], [-66.1621810, -33.2563572], [-66.1619721, -33.2499929], [-66.1608910, -33.2477104], [-66.1459108, -33.2300955], [-66.14388, -33.22636], [-66.13257, -33.20589], [-66.1244046, -33.1983400], [-66.11478, -33.18715], [-66.1051560, -33.1787679], [-66.0887422, -33.1661177], [-66.0812551, -33.1619531], [-66.0795276, -33.1600436], [-66.0770048, -33.1565807], [-66.0715501, -33.1496383], [-66.0642264, -33.1410146], [-66.06260, -33.13690], [-66.0624963, -33.1325323], [-66.0609568, -33.1248799], [-66.0610843, -33.1219898], [-66.0611008, -33.1213422], [-66.0604079, -33.1201798], [-66.0605107, -33.1196585], [-66.0613285, -33.1178613], [-66.0614705, -33.1171848], [-66.06262, -33.11649], [-66.06324, -33.11569], [-66.0635141, -33.1149876], [-66.06373, -33.11379], [-66.06364, -33.11248], [-66.0646486, -33.1091054], [-66.0643479, -33.1074080], [-66.0631890, -33.1061113], [-66.0605432, -33.1038122], [-66.0585147, -33.1028936], [-66.0601888, -33.0998769], [-66.0622379, -33.0979415], [-66.0626355, -33.0953862], [-66.0645675, -33.0923955], [-66.06580, -33.09079], [-66.06631, -33.08990], [-66.0664104, -33.0883178], [-66.0680077, -33.0855129], [-66.06843, -33.08447], [-66.0691788, -33.0786977], [-66.06918, -33.07712], [-66.0680049, -33.0749733], [-66.0674971, -33.0710576], [-66.0678734, -33.0670907], [-66.06971, -33.06371], [-66.0699767, -33.0627368], [-66.0684753, -33.0581652], [-66.0690266, -33.0546500], [-66.07012, -33.05265], [-66.07096, -33.05195], [-66.07095, -33.05115], [-66.07041, -33.05036], [-66.0697089, -33.0442515], [-66.0706904, -33.0422382], [-66.0702730, -33.0406413], [-66.06916, -33.03990], [-66.0681261, -33.0386863], [-66.06778, -33.03855], [-66.0674159, -33.0388527], [-66.06712, -33.03965], [-66.06720, -33.04045], [-66.0669757, -33.0406402], [-66.06618, -33.04069], [-66.0658968, -33.0402920], [-66.0662384, -33.0389074], [-66.06770, -33.03715], [-66.06769, -33.03576], [-66.0678967, -33.0352404], [-66.06958, -33.03422], [-66.07070, -33.03200], [-66.0707888, -33.0311968], [-66.07084, -33.02907], [-66.07119, -33.02796], [-66.0704734, -33.0264113], [-66.0698387, -33.0234716], [-66.06967, -33.02103], [-66.0688351, -33.0189983], [-66.06871, -33.01744], [-66.0679975, -33.0168873], [-66.06628, -33.01652], [-66.0657242, -33.0158962], [-66.06550, -33.01379], [-66.0666696, -33.0111517], [-66.06734, -33.01010], [-66.0663599, -33.0078240], [-66.06338, -33.00742], [-66.0620328, -33.0065199], [-66.06185, -33.00497], [-66.06077, -33.00293], [-66.0606878, -33.0000713], [-66.0639746, -32.9943818], [-66.07285, -32.98898], [-66.0734201, -32.9885078], [-66.07353, -32.98745], [-66.0724823, -32.9836892], [-66.0731135, -32.9834894], [-66.0742801, -32.9847916], [-66.0751414, -32.9840707], [-66.0732224, -32.9814666], [-66.0720435, -32.9756389], [-66.0751253, -32.9712397], [-66.0747327, -32.9706417], [-66.0713072, -32.9715782], [-66.0725387, -32.9679049], [-66.0732252, -32.9662906], [-66.0727819, -32.9609896], [-66.0711841, -32.9577465], [-66.0710016, -32.9565484], [-66.0722253, -32.9514609], [-66.0759940, -32.9483868], [-66.0749848, -32.9452098], [-66.0774082, -32.9424697], [-66.0789134, -32.9399731], [-66.0818890, -32.9397215], [-66.0835618, -32.9360928], [-66.0865393, -32.9354632], [-66.0858848, -32.9325892], [-66.0844561, -32.9284567], [-66.0865427, -32.9253088], [-66.0863847, -32.9230552], [-66.0875796, -32.9205065], [-66.0851702, -32.9127853], [-66.0872063, -32.9113057], [-66.0951406, -32.9089215], [-66.1007895, -32.9059158], [-66.1034052, -32.9032476], [-66.1131051, -32.8961549], [-66.1132357, -32.8891798], [-66.1134376, -32.8821211], [-66.1148482, -32.8737703], [-66.1141178, -32.8673710], [-66.1119368, -32.8597243], [-66.1099224, -32.8544387], [-66.1099802, -32.8523368], [-66.1067542, -32.8515273], [-66.1057992, -32.8461203], [-66.1063789, -32.8396561], [-66.0991251, -32.8271710], [-66.0991765, -32.8249498], [-66.0992505, -32.8232993], [-66.0959797, -32.8194888], [-66.0957608, -32.8185007]
                ]
            }
        },
        {
            "type": "Feature",
            "properties": {
                "id": "7",
                "nombre": "Autopista Serranías Puntanas (Ruta Nac. 7)",
                "color": "#f59e0b",
                "peajes": [
                    { nombre: "Peaje La Cumbre", lat: -33.3590784, lng: -66.0670751, imagen: "/img/peajes/la-cumbre.jpeg" },
                    { nombre: "Peaje Desaguadero Isla Oeste", lat: -33.4117474, lng: -67.1234507, imagen: "/img/peajes/desaguadero-o.jpeg" },
                    { nombre: "Peaje Desaguadero Isla Este", lat: -33.4128459, lng: -67.1147563, imagen: "/img/peajes/desaguadero-e.jpeg" },
                ]
            },

            "geometry": {
                "type": "LineString",
                "coordinates": [
                    [-65.6302887, -33.5853571], [-65.6341452, -33.5833255], [-65.6393134, -33.5805195], [-65.7934509, -33.4976835], [-65.8763957, -33.4549006], [-65.8857256, -33.4502931], [-65.8945380, -33.4459664], [-65.9407026, -33.4219924], [-65.9588587, -33.4123603], [-65.9825863, -33.3989463], [-65.9849649, -33.3975869], [-65.9942073, -33.3923420], [-66.0120921, -33.3820531], [-66.0132379, -33.3815848], [-66.0255856, -33.3743804], [-66.0390462, -33.3668776], [-66.0670751, -33.3590784], [-66.0999390, -33.3497519], [-66.1199303, -33.3441651], [-66.1266905, -33.3420922], [-66.2525518, -33.3066128], [-66.2602336, -33.3044338], [-66.2663436, -33.3033534], [-66.2875658, -33.3053900], [-66.2913012, -33.3058348], [-66.2981583, -33.3072593], [-66.3014272, -33.3117297], [-66.3017314, -33.3196764], [-66.3015280, -33.3225595], [-66.3026522, -33.3305993], [-66.3193233, -33.3367288], [-66.3358926, -33.3420111], [-66.3420783, -33.3440042], [-66.3522412, -33.3472516], [-66.3660286, -33.3508666], [-66.3705350, -33.3487985], [-66.3732413, -33.3451749], [-66.3744212, -33.3431870], [-66.3799839, -33.3336621], [-66.3821916, -33.3308561], [-66.3848813, -33.3290849], [-66.3880909, -33.3282726], [-66.4214244, -33.3345070], [-66.4423880, -33.3386702], [-66.6278656, -33.3753655], [-66.8017819, -33.4095785], [-66.9266112, -33.4373185], [-66.9366819, -33.4391916], [-67.0281041, -33.4255681], [-67.1147563, -33.4128459], [-67.1234507, -33.4117474], [-67.1295566, -33.4107981], [-67.1444324, -33.4071170], [-67.1489651, -33.4052565], [-67.1515111, -33.4042217]
                ]
            }
        }
    ]
}

const referenciasViales = {
    '20': [
        {
            "km": 4,
            "desc": "Puente Derivador",
            "lat": -33.2941849,
            "lng": -66.3007985
        },
        {
            "km": 4.87,
            "desc": "Traza principal",
            "lat": -33.29462,
            "lng": -66.2870306
        },
        {
            "km": 5,
            "desc": "Rodeo del Alto",
            "lat": -33.2944079,
            "lng": -66.2884821
        },
        {
            "km": 5.35,
            "desc": "Traza principal",
            "lat": -33.2969535,
            "lng": -66.2799964
        },
        {
            "km": 5.53,
            "desc": "Traza principal",
            "lat": -33.2972261,
            "lng": -66.2772227
        },
        {
            "km": 5.56,
            "desc": "Traza principal",
            "lat": -33.29721,
            "lng": -66.27677
        },
        {
            "km": 5.83,
            "desc": "Traza principal",
            "lat": -33.29616,
            "lng": -66.27268
        },
        {
            "km": 5.97,
            "desc": "Traza principal",
            "lat": -33.29552,
            "lng": -66.27062
        },
        {
            "km": 6.48,
            "desc": "Traza principal",
            "lat": -33.2920447,
            "lng": -66.2637178
        },
        {
            "km": 7,
            "desc": "Club La Estrega",
            "lat": -33.2945745,
            "lng": -66.2685864
        },
        {
            "km": 7.14,
            "desc": "Traza principal",
            "lat": -33.2878294,
            "lng": -66.2545769
        },
        {
            "km": 8.08,
            "desc": "Traza principal",
            "lat": -33.2804371,
            "lng": -66.2425691
        },
        {
            "km": 8.55,
            "desc": "Traza principal",
            "lat": -33.2768961,
            "lng": -66.2365943
        },
        {
            "km": 8.63,
            "desc": "Traza principal",
            "lat": -33.2761918,
            "lng": -66.2355086
        },
        {
            "km": 9,
            "desc": "Ave Fenix",
            "lat": -33.2847671,
            "lng": -66.2497884
        },
        {
            "km": 9.54,
            "desc": "Traza principal",
            "lat": -33.2648545,
            "lng": -66.2308586
        },
        {
            "km": 10.06,
            "desc": "Traza principal",
            "lat": -33.2587553,
            "lng": -66.2272913
        },
        {
            "km": 10.4,
            "desc": "Traza principal",
            "lat": -33.25423,
            "lng": -66.22708
        },
        {
            "km": 10.5,
            "desc": "Traza principal",
            "lat": -33.25293,
            "lng": -66.22684
        },
        {
            "km": 10.77,
            "desc": "Traza principal",
            "lat": -33.25135,
            "lng": -66.22341
        },
        {
            "km": 10.91,
            "desc": "Traza principal",
            "lat": -33.2516617,
            "lng": -66.2211567
        },
        {
            "km": 11.18,
            "desc": "Traza principal",
            "lat": -33.2534633,
            "lng": -66.2175249
        },
        {
            "km": 11.3,
            "desc": "Traza principal",
            "lat": -33.254,
            "lng": -66.21576
        },
        {
            "km": 11.38,
            "desc": "Traza principal",
            "lat": -33.2547688,
            "lng": -66.2147619
        },
        {
            "km": 11.42,
            "desc": "Traza principal",
            "lat": -33.25499,
            "lng": -66.21425
        },
        {
            "km": 11.5,
            "desc": "Traza principal",
            "lat": -33.25452,
            "lng": -66.21308
        },
        {
            "km": 11.58,
            "desc": "Traza principal",
            "lat": -33.25387,
            "lng": -66.21232
        },
        {
            "km": 11.62,
            "desc": "Traza principal",
            "lat": -33.25334,
            "lng": -66.21237
        },
        {
            "km": 11.67,
            "desc": "Traza principal",
            "lat": -33.25278,
            "lng": -66.21283
        },
        {
            "km": 11.77,
            "desc": "Traza principal",
            "lat": -33.25153,
            "lng": -66.21322
        },
        {
            "km": 11.79,
            "desc": "Traza principal",
            "lat": -33.2513059,
            "lng": -66.2130937
        },
        {
            "km": 11.85,
            "desc": "Traza principal",
            "lat": -33.2509,
            "lng": -66.21229
        },
        {
            "km": 12,
            "desc": "Rotonda Cruz de Piedra",
            "lat": -33.2638394,
            "lng": -66.2297909
        },
        {
            "km": 12.1,
            "desc": "Traza principal",
            "lat": -33.2518942,
            "lng": -66.2085661
        },
        {
            "km": 12.54,
            "desc": "Traza principal",
            "lat": -33.254,
            "lng": -66.20208
        },
        {
            "km": 12.59,
            "desc": "Traza principal",
            "lat": -33.2542032,
            "lng": -66.2012797
        },
        {
            "km": 12.72,
            "desc": "Traza principal",
            "lat": -33.25376,
            "lng": -66.19923
        },
        {
            "km": 12.88,
            "desc": "Traza principal",
            "lat": -33.2537,
            "lng": -66.19671
        },
        {
            "km": 12.96,
            "desc": "Traza principal",
            "lat": -33.2533267,
            "lng": -66.1955336
        },
        {
            "km": 13,
            "desc": "Peaje Cruz de Piedra",
            "lat": -33.2519519,
            "lng": -66.2260722
        },
        {
            "km": 13.12,
            "desc": "Traza principal",
            "lat": -33.25155,
            "lng": -66.19418
        },
        {
            "km": 13.17,
            "desc": "Traza principal",
            "lat": -33.2512997,
            "lng": -66.1935003
        },
        {
            "km": 13.23,
            "desc": "Traza principal",
            "lat": -33.2515,
            "lng": -66.19263
        },
        {
            "km": 13.41,
            "desc": "Traza principal",
            "lat": -33.2512703,
            "lng": -66.1899151
        },
        {
            "km": 13.59,
            "desc": "Traza principal",
            "lat": -33.2529043,
            "lng": -66.1878093
        },
        {
            "km": 13.98,
            "desc": "Traza principal",
            "lat": -33.251001,
            "lng": -66.1821855
        },
        {
            "km": 14.26,
            "desc": "Traza principal",
            "lat": -33.251568,
            "lng": -66.1777938
        },
        {
            "km": 14.69,
            "desc": "Traza principal",
            "lat": -33.2560138,
            "lng": -66.1758317
        },
        {
            "km": 14.91,
            "desc": "Traza principal",
            "lat": -33.2588567,
            "lng": -66.174879
        },
        {
            "km": 15,
            "desc": "Peaje Perilago",
            "lat": -33.2542878,
            "lng": -66.212412
        },
        {
            "km": 15.17,
            "desc": "Traza principal",
            "lat": -33.26137,
            "lng": -66.17225
        },
        {
            "km": 15.44,
            "desc": "Traza principal",
            "lat": -33.2627598,
            "lng": -66.1683674
        },
        {
            "km": 15.64,
            "desc": "Traza principal",
            "lat": -33.26404,
            "lng": -66.1655
        },
        {
            "km": 15.8,
            "desc": "Traza principal",
            "lat": -33.2657315,
            "lng": -66.1640798
        },
        {
            "km": 16.19,
            "desc": "Traza principal",
            "lat": -33.2707589,
            "lng": -66.1623082
        },
        {
            "km": 16.4,
            "desc": "Traza principal",
            "lat": -33.27335,
            "lng": -66.16144
        },
        {
            "km": 16.62,
            "desc": "Traza principal",
            "lat": -33.2752965,
            "lng": -66.1587576
        },
        {
            "km": 16.79,
            "desc": "Traza principal",
            "lat": -33.27542,
            "lng": -66.15613
        },
        {
            "km": 17,
            "desc": "La Hoya",
            "lat": -33.2509587,
            "lng": -66.1915335
        },
        {
            "km": 17.78,
            "desc": "Traza principal",
            "lat": -33.2721132,
            "lng": -66.1410199
        },
        {
            "km": 18.44,
            "desc": "Traza principal",
            "lat": -33.2710583,
            "lng": -66.1307046
        },
        {
            "km": 19,
            "desc": "Rotonda Virgen / El Volcan",
            "lat": -33.2530479,
            "lng": -66.1751547
        },
        {
            "km": 20,
            "desc": "Puente Los Puquios",
            "lat": -33.262433,
            "lng": -66.1698524
        },
        {
            "km": 22.02,
            "desc": "Traza principal",
            "lat": -33.2669278,
            "lng": -66.0744213
        },
        {
            "km": 22.45,
            "desc": "Traza principal",
            "lat": -33.26768,
            "lng": -66.0675449
        },
        {
            "km": 23.87,
            "desc": "Traza principal",
            "lat": -33.2672577,
            "lng": -66.0452015
        },
        {
            "km": 24.32,
            "desc": "Traza principal",
            "lat": -33.2644799,
            "lng": -66.0388586
        },
        {
            "km": 26.74,
            "desc": "Traza principal",
            "lat": -33.2561069,
            "lng": -66.0020352
        },
        {
            "km": 27,
            "desc": "Cruce Rp30 / Control Policial",
            "lat": -33.269815,
            "lng": -66.1078299
        },
        {
            "km": 32.93,
            "desc": "Traza principal",
            "lat": -33.2524878,
            "lng": -66.09964764
        },
        {
            "km": 33,
            "desc": "Arroyo y Retorno Los Risma",
            "lat": -33.266405,
            "lng": -66.0431169
        },
        {
            "km": 36.5,
            "desc": "Puente Arroyo La Petra",
            "lat": -33.2574904,
            "lng": -66.0071684
        },
        {
            "km": 38,
            "desc": "Retorno La Petra",
            "lat": -33.2524878,
            "lng": -65.9964764
        },
        {
            "km": 39.83,
            "desc": "Traza principal",
            "lat": -33.2498757,
            "lng": -65.9916789
        },
        {
            "km": 39.88,
            "desc": "Traza principal",
            "lat": -33.2494978,
            "lng": -65.9909967
        },
        {
            "km": 44.37,
            "desc": "Traza principal",
            "lat": -33.2427678,
            "lng": -65.9205906
        },
        {
            "km": 44.7,
            "desc": "Presa Saladillo",
            "lat": -33.2423537,
            "lng": -65.9254676
        },
        {
            "km": 45.42,
            "desc": "Traza principal",
            "lat": -33.2403134,
            "lng": -65.9044023
        },
        {
            "km": 45.88,
            "desc": "Traza principal",
            "lat": -33.240701,
            "lng": -65.8970534
        },
        {
            "km": 46,
            "desc": "Puente Rio 5to",
            "lat": -33.2415744,
            "lng": -65.8955251
        }
    ]
    ,
    '30': [
        { km: 0, desc: 'Empalme Ruta Prov. 20', lat: -33.2697162, lng: -66.1080016 },
        { km: 13.8, desc: 'Peaje Ruta 30', lat: -33.3026396, lng: -66.1093506 },
        { km: 16, desc: 'Marcador Km 16', lat: -33.3205677, lng: -66.1144070 },
        { km: 18, desc: 'Marcador Km 18', lat: -33.3371463, lng: -66.1189336 },
        { km: 22, desc: 'Empalme Ruta Nac. 7', lat: -33.3439209, lng: -66.1199308 } // Estimación de Km final
    ],
    '9': [
        {
            "km": 8,
            "desc": "Rotonda Rotary Club San Luis",
            "lat": -33.3039744,
            "lng": -66.2596378
        },
        {
            "km": 8.23,
            "desc": "Traza principal",
            "lat": -33.3028488,
            "lng": -66.2575399
        },
        {
            "km": 8.9,
            "desc": "Traza principal",
            "lat": -33.2994305,
            "lng": -66.2517099
        },
        {
            "km": 9.66,
            "desc": "Traza principal",
            "lat": -33.2936754,
            "lng": -66.2474
        },
        {
            "km": 9.98,
            "desc": "Traza principal",
            "lat": -33.2917852,
            "lng": -66.2447753
        },
        {
            "km": 10.25,
            "desc": "Traza principal",
            "lat": -33.2899865,
            "lng": -66.2429016
        },
        {
            "km": 10.36,
            "desc": "Rotonda Malvinas",
            "lat": -33.2893605,
            "lng": -66.2419404
        },
        {
            "km": 11.16,
            "desc": "Traza principal",
            "lat": -33.2839886,
            "lng": -66.2362907
        },
        {
            "km": 11.43,
            "desc": "Traza principal",
            "lat": -33.2828107,
            "lng": -66.233781
        },
        {
            "km": 12.45,
            "desc": "Ingreso a San Roque",
            "lat": -33.2772967,
            "lng": -66.225127
        },
        {
            "km": 12.7,
            "desc": "Traza principal",
            "lat": -33.2759428,
            "lng": -66.222957
        },
        {
            "km": 12.81,
            "desc": "Traza principal",
            "lat": -33.2751606,
            "lng": -66.2222975
        },
        {
            "km": 12.97,
            "desc": "Traza principal",
            "lat": -33.274449,
            "lng": -66.2207873
        },
        {
            "km": 13.2,
            "desc": "Traza principal",
            "lat": -33.2741117,
            "lng": -66.2183962
        },
        {
            "km": 13.35,
            "desc": "Traza principal",
            "lat": -33.2746321,
            "lng": -66.2168694
        },
        {
            "km": 13.49,
            "desc": "Traza principal",
            "lat": -33.2752692,
            "lng": -66.2156328
        },
        {
            "km": 13.73,
            "desc": "Traza principal",
            "lat": -33.2750318,
            "lng": -66.213084
        },
        {
            "km": 13.92,
            "desc": "Traza principal",
            "lat": -33.2735457,
            "lng": -66.2119968
        },
        {
            "km": 14.08,
            "desc": "Traza principal",
            "lat": -33.2722249,
            "lng": -66.2115001
        },
        {
            "km": 14.26,
            "desc": "Ingreso a Cuchi Corral",
            "lat": -33.2709984,
            "lng": -66.2101791
        },
        {
            "km": 14.62,
            "desc": "Traza principal",
            "lat": -33.269877,
            "lng": -66.2066197
        },
        {
            "km": 14.94,
            "desc": "Traza principal",
            "lat": -33.2711648,
            "lng": -66.2036138
        },
        {
            "km": 15.16,
            "desc": "Traza principal",
            "lat": -33.2714657,
            "lng": -66.2012375
        },
        {
            "km": 15.25,
            "desc": "Ingreso a perilago, Dique Cruz de Piedra",
            "lat": -33.2709067,
            "lng": -66.2006259
        },
        {
            "km": 15.36,
            "desc": "Traza principal",
            "lat": -33.2704274,
            "lng": -66.1995541
        },
        {
            "km": 15.52,
            "desc": "Traza principal",
            "lat": -33.2706217,
            "lng": -66.1978459
        },
        {
            "km": 15.68,
            "desc": "Peaje Los Puquios",
            "lat": -33.2714234,
            "lng": -66.1964652
        },
        {
            "km": 15.95,
            "desc": "Traza principal",
            "lat": -33.2727363,
            "lng": -66.1940288
        },
        {
            "km": 16.12,
            "desc": "Traza principal",
            "lat": -33.272577,
            "lng": -66.1921863
        },
        {
            "km": 16.46,
            "desc": "Traza principal",
            "lat": -33.2732907,
            "lng": -66.1887023
        },
        {
            "km": 16.89,
            "desc": "Traza principal",
            "lat": -33.2741965,
            "lng": -66.184214
        },
        {
            "km": 17.22,
            "desc": "Puente arroyo los p. y escultura",
            "lat": -33.2751313,
            "lng": -66.1808986
        },
        {
            "km": 17.4,
            "desc": "Traza principal",
            "lat": -33.2740136,
            "lng": -66.1795281
        },
        {
            "km": 17.72,
            "desc": "Traza principal",
            "lat": -33.2711448,
            "lng": -66.1791507
        },
        {
            "km": 18.12,
            "desc": "Traza principal",
            "lat": -33.2687124,
            "lng": -66.176009
        },
        {
            "km": 18.78,
            "desc": "Traza principal",
            "lat": -33.2635906,
            "lng": -66.172387
        },
        {
            "km": 19.05,
            "desc": "Puente Los Puquios - ingreso al Volcán",
            "lat": -33.2624352,
            "lng": -66.1698745
        },
        {
            "km": 19.65,
            "desc": "Traza principal",
            "lat": -33.2594697,
            "lng": -66.1645067
        },
        {
            "km": 20.07,
            "desc": "Traza principal",
            "lat": -33.2563572,
            "lng": -66.162181
        },
        {
            "km": 20.78,
            "desc": "Traza principal",
            "lat": -33.2499929,
            "lng": -66.1619721
        },
        {
            "km": 21.05,
            "desc": "Rotonda El Alpatacal",
            "lat": -33.2477104,
            "lng": -66.160891
        },
        {
            "km": 23.47,
            "desc": "Retorno arroyo Las Mondinas",
            "lat": -33.2300955,
            "lng": -66.1459108
        },
        {
            "km": 23.93,
            "desc": "Traza principal",
            "lat": -33.22636,
            "lng": -66.14388
        },
        {
            "km": 26.45,
            "desc": "Traza principal",
            "lat": -33.20589,
            "lng": -66.13257
        },
        {
            "km": 27.59,
            "desc": "Rotonda El Zorrito",
            "lat": -33.19834,
            "lng": -66.1244046
        },
        {
            "km": 29.13,
            "desc": "Traza principal",
            "lat": -33.18715,
            "lng": -66.11478
        },
        {
            "km": 30.43,
            "desc": "Ingreso a Dique Verta E. de Batini",
            "lat": -33.1787679,
            "lng": -66.105156
        },
        {
            "km": 32.52,
            "desc": "Ingreso a Polo Club",
            "lat": -33.1661177,
            "lng": -66.0887422
        },
        {
            "km": 33.37,
            "desc": "Traza principal",
            "lat": -33.1619531,
            "lng": -66.0812551
        },
        {
            "km": 33.63,
            "desc": "Arroyo Las Barranquitas",
            "lat": -33.1600436,
            "lng": -66.0795276
        },
        {
            "km": 34.09,
            "desc": "Traza principal",
            "lat": -33.1565807,
            "lng": -66.0770048
        },
        {
            "km": 35.02,
            "desc": "Ingreso a Las Barranquitas",
            "lat": -33.1496383,
            "lng": -66.0715501
        },
        {
            "km": 36.2,
            "desc": "Traza principal",
            "lat": -33.1410146,
            "lng": -66.0642264
        },
        {
            "km": 36.69,
            "desc": "Traza principal",
            "lat": -33.1369,
            "lng": -66.0626
        },
        {
            "km": 37.18,
            "desc": "Ingreso a Barrio Altos Del Trapiche",
            "lat": -33.1325323,
            "lng": -66.0624963
        },
        {
            "km": 38.04,
            "desc": "Rotonda El Trapiche - La Florida",
            "lat": -33.1248799,
            "lng": -66.0609568
        },
        {
            "km": 38.37,
            "desc": "Traza principal",
            "lat": -33.1219898,
            "lng": -66.0610843
        },
        {
            "km": 38.44,
            "desc": "Traza principal",
            "lat": -33.1213422,
            "lng": -66.0611008
        },
        {
            "km": 38.58,
            "desc": "Traza principal",
            "lat": -33.1201798,
            "lng": -66.0604079
        },
        {
            "km": 38.64,
            "desc": "Traza principal",
            "lat": -33.1196585,
            "lng": -66.0605107
        },
        {
            "km": 38.86,
            "desc": "Traza principal",
            "lat": -33.1178613,
            "lng": -66.0613285
        },
        {
            "km": 38.94,
            "desc": "Traza principal",
            "lat": -33.1171848,
            "lng": -66.0614705
        },
        {
            "km": 39.07,
            "desc": "Traza principal",
            "lat": -33.11649,
            "lng": -66.06262
        },
        {
            "km": 39.18,
            "desc": "Traza principal",
            "lat": -33.11569,
            "lng": -66.06324
        },
        {
            "km": 39.26,
            "desc": "Traza principal",
            "lat": -33.1149876,
            "lng": -66.0635141
        },
        {
            "km": 39.39,
            "desc": "Traza principal",
            "lat": -33.11379,
            "lng": -66.06373
        },
        {
            "km": 39.54,
            "desc": "Traza principal",
            "lat": -33.11248,
            "lng": -66.06364
        },
        {
            "km": 39.93,
            "desc": "Traza principal",
            "lat": -33.1091054,
            "lng": -66.0646486
        },
        {
            "km": 40.12,
            "desc": "Traza principal",
            "lat": -33.107408,
            "lng": -66.0643479
        },
        {
            "km": 40.3,
            "desc": "Traza principal",
            "lat": -33.1061113,
            "lng": -66.063189
        },
        {
            "km": 40.66,
            "desc": "Traza principal",
            "lat": -33.1038122,
            "lng": -66.0605432
        },
        {
            "km": 40.88,
            "desc": "Rotonda acceso a la Carolina",
            "lat": -33.1028936,
            "lng": -66.0585147
        },
        {
            "km": 41.25,
            "desc": "Traza principal",
            "lat": -33.0998769,
            "lng": -66.0601888
        },
        {
            "km": 41.54,
            "desc": "Traza principal",
            "lat": -33.0979415,
            "lng": -66.0622379
        },
        {
            "km": 41.83,
            "desc": "Traza principal",
            "lat": -33.0953862,
            "lng": -66.0626355
        },
        {
            "km": 42.21,
            "desc": "Traza principal",
            "lat": -33.0923955,
            "lng": -66.0645675
        },
        {
            "km": 42.42,
            "desc": "Limite El Trapiche - Rio Grande",
            "lat": -33.09079,
            "lng": -66.0658
        },
        {
            "km": 42.53,
            "desc": "Traza principal",
            "lat": -33.0899,
            "lng": -66.06631
        },
        {
            "km": 42.71,
            "desc": "Traza principal",
            "lat": -33.0883178,
            "lng": -66.0664104
        },
        {
            "km": 43.06,
            "desc": "Traza principal",
            "lat": -33.0855129,
            "lng": -66.0680077
        },
        {
            "km": 43.18,
            "desc": "Traza principal",
            "lat": -33.08447,
            "lng": -66.06843
        },
        {
            "km": 43.83,
            "desc": "Traza principal",
            "lat": -33.0786977,
            "lng": -66.0691788
        },
        {
            "km": 44,
            "desc": "Traza principal",
            "lat": -33.07712,
            "lng": -66.06918
        },
        {
            "km": 44.27,
            "desc": "Traza principal",
            "lat": -33.0749733,
            "lng": -66.0680049
        },
        {
            "km": 44.71,
            "desc": "Puente Rio Grande",
            "lat": -33.0710576,
            "lng": -66.0674971
        },
        {
            "km": 45.15,
            "desc": "Traza principal",
            "lat": -33.0670907,
            "lng": -66.0678734
        },
        {
            "km": 45.57,
            "desc": "Traza principal",
            "lat": -33.06371,
            "lng": -66.06971
        },
        {
            "km": 45.68,
            "desc": "Traza principal",
            "lat": -33.0627368,
            "lng": -66.0699767
        },
        {
            "km": 46.21,
            "desc": "Traza principal",
            "lat": -33.0581652,
            "lng": -66.0684753
        },
        {
            "km": 46.61,
            "desc": "Ingreso a dique Rio Grande",
            "lat": -33.05465,
            "lng": -66.0690266
        },
        {
            "km": 46.86,
            "desc": "Traza principal",
            "lat": -33.05265,
            "lng": -66.07012
        },
        {
            "km": 46.97,
            "desc": "Traza principal",
            "lat": -33.05195,
            "lng": -66.07096
        },
        {
            "km": 47.06,
            "desc": "Traza principal",
            "lat": -33.05115,
            "lng": -66.07095
        },
        {
            "km": 47.16,
            "desc": "Traza principal",
            "lat": -33.05036,
            "lng": -66.07041
        },
        {
            "km": 47.84,
            "desc": "Traza principal",
            "lat": -33.0442515,
            "lng": -66.0697089
        },
        {
            "km": 48.09,
            "desc": "Traza principal",
            "lat": -33.0422382,
            "lng": -66.0706904
        },
        {
            "km": 48.27,
            "desc": "Traza principal",
            "lat": -33.0406413,
            "lng": -66.070273
        },
        {
            "km": 48.4,
            "desc": "Traza principal",
            "lat": -33.0399,
            "lng": -66.06916
        },
        {
            "km": 48.57,
            "desc": "Traza principal",
            "lat": -33.0386863,
            "lng": -66.0681261
        },
        {
            "km": 48.61,
            "desc": "Traza principal",
            "lat": -33.03855,
            "lng": -66.06778
        },
        {
            "km": 48.65,
            "desc": "Traza principal",
            "lat": -33.0388527,
            "lng": -66.0674159
        },
        {
            "km": 48.75,
            "desc": "Traza principal",
            "lat": -33.03965,
            "lng": -66.06712
        },
        {
            "km": 48.84,
            "desc": "Traza principal",
            "lat": -33.04045,
            "lng": -66.0672
        },
        {
            "km": 48.87,
            "desc": "Traza principal",
            "lat": -33.0406402,
            "lng": -66.0669757
        },
        {
            "km": 48.94,
            "desc": "Traza principal",
            "lat": -33.04069,
            "lng": -66.06618
        },
        {
            "km": 48.99,
            "desc": "Traza principal",
            "lat": -33.040292,
            "lng": -66.0658968
        },
        {
            "km": 49.15,
            "desc": "Traza principal",
            "lat": -33.0389074,
            "lng": -66.0662384
        },
        {
            "km": 49.39,
            "desc": "Traza principal",
            "lat": -33.03715,
            "lng": -66.0677
        },
        {
            "km": 49.55,
            "desc": "Traza principal",
            "lat": -33.03576,
            "lng": -66.06769
        },
        {
            "km": 49.61,
            "desc": "Traza principal",
            "lat": -33.0352404,
            "lng": -66.0678967
        },
        {
            "km": 49.8,
            "desc": "Traza principal",
            "lat": -33.03422,
            "lng": -66.06958
        },
        {
            "km": 50.07,
            "desc": "Traza principal",
            "lat": -33.032,
            "lng": -66.0707
        },
        {
            "km": 50.16,
            "desc": "Traza principal",
            "lat": -33.0311968,
            "lng": -66.0707888
        },
        {
            "km": 50.4,
            "desc": "Traza principal",
            "lat": -33.02907,
            "lng": -66.07084
        },
        {
            "km": 50.53,
            "desc": "Traza principal",
            "lat": -33.02796,
            "lng": -66.07119
        },
        {
            "km": 50.72,
            "desc": "Traza principal",
            "lat": -33.0264113,
            "lng": -66.0704734
        },
        {
            "km": 51.05,
            "desc": "Ingreso Monumento a Pringles",
            "lat": -33.0234716,
            "lng": -66.0698387
        },
        {
            "km": 51.32,
            "desc": "Traza principal",
            "lat": -33.02103,
            "lng": -66.06967
        },
        {
            "km": 51.56,
            "desc": "Traza principal",
            "lat": -33.0189983,
            "lng": -66.0688351
        },
        {
            "km": 51.74,
            "desc": "Traza principal",
            "lat": -33.01744,
            "lng": -66.06871
        },
        {
            "km": 51.83,
            "desc": "Traza principal",
            "lat": -33.0168873,
            "lng": -66.0679975
        },
        {
            "km": 52,
            "desc": "Traza principal",
            "lat": -33.01652,
            "lng": -66.06628
        },
        {
            "km": 52.08,
            "desc": "Traza principal",
            "lat": -33.0158962,
            "lng": -66.0657242
        },
        {
            "km": 52.32,
            "desc": "Traza principal",
            "lat": -33.01379,
            "lng": -66.0655
        },
        {
            "km": 52.63,
            "desc": "Traza principal",
            "lat": -33.0111517,
            "lng": -66.0666696
        },
        {
            "km": 52.77,
            "desc": "Traza principal",
            "lat": -33.0101,
            "lng": -66.06734
        },
        {
            "km": 53.04,
            "desc": "Traza principal",
            "lat": -33.007824,
            "lng": -66.0663599
        },
        {
            "km": 53.32,
            "desc": "Traza principal",
            "lat": -33.00742,
            "lng": -66.06338
        },
        {
            "km": 53.48,
            "desc": "Traza principal",
            "lat": -33.0065199,
            "lng": -66.0620328
        },
        {
            "km": 53.66,
            "desc": "Traza principal",
            "lat": -33.00497,
            "lng": -66.06185
        },
        {
            "km": 53.91,
            "desc": "Traza principal",
            "lat": -33.00293,
            "lng": -66.06077
        },
        {
            "km": 54.23,
            "desc": "Traza principal",
            "lat": -33.0000713,
            "lng": -66.0606878
        },
        {
            "km": 54.93,
            "desc": "Traza principal",
            "lat": -32.9943818,
            "lng": -66.0639746
        },
        {
            "km": 55.96,
            "desc": "Traza principal",
            "lat": -32.98898,
            "lng": -66.07285
        },
        {
            "km": 56.04,
            "desc": "Traza principal",
            "lat": -32.9885078,
            "lng": -66.0734201
        },
        {
            "km": 56.16,
            "desc": "Traza principal",
            "lat": -32.98745,
            "lng": -66.07353
        },
        {
            "km": 56.59,
            "desc": "Traza principal",
            "lat": -32.9836892,
            "lng": -66.0724823
        },
        {
            "km": 56.65,
            "desc": "Traza principal",
            "lat": -32.9834894,
            "lng": -66.0731135
        },
        {
            "km": 56.84,
            "desc": "Traza principal",
            "lat": -32.9847916,
            "lng": -66.0742801
        },
        {
            "km": 56.95,
            "desc": "Traza principal",
            "lat": -32.9840707,
            "lng": -66.0751414
        },
        {
            "km": 57.29,
            "desc": "Traza principal",
            "lat": -32.9814666,
            "lng": -66.0732224
        },
        {
            "km": 57.95,
            "desc": "Traza principal",
            "lat": -32.9756389,
            "lng": -66.0720435
        },
        {
            "km": 58.52,
            "desc": "Traza principal",
            "lat": -32.9712397,
            "lng": -66.0751253
        },
        {
            "km": 58.6,
            "desc": "Traza principal",
            "lat": -32.9706417,
            "lng": -66.0747327
        },
        {
            "km": 58.94,
            "desc": "Traza principal",
            "lat": -32.9715782,
            "lng": -66.0713072
        },
        {
            "km": 59.37,
            "desc": "Traza principal",
            "lat": -32.9679049,
            "lng": -66.0725387
        },
        {
            "km": 59.56,
            "desc": "Traza principal",
            "lat": -32.9662906,
            "lng": -66.0732252
        },
        {
            "km": 60.15,
            "desc": "Traza principal",
            "lat": -32.9609896,
            "lng": -66.0727819
        },
        {
            "km": 60.54,
            "desc": "Traza principal",
            "lat": -32.9577465,
            "lng": -66.0711841
        },
        {
            "km": 60.68,
            "desc": "Paso del Rey - La Arenilla",
            "lat": -32.9565484,
            "lng": -66.0710016
        },
        {
            "km": 61.26,
            "desc": "Traza principal",
            "lat": -32.9514609,
            "lng": -66.0722253
        },
        {
            "km": 61.75,
            "desc": "Traza principal",
            "lat": -32.9483868,
            "lng": -66.075994
        },
        {
            "km": 62.12,
            "desc": "Traza principal",
            "lat": -32.9452098,
            "lng": -66.0749848
        },
        {
            "km": 62.5,
            "desc": "Traza principal",
            "lat": -32.9424697,
            "lng": -66.0774082
        },
        {
            "km": 62.82,
            "desc": "Traza principal",
            "lat": -32.9399731,
            "lng": -66.0789134
        },
        {
            "km": 63.1,
            "desc": "Traza principal",
            "lat": -32.9397215,
            "lng": -66.081889
        },
        {
            "km": 63.53,
            "desc": "Traza principal",
            "lat": -32.9360928,
            "lng": -66.0835618
        },
        {
            "km": 63.82,
            "desc": "Traza principal",
            "lat": -32.9354632,
            "lng": -66.0865393
        },
        {
            "km": 64.15,
            "desc": "Traza principal",
            "lat": -32.9325892,
            "lng": -66.0858848
        },
        {
            "km": 64.63,
            "desc": "Traza principal",
            "lat": -32.9284567,
            "lng": -66.0844561
        },
        {
            "km": 65.03,
            "desc": "Traza principal",
            "lat": -32.9253088,
            "lng": -66.0865427
        },
        {
            "km": 65.29,
            "desc": "Traza principal",
            "lat": -32.9230552,
            "lng": -66.0863847
        },
        {
            "km": 65.59,
            "desc": "Traza principal",
            "lat": -32.9205065,
            "lng": -66.0875796
        },
        {
            "km": 66.48,
            "desc": "Traza principal",
            "lat": -32.9127853,
            "lng": -66.0851702
        },
        {
            "km": 66.74,
            "desc": "Valle de Pancanta",
            "lat": -32.9113057,
            "lng": -66.0872063
        },
        {
            "km": 67.53,
            "desc": "Traza principal",
            "lat": -32.9089215,
            "lng": -66.0951406
        },
        {
            "km": 68.16,
            "desc": "Traza principal",
            "lat": -32.9059158,
            "lng": -66.1007895
        },
        {
            "km": 68.54,
            "desc": "Traza principal",
            "lat": -32.9032476,
            "lng": -66.1034052
        },
        {
            "km": 69.75,
            "desc": "Centro de Salud 18 Valle de Pancanta",
            "lat": -32.8961549,
            "lng": -66.1131051
        },
        {
            "km": 70.53,
            "desc": "Traza principal",
            "lat": -32.8891798,
            "lng": -66.1132357
        },
        {
            "km": 71.32,
            "desc": "Traza principal",
            "lat": -32.8821211,
            "lng": -66.1134376
        },
        {
            "km": 72.27,
            "desc": "Traza principal",
            "lat": -32.8737703,
            "lng": -66.1148482
        },
        {
            "km": 72.99,
            "desc": "Traza principal",
            "lat": -32.867371,
            "lng": -66.1141178
        },
        {
            "km": 73.86,
            "desc": "Traza principal",
            "lat": -32.8597243,
            "lng": -66.1119368
        },
        {
            "km": 74.49,
            "desc": "Traza principal",
            "lat": -32.8544387,
            "lng": -66.1099224
        },
        {
            "km": 74.72,
            "desc": "Traza principal",
            "lat": -32.8523368,
            "lng": -66.1099802
        },
        {
            "km": 75.04,
            "desc": "Traza principal",
            "lat": -32.8515273,
            "lng": -66.1067542
        },
        {
            "km": 75.65,
            "desc": "Traza principal",
            "lat": -32.8461203,
            "lng": -66.1057992
        },
        {
            "km": 76.37,
            "desc": "Traza principal",
            "lat": -32.8396561,
            "lng": -66.1063789
        },
        {
            "km": 77.93,
            "desc": "Traza principal",
            "lat": -32.827171,
            "lng": -66.0991251
        },
        {
            "km": 78.18,
            "desc": "Traza principal",
            "lat": -32.8249498,
            "lng": -66.0991765
        },
        {
            "km": 78.36,
            "desc": "Traza principal",
            "lat": -32.8232993,
            "lng": -66.0992505
        },
        {
            "km": 78.89,
            "desc": "Traza principal",
            "lat": -32.8194888,
            "lng": -66.0959797
        },
        {
            "km": 79,
            "desc": "La Carolina",
            "lat": -32.8185007,
            "lng": -66.0957608
        }
    ],

    '7': [
        { km: 713, desc: 'Marcador Km 713', lat: -33.5853571, lng: -65.6302887 },
        { km: 714, desc: 'Marcador Km 714', lat: -33.5805195, lng: -65.6393134 },
        { km: 715, desc: 'Liborio Luna - Inicio región centro', lat: -33.5833255, lng: -65.6341452 },
        { km: 731, desc: 'Puente de Fraga', lat: -33.4976835, lng: -65.7934509 },
        { km: 740, desc: 'Retorno Granville', lat: -33.4549006, lng: -65.8763957 },
        { km: 741, desc: 'Marcador Km 741', lat: -33.4502931, lng: -65.8857256 },
        { km: 742, desc: 'Puente Los Italianos - Ser Beef SA', lat: -33.4459664, lng: -65.894538 },
        { km: 747, desc: 'Marcador Km 747', lat: -33.4219924, lng: -65.9407026 },
        { km: 749, desc: 'Marcador Km 749', lat: -33.4123603, lng: -65.9588587 },
        { km: 751.65, desc: 'Acceso a Dique Paso de Las Carretas', lat: -33.3989463, lng: -65.9825863 },
        { km: 752, desc: 'Marcador Km 752', lat: -33.3975869, lng: -65.9849649 },
        { km: 753, desc: 'Marcador Km 753', lat: -33.392342, lng: -65.9942073 },
        { km: 755, desc: 'Marcador Km 755', lat: -33.3820531, lng: -66.0120921 },
        { km: 755.25, desc: 'Eleodoro Lobos', lat: -33.3815848, lng: -66.0132379 },
        { km: 756.6, desc: 'La Petra', lat: -33.3743804, lng: -66.0255856 },
        { km: 761, desc: 'Peaje La Cumbre', lat: -33.3590784, lng: -66.0670751 },
        { km: 764, desc: 'Marcador Km 764', lat: -33.3497519, lng: -66.099939 },
        { km: 765.75, desc: 'Destacamento Policial La Cumbre', lat: -33.3420922, lng: -66.1266905 },
        { km: 766, desc: 'Marcador Km 766', lat: -33.3441651, lng: -66.1199303 },
        { km: 779, desc: 'Marcador Km 779', lat: -33.3066128, lng: -66.2525518 },
        { km: 780.45, desc: 'Ingreso a Donovan', lat: -33.3044338, lng: -66.2602336 },
        { km: 781, desc: 'Puente los Donovan', lat: -33.3033534, lng: -66.2663436 },
        { km: 782, desc: 'Puente Hospital Ramón Carrillo', lat: -33.30539, lng: -66.2875658 },
        { km: 783, desc: 'Terrazas del Portezuelo', lat: -33.3058348, lng: -66.2913012 },
        { km: 784, desc: 'Marcador Km 784', lat: -33.3196764, lng: -66.3017314 },
        { km: 786.91, desc: 'Puente Ruta Prov. Nº 3 (Zanjitas)', lat: -33.3367288, lng: -66.3193233 },
        { km: 792.51, desc: 'Puente Ruta Nac. Nº 146 (Beazley)', lat: -33.3451749, lng: -66.3732413 },
        { km: 794.65, desc: 'Puente Autódromo', lat: -33.3290849, lng: -66.3848813 },
        { km: 794.95, desc: 'Autódromo', lat: -33.3282726, lng: -66.3880909 },
        { km: 796, desc: 'Escuela Agraria', lat: -33.334507, lng: -66.4214244 },
        { km: 798, desc: 'Centro de Disposición Final - Basural', lat: -33.3386702, lng: -66.4423880 },
        { km: 815.7, desc: 'Puente Balde - Salinas del Bebed.', lat: -33.3753655, lng: -66.6278656 },
        { km: 832, desc: 'Ingreso a Chosme', lat: -33.4095785, lng: -66.8017819 },
        { km: 844.2, desc: 'Ingreso Alto Pencoso', lat: -33.4373185, lng: -66.9266112 },
        { km: 856, desc: 'Ingreso a Jarilla', lat: -33.4255681, lng: -67.0281041 },
        { km: 862, desc: 'Peaje Desaguadero Isla Este', lat: -33.4117474, lng: -67.1234507 },
        { km: 863, desc: 'Peaje Desaguadero Isla Oeste', lat: -33.4128459, lng: -67.1147563 },
        { km: 866, desc: 'Limite Provincia de Mendoza - Arco', lat: -33.4042217, lng: -67.1515111 }
    ]


}


const calibrarRuta20Densa = () => {

    const coordenadasRuta20 = [
        [-66.3007985, -33.2941849], [-66.2884821, -33.2944079], [-66.2870306, -33.2946200],
        [-66.2799964, -33.2969535], [-66.2772227, -33.2972261], [-66.27677, -33.29721],
        [-66.27268, -33.29616], [-66.27062, -33.29552], [-66.2685864, -33.2945745],
        [-66.2637178, -33.2920447], [-66.2545769, -33.2878294], [-66.2497884, -33.2847671],
        [-66.2425691, -33.2804371], [-66.2365943, -33.2768961], [-66.2355086, -33.2761918],
        [-66.2308586, -33.2648545], [-66.2297909, -33.2638394], [-66.2272913, -33.2587553],
        [-66.22708, -33.25423], [-66.22684, -33.25293], [-66.2260722, -33.2519519],
        [-66.22341, -33.25135], [-66.2211567, -33.2516617], [-66.2175249, -33.2534633],
        [-66.21576, -33.25400], [-66.2147619, -33.2547688], [-66.21425, -33.25499],
        [-66.21308, -33.25452], [-66.2124120, -33.2542878], [-66.21232, -33.25387],
        [-66.21237, -33.25334], [-66.21283, -33.25278], [-66.21322, -33.25153],
        [-66.2130937, -33.2513059], [-66.21229, -33.25090], [-66.2085661, -33.2518942],
        [-66.20208, -33.25400], [-66.2012797, -33.2542032], [-66.19923, -33.25376],
        [-66.19671, -33.25370], [-66.1955336, -33.2533267], [-66.19418, -33.25155],
        [-66.1935003, -33.2512997], [-66.19263, -33.25150], [-66.1915335, -33.2509587],
        [-66.1899151, -33.2512703], [-66.1878093, -33.2529043], [-66.1821855, -33.2510010],
        [-66.1777938, -33.2515680], [-66.1751547, -33.2530479], [-66.1758317, -33.2560138],
        [-66.1748790, -33.2588567], [-66.17225, -33.26137], [-66.1698524, -33.2624330],
        [-66.1683674, -33.2627598], [-66.16550, -33.26404], [-66.1640798, -33.2657315],
        [-66.1623082, -33.2707589], [-66.16144, -33.27335], [-66.1587576, -33.2752965],
        [-66.15613, -33.27542], [-66.1410199, -33.2721132], [-66.1307046, -33.2710583],
        [-66.1078299, -33.2698150], [-66.0744213, -33.2669278], [-66.0675449, -33.2676800],
        [-66.0452015, -33.2672577], [-66.0431169, -33.2664050], [-66.0388586, -33.2644799],
        [-66.0071684, -33.2574904], [-66.0020352, -33.2561069], [-66.09964764, -33.2524878],  
        [-65.9964764, -33.2524878], [-65.9916789, -33.2498757], [-65.9909967, -33.2494978],
        [-65.9254676, -33.2423537], [-65.9205906, -33.2427678], [-65.9044023, -33.2403134],
        [-65.8970534, -33.2407010], [-65.8955251, -33.2415744]
    ];

 
    const hitosRuta20 = [
        { km: 4, desc: 'Puente Derivador', lat: -33.2941849, lng: -66.3007985 },
        { km: 5, desc: 'Rodeo del Alto', lat: -33.2944079, lng: -66.2884821 },
        { km: 7, desc: 'Club La Estrega', lat: -33.2945745, lng: -66.2685864 },
        { km: 9, desc: 'Ave Fenix', lat: -33.2847671, lng: -66.2497884 },
        { km: 12, desc: 'Rotonda Cruz de Piedra', lat: -33.2638394, lng: -66.2297909 },
        { km: 13, desc: 'Peaje Cruz de Piedra', lat: -33.2519519, lng: -66.2260722 },
        { km: 15, desc: 'Peaje Perilago', lat: -33.2542878, lng: -66.2124120 },
        { km: 17, desc: 'La Hoya', lat: -33.2509587, lng: -66.1915335 },
        { km: 19, desc: 'Rotonda Virgen / El Volcan', lat: -33.2530479, lng: -66.1751547 },
        { km: 20, desc: 'Puente Los Puquios', lat: -33.2624330, lng: -66.1698524 },
        { km: 27, desc: 'Cruce Rp30 / Control Policial', lat: -33.2698150, lng: -66.1078299 },
        { km: 33, desc: 'Arroyo y Retorno Los Risma', lat: -33.2664050, lng: -66.0431169 },
        { km: 36.5, desc: 'Puente Arroyo La Petra', lat: -33.2574904, lng: -66.0071684 },
        { km: 38, desc: 'Retorno La Petra', lat: -33.2524878, lng: -65.9964764 },
        { km: 44.7, desc: 'Presa Saladillo', lat: -33.2423537, lng: -65.9254676 },
        { km: 46, desc: 'Puente Rio 5to', lat: -33.2415744, lng: -65.8955251 }
    ];

    const kmInicio = 4.0;
    const kmFin = 46.0;

    let distanciasAcumuladas = [0];
    let distanciaTotalRutaMetros = 0;

    for (let i = 1; i < coordenadasRuta20.length; i++) {
        const pAnterior = coordenadasRuta20[i - 1];
        const pActual = coordenadasRuta20[i];
        const distanciaSegmento = map.distance(
            L.latLng(pAnterior[1], pAnterior[0]),
            L.latLng(pActual[1], pActual[0])
        );
        distanciaTotalRutaMetros += distanciaSegmento;
        distanciasAcumuladas.push(distanciaTotalRutaMetros);
    }

    const nuevasReferencias = coordenadasRuta20.map((coord, index) => {
        const porcentajeRecorrido = distanciasAcumuladas[index] / distanciaTotalRutaMetros;
        const kmExacto = kmInicio + (porcentajeRecorrido * (kmFin - kmInicio));

        return {
            km: parseFloat(kmExacto.toFixed(2)),
            desc: 'Traza principal',
            lat: coord[1],
            lng: coord[0]
        };
    });

    hitosRuta20.forEach(hito => {
        let puntoMasCercano = null;
        let dMin = Infinity;
        nuevasReferencias.forEach(ref => {
            const d = map.distance([hito.lat, hito.lng], [ref.lat, ref.lng]);
            if (d < dMin) { dMin = d; puntoMasCercano = ref; }
        });
        if (puntoMasCercano && dMin < 400) {
            puntoMasCercano.desc = hito.desc;
            puntoMasCercano.km = hito.km; // Sincroniza con el KM oficial
        }
    });

    console.log("=== NUEVO ARREGLO RUTA 20 CALIBRADO ===");
    console.log(JSON.stringify(nuevasReferencias.sort((a, b) => a.km - b.km), null, 2));
}


const calibrarRutaInteligente = () => {

    const coordenadasRuta = [[-66.2596378, -33.3039744], [-66.2575399, -33.3028488], [-66.2517099, -33.2994305], [-66.2474000, -33.2936754], [-66.2447753, -33.2917852], [-66.2429016, -33.2899865], [-66.2419404, -33.2893605], [-66.2362907, -33.2839886], [-66.2337810, -33.2828107], [-66.2251270, -33.2772967], [-66.2229570, -33.2759428], [-66.2222975, -33.2751606], [-66.2207873, -33.2744490], [-66.2183962, -33.2741117], [-66.2168694, -33.2746321], [-66.2156328, -33.2752692], [-66.2130840, -33.2750318], [-66.2119968, -33.2735457], [-66.2115001, -33.2722249], [-66.2101791, -33.2709984], [-66.2066197, -33.2698770], [-66.2036138, -33.2711648], [-66.2012375, -33.2714657], [-66.2006259, -33.2709067], [-66.1995541, -33.2704274], [-66.1978459, -33.2706217], [-66.1964652, -33.2714234], [-66.1940288, -33.2727363], [-66.1921863, -33.2725770], [-66.1887023, -33.2732907], [-66.1842140, -33.2741965], [-66.1808986, -33.2751313], [-66.1795281, -33.2740136], [-66.1791507, -33.2711448], [-66.1760090, -33.2687124], [-66.1723870, -33.2635906], [-66.1698745, -33.2624352], [-66.1645067, -33.2594697], [-66.1621810, -33.2563572], [-66.1619721, -33.2499929], [-66.1608910, -33.2477104], [-66.1459108, -33.2300955], [-66.14388, -33.22636], [-66.13257, -33.20589], [-66.1244046, -33.1983400], [-66.11478, -33.18715], [-66.1051560, -33.1787679], [-66.0887422, -33.1661177], [-66.0812551, -33.1619531], [-66.0795276, -33.1600436], [-66.0770048, -33.1565807], [-66.0715501, -33.1496383], [-66.0642264, -33.1410146], [-66.06260, -33.13690], [-66.0624963, -33.1325323], [-66.0609568, -33.1248799], [-66.0610843, -33.1219898], [-66.0611008, -33.1213422], [-66.0604079, -33.1201798], [-66.0605107, -33.1196585], [-66.0613285, -33.1178613], [-66.0614705, -33.1171848], [-66.06262, -33.11649], [-66.06324, -33.11569], [-66.0635141, -33.1149876], [-66.06373, -33.11379], [-66.06364, -33.11248], [-66.0646486, -33.1091054], [-66.0643479, -33.1074080], [-66.0631890, -33.1061113], [-66.0605432, -33.1038122], [-66.0585147, -33.1028936], [-66.0601888, -33.0998769], [-66.0622379, -33.0979415], [-66.0626355, -33.0953862], [-66.0645675, -33.0923955], [-66.06580, -33.09079], [-66.06631, -33.08990], [-66.0664104, -33.0883178], [-66.0680077, -33.0855129], [-66.06843, -33.08447], [-66.0691788, -33.0786977], [-66.06918, -33.07712], [-66.0680049, -33.0749733], [-66.0674971, -33.0710576], [-66.0678734, -33.0670907], [-66.06971, -33.06371], [-66.0699767, -33.0627368], [-66.0684753, -33.0581652], [-66.0690266, -33.0546500], [-66.07012, -33.05265], [-66.07096, -33.05195], [-66.07095, -33.05115], [-66.07041, -33.05036], [-66.0697089, -33.0442515], [-66.0706904, -33.0422382], [-66.0702730, -33.0406413], [-66.06916, -33.03990], [-66.0681261, -33.0386863], [-66.06778, -33.03855], [-66.0674159, -33.0388527], [-66.06712, -33.03965], [-66.06720, -33.04045], [-66.0669757, -33.0406402], [-66.06618, -33.04069], [-66.0658968, -33.0402920], [-66.0662384, -33.0389074], [-66.06770, -33.03715], [-66.06769, -33.03576], [-66.0678967, -33.0352404], [-66.06958, -33.03422], [-66.07070, -33.03200], [-66.0707888, -33.0311968], [-66.07084, -33.02907], [-66.07119, -33.02796], [-66.0704734, -33.0264113], [-66.0698387, -33.0234716], [-66.06967, -33.02103], [-66.0688351, -33.0189983], [-66.06871, -33.01744], [-66.0679975, -33.0168873], [-66.06628, -33.01652], [-66.0657242, -33.0158962], [-66.06550, -33.01379], [-66.0666696, -33.0111517], [-66.06734, -33.01010], [-66.0663599, -33.0078240], [-66.06338, -33.00742], [-66.0620328, -33.0065199], [-66.06185, -33.00497], [-66.06077, -33.00293], [-66.0606878, -33.0000713], [-66.0639746, -32.9943818], [-66.07285, -32.98898], [-66.0734201, -32.9885078], [-66.07353, -32.98745], [-66.0724823, -32.9836892], [-66.0731135, -32.9834894], [-66.0742801, -32.9847916], [-66.0751414, -32.9840707], [-66.0732224, -32.9814666], [-66.0720435, -32.9756389], [-66.0751253, -32.9712397], [-66.0747327, -32.9706417], [-66.0713072, -32.9715782], [-66.0725387, -32.9679049], [-66.0732252, -32.9662906], [-66.0727819, -32.9609896], [-66.0711841, -32.9577465], [-66.0710016, -32.9565484], [-66.0722253, -32.9514609], [-66.0759940, -32.9483868], [-66.0749848, -32.9452098], [-66.0774082, -32.9424697], [-66.0789134, -32.9399731], [-66.0818890, -32.9397215], [-66.0835618, -32.9360928], [-66.0865393, -32.9354632], [-66.0858848, -32.9325892], [-66.0844561, -32.9284567], [-66.0865427, -32.9253088], [-66.0863847, -32.9230552], [-66.0875796, -32.9205065], [-66.0851702, -32.9127853], [-66.0872063, -32.9113057], [-66.0951406, -32.9089215], [-66.1007895, -32.9059158], [-66.1034052, -32.9032476], [-66.1131051, -32.8961549], [-66.1132357, -32.8891798], [-66.1134376, -32.8821211], [-66.1148482, -32.8737703], [-66.1141178, -32.8673710], [-66.1119368, -32.8597243], [-66.1099224, -32.8544387], [-66.1099802, -32.8523368], [-66.1067542, -32.8515273], [-66.1057992, -32.8461203], [-66.1063789, -32.8396561], [-66.0991251, -32.8271710], [-66.0991765, -32.8249498], [-66.0992505, -32.8232993], [-66.0959797, -32.8194888], [-66.0957608, -32.8185007]];


    const hitosOriginales = [
        { desc: 'Rotonda Rotary Club San Luis', lat: -33.3039744, lng: -66.2596378 },
        { desc: 'Rotonda Malvinas', lat: -33.2893605, lng: -66.2419404 },
        { desc: 'Ingreso a San Roque', lat: -33.2772967, lng: -66.2251270 },
        { desc: 'Ingreso a Cuchi Corral', lat: -33.2709984, lng: -66.2101791 },
        { desc: 'Ingreso a perilago, Dique Cruz de Piedra', lat: -33.2709067, lng: -66.2006259 },
        { desc: 'Peaje Los Puquios', lat: -33.2714234, lng: -66.1964652 },
        { desc: 'Puente arroyo los p. y escultura', lat: -33.2751313, lng: -66.1808986 },
        { desc: 'Puente Los Puquios - ingreso al Volcán', lat: -33.2624352, lng: -66.1698745 },
        { desc: 'Rotonda El Alpatacal', lat: -33.2477104, lng: -66.1608910 },
        { desc: 'Retorno arroyo Las Mondinas', lat: -33.2300955, lng: -66.1459108 },
        { desc: 'Rotonda El Zorrito', lat: -33.1983400, lng: -66.1244046 },
        { desc: 'Ingreso a Dique Verta E. de Batini', lat: -33.1787679, lng: -66.1051560 },
        { desc: 'Ingreso a Polo Club', lat: -33.1661177, lng: -66.0887422 },
        { desc: 'Arroyo Las Barranquitas', lat: -33.1600436, lng: -66.0795276 },
        { desc: 'Ingreso a Las Barranquitas', lat: -33.1496383, lng: -66.0715501 },
        { desc: 'Ingreso a Barrio Altos Del Trapiche', lat: -33.1325323, lng: -66.0624963 },
        { desc: 'Rotonda El Trapiche - La Florida', lat: -33.1248799, lng: -66.0609568 },
        { desc: 'Rotonda acceso a la Carolina', lat: -33.1028936, lng: -66.0585147 },
        { desc: 'Limite El Trapiche - Rio Grande', lat: -33.0909919, lng: -66.0668802 },
        { desc: 'Puente Rio Grande', lat: -33.0710576, lng: -66.0674971 },
        { desc: 'Ingreso a dique Rio Grande', lat: -33.0546500, lng: -66.0690266 },
        { desc: 'Ingreso Monumento a Pringles', lat: -33.0234716, lng: -66.0698387 },
        { desc: 'Paso del Rey - La Arenilla', lat: -32.9565484, lng: -66.0710016 },
        { desc: 'Valle de Pancanta', lat: -32.9113057, lng: -66.0872063 },
        { desc: 'Centro de Salud 18 Valle de Pancanta', lat: -32.8979906, lng: -66.1105603 },
        { desc: 'La Carolina', lat: -32.8185007, lng: -66.0957608 }
    ];

    const kmInicio = 8.0;
    const kmFin = 79.0;


    let distanciasAcumuladas = [0];
    let distanciaTotalRutaMetros = 0;

    for (let i = 1; i < coordenadasRuta.length; i++) {
        const pAnterior = coordenadasRuta[i - 1];
        const pActual = coordenadasRuta[i];
        const dist = map.distance(L.latLng(pAnterior[1], pAnterior[0]), L.latLng(pActual[1], pActual[0]));
        distanciaTotalRutaMetros += dist;
        distanciasAcumuladas.push(distanciaTotalRutaMetros);
    }

    const nuevasReferencias = coordenadasRuta.map((coord, index) => {
        const kmExacto = kmInicio + ((distanciasAcumuladas[index] / distanciaTotalRutaMetros) * (kmFin - kmInicio));
        return {
            km: parseFloat(kmExacto.toFixed(2)),
            desc: 'Traza principal',
            lat: coord[1],
            lng: coord[0]
        };
    });


    hitosOriginales.forEach(hito => {
        let puntoMasCercano = null;
        let distanciaMinima = Infinity;


        nuevasReferencias.forEach(ref => {
            const dist = map.distance([hito.lat, hito.lng], [ref.lat, ref.lng]);
            if (dist < distanciaMinima) {
                distanciaMinima = dist;
                puntoMasCercano = ref;
            }
        });


        if (puntoMasCercano && distanciaMinima < 500) {
            puntoMasCercano.desc = hito.desc;
        }
    });


    console.log(JSON.stringify(nuevasReferencias, null, 2));
}

const calibrarRutaPorCurvas = () => {

    const coordenadasRuta = [
        [-66.2596378, -33.3039744], [-66.2575399, -33.3028488], [-66.2517099, -33.2994305], [-66.2474000, -33.2936754], [-66.2447753, -33.2917852], [-66.2429016, -33.2899865], [-66.2419404, -33.2893605], [-66.2362907, -33.2839886], [-66.2337810, -33.2828107], [-66.2251270, -33.2772967], [-66.2229570, -33.2759428], [-66.2222975, -33.2751606], [-66.2207873, -33.2744490], [-66.2183962, -33.2741117], [-66.2168694, -33.2746321], [-66.2156328, -33.2752692], [-66.2130840, -33.2750318], [-66.2119968, -33.2735457], [-66.2115001, -33.2722249], [-66.2101791, -33.2709984], [-66.2066197, -33.2698770], [-66.2036138, -33.2711648], [-66.2012375, -33.2714657], [-66.2006259, -33.2709067], [-66.1995541, -33.2704274], [-66.1978459, -33.2706217], [-66.1964652, -33.2714234], [-66.1940288, -33.2727363], [-66.1921863, -33.2725770], [-66.1887023, -33.2732907], [-66.1842140, -33.2741965], [-66.1808986, -33.2751313], [-66.1795281, -33.2740136], [-66.1791507, -33.2711448], [-66.1760090, -33.2687124], [-66.1723870, -33.2635906], [-66.1698745, -33.2624352], [-66.1645067, -33.2594697], [-66.1621810, -33.2563572], [-66.1619721, -33.2499929], [-66.1608910, -33.2477104], [-66.1459108, -33.2300955], [-66.14388, -33.22636], [-66.13257, -33.20589], [-66.1244046, -33.1983400], [-66.11478, -33.18715], [-66.1051560, -33.1787679], [-66.0887422, -33.1661177], [-66.0812551, -33.1619531], [-66.0795276, -33.1600436], [-66.0770048, -33.1565807], [-66.0715501, -33.1496383], [-66.0642264, -33.1410146], [-66.06260, -33.13690], [-66.0624963, -33.1325323], [-66.0609568, -33.1248799], [-66.0610843, -33.1219898], [-66.0611008, -33.1213422], [-66.0604079, -33.1201798], [-66.0605107, -33.1196585], [-66.0613285, -33.1178613], [-66.0614705, -33.1171848], [-66.06262, -33.11649], [-66.06324, -33.11569], [-66.0635141, -33.1149876], [-66.06373, -33.11379], [-66.06364, -33.11248], [-66.0646486, -33.1091054], [-66.0643479, -33.1074080], [-66.0631890, -33.1061113], [-66.0605432, -33.1038122], [-66.0585147, -33.1028936], [-66.0601888, -33.0998769], [-66.0622379, -33.0979415], [-66.0626355, -33.0953862], [-66.0645675, -33.0923955], [-66.06580, -33.09079], [-66.06631, -33.08990], [-66.0664104, -33.0883178], [-66.0680077, -33.0855129], [-66.06843, -33.08447], [-66.0691788, -33.0786977], [-66.06918, -33.07712], [-66.0680049, -33.0749733], [-66.0674971, -33.0710576], [-66.0678734, -33.0670907], [-66.06971, -33.06371], [-66.0699767, -33.0627368], [-66.0684753, -33.0581652], [-66.0690266, -33.0546500], [-66.07012, -33.05265], [-66.07096, -33.05195], [-66.07095, -33.05115], [-66.07041, -33.05036], [-66.0697089, -33.0442515], [-66.0706904, -33.0422382], [-66.0702730, -33.0406413], [-66.06916, -33.03990], [-66.0681261, -33.0386863], [-66.06778, -33.03855], [-66.0674159, -33.0388527], [-66.06712, -33.03965], [-66.06720, -33.04045], [-66.0669757, -33.0406402], [-66.06618, -33.04069], [-66.0658968, -33.0402920], [-66.0662384, -33.0389074], [-66.06770, -33.03715], [-66.06769, -33.03576], [-66.0678967, -33.0352404], [-66.06958, -33.03422], [-66.07070, -33.03200], [-66.0707888, -33.0311968], [-66.07084, -33.02907], [-66.07119, -33.02796], [-66.0704734, -33.0264113], [-66.0698387, -33.0234716], [-66.06967, -33.02103], [-66.0688351, -33.0189983], [-66.06871, -33.01744], [-66.0679975, -33.0168873], [-66.06628, -33.01652], [-66.0657242, -33.0158962], [-66.06550, -33.01379], [-66.0666696, -33.0111517], [-66.06734, -33.01010], [-66.0663599, -33.0078240], [-66.06338, -33.00742], [-66.0620328, -33.0065199], [-66.06185, -33.00497], [-66.06077, -33.00293], [-66.0606878, -33.0000713], [-66.0639746, -32.9943818], [-66.07285, -32.98898], [-66.0734201, -32.9885078], [-66.07353, -32.98745], [-66.0724823, -32.9836892], [-66.0731135, -32.9834894], [-66.0742801, -32.9847916], [-66.0751414, -32.9840707], [-66.0732224, -32.9814666], [-66.0720435, -32.9756389], [-66.0751253, -32.9712397], [-66.0747327, -32.9706417], [-66.0713072, -32.9715782], [-66.0725387, -32.9679049], [-66.0732252, -32.9662906], [-66.0727819, -32.9609896], [-66.0711841, -32.9577465], [-66.0710016, -32.9565484], [-66.0722253, -32.9514609], [-66.0759940, -32.9483868], [-66.0749848, -32.9452098], [-66.0774082, -32.9424697], [-66.0789134, -32.9399731], [-66.0818890, -32.9397215], [-66.0835618, -32.9360928], [-66.0865393, -32.9354632], [-66.0858848, -32.9325892], [-66.0844561, -32.9284567], [-66.0865427, -32.9253088], [-66.0863847, -32.9230552], [-66.0875796, -32.9205065], [-66.0851702, -32.9127853], [-66.0872063, -32.9113057], [-66.0951406, -32.9089215], [-66.1007895, -32.9059158], [-66.1034052, -32.9032476], [-66.1131051, -32.8961549], [-66.1132357, -32.8891798], [-66.1134376, -32.8821211], [-66.1148482, -32.8737703], [-66.1141178, -32.8673710], [-66.1119368, -32.8597243], [-66.1099224, -32.8544387], [-66.1099802, -32.8523368], [-66.1067542, -32.8515273], [-66.1057992, -32.8461203], [-66.1063789, -32.8396561], [-66.0991251, -32.8271710], [-66.0991765, -32.8249498], [-66.0992505, -32.8232993], [-66.0959797, -32.8194888], [-66.0957608, -32.8185007]
    ];

    const kmInicio = 8.0;   // Rotonda Rotary Club
    const kmFin = 79.0;     // La Carolina


    let distanciasAcumuladas = [0];
    let distanciaTotalRutaMetros = 0;

    for (let i = 1; i < coordenadasRuta.length; i++) {
        const pAnterior = coordenadasRuta[i - 1];
        const pActual = coordenadasRuta[i];

        const distanciaSegmento = map.distance(
            L.latLng(pAnterior[1], pAnterior[0]),
            L.latLng(pActual[1], pActual[0])
        );

        distanciaTotalRutaMetros += distanciaSegmento;
        distanciasAcumuladas.push(distanciaTotalRutaMetros);
    }


    const rangoKm = kmFin - kmInicio;

    const nuevasReferencias = coordenadasRuta.map((coord, index) => {

        const porcentajeRecorrido = distanciasAcumuladas[index] / distanciaTotalRutaMetros;
        const kmExacto = kmInicio + (porcentajeRecorrido * rangoKm);

        return {
            km: parseFloat(kmExacto.toFixed(2)),
            desc: 'Traza principal',
            lat: coord[1],
            lng: coord[0]
        };
    });



}


const peajes = ref([])


const cargarPeajesBase = async () => {
    try {
        const respuesta = await axios.get('/api/tolls', {
            params: { per_page: 50 }
        })
        peajes.value = respuesta.data.data
    } catch (error) {
        console.error('Error al cargar peajes para el mapa:', error)
    }
}


const enfocarPeaje = (nombrePeaje) => {
    if (!nombrePeaje || !map) return;

    const targetMarker = marcadoresPeajesGenerados[nombrePeaje.toLowerCase()];

    if (targetMarker) {

        map.flyTo(targetMarker.getLatLng(), 15, {
            animate: true,
            duration: 1.5
        });


        targetMarker.openPopup();
    }
}


// DEBUGGGINNNGG

const renderizarPuntosDeReferencia = () => {

    Object.entries(referenciasViales).forEach(([rutaId, puntos]) => {
        puntos.forEach((punto) => {

            const dot = L.circleMarker([punto.lat, punto.lng], {
                radius: 5,
                fillColor: "#3b82f6", //  brillantee
                color: "#ffffff",     //  blanco
                weight: 2,
                opacity: 1,
                fillOpacity: 1
            }).addTo(map);


            dot.bindPopup(`
                <div class="font-['Barlow_Condensed'] text-sm tracking-wide">
                    <div class="text-blue-500 font-bold uppercase mb-1">Referencia Calibrada</div>
                    <strong>Ruta ${rutaId}</strong><br>
                    KM Registrado: <span class="text-amber-600 font-black text-lg">${punto.km}</span><br>
                    <div class="text-xs text-slate-500 mt-1">${punto.desc}</div>
                </div>
            `);
        });
    });
}

const renderizarPuntosDeControl = () => {
    trazasVialesGeoJSON.features.forEach(feature => {
        if (feature.geometry && feature.geometry.type === 'LineString') {
            const rutaNombre = feature.properties.nombre;

            feature.geometry.coordinates.forEach((coord, index) => {

                const lng = coord[0];
                const lat = coord[1];

                const dot = L.circleMarker([lat, lng], {
                    radius: 4,
                    fillColor: "#ef4444", // Rojo brillante
                    color: "#ffffff",     // Borde blanco
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 1
                }).addTo(map);


                dot.bindPopup(`
                    <div class="font-['Barlow_Condensed'] text-sm tracking-wide">
                        <div class="text-red-500 font-bold uppercase mb-1">Vértice Real</div>
                        <strong>${rutaNombre}</strong><br>
                        Índice en el arreglo: <span class="text-amber-600 font-black text-lg">${index}</span><br>
                        <div class="text-xs text-slate-500 mt-2 font-mono">
                            [${lng}, ${lat}]
                        </div>
                    </div>
                `);
            });
        }
    });
}


const renderizarTrazasEstaticas = () => {

    const geoJsonSuavizado = JSON.parse(JSON.stringify(trazasVialesGeoJSON));


    geoJsonSuavizado.features.forEach(feature => {
        if (feature.geometry && feature.geometry.type === 'LineString') {
            feature.geometry.coordinates = suavizarCoordenadas(feature.geometry.coordinates, 3);
        }
    });


    L.geoJSON(geoJsonSuavizado, {
        style: (feature) => ({ color: feature.properties.color, weight: 8, opacity: 0.6, lineCap: 'round', lineJoin: 'round' }),

        onEachFeature: (feature, layer) => {


            layer.on('mouseover', () => {
                layer.setStyle({ opacity: 0.9, weight: 10 });
                L.DomUtil.addClass(layer._path, 'cursor-pointer');
            });
            layer.on('mouseout', () => {
                layer.setStyle({ opacity: 0.6, weight: 8 });
            });


            layer.on('click', (e) => {
                L.DomEvent.stopPropagation(e);

                let rutaId = feature.properties.id;
                if (!rutaId && feature.properties.nombre) {
                    const coincidencia = feature.properties.nombre.match(/\d+/);
                    rutaId = coincidencia ? coincidencia[0] : null;
                }

                const km = obtenerKmDesdeCoordenadas(rutaId, e.latlng.lat, e.latlng.lng);

                const popupHtml = `
                    <div class="font-['Barlow_Condensed'] min-w-[200px] p-1 transition-colors">
                        <div class="text-[10px] text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mb-1 font-bold">
                            Referencia Vial
                        </div>
                        <strong class="text-base uppercase text-slate-800 dark:text-slate-100 block leading-tight tracking-wide">
                            ${feature.properties.nombre}
                        </strong>
                        <div class="mt-3 p-3 bg-amber-500/5 dark:bg-amber-500/10 border border-slate-200 dark:border-amber-500/20 rounded-lg flex items-center justify-between shadow-sm">
                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                Ubicación
                            </span>
                            <span class="font-black text-xl text-amber-600 dark:text-amber-400">
                                ${km ? 'KM ' + km : 'Fuera de rango'}
                            </span>
                        </div>
                        <div class="mt-2 text-[12px] text-slate-400 dark:text-slate-500 italic text-right">
                            Detección automática por coordenadas
                        </div>
                    </div>
                `;

                L.popup().setLatLng(e.latlng).setContent(popupHtml).openOn(map);
            });


            if (feature.properties.peajes) {
                feature.properties.peajes.forEach(peajeGeo => {

                    const peajeDB = peajes.value.find(p => {
                        const nombreDB = p.name.toLowerCase();
                        const nombreGeo = peajeGeo.nombre.toLowerCase();

                        if (nombreGeo.includes('desaguadero') && nombreDB.includes('desaguadero')) {
                            const geoOeste = /\boeste\b/.test(nombreGeo);
                            const dbOeste = /\boeste\b/.test(nombreDB);
                            const geoEste = /\beste\b/.test(nombreGeo);
                            const dbEste = /\beste\b/.test(nombreDB);

                            if (geoOeste && dbOeste) return true;
                            if (geoEste && dbEste) return true;

                            return false;
                        }

                        if (nombreDB.includes(nombreGeo) || nombreGeo.includes(nombreDB)) return true;
                        return false;
                    });

                    const imagenDinamica = (peajeDB && peajeDB.image_path)
                        ? peajeDB.image_path
                        : `/data/600x400.svg`;

                    const iconPeaje = L.divIcon({
                        className: 'bg-transparent border-none',
                        html: `
    <div class="w-10 h-10 bg-white dark:bg-slate-800 rounded 
                border-2 shadow-lg flex items-center justify-center 
                relative -left-4.5 -top-4.5"
         style="border-color:${feature.properties.color}; color:${feature.properties.color};">

        <svg viewBox="0 0 64 80" width="30" height="30" 
             fill="currentColor" xmlns="http://www.w3.org/2000/svg">

            <path d="M54,45H38a1,1,0,0,0-1,1v8a1,1,0,0,0,1,1H54a1,1,0,0,0,1-1V46A1,1,0,0,0,54,45Zm-1,8H39V47H53Z"/><rect x="41" y="49" width="10" height="2"/><path d="M54,23H38a1,1,0,0,0-1,1V42a1,1,0,0,0,1,1H54a1,1,0,0,0,1-1V24A1,1,0,0,0,54,23ZM49,41H43V37a3,3,0,0,1,6,0Zm4,0H51V37a5,5,0,0,0-10,0v4H39V25H53Z"/><path d="M61,61V58a1,1,0,0,0-1-1H59V21h1a1,1,0,0,0,.88-.53,1,1,0,0,0-.05-1l-4-6A1,1,0,0,0,56,13H36a1,1,0,0,0-.83.45l-4,6a1,1,0,0,0-.05,1A1,1,0,0,0,32,21h1V36H7a4,4,0,0,0,0,8H33V57H32a1,1,0,0,0-1,1v3H11V50a4,4,0,0,0-8,0V61H1v2H63V61ZM36.54,15H55.46l2.67,4H33.87ZM19.41,38h3.18l-4,4H15.41Zm-6.82,4H9.41l4-4h3.18Zm12.82-4h3.18l-4,4H21.41ZM5,40a2,2,0,0,1,2-2h3.59l-4,4A2,2,0,0,1,5,40Zm22.41,2,4-4H33v4ZM35,21H57V57H35ZM33,59H59v2H33ZM7,48a2,2,0,0,1,2,2v5H5V50A2,2,0,0,1,7,48ZM5,57H9v4H5Z"/><path d="M16,19a2,2,0,0,1-2-2H12a4,4,0,0,0,3,3.86V23h2V20.86A4,4,0,0,0,16,13a2,2,0,1,1,2-2h2a4,4,0,0,0-3-3.86V5H15V7.14A4,4,0,0,0,16,15a2,2,0,0,1,0,4Z"/><path d="M16,27A13,13,0,1,0,3,14,13,13,0,0,0,16,27ZM16,3A11,11,0,1,1,5,14,11,11,0,0,1,16,3Z"/>
            
        </svg>
    </div>`,
                        iconSize: [36, 36]
                    });

                    const marcadorPeaje = L.marker([peajeGeo.lat, peajeGeo.lng], { icon: iconPeaje })
                        .addTo(map)
                        .bindPopup(`
                            <div class="bg-white dark:bg-[#0d1b2a] p-1 rounded-lg shadow-xl border border-slate-200 dark:border-white/10 transition-colors">
                                <div class="mb-3 rounded overflow-hidden border border-slate-100 dark:border-slate-800">
                                    <img src="${imagenDinamica}" 
                                         alt="Fachada ${peajeGeo.nombre}" 
                                         class="w-full h-60 object-cover transition-transform duration-500" 
                                         onerror="this.src='/data/600x400.svg'" />
                                </div>
                                <div class="p-2">
                                    <strong class="font-['Barlow_Condensed'] text-[18px] tracking-wide border-b border-slate-100 dark:border-white/5 pb-2 mb-2 block text-slate-800 dark:text-slate-100 uppercase">
                                        ${peajeDB ? peajeDB.name : peajeGeo.nombre}
                                    </strong>
                                    
                                    <div class="flex items-center gap-2 ">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                        <div class="text-[12px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Operativo
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `, {
                            minWidth: 320,
                            maxWidth: 350,
                            className: 'custom-popup-peaje'
                        });

                    if (peajeDB) marcadoresPeajesGenerados[peajeDB.name.toLowerCase()] = marcadorPeaje;
                    marcadoresPeajesGenerados[peajeGeo.nombre.toLowerCase()] = marcadorPeaje;

                });
            }
        }
    }).addTo(map);
}

const cargarPuntosGuardados = async () => {
    try {
        const respuesta = await axios.get('/api/incidents')
        const sucesos = respuesta.data.data

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
    map = L.map(mapContainer.value, {


        zoomControl: false,
        maxBounds: sanLuisBounds,
        maxBoundsViscosity: 1.0,
        minZoom: 8.2,
        maxZoom: 18




    }).setView([-33.3017, -66.3378], 8)
    const isDark = document.documentElement.classList.contains('dark')

    tileLayer = L.tileLayer(isDark ? darkTile : lightTile, {
        attribution: '&copy; OpenStreetMap contributors &copy; CARTO', subdomains: 'abcd', maxZoom: 19
    }).addTo(map)

    map.attributionControl.addAttribution(
        'Icono peaje: Start Up Graphic Design / Noun Project'
    );

    L.control.zoom({ position: 'bottomright' }).addTo(map)

    dibujarLimitesProvinciales()

    renderizarTrazasEstaticas()
    // renderizarPuntosDeControl() //debugg
    //  renderizarPuntosDeReferencia() //debugg
    cargarPuntosGuardados()
    //  calibrarRutaInteligente() //debugg
 
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
                actualizarCapaMapa();
            }
        });
    });
    observer.observe(document.documentElement, { attributes: true });
}


const calcularCoordenadaAproximada = (rutaId, kmBuscado) => {

    const puntosConCoordenadas = referenciasViales[rutaId].filter(p => p.lat && p.lng);


    if (puntosConCoordenadas.length === 0) {
        let lat = -33.30, lng = -66.33;
        if (rutaId === '9') { lat = -33.27 + (kmBuscado * 0.006); lng = -66.20 + (kmBuscado * 0.004); }
        else if (rutaId === '20') { lat = -33.25 + (kmBuscado * 0.008); lng = -66.22 - (kmBuscado * 0.005); }
        return [lat, lng];
    }


    puntosConCoordenadas.sort((a, b) => a.km - b.km);


    const exacto = puntosConCoordenadas.find(p => p.km === kmBuscado);
    if (exacto) return [exacto.lat, exacto.lng];


    if (kmBuscado < puntosConCoordenadas[0].km) {
        return [puntosConCoordenadas[0].lat, puntosConCoordenadas[0].lng];
    }


    const ultimoPunto = puntosConCoordenadas[puntosConCoordenadas.length - 1];
    if (kmBuscado > ultimoPunto.km) {
        return [ultimoPunto.lat, ultimoPunto.lng];
    }


    let puntoA = puntosConCoordenadas[0];
    let puntoB = puntosConCoordenadas[1];

    for (let i = 0; i < puntosConCoordenadas.length - 1; i++) {
        if (kmBuscado > puntosConCoordenadas[i].km && kmBuscado < puntosConCoordenadas[i + 1].km) {
            puntoA = puntosConCoordenadas[i];
            puntoB = puntosConCoordenadas[i + 1];
            break;
        }
    }


    const porcentajeRecorrido = (kmBuscado - puntoA.km) / (puntoB.km - puntoA.km);


    const latCalculada = puntoA.lat + ((puntoB.lat - puntoA.lat) * porcentajeRecorrido);
    const lngCalculada = puntoA.lng + ((puntoB.lng - puntoA.lng) * porcentajeRecorrido);

    return [latCalculada, lngCalculada];
}




const limitesRuta = computed(() => {
    if (!searchRuta.value || !referenciasViales[searchRuta.value]) {
        return { min: 0, max: 1000 };
    }
    const puntos = referenciasViales[searchRuta.value];

    const min = puntos[0].km;
    const max = puntos[puntos.length - 1].km;
    return { min, max };
});


const buscarKilometro = () => {

    if (!searchRuta.value || searchKm.value === '') {
        toast.warning('Debe ingresar un kilómetro válido.')
        return
    }

    const puntos = referenciasViales[searchRuta.value]
    const km = parseFloat(searchKm.value)
    const { min, max } = limitesRuta.value;

    if (km < min || km > max) {
        toast.warning(`El kilómetro debe estar comprendido entre ${min} y ${max} para esta traza.`);
        return;
    }


    const exacto = puntos.find(p => p.km === km && p.desc !== 'Traza principal')

    if (exacto) {
        resultadoBusqueda.value = { tipo: 'exacto', punto: exacto }
        toast.info('Punto kilométrico exacto encontrado.')
    } else {

        const puntosReales = puntos.filter(p => p.desc !== 'Traza principal')


        puntosReales.sort((a, b) => a.km - b.km)


        const anterior = puntosReales.slice().reverse().find(p => p.km <= km) || puntosReales[0]


        const posterior = puntosReales.find(p => p.km >= km) || puntosReales[puntosReales.length - 1]

        resultadoBusqueda.value = {
            tipo: 'aproximado',
            anterior: anterior,
            posterior: posterior
        }
    }

    marcadoresBusqueda.forEach(m => map.removeLayer(m))
    marcadoresBusqueda.length = 0

    const [mockLat, mockLng] = calcularCoordenadaAproximada(searchRuta.value, km)

    const dotKm = L.circleMarker([mockLat, mockLng], {
        radius: 8, fillColor: "#ef4444", color: "#ffffff", weight: 3, opacity: 1, fillOpacity: 0.9
    }).addTo(map)

    dotKm.bindPopup(`
        <div class="font-[Barlow_Condensed] text-sm tracking-wide font-bold border-b border-slate-200 dark:border-white/10 pb-1 mb-1 text-red-600 dark:text-red-400">
            Marcador Kilométrico (Aprox)
        </div>
        <div class="text-[11px] text-slate-600 dark:text-slate-400 uppercase tracking-widest font-bold mb-1">
            Ruta ${searchRuta.value === '7' ? 'Nac. 7' : (searchRuta.value === '9' ? 'Prov. 9' : 'Prov. 20')} - Km ${km}
        </div>
        <div class="text-[10px] text-slate-500 italic">Coordenada temporal pendiente de relevamiento.</div>
    `).openPopup()

    map.flyTo([mockLat, mockLng], 12, { duration: 1.5 })

    marcadoresBusqueda.push(dotKm)
}

const limpiarBusqueda = () => {
    searchKm.value = ''
    resultadoBusqueda.value = null
    marcadoresBusqueda.forEach(m => map.removeLayer(m))
    marcadoresBusqueda.length = 0
    map.setView([-33.3017, -66.3378], 8)
}


const obtenerKmDesdeCoordenadas = (rutaId, lat, lng) => {

    const puntos = referenciasViales[rutaId];
    if (!puntos || puntos.length < 2) {
        console.warn(`ID de ruta "${rutaId}" no encontrado en referenciasViales.`);
        return null;
    }

    let mejorKm = null;
    let distanciaMinima = Infinity;

    for (let i = 0; i < puntos.length - 1; i++) {
        const p1 = puntos[i];
        const p2 = puntos[i + 1];

        const dx = p2.lng - p1.lng;
        const dy = p2.lat - p1.lat;
        if (dx === 0 && dy === 0) continue;

        let t = ((lng - p1.lng) * dx + (lat - p1.lat) * dy) / (dx * dx + dy * dy);
        t = Math.max(0, Math.min(1, t));


        const proyLat = p1.lat + t * dy;
        const proyLng = p1.lng + t * dx;


        const dist = Math.sqrt(Math.pow(lat - proyLat, 2) + Math.pow(lng - proyLng, 2));

        if (dist < distanciaMinima) {
            distanciaMinima = dist;
            mejorKm = p1.km + t * (p2.km - p1.km);
        }
    }

    return (distanciaMinima < 0.008) ? mejorKm.toFixed(1) : null;
};

const panelHerramientasAbierto = ref(false)


const confirmarPunto = async () => {
    if (!puntoFormulario.tipo) return;
    guardando.value = true;
    try {
        const payload = {
            toll_id: 1,
            incident_type: puntoFormulario.tipo,

            dynamic_data: JSON.stringify({
                latitud: puntoFormulario.lat,
                longitud: puntoFormulario.lng,
                observaciones_mapa: puntoFormulario.observaciones
            })
        }
        await axios.post('/api/incidents', payload)
        toast.success('Punto georreferenciado guardado.')

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
onMounted(async () => {
    await cargarPeajesBase();
    initMap();

    if (route.query.focus) {
        setTimeout(() => {
            enfocarPeaje(route.query.focus)
        }, 500);
    }

})

watch(() => route.query.focus, (nuevoFoco) => {
    if (nuevoFoco) {
        enfocarPeaje(nuevoFoco);
    }
})

onBeforeUnmount(() => { if (map) { map.remove() } })

</script>
<template>
    <div class="relative w-full h-[calc(100vh-56px)] flex overflow-hidden">

        <div v-if="panelHerramientasAbierto" @click="panelHerramientasAbierto = false"
            class="absolute inset-0 bg-black/60 backdrop-blur-sm z-[1001] md:hidden transition-opacity">
        </div>

        <div :class="[
            'w-80 bg-white dark:bg-[#0d1b2a] border-r border-slate-200 dark:border-white/10 z-[1002] flex flex-col shadow-[4px_0_24px_rgba(0,0,0,0.05)] transition-transform duration-300 ease-in-out',
            'absolute inset-y-0 left-0 md:relative md:translate-x-0',
            panelHerramientasAbierto ? 'translate-x-0' : '-translate-x-full'
        ]">
            <div class="p-5 border-b border-slate-100 dark:border-white/5 flex items-start justify-between">
                <div>
                    <h3
                        class="font-['Barlow_Condensed'] text-[22px] font-extrabold text-slate-900 dark:text-slate-100 m-0 leading-none">
                        Herramientas SIG
                    </h3>
                    <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1 m-0">Sistema de información geográfica
                    </p>
                </div>
                <button @click="panelHerramientasAbierto = false"
                    class="md:hidden text-slate-400 hover:text-amber-500 bg-transparent border-none p-1 cursor-pointer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="p-5 flex-1 overflow-y-auto">
                <div class="mb-6">
                    <h4
                        class="font-['Barlow_Condensed'] text-[13px] font-bold tracking-widest uppercase text-amber-600 dark:text-amber-500 mb-4 border-b border-amber-500/20 pb-2">
                        Localizador por progresiva
                    </h4>

                    <form @submit.prevent="buscarKilometro" class="space-y-4">
                        <div>
                            <label
                                class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Corredor
                                Vial</label>
                            <select v-model="searchRuta"
                                class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50">
                                <option value="20">Ruta Provincial 20</option>
                                <option value="9">Ruta Prov. 9 (Autopista Puquios)</option>
                                <option value="7">Ruta Nac. 7 (Autopista Serranías Puntanas)</option>
                                <option value="30">Ruta Provincial 30</option>
                            </select>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label
                                    class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block">Kilómetro
                                    exacto</label>
                                <span
                                    class="text-[9px] font-bold text-amber-600 dark:text-amber-500 bg-amber-50 dark:bg-amber-500/10 px-1.5 py-0.5 rounded tracking-widest">
                                    RANGO: {{ limitesRuta.min }} a {{ limitesRuta.max }}
                                </span>
                            </div>
                            <input v-model="searchKm" type="number" step="0.1" :min="limitesRuta.min"
                                :max="limitesRuta.max" :placeholder="`Ej: ${limitesRuta.min + 2.5}`" required
                                class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded px-3 py-2 text-slate-900 dark:text-white text-xs outline-none focus:border-amber-500/50 transition-colors" />
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="limpiarBusqueda"
                                class="w-1/3 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-white/10 font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-2 py-2.5 rounded border border-slate-200 dark:border-white/10 cursor-pointer transition-all">Limpiar</button>
                            <button type="submit"
                                class="w-2/3 bg-slate-800 dark:bg-white/10 text-white hover:bg-slate-700 dark:hover:bg-white/20 font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-4 py-2.5 rounded border-none cursor-pointer transition-all">Localizar
                                Punto</button>
                        </div>
                    </form>
                </div>

                <div v-if="resultadoBusqueda"
                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded p-4 shadow-sm transition-colors mb-6">
                    <h4
                        class="font-['Barlow_Condensed'] text-[13px] font-bold text-blue-800 dark:text-blue-400 uppercase tracking-widest mb-3 border-b border-blue-200 dark:border-blue-800/50 pb-2">
                        Resultado</h4>

                    <div v-if="resultadoBusqueda.tipo === 'exacto'" class="text-sm">
                        <div class="text-slate-600 dark:text-slate-300 mb-1 text-[11px] uppercase tracking-widest">
                            Coincidencia en Km {{ resultadoBusqueda.punto.km }}:</div>
                        <strong class="text-blue-900 dark:text-blue-300 block text-sm font-['DM_Sans']">{{
                            resultadoBusqueda.punto.desc }}</strong>
                    </div>

                    <div v-else class="text-sm">
                        <div class="text-slate-600 dark:text-slate-300 mb-3 leading-relaxed text-xs">El Km {{ searchKm
                        }} se encuentra en el tramo comprendido entre:</div>
                        <ul class="list-none p-0 m-0 space-y-3 relative">
                            <li class="flex items-start gap-2 relative z-10">
                                <span
                                    class="bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm">Km
                                    {{ resultadoBusqueda.anterior.km }}</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{
                                    resultadoBusqueda.anterior.desc }}</span>
                            </li>
                            <li
                                class="pl-4 border-l-2 border-dashed border-blue-300 dark:border-blue-700/50 py-1.5 text-[10px] text-blue-600 dark:text-blue-400 font-bold tracking-widest uppercase ml-3">
                                Punto reportado</li>
                            <li class="flex items-start gap-2 relative z-10">
                                <span
                                    class="bg-blue-200 dark:bg-blue-800 text-blue-800 dark:text-blue-200 text-[10px] font-bold px-1.5 py-0.5 rounded shadow-sm">Km
                                    {{ resultadoBusqueda.posterior.km }}</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 text-xs">{{
                                    resultadoBusqueda.posterior.desc }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div>
                    <h4
                        class="font-['Barlow_Condensed'] text-[13px] font-bold tracking-widest uppercase text-amber-600 dark:text-amber-500 mb-4 border-b border-amber-500/20 pb-2">
                        Control georreferenciado</h4>
                    <div
                        class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-500/20 rounded p-3 text-xs text-amber-800 dark:text-amber-300 leading-relaxed mb-3">
                        <strong>Trazas activas:</strong> El mapa despliega la jurisdicción operativa de las estaciones
                        base.
                    </div>
                    <div
                        class="bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded p-3 text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Hacé clic en el mapa para iniciar un suceso. Seleccioná "Quitar" para borrar la coordenada
                        temporal.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 relative w-full h-full">

            <button @click="panelHerramientasAbierto = true"
                class="md:hidden absolute top-4 left-4 z-[400] bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 p-2.5 rounded-md shadow-md border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center justify-center">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="11" y1="8" x2="11" y2="14"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                </svg>
            </button>

            <div class="absolute top-4 right-4 z-[400]">
                <button @click="menuMapasAbierto = !menuMapasAbierto"
                    class="bg-white dark:bg-[#0d1b2a] text-slate-700 dark:text-slate-200 px-3 py-2 rounded-md shadow-md border border-slate-200 dark:border-white/10 font-['Barlow_Condensed'] text-xs font-bold uppercase tracking-wider cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 transition-colors flex items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"></polygon>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                    <span class="hidden sm:inline">Capas Base</span>
                    <span class="sm:hidden">Capas</span>
                </button>

                <div v-if="menuMapasAbierto"
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#0d1b2a] rounded-md shadow-[0_10px_25px_rgba(0,0,0,0.15)] border border-slate-200 dark:border-white/10 overflow-hidden font-['Barlow_Condensed'] transition-all">

                    <button v-for="(capa, key) in capasDisponibles" :key="key" @click="seleccionarCapa(key)" :class="[
                        'w-full text-left px-4 py-3 text-xs font-bold tracking-wider uppercase transition-colors flex items-center gap-3 border-b border-slate-100 dark:border-white/5 last:border-0 cursor-pointer',
                        tipoMapa === key
                            ? 'bg-amber-500 text-[#0d1b2a]'
                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'
                    ]">
                        <div :class="[
                            'w-2 h-2 rounded-full shadow-inner',
                            tipoMapa === key ? 'bg-[#0d1b2a]' : 'bg-transparent border border-slate-400 dark:border-slate-500'
                        ]"></div>

                        {{ capa.nombre }}
                    </button>
                </div>
            </div>

            <div ref="mapContainer" class="w-full h-full z-[100]"></div>
        </div>

        <div v-if="mostrarModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-opacity px-4">
            <div
                class="w-full max-w-sm bg-white dark:bg-[#0d1b2a] border border-slate-200 dark:border-white/10 shadow-2xl overflow-hidden relative">
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-1"></div>
                <div class="px-5 py-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <h3
                        class="font-['Barlow_Condensed'] text-[18px] font-bold text-slate-900 dark:text-slate-100 tracking-wide m-0">
                        Clasificar coordenada</h3>
                </div>

                <form @submit.prevent="confirmarPunto" class="p-5">
                    <div class="mb-4">
                        <label
                            class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Tipo
                            de Evento</label>
                        <select v-model="puntoFormulario.tipo" required
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors focus:border-amber-500/50">
                            <option value="" disabled>— Seleccionar clasificación —</option>
                            <option value="accidente_vial">Accidente</option>
                            <option value="animal_ruta">Animal en ruta</option>
                            <option value="corte_ruta">Corte de ruta</option>
                            <option value="falla_infraestructura">Falla en infraestructura</option>
                        </select>
                    </div>

                    <div class="mb-5">
                        <label
                            class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Observaciones
                            adicionales</label>
                        <textarea v-model="puntoFormulario.observaciones" rows="3"
                            placeholder="Detalles de la ubicación o gravedad..."
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded px-3 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors focus:border-amber-500/50 resize-none"></textarea>
                    </div>

                    <div class="flex gap-3 justify-end pt-2">
                        <button type="button" @click="cerrarModal"
                            class="px-4 py-2 rounded text-xs font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/5 border-none bg-transparent cursor-pointer font-['Barlow_Condensed'] uppercase tracking-wider">Cancelar</button>
                        <button type="submit" :disabled="guardando"
                            class="bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-xs font-bold tracking-wider uppercase px-5 py-2 rounded border-none cursor-pointer inline-flex items-center transition-all hover:bg-amber-400 disabled:opacity-50">
                            {{ guardando ? 'Registrando...' : 'Confirmar Punto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>