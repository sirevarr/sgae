<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String },
});

const form = useForm({
    codigo_usuario: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head>
            <title>Iniciar Sesión — SGAE</title>
        </Head>

        <!-- Banner SGAE -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <img src="/imagenes/SGAE.png" alt="SGAE" class="h-20 drop-shadow-xl" />
            </div>
            <h1 class="text-3xl font-black text-sky-700 uppercase tracking-tight">SGAE</h1>
            <p class="text-xs text-sky-500 font-semibold uppercase tracking-widest mt-1">
                Sistema de Gestión Académica Escolar
            </p>
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 bg-green-50 p-3 rounded-lg border border-green-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="codigo_usuario" class="block text-sm font-bold text-sky-800">Código de Usuario</label>
                <input
                    id="codigo_usuario"
                    type="text"
                    class="mt-1 block w-full border-sky-200 focus:border-sky-500 focus:ring-sky-500 rounded-xl"
                    v-model="form.codigo_usuario"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Ej: admin01"
                />
                <InputError class="mt-2" :message="form.errors.codigo_usuario" />
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-sky-800">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    class="mt-1 block w-full border-sky-200 focus:border-sky-500 focus:ring-sky-500 rounded-xl"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="rounded border-sky-300 text-sky-600 focus:ring-sky-500"
                    />
                    <span class="text-sm text-gray-600">Recordarme</span>
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white font-bold text-sm rounded-xl transition-all duration-150 shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
                </button>
            </div>
        </form>

        <!-- Footer institucional -->
        <p class="text-center text-xs text-gray-400 mt-8">
            Sistema académico institucional &middot; Acceso restringido
        </p>
    </GuestLayout>
</template>
