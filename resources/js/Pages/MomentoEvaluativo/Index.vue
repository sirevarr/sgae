<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

const lista = ref([]);
const anios = ref([]);
const saving = ref(false);
const errors = ref({});
const modal = ref(false);
const editando = ref(false);
const viewing = ref(false);
const filtroAnio = ref('');
const successMsg = ref('');

const form = reactive({
    numero_momento: 1,
    codigo_ano_escolar: '',
    nombre: '',
    fecha_inicio: '',
    fecha_fin: '',
    porcentaje: '',
    estado: 'por_iniciar'
});

async function cargar() {
    const { data } = await axios.get('/api/momentos', { params: { codigo_ano_escolar: filtroAnio.value || undefined } });
    lista.value = data;
}

async function cargarAnios() {
    const { data } = await axios.get('/api/anios-escolares');
    anios.value = data;
}

function abrir(item = null) {
    editando.value = !!item;
    viewing.value = false;
    const payload = item ? { ...item } : {
        numero_momento: 1,
        codigo_ano_escolar: filtroAnio.value || '',
        nombre: '',
        fecha_inicio: '',
        fecha_fin: '',
        porcentaje: '',
        estado: 'programado',
    };
    if (payload.fecha_inicio) payload.fecha_inicio = String(payload.fecha_inicio).substring(0, 10);
    if (payload.fecha_fin) payload.fecha_fin = String(payload.fecha_fin).substring(0, 10);
    Object.assign(form, payload);
    errors.value = {};
    modal.value = true;
}

function ver(item) {
    editando.value = false;
    viewing.value = true;
    Object.assign(form, item);
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put('/api/momentos', form);
        else await axios.post('/api/momentos', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Momento actualizado.' : 'Momento creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    } finally {
        saving.value = false;
    }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar ${item.nombre} (${item.codigo_ano_escolar})?`)) return;
    try {
        await axios.delete('/api/momentos', {
            data: {
                numero_momento: item.numero_momento,
                codigo_ano_escolar: item.codigo_ano_escolar
            }
        });
        successMsg.value = 'Momento evaluativo eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.message ?? e.response?.data?.error ?? e.message);
    }
}

onMounted(async () => {
    await cargarAnios();
    cargar();
});
</script>

<template>
    <Head title="Momentos Evaluativos — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center flex-wrap gap-10 w-full">
                <div class="pr-8">
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Momentos Evaluativos</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Períodos de evaluación del año escolar</p>
                </div>
                <div class="flex gap-3">
                    <select v-model="filtroAnio" @change="cargar" class="inp-filter">
                        <option value="">Todos los años</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                    <button v-if="canManageRecords" @click="abrir()" class="btn-primary ml-4">
                        Nuevo Momento
                    </button>
                </div>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="m in lista" :key="`${m.numero_momento}-${m.codigo_ano_escolar}`"
                :class="['bg-paper border border-borde rounded-[6px] p-5 flex flex-col justify-between hover:border-dorado transition-colors',
                    m.estado === 'activo' ? 'border-t-[3px] border-t-dorado' : '']">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <div class="w-9 h-9 border border-dorado rounded-[4px] flex items-center justify-center font-serif font-semibold text-[15px] text-tinta">
                            {{ m.numero_momento }}°
                        </div>
                        <span :class="['badge',
                            m.estado === 'activo'      ? 'badge-ok' :
                            m.estado === 'por_iniciar' ? 'badge-alerta' :
                            'badge-neutral']">
                            {{ m.estado }}
                        </span>
                    </div>
                    <p class="font-semibold text-tinta text-[14px]">{{ m.nombre }}</p>
                    <p class="text-[11px] text-piedra-soft font-mono">{{ m.codigo_ano_escolar }}</p>
                    <p class="text-[11px] text-piedra mt-1">{{ m.fecha_inicio ?? '—' }} → {{ m.fecha_fin ?? '—' }}</p>
                    <p v-if="m.porcentaje" class="text-[11px] text-tinta font-semibold mt-1">{{ m.porcentaje }}% de la nota</p>
                </div>

                <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-borde">
                    <button v-if="canManageRecords" @click="abrir(m)" class="btn-table-action">Editar</button>
                    <button v-if="canManageRecords" @click="eliminar(m)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                    <button v-else @click="ver(m)" class="btn-table-action">Ver</button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-md', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">
                            {{ viewing ? 'Ver Momento' : editando ? 'Editar Momento' : 'Nuevo Momento' }}
                        </h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" class="inp mt-1">
                                <option value="">—</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                            </select>
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Momento *</label>
                            <select v-model="form.numero_momento" class="inp mt-1">
                                <option :value="1">1° Momento</option>
                                <option :value="2">2° Momento</option>
                                <option :value="3">3° Momento</option>
                            </select>
                            <p v-if="errors.numero_momento" class="err">{{ errors.numero_momento[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Nombre *</label>
                            <input v-model="form.nombre" type="text" class="inp mt-1" placeholder="Ej: Primer Momento" />
                            <p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="lbl">Fecha Inicio</label><input v-model="form.fecha_inicio" type="date" class="inp mt-1" />
                                <p v-if="errors.fecha_inicio" class="err">{{ errors.fecha_inicio[0] }}</p>
                            </div>
                            <div><label class="lbl">Fecha Fin</label><input v-model="form.fecha_fin" type="date" class="inp mt-1" />
                                <p v-if="errors.fecha_fin" class="err">{{ errors.fecha_fin[0] }}</p>
                            </div>
                        </div>
                        <div><label class="lbl">Porcentaje (%)</label><input v-model="form.porcentaje" type="number" min="0" max="100" class="inp mt-1" />
                            <p v-if="errors.porcentaje" class="err">{{ errors.porcentaje[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Estado *</label>
                            <select v-model="form.estado" class="inp mt-1">
                                <option value="por_iniciar">Por iniciar</option>
                                <option value="activo">Activo</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                            <p v-if="errors.estado" class="err">{{ errors.estado[0] }}</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cancelar</button>
                        <button v-if="canManageRecords && !viewing" @click="guardar" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors; }
.inp-filter    { @apply border border-borde rounded-[4px] px-3 py-[9px] text-[13px] bg-paper text-tinta focus:outline-none focus:border-rojo transition-colors; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
</style>
