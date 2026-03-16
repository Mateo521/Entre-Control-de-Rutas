<script setup>
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    mostrar: { type: Boolean, required: true },
    imagenes: { type: Array, required: true }, // Ahora acepta ['url'] o [{url: '', title: ''}]
    imagenInicial: { type: String, required: false, default: '' }
})

const emit = defineEmits(['cerrar'])

const indiceActual = ref(0)

// Detecta si el array tiene strings (URLs simples) u objetos (URLs con info)
const urlActual = computed(() => {
    if (!props.imagenes || props.imagenes.length === 0) return ''
    const item = props.imagenes[indiceActual.value]
    return typeof item === 'string' ? item : item?.url
})

// Extrae la información solo si se pasó como objeto
const infoActual = computed(() => {
    if (!props.imagenes || props.imagenes.length === 0) return null
    const item = props.imagenes[indiceActual.value]
    return typeof item === 'string' ? null : item
})

watch(() => props.mostrar, (nuevoValor) => {
    if (nuevoValor) {
        const index = props.imagenes.findIndex(img => {
            const url = typeof img === 'string' ? img : img.url
            return url === props.imagenInicial
        })
        indiceActual.value = index !== -1 ? index : 0
        document.body.style.overflow = 'hidden' 
    } else {
        document.body.style.overflow = '' 
    }
})

const cerrar = () => {
    emit('cerrar')
}

const siguiente = () => {
    if (indiceActual.value < props.imagenes.length - 1) {
        indiceActual.value++
    } else {
        indiceActual.value = 0
    }
}

const anterior = () => {
    if (indiceActual.value > 0) {
        indiceActual.value--
    } else {
        indiceActual.value = props.imagenes.length - 1
    }
}

const manejarTeclado = (e) => {
    if (!props.mostrar) return
    if (e.key === 'Escape') cerrar()
    if (e.key === 'ArrowRight' && props.imagenes.length > 1) siguiente()
    if (e.key === 'ArrowLeft' && props.imagenes.length > 1) anterior()
}

onMounted(() => window.addEventListener('keydown', manejarTeclado))
onUnmounted(() => window.removeEventListener('keydown', manejarTeclado))
</script>

<template>
    <teleport to="body">
        <div v-if="mostrar" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/95 backdrop-blur-md transition-opacity select-none">
            
            <div v-if="infoActual && (infoActual.title || infoActual.subtitle)" 
                 class="absolute top-4 left-4 md:top-6 md:left-8 bg-black/50 p-4 md:p-5  backdrop-blur-md border border-white/10 text-left max-w-xs md:max-w-sm z-[320] shadow-2xl">
                <div v-if="infoActual.badge" class="text-amber-400 text-[10px] font-bold tracking-widest uppercase mb-1.5">{{ infoActual.badge }}</div>
                <div v-if="infoActual.title" class="text-white text-lg md:text-xl font-['Barlow_Condensed'] uppercase tracking-wide leading-tight drop-shadow-md">{{ infoActual.title }}</div>
                <div v-if="infoActual.subtitle" class="text-slate-300 text-sm mt-1.5 font-medium">{{ infoActual.subtitle }}</div>
                <div v-if="infoActual.date" class="text-slate-400 text-xs mt-3 flex items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                    {{ infoActual.date }}
                </div>
            </div>

            <button @click="cerrar" class="absolute top-4 md:top-6 right-4 md:right-8 text-white/50 hover:text-white bg-black/50 hover:bg-white/10 rounded-full p-3 z-[310] transition-all cursor-pointer border-none backdrop-blur-sm">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>

            <button v-if="imagenes.length > 1" @click="anterior" class="absolute left-2 md:left-6 top-1/2 -translate-y-1/2 text-white/50 hover:text-white bg-black/50 hover:bg-white/10 rounded-full p-3 md:p-4 z-[310] transition-all cursor-pointer border-none backdrop-blur-sm hover:scale-110 active:scale-95">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>

            <div class="w-full h-full p-4 md:p-12 flex flex-col items-center justify-center relative">
                <transition name="fade" mode="out-in">
                    <img :key="urlActual" :src="urlActual" 
                        class="max-w-full max-h-full object-contain rounded shadow-[0_0_50px_rgba(0,0,0,0.5)] drop-shadow-2xl" />
                </transition>
                
                <div v-if="imagenes.length > 1" class="absolute bottom-6 md:bottom-10 left-1/2 -translate-x-1/2 bg-black/80 border border-white/10 text-white px-5 py-2  font-['Barlow_Condensed'] tracking-widest text-sm font-bold backdrop-blur-sm shadow-xl">
                    FOTO {{ indiceActual + 1 }} DE {{ imagenes.length }}
                </div>
            </div>

            <button v-if="imagenes.length > 1" @click="siguiente" class="absolute right-2 md:right-6 top-1/2 -translate-y-1/2 text-white/50 hover:text-white bg-black/50 hover:bg-white/10 rounded-full p-3 md:p-4 z-[310] transition-all cursor-pointer border-none backdrop-blur-sm hover:scale-110 active:scale-95">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
        </div>
    </teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; transform: scale(0.98); }
</style>