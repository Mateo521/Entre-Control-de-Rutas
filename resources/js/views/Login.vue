<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()

const email = ref('admin@enterutas.gov.ar')
const password = ref('Admin1234!')
const errorMensaje = ref('')
const cargando = ref(false)

const handleLogin = async () => {
    cargando.value = true
    errorMensaje.value = ''

    try {
        // 1. Usamos ruta relativa y definimos los Headers obligatorios
        const response = await axios.post('/api/login', {
            email: email.value,
            password: password.value
        }, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })

        localStorage.setItem('access_token', response.data.access_token)
        console.log('Login exitoso. Token:', response.data.access_token)
           router.push('/panel')

    } catch (error) {
        // 2. Mejoramos la captura de errores para saber exactamente qué falla
        if (error.response) {
            if (error.response.status === 422) {
                errorMensaje.value = 'Las credenciales son incorrectas.'
            } else if (error.response.status === 404) {
                errorMensaje.value = 'El endpoint de la API no existe. Verifica api.php.'
            } else {
                // Muestra el error real que devuelve Laravel
                errorMensaje.value = error.response.data.message || 'Error interno del servidor.'
                console.error("Error del servidor:", error.response.data);
            }
        } else {
            errorMensaje.value = 'No hay conexión con el backend.'
        }
    } finally {
        cargando.value = false
    }
}
</script>

<template>
    <div
        class="bg-[#f0f4f8] dark:bg-[#0a1628] flex items-center justify-center transition-colors min-h-screen py-15 bg-[radial-gradient(ellipse_at_30%_50%,rgba(245,158,11,0.1)_0%,transparent_60%),radial-gradient(ellipse_at_80%_20%,rgba(59,130,246,0.06)_0%,transparent_50%)] dark:bg-[radial-gradient(ellipse_at_30%_50%,rgba(245,158,11,0.08)_0%,transparent_60%),radial-gradient(ellipse_at_80%_20%,rgba(59,130,246,0.05)_0%,transparent_50%)] relative overflow-hidden">
        <div
            class="w-[420px] bg-white dark:bg-white/5 border border-black/10 dark:border-white/10 overflow-hidden shadow-[0_24px_80px_rgba(0,0,0,0.12)] dark:shadow-[0_24px_80px_rgba(0,0,0,0.5)] transition-all relative z-10">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 h-1"></div>
            <div class="pt-9 px-10 pb-7 text-center border-b border-black/5 dark:border-white/5">
                <h1
                    class="font-['Barlow_Condensed'] text-[26px] font-extrabold text-slate-900 dark:text-slate-100 m-0 mb-1 tracking-wide">
                    ECR Sistema</h1>
                <p
                    class="font-['DM_Sans'] text-[12.5px] text-slate-500 dark:text-slate-400 m-0 tracking-widest font-medium">
                    ENTE CONTROL DE RUTAS · REGIÓN CENTRO</p>
            </div>



            <form @submit.prevent="handleLogin" class="py-8 px-10">

                <div v-if="errorMensaje"
                    class="mb-4 bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-xs font-semibold">
                    {{ errorMensaje }}
                </div>

                <div class="mb-4.5">
                    <span
                        class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Usuario</span>
                    <div class="relative">
                        <input v-model="email"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-amber-500/50"
                            placeholder="usuario@correo.com" type="email" required />
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 opacity-30" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </div>
                </div>

                <div class="mb-6">
                    <span
                        class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Contraseña</span>
                    <div class="relative">
                        <input v-model="password"
                            class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none transition-colors font-sans focus:border-amber-500/50"
                            type="password" required />
                        <svg class="absolute right-3 top-1/2 -translate-y-1/2 opacity-30" width="16" height="16"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" />
                            <path d="M7 11V7a5 5 0 0110 0v4" />
                        </svg>
                    </div>
                </div>

                <button type="submit" :disabled="cargando"
                    class="w-full justify-center bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-3 rounded-lg border-none cursor-pointer inline-flex items-center gap-2 transition-all hover:bg-amber-400 disabled:opacity-50">
                    <span v-if="cargando">Verificando...</span>
                    <span v-else class="inline-flex items-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round">
                            <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        Ingresar al sistema
                    </span>
                </button>
            </form>




        </div>
    </div>
</template>