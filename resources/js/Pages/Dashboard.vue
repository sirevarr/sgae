<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({ stats: Object });

const stats = ref(props.stats ?? {
    estudiantesCount: 0,
    docentesCount: 0,
    seccionesCount: 0,
    promedioGlobal: 0,
    porcentajeAprobados: 0,
    anioVigente: 'Sin datos',
});

const cards = [
    { key: 'estudiantesCount', label: 'Estudiantes Matriculados', icon: '🧑‍🎓', color: 'sky' },
    { key: 'docentesCount',    label: 'Docentes Activos',         icon: '👨‍🏫', color: 'indigo' },
    { key: 'seccionesCount',   label: 'Secciones',                icon: '🗂️', color: 'violet' },
    { key: 'porcentajeAprobados', label: 'Tasa de Aprobación',   icon: '✅', color: 'emerald', suffix: '%' },
];

const modulos = [
    { route: 'estudiantes.index', icon: '🧑‍🎓', title: 'Estudiantes',      desc: 'Registro y gestión de alumnos',             color: 'emerald' },
    { route: 'matriculas.index',  icon: '📝',   title: 'Matrículas',        desc: 'Inscripción de estudiantes en secciones',   color: 'sky' },
    { route: 'evaluaciones.index',icon: '📈',   title: 'Evaluaciones',      desc: 'Ingreso y consulta de notas por momento',   color: 'amber' },
    { route: 'secciones.index',   icon: '🗂️',  title: 'Secciones',         desc: 'Gestionar secciones del año escolar',       color: 'violet' },
    { route: 'documentos.index',  icon: '🖨️',  title: 'Documentos',        desc: 'Generar boletines y constancias',           color: 'rose' },
    { route: 'personal.index',    icon: '👤',   title: 'Personal',          desc: 'Gestión del personal docente y directivo',  color: 'indigo' },
];

const colorMap = {
    sky: { bg: 'bg-sky-50', border: 'border-sky-500', text: 'text-sky-700', num: 'text-sky-600' },
    indigo: { bg: 'bg-indigo-50', border: 'border-indigo-500', text: 'text-indigo-700', num: 'text-indigo-600' },
    violet: { bg: 'bg-violet-50', border: 'border-violet-500', text: 'text-violet-700', num: 'text-violet-600' },
    emerald: { bg: 'bg-emerald-50', border: 'border-emerald-500', text: 'text-emerald-700', num: 'text-emerald-600' },
    amber: { bg: 'bg-amber-50', border: 'border-amber-500', text: 'text-amber-700', num: 'text-amber-600' },
    rose: { bg: 'bg-rose-50', border: 'border-rose-500', text: 'text-rose-700', num: 'text-rose-600' },
};
</script>

<template>
    <Head title="Dashboard — SGAE" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight">Panel de Control</h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Año Escolar vigente:
                        <span class="font-bold text-sky-600">{{ stats.anioVigente }}</span>
                    </p>
                </div>
                <img src="/imagenes/SGAE.png" alt="SGAE" class="h-12 drop-shadow-md hidden sm:block" />
            </div>
        </template>

        <!-- ── TARJETAS ESTADÍSTICAS ─────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div
                v-for="card in cards"
                :key="card.key"
                :class="['rounded-2xl border-l-4 p-5 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md bg-white', `border-${card.color}-500`]"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-2xl">{{ card.icon }}</span>
                    <span :class="['text-[10px] font-black uppercase tracking-widest', `text-${card.color}-500`]">{{ card.label }}</span>
                </div>
                <div :class="['text-4xl font-black', `text-${card.color}-700`]">
                    {{ stats[card.key] ?? 0 }}{{ card.suffix ?? '' }}
                </div>
            </div>
        </div>

        <!-- Promedio Global banner -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-2xl p-6 mb-8 flex items-center justify-between shadow-xl">
            <div>
                <p class="text-slate-400 text-xs font-black uppercase tracking-widest mb-1">Promedio General Institucional</p>
                <p class="text-5xl font-black text-white">{{ stats.promedioGlobal }}</p>
                <p class="text-slate-500 text-xs mt-1">Sobre 20 puntos · Escala venezolana MPPE</p>
            </div>
            <div class="text-6xl opacity-20 select-none">📊</div>
        </div>

        <!-- ── MÓDULOS RÁPIDOS ────────────────────────────────────────── -->
        <h2 class="text-xs font-black uppercase tracking-[0.25em] text-slate-400 mb-4">Acceso Rápido a Módulos</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <Link
                v-for="mod in modulos"
                :key="mod.route"
                :href="route(mod.route)"
                :class="['group rounded-2xl bg-white border border-slate-100 p-6 shadow-sm hover:shadow-lg transition-all hover:-translate-y-0.5 flex items-start gap-4']"
            >
                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-2xl shrink-0 transition-transform group-hover:scale-110', `bg-${mod.color}-50`]">
                    {{ mod.icon }}
                </div>
                <div>
                    <p :class="['font-black text-base text-slate-800 group-hover:' + colorMap[mod.color]?.text]">{{ mod.title }}</p>
                    <p class="text-slate-400 text-xs mt-0.5">{{ mod.desc }}</p>
                </div>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>