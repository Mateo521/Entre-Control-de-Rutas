<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { toast } from 'vue3-toastify'

const router = useRouter()


const email = ref('')
const password = ref('')
const cargando = ref(false)
const errorMensaje = ref('')

const indiceImagenActual = ref(0)
let intervaloFondo = null


const imagenesPeajes = [
    '/img/peajes/cruz-piedra.jpeg',
    '/img/peajes/perilago.jpeg',
    '/img/peajes/la-cumbre.jpeg',
    '/img/peajes/ruta-30.jpeg',
    '/img/peajes/desaguadero-e.jpeg',
    '/img/peajes/desaguadero-o.jpeg',
    '/img/peajes/puquios.jpeg'
]

const iniciarRotacionFondo = () => {
    intervaloFondo = setInterval(() => {
        indiceImagenActual.value = (indiceImagenActual.value + 1) % imagenesPeajes.length
    }, 6000)
}


const handleLogin = async () => {
    cargando.value = true
    errorMensaje.value = ''

    try {
        const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value
        })

        localStorage.setItem('access_token', response.data.access_token)

        toast.success('Ingreso exitoso')

        router.push('/panel')

    } catch (error) {
        if (error.response) {
            if (error.response.status === 422) {
                toast.warning('Las credenciales son incorrectas.')
            } else {
                toast.error(error.response.data.message || 'Error interno del servidor.')
            }
        } else {
            toast.error('No hay conexión con el back')
        }
    } finally {
        cargando.value = false
    }
}

onMounted(() => {
    iniciarRotacionFondo()
})

onUnmounted(() => {
    if (intervaloFondo) clearInterval(intervaloFondo)
})
</script>

<template>
    <div class="flex min-h-screen w-full overflow-hidden bg-slate-950">
        
        <div class="w-full md:w-[450px] flex flex-col justify-center bg-white dark:bg-[#0d1b2a] z-20 shadow-2xl relative border-r border-white/5">
            
            <div class="absolute top-0 left-0 w-full bg-gradient-to-r from-amber-500 to-orange-600 h-1.5"></div>

            <div class="px-8 md:px-12 py-10">
                <div class="mb-10 text-left">
                    <h1 class="font-['Barlow_Condensed'] text-[32px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-1 tracking-tight uppercase">
                        Sistema ECR
                    </h1>
                    <p class="font-['DM_Sans'] text-[11px] text-slate-500 dark:text-slate-400 m-0 tracking-[0.2em] font-bold uppercase border-l-2 border-amber-500 pl-3">
                        Ente Control de Rutas <br> Región centro
                    </p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-6">
                    <div v-if="errorMensaje"
                        class="bg-red-500/10 border border-red-500/50 text-red-600 dark:text-red-400 px-4 py-3 text-xs font-semibold">
                        {{ errorMensaje }}
                    </div>

                    <div>
                        <span class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-widest uppercase text-slate-500 dark:text-slate-400 block mb-2">Usuario de acceso</span>
                        <div class="relative group">
                            <input v-model="email"
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 px-4 py-3 text-slate-900 dark:text-white text-sm outline-none transition-all focus:border-amber-500/50 focus:bg-white dark:focus:bg-black/40"
                                placeholder="usuario@correo.com" type="email" required />
                            <svg class="absolute right-4 top-1/2 -translate-y-1/2 opacity-30 text-slate-500 group-focus-within:opacity-100 group-focus-within:text-amber-500 transition-opacity" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>
                    </div>

                    <div>
                        <span class="font-['Barlow_Condensed'] text-[11px] font-bold tracking-widest uppercase text-slate-500 dark:text-slate-400 block mb-2">Contraseña</span>
                        <div class="relative group">
                            <input v-model="password"
                                class="w-full bg-slate-50 dark:bg-black/20 border border-slate-200 dark:border-white/10 px-4 py-3 text-slate-900 dark:text-white text-sm outline-none transition-all focus:border-amber-500/50 focus:bg-white dark:focus:bg-black/40"
                                type="password" required />
                            <svg class="absolute right-4 top-1/2 -translate-y-1/2 opacity-30 text-slate-500 group-focus-within:opacity-100 group-focus-within:text-amber-500 transition-opacity" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" />
                                <path d="M7 11V7a5 5 0 0110 0v4" />
                            </svg>
                        </div>
                    </div>

                    <button type="submit" :disabled="cargando"
                        class="w-full justify-center bg-amber-500 hover:bg-amber-600 text-[#0d1b2a] font-['Barlow_Condensed'] text-base font-bold tracking-wider uppercase px-6 py-4 border-none cursor-pointer inline-flex items-center gap-3 transition-all disabled:opacity-50 shadow-xl shadow-amber-500/10 mt-4">
                        <span v-if="cargando">Autenticando...</span>
                        <span v-else class="inline-flex items-center gap-2">
                            Ingresar
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </span>
                    </button>
                </form>

                <div class="mt-16 pt-8 border-t border-slate-100 dark:border-white/5">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-medium tracking-tighter">
                        &copy; 2026 ENTE CONTROL DE RUTAS. 
                    </p>
                </div>
            </div>
        </div>

        <div class="hidden md:block flex-1 relative bg-slate-900">
            
            <div class="absolute inset-0 z-0">
                <div v-for="(img, index) in imagenesPeajes" :key="index"
                    class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                    :class="indiceImagenActual === index ? 'opacity-100' : 'opacity-0'">
                    <img :src="img" class="w-full h-full object-cover scale-110"
                        :class="indiceImagenActual === index ? 'animate-ken-burns' : ''" alt="Fondo estación de peaje" />
                </div>
            </div>

            <div class="absolute inset-0 z-10 bg-gradient-to-r from-[#0d1b2a] via-transparent to-transparent opacity-60"></div>
            <div class="absolute inset-0 z-10 bg-gradient-to-br from-amber-600/10 via-transparent to-black/40"></div>

            <div class="absolute bottom-10 right-10 z-20 text-right">
                <div class="bg-black/20 backdrop-blur-md border border-white/10 px-4 py-2">
                   <p class="text-white font-['Barlow_Condensed'] text-xs font-bold  uppercase m-0">Infraestructura vial</p>
                   <p class="text-amber-500 font-['Barlow_Condensed'] text-[10px] font-bold uppercase m-0">San Luis, Argentina</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes kenburns {
    0% {
        transform: scale(1.1) translate(0, 0);
    }

    100% {
        transform: scale(1.2) translate(-1%, -1%);
    }
}

.animate-ken-burns {
    animation: kenburns 12s ease-out forwards;
}
</style>