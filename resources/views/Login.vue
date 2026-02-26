<script setup>
// Aquí programaremos la petición a la API luego
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
            <div class="py-8 px-10">
                <div class="mb-4.5">
                    <span
                        class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Usuario</span>
                    <input
                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                        placeholder="usuario@correo.com" type="email" />
                </div>
                <div class="mb-6">
                    <span
                        class="font-['Barlow_Condensed'] text-[10px] font-bold tracking-[0.12em] uppercase text-slate-500 dark:text-slate-400 block mb-1.5">Contraseña</span>
                    <input
                        class="w-full bg-slate-50 dark:bg-white/5 border border-slate-300 dark:border-white/10 rounded-lg px-3.5 py-2.5 text-slate-900 dark:text-white text-sm outline-none focus:border-amber-500/50"
                        type="password" />
                </div>
                <button
                    class="w-full justify-center bg-amber-500 text-[#0d1b2a] font-['Barlow_Condensed'] text-sm font-bold tracking-wider uppercase px-5 py-3 rounded-lg border-none cursor-pointer">
                    Ingresar al sistema
                </button>
            </div>
        </div>
    </div>
</template>