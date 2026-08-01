<script setup>
// GuestLayout — pantalla de login/auth
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-tinta px-4 font-sans">
        <div class="w-full sm:max-w-md overflow-hidden rounded-[6px] border border-borde bg-paper shadow-[0_24px_64px_rgba(0,0,0,0.35)]">
            <!-- Franja institucional -->
            <div class="px-6 py-5 border-b border-borde bg-paper">
                <div class="flex items-center gap-4">
                    <!-- Sello institucional -->
                    <div class="relative w-11 h-11 shrink-0">
                        <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                        <div class="absolute inset-[4px] rounded-full border border-rojo flex items-center justify-center overflow-hidden bg-paper">
                            <img src="/imagenes/SGAE.png" alt="SGAE" class="w-full h-full object-contain p-0.5" />
                        </div>
                    </div>
                    <div>
                        <p class="font-serif font-semibold text-tinta text-lg leading-tight">SGAE</p>
                        <p class="text-[11px] text-piedra uppercase tracking-[0.06em] mt-0.5 font-sans">
                            Sistema de Gestión Académica
                        </p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-7">
                <slot />
            </div>
        </div>
        <p class="text-[11px] text-piedra-soft mt-6 uppercase tracking-[0.06em]">
            Acceso restringido — Uso institucional
        </p>
    </div>
</template>
