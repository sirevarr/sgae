<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigation = ref(false);
const page = usePage();

const user = computed(() => page.props.auth?.user);
const userRole = computed(() => String(user.value?.rol ?? 'docente').trim().toLowerCase());

const initials = computed(() => {
    const code = user.value?.codigo_usuario ?? 'U';
    return code.slice(0, 2).toUpperCase();
});

const navGroups = [
    {
        label: 'Administración',
        links: [
            { name: 'dashboard',        label: 'Inicio',          roles: ['administrador', 'control_estudios', 'docente'] },
            { name: 'institucion.index', label: 'Institución',     roles: ['administrador'] },
            { name: 'personal.index',   label: 'Personal',        roles: ['administrador', 'control_estudios'] },
            { name: 'usuarios.index',   label: 'Usuarios',        roles: ['administrador'] },
        ]
    },
    {
        label: 'Estructura Académica',
        links: [
            { name: 'anios.index',    label: 'Años Escolares',   roles: ['administrador', 'control_estudios'] },
            { name: 'grados.index',   label: 'Grados',           roles: ['administrador', 'control_estudios'] },
            { name: 'menciones.index', label: 'Menciones',       roles: ['administrador', 'control_estudios'] },
            { name: 'materias.index', label: 'Materias',         roles: ['administrador', 'control_estudios'] },
            { name: 'plan.index',     label: 'Plan de Estudios', roles: ['administrador', 'control_estudios'] },
        ]
    },
    {
        label: 'Gestión de Secciones',
        links: [
            { name: 'secciones.index', label: 'Secciones',            roles: ['administrador', 'control_estudios', 'docente'] },
            { name: 'momentos.index',  label: 'Momentos Evaluativos', roles: ['administrador', 'control_estudios', 'docente'] },
        ]
    },
    {
        label: 'Estudiantes',
        links: [
            { name: 'representantes.index', label: 'Representantes', roles: ['administrador', 'control_estudios', 'docente'] },
            { name: 'estudiantes.index',    label: 'Estudiantes',    roles: ['administrador', 'control_estudios', 'docente'] },
            { name: 'matriculas.index',     label: 'Matrículas',     roles: ['administrador', 'control_estudios', 'docente'] },
        ]
    },
    {
        label: 'Control Académico',
        links: [
            { name: 'evaluaciones.index', label: 'Evaluaciones',    roles: ['administrador', 'control_estudios', 'docente'] },
            { name: 'documentos.index',   label: 'Documentos / PDF', roles: ['administrador', 'control_estudios', 'docente'] },
            { name: 'auditoria.index',    label: 'Auditoría',       roles: ['administrador'] },
        ]
    },
];

const filteredNavGroups = computed(() => {
    return navGroups
        .map(group => ({
            ...group,
            links: group.links.filter(link => !link.roles || link.roles.includes(userRole.value))
        }))
        .filter(group => group.links.length > 0);
});
</script>

<template>
    <div class="min-h-screen bg-crema text-tinta flex font-sans">

        <!-- ═══ SIDEBAR ═══════════════════════════════════════════════ -->
        <aside class="hidden lg:flex flex-col w-[220px] bg-tinta min-h-screen shrink-0">

            <!-- Marca -->
            <div class="flex items-center gap-3 px-5 py-5 border-b border-white/10">
                <!-- Sello -->
                <div class="relative w-9 h-9 shrink-0">
                    <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                    <div class="absolute inset-[4px] rounded-full border border-rojo flex items-center justify-center overflow-hidden bg-paper">
                        <img src="/imagenes/SGAE.png" alt="SGAE" class="w-full h-full object-contain p-0.5" />
                    </div>
                </div>
                <div>
                    <div class="text-paper font-serif font-semibold text-base leading-tight tracking-tight">SGAE</div>
                    <div class="text-piedra-soft text-[10px] uppercase tracking-[0.08em] font-sans mt-0.5">Gestión Académica</div>
                </div>
            </div>

            <!-- Navegación -->
            <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-5">
                <template v-for="group in filteredNavGroups" :key="group.label">
                    <div>
                        <p class="text-[10px] font-sans font-semibold uppercase tracking-[0.08em] text-piedra-soft px-2 mb-2">
                            {{ group.label }}
                        </p>
                        <div class="space-y-0.5">
                            <Link
                                v-for="link in group.links"
                                :key="link.name"
                                :href="route(link.name)"
                                :class="[
                                    'block px-3 py-2 text-[13px] font-sans border-l-2 transition-colors duration-100',
                                    route().current(link.name)
                                        ? 'border-dorado bg-dorado/14 text-paper font-semibold'
                                        : 'border-transparent text-piedra hover:text-paper hover:bg-white/5'
                                ]"
                            >
                                {{ link.label }}
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Usuario -->
            <div class="border-t border-white/10 px-4 py-4">
                <div class="flex items-center gap-3">
                    <!-- Sello usuario -->
                    <div class="relative w-8 h-8 shrink-0">
                        <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                        <div class="absolute inset-[4px] rounded-full border border-rojo flex items-center justify-center bg-tinta">
                            <span class="font-serif text-[9px] font-bold text-paper leading-none">{{ initials }}</span>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <p class="text-paper text-xs font-semibold truncate">{{ user?.codigo_usuario }}</p>
                        <p class="text-piedra-soft text-[10px] uppercase tracking-[0.06em]">{{ user?.rol }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ═══ CONTENIDO PRINCIPAL ═══════════════════════════════════ -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Topbar móvil -->
            <header class="lg:hidden bg-paper border-b border-borde px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="relative w-7 h-7 shrink-0">
                        <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                        <div class="absolute inset-[3px] rounded-full border border-rojo flex items-center justify-center overflow-hidden bg-paper">
                            <img src="/imagenes/SGAE.png" alt="SGAE" class="w-full h-full object-contain p-0.5" />
                        </div>
                    </div>
                    <span class="text-tinta font-serif font-semibold text-base">SGAE</span>
                </div>
                <button @click="showingNavigation = !showingNavigation" class="text-piedra p-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            :d="showingNavigation ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'" />
                    </svg>
                </button>
            </header>

            <!-- Nav móvil desplegable -->
            <div v-if="showingNavigation" class="lg:hidden bg-tinta border-b border-white/10 px-4 py-3 space-y-4">
                <template v-for="group in filteredNavGroups" :key="group.label + '_m'">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.08em] text-piedra-soft mb-1">{{ group.label }}</p>
                        <Link
                            v-for="link in group.links"
                            :key="link.name + '_m'"
                            :href="route(link.name)"
                            @click="showingNavigation = false"
                            :class="[
                                'block px-3 py-2 text-sm border-l-2 transition-colors',
                                route().current(link.name)
                                    ? 'border-dorado bg-dorado/14 text-paper font-semibold'
                                    : 'border-transparent text-piedra'
                            ]"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </template>
                <div class="pt-3 border-t border-white/10 flex justify-between items-center text-xs text-piedra">
                    <span>{{ user?.codigo_usuario }} ({{ user?.rol }})</span>
                    <Link :href="route('logout')" method="post" as="button" class="text-dorado">Salir</Link>
                </div>
            </div>

            <!-- Topbar escritorio -->
            <header class="hidden lg:flex bg-paper border-b border-borde px-7 py-3.5 items-center justify-between shrink-0">
                <div v-if="$slots.header" class="flex-1 min-w-0">
                    <slot name="header" />
                </div>
                <div v-else class="text-tinta font-serif font-semibold text-[19px]">Panel</div>
                <!-- Usuario topbar -->
                <div class="flex items-center gap-4 ml-auto">
                    <div class="flex items-center gap-3">
                        <div class="text-right">
                            <p class="text-tinta text-xs font-semibold">{{ user?.codigo_usuario }}</p>
                            <p class="text-piedra text-[10px] uppercase tracking-[0.06em]">{{ user?.rol }}</p>
                        </div>
                        <div class="relative w-8 h-8 shrink-0">
                            <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                            <div class="absolute inset-[4px] rounded-full border border-rojo flex items-center justify-center bg-paper">
                                <span class="font-serif text-[9px] font-bold text-tinta leading-none">{{ initials }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-px h-6 bg-borde mx-1"></div>
                    
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="text-piedra hover:text-rojo transition-colors flex items-center gap-1.5"
                        title="Cerrar sesión"
                    >
                        <span class="text-[13px] font-semibold hidden md:block">Salir</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </Link>
                </div>
            </header>

            <!-- Flash error -->
            <div v-if="$page.props.flash?.error"
                class="bg-[#F4DEDA] border-b border-rojo/20 text-rojo-dark text-xs font-semibold px-6 py-2.5 flex justify-between items-center">
                <span>{{ $page.props.flash.error }}</span>
                <button @click="$page.props.flash.error = null" class="text-rojo-dark hover:text-rojo ml-4">&times;</button>
            </div>

            <!-- Contenido principal -->
            <main class="flex-1 px-7 py-7 overflow-y-auto min-w-0 bg-crema">
                <div class="max-w-[1440px] mx-auto w-full space-y-5">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style>
/* Clase de utilidad dorado/14 no generada por Tailwind puro */
.bg-dorado\/14 { background-color: rgba(184, 145, 46, 0.14); }
</style>