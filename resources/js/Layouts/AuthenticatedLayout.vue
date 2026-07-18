<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const showingNavigation = ref(false);
const page = usePage();

const user = computed(() => page.props.auth?.user);

const navGroups = [
    {
        label: 'Administración',
        color: 'sky',
        links: [
            { name: 'dashboard', label: 'Inicio', icon: '🏠' },
            { name: 'institucion.index', label: 'Institución', icon: '🏫' },
            { name: 'personal.index', label: 'Personal', icon: '👤' },
            { name: 'usuarios.index', label: 'Usuarios', icon: '🔑' },
        ]
    },
    {
        label: 'Estructura Académica',
        color: 'indigo',
        links: [
            { name: 'anios.index', label: 'Años Escolares', icon: '📅' },
            { name: 'grados.index', label: 'Grados', icon: '📊' },
            { name: 'menciones.index', label: 'Menciones', icon: '🎓' },
            { name: 'materias.index', label: 'Materias', icon: '📚' },
            { name: 'plan.index', label: 'Plan de Estudios', icon: '📋' },
        ]
    },
    {
        label: 'Gestión de Secciones',
        color: 'violet',
        links: [
            { name: 'secciones.index', label: 'Secciones', icon: '🗂️' },
            { name: 'momentos.index', label: 'Momentos Evaluativos', icon: '⏱️' },
        ]
    },
    {
        label: 'Estudiantes',
        color: 'emerald',
        links: [
            { name: 'estudiantes.index', label: 'Estudiantes', icon: '🧑‍🎓' },
            { name: 'representantes.index', label: 'Representantes', icon: '👨‍👩‍👦' },
            { name: 'matriculas.index', label: 'Matrículas', icon: '📝' },
        ]
    },
    {
        label: 'Control Académico',
        color: 'amber',
        links: [
            { name: 'evaluaciones.index', label: 'Evaluaciones', icon: '📈' },
            { name: 'documentos.index', label: 'Documentos / PDF', icon: '🖨️' },
            { name: 'auditoria.index', label: 'Auditoría', icon: '🔒' },
        ]
    },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex">

        <!-- ── SIDEBAR ──────────────────────────────────────────────── -->
        <aside class="hidden lg:flex flex-col w-64 bg-slate-900 min-h-screen shadow-2xl shrink-0">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700">
                <Link :href="route('dashboard')">
                    <img src="/imagenes/SGAE.png" alt="SGAE" class="h-10 w-10 object-contain drop-shadow" />
                </Link>
                <div>
                    <div class="text-white font-black text-lg leading-tight tracking-tight">SGAE</div>
                    <div class="text-slate-400 text-[9px] uppercase tracking-widest font-semibold">Gestión Académica</div>
                </div>
            </div>

            <!-- Nav grupos -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-4">
                <template v-for="group in navGroups" :key="group.label">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-500 px-2 mb-1.5">{{ group.label }}</p>
                        <div class="space-y-0.5">
                            <Link
                                v-for="link in group.links"
                                :key="link.name"
                                :href="route(link.name)"
                                :class="[
                                    'flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-150',
                                    route().current(link.name)
                                        ? 'bg-sky-600 text-white shadow-md'
                                        : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                ]"
                            >
                                <span class="text-base leading-none">{{ link.icon }}</span>
                                <span>{{ link.label }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Usuario en la parte inferior -->
            <div class="border-t border-slate-700 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-sky-600 rounded-full flex items-center justify-center text-white font-black text-sm shrink-0">
                        {{ user?.codigo_usuario?.[0]?.toUpperCase() ?? 'U' }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-bold truncate">{{ user?.codigo_usuario }}</p>
                        <p class="text-slate-400 text-[10px] capitalize">{{ user?.rol }}</p>
                    </div>
                </div>
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="mt-3 w-full text-center text-xs text-slate-400 hover:text-red-400 transition-colors font-semibold"
                >
                    Cerrar sesión
                </Link>
            </div>
        </aside>

        <!-- ── CONTENIDO PRINCIPAL ────────────────────────────────────── -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Topbar móvil -->
            <header class="lg:hidden bg-slate-900 px-4 py-3 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <img src="/imagenes/SGAE.png" alt="SGAE" class="h-8 w-8 object-contain" />
                    <span class="text-white font-black text-lg">SGAE</span>
                </div>
                <button @click="showingNavigation = !showingNavigation" class="text-slate-300 hover:text-white p-1">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            :d="showingNavigation ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'" />
                    </svg>
                </button>
            </header>

            <!-- Menú móvil desplegable -->
            <div v-if="showingNavigation" class="lg:hidden bg-slate-800 border-b border-slate-700 px-4 py-3 space-y-2">
                <template v-for="group in navGroups" :key="group.label + '_m'">
                    <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 pt-2">{{ group.label }}</p>
                    <Link
                        v-for="link in group.links"
                        :key="link.name + '_m'"
                        :href="route(link.name)"
                        @click="showingNavigation = false"
                        class="flex items-center gap-2 px-2 py-1.5 rounded text-slate-300 hover:text-white hover:bg-slate-700 text-sm font-medium transition"
                    >
                        <span>{{ link.icon }}</span>
                        <span>{{ link.label }}</span>
                    </Link>
                </template>
            </div>

            <!-- Header de página (slot) -->
            <header v-if="$slots.header" class="bg-white border-b border-slate-200 shadow-sm">
                <div class="max-w-7xl mx-auto px-6 py-4">
                    <slot name="header" />
                </div>
            </header>

            <!-- Main content -->
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>