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
            <div>
                <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Auditoría del Sistema</h1>
                <p class="text-[11px] text-piedra mt-0.5">Registro de cambios y accesos al sistema</p>
            </div>
        </template>

        <!-- Tabs -->
        <div class="flex gap-6 mb-5 border-b border-borde">
            <button v-for="t in ['auditoria', 'logins']" :key="t" @click="cambiarTab(t)"
                :class="['pb-3 text-[13px] font-semibold transition-colors border-b-2 -mb-px',
                    tab === t ? 'border-dorado text-tinta' : 'border-transparent text-piedra hover:text-tinta']">
                {{ t === 'auditoria' ? 'Cambios en Base de Datos' : 'Registro de Accesos (Logins)' }}
            </button>
        </div>

        <!-- Filtros auditoría -->
        <div v-if="tab === 'auditoria'" class="flex flex-wrap gap-3 mb-4">
            <input v-model="filtro.tabla_afectada" @input="cargar" placeholder="Tabla..." class="inp-filter" />
            <select v-model="filtro.operacion" @change="cargar" class="inp-filter">
                <option value="">Todas las operaciones</option>
                <option value="INSERT">INSERT</option>
                <option value="UPDATE">UPDATE</option>
                <option value="DELETE">DELETE</option>
            </select>
            <input v-model="filtro.fecha_desde" @change="cargar" type="date" class="inp-filter" />
            <input v-model="filtro.fecha_hasta" @change="cargar" type="date" class="inp-filter" />
        </div>

        <!-- Tabla auditoría -->
        <div class="bg-paper border border-borde rounded-[6px] overflow-x-auto">
            <div v-if="loading" class="p-10 text-center text-piedra text-[13px]">Cargando...</div>

            <!-- Registros BD -->
            <table v-else-if="tab === 'auditoria'" class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Fecha/Hora</th>
                        <th class="th">Usuario</th>
                        <th class="th">Operación</th>
                        <th class="th">Tabla</th>
                        <th class="th">ID Registro</th>
                        <th class="th">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!registros.length">
                        <td colspan="6" class="text-center py-10 text-piedra text-[13px]">Sin registros de auditoría.</td>
                    </tr>
                    <tr v-for="r in registros" :key="r.id_auditoria" class="hover:bg-crema transition-colors">
                        <td class="td font-mono text-[12px] text-piedra">{{ r.fecha_hora }}</td>
                        <td class="td font-mono text-[12.5px] font-semibold text-tinta">{{ r.usuario?.codigo_usuario ?? r.id_usuario }}</td>
                        <td class="td">
                            <span :class="['badge',
                                r.operacion === 'INSERT' ? 'badge-ok' :
                                r.operacion === 'UPDATE' ? 'badge-alerta' :
                                'badge-[#F4DEDA] text-rojo-dark']">
                                {{ r.operacion }}
                            </span>
                        </td>
                        <td class="td font-mono text-[12px] text-piedra">{{ r.tabla_afectada }}</td>
                        <td class="td font-mono text-[12px] text-piedra-soft">{{ r.id_registro }}</td>
                        <td class="td font-mono text-[12px] text-piedra-soft">{{ r.ip_usuario ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Logins -->
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Fecha</th>
                        <th class="th">Hora</th>
                        <th class="th">Usuario</th>
                        <th class="th">IP</th>
                        <th class="th">Tipo</th>
                        <th class="th">Resultado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!logins.length">
                        <td colspan="6" class="text-center py-10 text-piedra text-[13px]">Sin registros de login.</td>
                    </tr>
                    <tr v-for="l in logins" :key="l.id_login" class="hover:bg-crema transition-colors">
                        <td class="td font-mono text-[12px] text-piedra">{{ l.fecha }}</td>
                        <td class="td font-mono text-[12px] text-piedra">{{ l.hora }}</td>
                        <td class="td font-mono text-[12.5px] font-semibold text-tinta">{{ l.usuario?.codigo_usuario ?? l.id_usuario }}</td>
                        <td class="td font-mono text-[12px] text-piedra-soft">{{ l.ip_acceso ?? '—' }}</td>
                        <td class="td text-[12px] text-piedra">{{ l.tipo_acceso === 'E' ? 'Entrada' : 'Salida' }}</td>
                        <td class="td">
                            <span :class="['badge', l.exitoso ? 'badge-ok' : 'bg-[#F4DEDA] text-rojo-dark']">
                                {{ l.exitoso ? 'Exitoso' : 'Fallido' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.inp-filter    { @apply border border-borde rounded-[4px] px-3 py-[9px] text-[13px] bg-paper text-tinta focus:outline-none focus:border-rojo transition-colors; }
.th            { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td            { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
</style>
