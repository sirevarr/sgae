<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// Recibimos las estadísticas reales desde el servidor (web.php)
const props = defineProps({
    stats: Object
});
</script>

<template>
    <Head title="Panel de Control" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-sky-500 uppercase tracking-tight">Panel de Control SGAE</h2>
                
                
            </div>
        </template>

        <div class="py-12 bg-sky-50/30 min-h-screen"> 
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-sky-500 overflow-hidden shadow-xl rounded-3xl p-8 flex items-center mb-10 border-b-8 border-sky-600">
                    <div class="flex-1">
                        <h1 class="text-5xl font-black text-white mb-2 tracking-tighter uppercase">SGAE</h1>
                        <p class="text-white text-sm font-black uppercase opacity-90 tracking-widest">Sistema de Control Académico Institucional</p>
                    </div>
                    <div class="hidden sm:block bg-white/20 p-5 rounded-3xl border border-white/30 shadow-inner backdrop-blur-sm">
                        <img src="/imagenes/SGAE.png" class="h-24 drop-shadow-2xl" alt="Logo">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                    <div class="bg-white p-6 rounded-3xl border-b-4 border-sky-500 shadow-sm transition-transform hover:-translate-y-1">
                        <div class="text-[10px] font-black text-sky-400 uppercase tracking-widest mb-2">Estudiantes Inscritos</div>
                        <div class="text-4xl font-black text-slate-700">{{ stats.estudiantesCount }}</div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-3xl border-b-4 border-sky-400 shadow-sm transition-transform hover:-translate-y-1">
                        <div class="text-[10px] font-black text-sky-400 uppercase tracking-widest mb-2">Materias Activas</div>
                        <div class="text-4xl font-black text-slate-700">{{ stats.materiasCount }}</div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border-b-4 border-emerald-400 shadow-sm transition-transform hover:-translate-y-1">
                        <div class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-2">Promedio Institucional</div>
                        <div class="text-4xl font-black text-emerald-600">{{ stats.promedioGlobal }}</div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border-b-4 border-amber-400 shadow-sm transition-transform hover:-translate-y-1">
                        <div class="text-[10px] font-black text-amber-400 uppercase tracking-widest mb-2">Tasa de Aprobación</div>
                        <div class="text-4xl font-black text-amber-600">{{ stats.porcentajeAprobados }}%</div>
                    </div>
                </div>

                <h3 class="text-[10px] font-black text-sky-400 uppercase tracking-[0.3em] mb-6 ml-2">Gestión de Módulos</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <Link :href="route('estudiantes.index')" class="bg-white border-l-[12px] border-sky-500 p-8 rounded-3xl shadow-sm hover:shadow-xl transition-all group">
                        <div class="text-[11px] font-black text-sky-500 uppercase tracking-[0.2em] mb-1">Registro de Alumnos</div>
                        <div class="text-3xl font-black text-slate-800 uppercase tracking-tighter group-hover:text-sky-600 transition-colors">Estudiantes</div>
                        <p class="text-gray-400 text-[11px] mt-4 font-black uppercase tracking-wider italic group-hover:text-gray-600">Acceder al censo completo</p>
                    </Link>

                    <Link :href="route('materias.index')" class="bg-white border-l-[12px] border-sky-400 p-8 rounded-3xl shadow-sm hover:shadow-xl transition-all group">
                        <div class="text-[11px] font-black text-sky-400 uppercase tracking-[0.2em] mb-1">Carga Académica</div>
                        <div class="text-3xl font-black text-slate-800 uppercase tracking-tighter group-hover:text-sky-400 transition-colors">Materias</div>
                        <p class="text-gray-400 text-[11px] mt-4 font-black uppercase tracking-wider italic group-hover:text-gray-600">Configurar unidades</p>
                    </Link>

                    <Link :href="route('evaluaciones.index')" class="bg-white border-l-[12px] border-sky-300 p-8 rounded-3xl shadow-sm hover:shadow-xl transition-all group">
                        <div class="text-[11px] font-black text-sky-300 uppercase tracking-[0.2em] mb-1">Control de Notas</div>
                        <div class="text-3xl font-black text-slate-800 uppercase tracking-tighter group-hover:text-sky-300 transition-colors">Evaluaciones</div>
                        <p class="text-gray-400 text-[11px] mt-4 font-black uppercase tracking-wider italic group-hover:text-gray-600">Ver rendimiento real</p>
                    </Link>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>