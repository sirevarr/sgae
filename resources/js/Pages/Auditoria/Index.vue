<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const registros  = ref([]);
const logins     = ref([]);
const loading    = ref(false);
const tab        = ref('auditoria');
const pagination = ref({});

const filtro = reactive({
    buscar: '',
    tabla_afectada: '',
    operacion: '',
    fecha_desde: '',
    fecha_hasta: '',
});

async function cargar(page = 1) {
    loading.value = true;
    try {
        if (tab.value === 'auditoria') {
            const { data } = await axios.get('/api/auditoria', { params: { ...filtro, page } });
            registros.value = data.data ?? data;
            pagination.value = data;
        } else {
            const { data } = await axios.get('/api/auditoria/logins', { params: { buscar: filtro.buscar, page } });
            logins.value = data.data ?? data;
            pagination.value = data;
        }
    } finally {
        loading.value = false;
    }
}

function cambiarTab(t) {
    tab.value = t;
    cargar(1);
}

function formatearOperacion(op) {
    if (!op) return '—';
    const clean = String(op).toUpperCase();
    if (clean === 'I' || clean === 'INSERT') return 'INSERT';
    if (clean === 'U' || clean === 'UPDATE') return 'UPDATE';
    if (clean === 'D' || clean === 'DELETE') return 'DELETE';
    return clean;
}

function operacionBadgeClass(op) {
    const clean = String(op).toUpperCase();
    if (clean === 'I' || clean === 'INSERT') return 'badge-ok';
    if (clean === 'U' || clean === 'UPDATE') return 'badge-alerta';
    return 'bg-[#F4DEDA] text-rojo-dark';
}

function formatearFechaHora(fh) {
    if (!fh) return '—';
    try {
        const d = new Date(fh);
        return d.toLocaleString('es-VE', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    } catch (e) {
        return fh;
    }
}

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
        <div class="flex flex-wrap gap-3 mb-4 items-center">
            <input v-model="filtro.buscar" @input="cargar(1)" placeholder="Buscar por usuario, ID, tabla..." class="inp-filter w-64" />
            <input v-if="tab === 'auditoria'" v-model="filtro.tabla_afectada" @input="cargar(1)" placeholder="Filtrar por tabla..." class="inp-filter w-40" />
            <select v-if="tab === 'auditoria'" v-model="filtro.operacion" @change="cargar(1)" class="inp-filter">
                <option value="">Todas las operaciones</option>
                <option value="I">INSERT (Crear)</option>
                <option value="U">UPDATE (Modificar)</option>
                <option value="D">DELETE (Eliminar)</option>
            </select>
            <div v-if="tab === 'auditoria'" class="flex items-center gap-1">
                <span class="text-[11px] text-piedra">Desde:</span>
                <input v-model="filtro.fecha_desde" @change="cargar(1)" type="date" class="inp-filter" />
            </div>
            <div v-if="tab === 'auditoria'" class="flex items-center gap-1">
                <span class="text-[11px] text-piedra">Hasta:</span>
                <input v-model="filtro.fecha_hasta" @change="cargar(1)" type="date" class="inp-filter" />
            </div>
        </div>

        <!-- Tabla auditoría -->
        <div class="bg-paper border border-borde rounded-[6px] overflow-hidden shadow-sm">
            <div v-if="loading" class="p-10 text-center text-piedra text-[13px]">Cargando registros...</div>

            <!-- Registros BD -->
            <div v-else-if="tab === 'auditoria'">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-borde bg-crema/40">
                                <th class="th">Fecha/Hora</th>
                                <th class="th">Usuario</th>
                                <th class="th">Operación</th>
                                <th class="th">Tabla Afectada</th>
                                <th class="th">ID Registro Afectado</th>
                                <th class="th">Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-borde">
                            <tr v-if="!registros.length">
                                <td colspan="6" class="text-center py-10 text-piedra text-[13px]">No se encontraron registros de auditoría con los filtros aplicados.</td>
                            </tr>
                            <tr v-for="r in registros" :key="r.id_auditoria" class="hover:bg-crema transition-colors">
                                <td class="td font-mono text-[11.5px] text-tinta">{{ formatearFechaHora(r.fecha_hora) }}</td>
                                <td class="td font-mono text-[12px] font-semibold text-tinta">
                                    {{ r.usuario?.codigo_usuario ?? ('ID:' + r.id_usuario) }}
                                    <span v-if="r.usuario?.personal" class="block font-sans text-[11px] font-normal text-piedra">
                                        {{ r.usuario.personal.nombres }} {{ r.usuario.personal.apellidos }}
                                    </span>
                                </td>
                                <td class="td">
                                    <span :class="['badge', operacionBadgeClass(r.operacion)]">
                                        {{ formatearOperacion(r.operacion) }}
                                    </span>
                                </td>
                                <td class="td font-mono text-[12px] text-tinta font-semibold">{{ r.tabla_afectada }}</td>
                                <td class="td font-mono text-[12px] text-piedra font-semibold bg-crema/30 rounded px-2 py-1 inline-block my-2">
                                    {{ r.id_registro_afectado ?? r.id_registro ?? '—' }}
                                </td>
                                <td class="td font-mono text-[12px] text-piedra-soft">{{ r.ip_usuario ?? '127.0.0.1' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Logins -->
            <div v-else>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-borde bg-crema/40">
                                <th class="th">Fecha</th>
                                <th class="th">Hora</th>
                                <th class="th">Usuario</th>
                                <th class="th">IP de Acceso</th>
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
                                <td class="td font-mono text-[12.5px] font-semibold text-tinta">
                                    {{ l.usuario?.codigo_usuario ?? ('ID:' + l.id_usuario) }}
                                    <span v-if="l.usuario?.personal" class="block font-sans text-[11px] font-normal text-piedra">
                                        {{ l.usuario.personal.nombres }} {{ l.usuario.personal.apellidos }}
                                    </span>
                                </td>
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
            </div>

            <!-- Paginación -->
            <div v-if="pagination.last_page > 1" class="px-5 py-3 border-t border-borde flex items-center justify-between bg-crema/20">
                <span class="text-[12px] text-piedra">
                    Mostrando {{ pagination.from ?? 0 }} - {{ pagination.to ?? 0 }} de {{ pagination.total ?? 0 }} registros
                </span>
                <div class="flex gap-2">
                    <button @click="cargar(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                        class="btn-page" :class="{ 'opacity-40 cursor-not-allowed': pagination.current_page <= 1 }">
                        &laquo; Anterior
                    </button>
                    <span class="text-[12px] font-semibold text-tinta px-2 flex items-center">
                        Página {{ pagination.current_page }} de {{ pagination.last_page }}
                    </span>
                    <button @click="cargar(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                        class="btn-page" :class="{ 'opacity-40 cursor-not-allowed': pagination.current_page >= pagination.last_page }">
                        Siguiente &raquo;
                    </button>
                </div>
            </div>
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
.btn-page      { @apply border border-borde bg-paper px-3 py-1 text-[12px] font-semibold text-tinta rounded hover:bg-crema transition-colors; }
</style>
