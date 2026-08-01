<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Bienvenido — SGAE" />

    <div class="relative min-h-screen bg-tinta flex flex-col justify-center items-center px-4 py-12 font-sans">
        <div v-if="canLogin" class="absolute top-0 right-0 p-6 text-right z-10">
            <Link
                v-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="font-semibold text-dorado hover:text-paper transition-colors text-sm"
            >
                Ir al Panel de Control &rarr;
            </Link>

            <template v-else>
                <Link
                    :href="route('login')"
                    class="btn-primary"
                >
                    Iniciar Sesión
                </Link>

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="ms-4 font-semibold text-dorado hover:text-paper transition-colors text-sm"
                >
                    Registrarse
                </Link>
            </template>
        </div>

        <div class="max-w-4xl mx-auto text-center">
            <!-- Sello de marca -->
            <div class="flex justify-center mb-6">
                <div class="relative w-20 h-20">
                    <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                    <div class="absolute inset-[6px] rounded-full border border-rojo flex items-center justify-center overflow-hidden bg-paper">
                        <img src="/imagenes/SGAE.png" alt="Logo SGAE" class="w-full h-full object-contain p-1" />
                    </div>
                </div>
            </div>

            <h1 class="text-4xl font-serif font-semibold text-paper tracking-tight">
                SGAE
            </h1>
            <p class="mt-2 text-sm text-piedra-soft font-sans uppercase tracking-[0.08em]">
                Sistema de Gestión Académica Escolar
            </p>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
                <div class="p-6 bg-paper rounded-[6px] border border-borde border-t-[3px] border-t-dorado shadow-lg">
                    <h2 class="text-base font-serif font-semibold text-tinta">Control de Estudios</h2>
                    <p class="mt-2 text-xs text-piedra leading-relaxed">
                        Gestión institucional centralizada de matrículas, secciones y expediente estudiantil.
                    </p>
                </div>

                <div class="p-6 bg-paper rounded-[6px] border border-borde border-t-[3px] border-t-rojo shadow-lg">
                    <h2 class="text-base font-serif font-semibold text-tinta">Control Académico</h2>
                    <p class="mt-2 text-xs text-piedra leading-relaxed">
                        Carga de notas por momento evaluativo, boletines y emisión oficial de constancias.
                    </p>
                </div>
            </div>

            <p class="mt-12 text-piedra-soft uppercase text-[10px] tracking-[0.08em]">
                Sistema de Gestión Institucional
            </p>
        </div>
    </div>
</template>

<style scoped>
.btn-primary {
    @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-5 py-2.5 rounded-[4px] transition-colors inline-block;
}
</style>
