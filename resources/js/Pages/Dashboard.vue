<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol || 'docente').toLowerCase());

const props = defineProps({ stats: Object });

const stats = ref(props.stats ?? {
    estudiantesCount: 0,
    docentesCount: 0,
    seccionesCount: 0,
    promedioGlobal: 0,
    porcentajeAprobados: 0,
    anioVigente: 'Sin datos',
});

const kpis = [
    { key: 'estudiantesCount',   label: 'Estudiantes Matriculados', accent: 'rojo' },
    { key: 'docentesCount',      label: 'Docentes Activos',         accent: 'dorado' },
    { key: 'seccionesCount',     label: 'Secciones',                accent: 'dorado' },
    { key: 'porcentajeAprobados', label: 'Tasa de Aprobación',      accent: 'dorado', suffix: '%' },
];

const modulos = [
    { route: 'estudiantes.index',    title: 'Estudiantes',  desc: 'Registro y gestión del censo estudiantil', roles: ['administrador', 'control_estudios', 'docente'] },
    { route: 'matriculas.index',     title: 'Matrículas',   desc: 'Inscripción y control de matrícula', roles: ['administrador', 'control_estudios'] },
    { route: 'evaluaciones.index',   title: 'Evaluaciones', desc: 'Registro y consulta de notas por momento', roles: ['administrador', 'control_estudios', 'docente'] },
    { route: 'secciones.index',      title: 'Secciones',    desc: 'Organización de grupos y asignaciones', roles: ['administrador', 'control_estudios'] },
    { route: 'documentos.index',     title: 'Documentos',   desc: 'Generación de boletines y constancias', roles: ['administrador', 'control_estudios', 'docente'] },
    { route: 'personal.index',       title: 'Personal',     desc: 'Gestión del personal docente y administrativo', roles: ['administrador', 'control_estudios'] },
];

const modulosVisibles = computed(() => modulos.filter(mod => mod.roles.includes(userRole.value)));
</script>

<template>
    <Head>
        <title>Dashboard — SGAE</title>
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="font-serif font-semibold text-tinta text-[20px] leading-tight">Panel de Control</h1>
                <p class="text-[11px] text-piedra mt-0.5">
                    Año escolar vigente:
                    <span class="font-semibold text-tinta-soft">{{ stats.anioVigente }}</span>
                </p>
            </div>
        </template>

        <!-- KPIs -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                v-for="kpi in kpis"
                :key="kpi.key"
                :class="[
                    'bg-paper border border-borde rounded-[6px] p-5',
                    kpi.accent === 'rojo' ? 'border-t-[3px] border-t-rojo' : 'border-t-[3px] border-t-dorado'
                ]"
            >
                <p class="text-[11px] text-piedra uppercase tracking-[0.04em] mb-2">{{ kpi.label }}</p>
                <p class="font-serif font-semibold text-[26px] text-tinta leading-none">
                    {{ stats[kpi.key] ?? 0 }}{{ kpi.suffix ?? '' }}
                </p>
            </div>
        </div>

        <!-- Promedio Global -->
        <div class="bg-paper border border-borde border-l-[3px] border-l-dorado rounded-[6px] p-6 flex items-center gap-6">
            <div>
                <p class="text-[11px] text-piedra uppercase tracking-[0.04em] mb-1">Promedio General Institucional</p>
                <p class="font-serif font-semibold text-[40px] text-tinta leading-none">{{ stats.promedioGlobal }}</p>
                <p class="text-[11px] text-piedra mt-1">Sobre 20 puntos · Escala venezolana MPPE</p>
            </div>
        </div>

        <!-- Módulos -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] text-piedra uppercase tracking-[0.06em] font-semibold">Acceso directo por módulo</p>
                <span class="text-[11px] text-piedra-soft">{{ modulos.length }} módulos</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="mod in modulosVisibles"
                    :key="mod.route"
                    :href="route(mod.route)"
                    class="group bg-paper border border-borde rounded-[6px] px-5 py-4 hover:border-dorado transition-colors duration-100"
                >
                    <p class="font-semibold text-[14px] text-tinta group-hover:text-rojo transition-colors">{{ mod.title }}</p>
                    <p class="text-piedra text-[11px] mt-0.5">{{ mod.desc }}</p>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>