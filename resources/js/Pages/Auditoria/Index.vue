<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const registros  = ref([]); const logins = ref([]);
const loading    = ref(false); const tab = ref('auditoria');
const pagination = ref({});
const filtro = reactive({ tabla_afectada: '', operacion: '', fecha_desde: '', fecha_hasta: '' });

async function cargar(page = 1) {
    loading.value = true;
    try {
        if (tab.value === 'auditoria') {
            const { data } = await axios.get('/api/auditoria', { params: { ...filtro, page } });
            registros.value = data.data ?? data; pagination.value = data;
        } else {
            const { data } = await axios.get('/api/auditoria/logins', { params: { page } });
            logins.value = data.data ?? data; pagination.value = data;
        }
    } finally { loading.value = false; }
}
function cambiarTab(t) { tab.value = t; cargar(); }

onMounted(cargar);
</script>
<template>
    <Head title="Auditoría — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-black text-slate-800">🔒 Auditoría del Sistema</h1>
            <p class="text-xs text-slate-500 mt-0.5">Registro de cambios y accesos al sistema</p>
        </template>
        <!-- Tabs -->
        <div class="flex gap-2 mb-5 border-b border-slate-200">
            <button v-for="t in ['auditoria', 'logins']" :key="t" @click="cambiarTab(t)"
                :class="['px-5 py-2.5 text-sm font-bold transition border-b-2 -mb-px',
                    tab === t ? 'border-sky-600 text-sky-700' : 'border-transparent text-slate-500 hover:text-slate-700']">
                {{ t === 'auditoria' ? '📋 Cambios en BD' : '🔐 Registro de Logins' }}
            </button>
        </div>
        <!-- Filtros auditoría -->
        <div v-if="tab === 'auditoria'" class="flex flex-wrap gap-3 mb-5">
            <input v-model="filtro.tabla_afectada" @input="cargar" placeholder="Tabla…" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white" />
            <select v-model="filtro.operacion" @change="cargar" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <option value="">Todas las operaciones</option>
                <option value="INSERT">INSERT</option>
                <option value="UPDATE">UPDATE</option>
                <option value="DELETE">DELETE</option>
            </select>
            <input v-model="filtro.fecha_desde" @change="cargar" type="date" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white" />
            <input v-model="filtro.fecha_hasta" @change="cargar" type="date" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white" />
        </div>
        <!-- Tabla auditoría -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div v-if="loading" class="p-12 text-center text-slate-400">Cargando…</div>
            <!-- Registros BD -->
            <table v-else-if="tab === 'auditoria'" class="w-full text-xs">
                <thead class="bg-slate-800 text-white"><tr>
                    <th class="px-3 py-3 text-left font-black uppercase">Fecha/Hora</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Usuario</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Operación</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Tabla</th>
                    <th class="px-3 py-3 text-left font-black uppercase">ID Registro</th>
                    <th class="px-3 py-3 text-left font-black uppercase">IP</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!registros.length"><td colspan="6" class="text-center py-10 text-slate-400">Sin registros de auditoría.</td></tr>
                    <tr v-for="r in registros" :key="r.id_auditoria" class="hover:bg-slate-50">
                        <td class="px-3 py-2.5 font-mono text-slate-600">{{ r.fecha_hora }}</td>
                        <td class="px-3 py-2.5 font-mono text-slate-700">{{ r.usuario?.codigo_usuario ?? r.id_usuario }}</td>
                        <td class="px-3 py-2.5">
                            <span :class="['px-2 py-0.5 rounded-full font-black uppercase text-[9px]',
                                r.operacion === 'INSERT' ? 'bg-emerald-100 text-emerald-700' :
                                r.operacion === 'UPDATE' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700']">
                                {{ r.operacion }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 font-mono text-slate-600">{{ r.tabla_afectada }}</td>
                        <td class="px-3 py-2.5 font-mono text-xs text-slate-400">{{ r.id_registro }}</td>
                        <td class="px-3 py-2.5 text-slate-400">{{ r.ip_usuario ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
            <!-- Logins -->
            <table v-else class="w-full text-xs">
                <thead class="bg-slate-800 text-white"><tr>
                    <th class="px-3 py-3 text-left font-black uppercase">Fecha</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Hora</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Usuario</th>
                    <th class="px-3 py-3 text-left font-black uppercase">IP</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Tipo</th>
                    <th class="px-3 py-3 text-left font-black uppercase">Resultado</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!logins.length"><td colspan="6" class="text-center py-10 text-slate-400">Sin registros de login.</td></tr>
                    <tr v-for="l in logins" :key="l.id_login" class="hover:bg-slate-50">
                        <td class="px-3 py-2.5 font-mono">{{ l.fecha }}</td>
                        <td class="px-3 py-2.5 font-mono">{{ l.hora }}</td>
                        <td class="px-3 py-2.5 font-mono text-slate-700">{{ l.usuario?.codigo_usuario ?? l.id_usuario }}</td>
                        <td class="px-3 py-2.5 text-slate-400">{{ l.ip_acceso ?? '—' }}</td>
                        <td class="px-3 py-2.5">{{ l.tipo_acceso === 'E' ? 'Entrada' : 'Salida' }}</td>
                        <td class="px-3 py-2.5">
                            <span :class="['px-2 py-0.5 rounded-full font-black uppercase text-[9px]', l.exitoso ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700']">
                                {{ l.exitoso ? 'Exitoso' : 'Fallido' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
