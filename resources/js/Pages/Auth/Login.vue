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

        <p class="text-tinta font-serif font-semibold text-[19px] mb-1">Iniciar sesión</p>
        <p class="text-piedra text-[12px] mb-6">Ingrese sus credenciales institucionales.</p>

        <div v-if="status" class="mb-4 text-[12px] text-ok bg-[#E6EEE0] px-3 py-2.5 rounded-[4px] border border-ok/30 font-semibold">
            {{ status }}
        </div>

        <div v-if="$page.props.flash?.error" class="mb-4 text-[12px] text-rojo-dark bg-[#F4DEDA] px-3 py-2.5 rounded-[4px] border border-rojo/20 font-semibold">
            {{ $page.props.flash.error }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="codigo_usuario" class="block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em] mb-1">
                    Código de usuario
                </label>
                <input
                    id="codigo_usuario"
                    type="text"
                    class="w-full border border-borde rounded-[4px] px-3 py-[11px] text-[13px] bg-crema text-tinta placeholder-piedra-soft
                           focus:outline-none focus:border-rojo focus:bg-paper transition-colors"
                    v-model="form.codigo_usuario"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Ej: admin01"
                />
                <InputError class="mt-1.5" :message="form.errors.codigo_usuario" />
            </div>

            <div>
                <label for="password" class="block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em] mb-1">
                    Contraseña
                </label>
                <input
                    id="password"
                    type="password"
                    class="w-full border border-borde rounded-[4px] px-3 py-[11px] text-[13px] bg-crema text-tinta placeholder-piedra-soft
                           focus:outline-none focus:border-rojo focus:bg-paper transition-colors"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1 gap-3">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="checkbox"
                        v-model="form.remember"
                        class="rounded-[2px] border-borde text-rojo focus:ring-rojo"
                    />
                    <span class="text-[12px] text-piedra">Recordarme</span>
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-5 py-[11px] bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold rounded-[4px] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
