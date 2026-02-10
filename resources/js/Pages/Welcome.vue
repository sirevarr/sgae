<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Bienvenido" />

    <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-sky-50">
        <div v-if="canLogin" class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
            <Link
                v-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="font-bold text-sky-500 hover:text-sky-700 transition"
                >Ir al Panel de Control</Link
            >

            <template v-else>
                <Link
                    :href="route('login')"
                    class="font-bold text-white bg-sky-500 px-6 py-2 rounded-full hover:bg-sky-400 shadow-md transition"
                    >Iniciar Sesión</Link
                >

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="ms-4 font-bold text-sky-500 hover:text-sky-700 transition"
                    >Registrarse</Link
                >
            </template>
        </div>

        <div class="max-w-7xl mx-auto p-6 lg:p-8 text-center">
            <div class="flex justify-center mb-6">
                <img src="/imagenes/SGAE2.png" alt="Logo SGAE" class="h-48 w-auto drop-shadow-[0_10px_10px_rgba(14,165,233,0.3)]">
            </div>

            <div class="mt-4">
                <h1 class="text-6xl font-black text-sky-500 tracking-tighter uppercase">
                    SGAE
                </h1>
                <p class="mt-2 text-2xl text-sky-400 font-bold uppercase tracking-widest">
                    Sistema de Gestión Académica Estudiantil
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="p-8 bg-white/80 backdrop-blur rounded-3xl border-2 border-sky-100 shadow-sm">
                    <h2 class="text-xl font-black text-sky-500 uppercase">Control de Estudios</h2>
                    <p class="mt-3 text-sky-400 font-medium">Acceso centralizado para la administración de alumnos e inscripciones.</p>
                </div>

                <div class="p-8 bg-white/80 backdrop-blur rounded-3xl border-2 border-sky-100 shadow-sm">
                    <h2 class="text-xl font-black text-sky-500 uppercase">Evaluaciones</h2>
                    <p class="mt-3 text-sky-400 font-medium">Registro eficiente de calificaciones y control de rendimiento escolar.</p>
                </div>
            </div>

            <div class="mt-12 text-sky-300 font-bold uppercase text-xs tracking-[0.3em]">
                Sistema desarrollado para el Éxito Académico
            </div>
        </div>
    </div>
</template>
