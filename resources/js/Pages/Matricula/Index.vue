<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

const matriculas   = ref([]);
const loading      = ref(false);
const pagination   = ref({});
const anios        = ref([]);
const secciones    = ref([]);
const estudiantes  = ref([]);
const representantes = ref([]);

const filtros = reactive({ codigo_ano_escolar: '', codigo_seccion: '', buscar: '' });
const modal   = ref(false);
const editando = ref(false);
const viewing = ref(false);
const saving  = ref(false);
const errors  = ref({});
const successMsg = ref('');

const form = reactive({
    cedula_estudiante: '', codigo_ano_escolar: '', codigo_seccion: '',
    cedula_representante: '', fecha_matricula: new Date().toISOString().split('T')[0],
    numero_lista: '', condicion_ingreso: 'NE', procedencia: '',
    ano_inicio_cursante: '', estado_matricula: 'activa', observaciones: '',
    id_matricula: null,
});

const condiciones = [
    { value: 'NE', label: 'Nuevo Ingreso' },
    { value: 'PR', label: 'Promovido' },
    { value: 'TR', label: 'Traslado' },
    { value: 'RE', label: 'Repitiente' },
];

async function cargar(page = 1) {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/matriculas', { params: { ...filtros, page } });
        matriculas.value = data.data;
        pagination.value = data;
    } finally {
        loading.value = false;
    }
}

async function cargarCatalogos() {
    const [a, e, r] = await Promise.all([
        axios.get('/api/anios-escolares'),
        axios.get('/api/estudiantes', { params: { buscar: '', page: 1 } }),
        axios.get('/api/representantes', { params: { buscar: '', page: 1 } }),
    ]);
    anios.value         = a.data;
    estudiantes.value   = e.data.data ?? e.data;
    representantes.value = r.data.data ?? r.data;
}

async function cargarSecciones() {
    if (!filtros.codigo_ano_escolar && !form.codigo_ano_escolar) return;
    const anio = form.codigo_ano_escolar || filtros.codigo_ano_escolar;
    const { data } = await axios.get('/api/secciones', { params: { codigo_ano_escolar: anio } });
    secciones.value = data;
}

function abrirNuevo() {
    viewing.value = false;
    editando.value = false;
    Object.assign(form, {
        cedula_estudiante: '', codigo_ano_escolar: filtros.codigo_ano_escolar || '',
        codigo_seccion: '', cedula_representante: '', numero_lista: '',
        condicion_ingreso: 'NE', procedencia: '', ano_inicio_cursante: '',
        estado_matricula: 'activa', observaciones: '',
        fecha_matricula: new Date().toISOString().split('T')[0], id_matricula: null,
    });
    errors.value = {};
    cargarSecciones();
    modal.value = true;
}

function abrirEditar(m) {
    viewing.value = false;
    editando.value = true;
    const payload = { ...m, cedula_representante: m.cedula_representante ?? '', id_matricula: m.id_matricula };
    if (payload.fecha_matricula) payload.fecha_matricula = String(payload.fecha_matricula).substring(0, 10);
    Object.assign(form, payload);
    errors.value = {};
    cargarSecciones();
    modal.value = true;
}

function ver(m) {
    viewing.value = true;
    editando.value = false;
    const payload = { ...m, cedula_representante: m.cedula_representante ?? '', id_matricula: m.id_matricula };
    if (payload.fecha_matricula) payload.fecha_matricula = String(payload.fecha_matricula).substring(0, 10);
    Object.assign(form, payload);
    errors.value = {};
    cargarSecciones();
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/matriculas/${form.id_matricula}`, form);
        else await axios.post('/api/matriculas', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Matrícula actualizada.' : 'Matrícula creada.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else if (e.response?.data?.error) alert(e.response.data.error);
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally {
        saving.value = false;
    }
}

async function eliminar(m) {
    if (!confirm(`¿Eliminar la matrícula de "${m.estudiante?.apellidos}, ${m.estudiante?.nombres}"?`)) return;
    try {
        await axios.delete(`/api/matriculas/${m.id_matricula}`);
        successMsg.value = 'Matrícula eliminada correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    }
}

let timer;
function onFiltro() { clearTimeout(timer); timer = setTimeout(() => cargar(), 400); }

onMounted(async () => {
    await cargarCatalogos();
    cargar();
});
</script>

<template>
    <Head title="Matrículas — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-8 w-full">
                <div class="pr-8">
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Matrículas</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Inscripción de estudiantes en secciones del año escolar</p>
                </div>
                <button v-if="canManageRecords" @click="abrirNuevo" class="btn-primary ml-auto">Nueva matrícula</button>
            </div>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="filtros.codigo_ano_escolar" @change="() => { cargarSecciones(); onFiltro(); }" class="inp-filter">
                <option value="">Todos los años</option>
                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                    {{ a.codigo_ano_escolar }}
                </option>
            </select>
            <select v-model="filtros.codigo_seccion" @change="onFiltro" class="inp-filter">
                <option value="">Todas las secciones</option>
                <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                    {{ s.grado?.nombre }} {{ s.letra }}
                </option>
            </select>
            <input v-model="filtros.buscar" @input="onFiltro" type="text"
                placeholder="Buscar estudiante..."
                class="inp flex-1 min-w-48" />
        </div>

        <!-- Éxito -->
        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <!-- Tabla -->
        <div class="bg-paper border border-borde rounded-[6px] overflow-x-auto">
            <div v-if="loading" class="p-10 text-center text-piedra text-[13px]">Cargando matrículas...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">#</th>
                        <th class="th">Estudiante</th>
                        <th class="th">Sección / Grado</th>
                        <th class="th">Año Escolar</th>
                        <th class="th">Condición</th>
                        <th class="th">Estado</th>
                        <th class="th text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!matriculas.length">
                        <td colspan="7" class="text-center py-10 text-piedra text-[13px]">No hay matrículas registradas.</td>
                    </tr>
                    <tr v-for="m in matriculas" :key="m.id_matricula" class="hover:bg-crema transition-colors">
                        <td class="td text-piedra font-mono text-[12px]">{{ m.numero_lista ?? '—' }}</td>
                        <td class="td">
                            <p class="font-semibold text-tinta text-[12.5px]">{{ m.estudiante?.apellidos }}, {{ m.estudiante?.nombres }}</p>
                            <p class="text-[11px] text-piedra-soft font-mono">{{ m.estudiante?.tipo_documento }}-{{ m.estudiante?.cedula_estudiante }}</p>
                        </td>
                        <td class="td">
                            <p class="font-semibold text-[12.5px] text-tinta">{{ m.seccion?.grado?.nombre }} — {{ m.seccion?.letra }}</p>
                            <p class="text-[11px] text-piedra">{{ m.seccion?.turno }}</p>
                        </td>
                        <td class="td font-mono text-[12px] text-piedra">{{ m.codigo_ano_escolar }}</td>
                        <td class="td text-[12.5px] text-piedra">
                            {{ condiciones.find(c => c.value === m.condicion_ingreso)?.label ?? m.condicion_ingreso }}
                        </td>
                        <td class="td">
                            <span :class="[
                                'badge',
                                m.estado_matricula === 'activa'    ? 'badge-ok' :
                                m.estado_matricula === 'retirada'  ? 'badge-alerta' :
                                'badge-neutral'
                            ]">{{ m.estado_matricula }}</span>
                        </td>
                        <td class="td text-center">
                            <div class="flex justify-center gap-2">
                                    <button v-if="canManageRecords" @click="abrirEditar(m)" class="btn-table-action">Editar</button>
                                    <button v-if="canManageRecords" @click="eliminar(m)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                                    <button v-else @click="ver(m)" class="btn-table-action">Ver</button>
                                </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="pagination.last_page > 1" class="flex justify-between items-center px-5 py-3 border-t border-borde bg-crema">
                <span class="text-[11px] text-piedra">{{ pagination.from }}–{{ pagination.to }} de {{ pagination.total }}</span>
                <div class="flex gap-1">
                    <button v-for="p in pagination.last_page" :key="p" @click="cargar(p)"
                        :class="['w-7 h-7 rounded-[4px] text-[11px] font-semibold transition-colors',
                            p === pagination.current_page
                                ? 'bg-rojo text-paper'
                                : 'bg-paper text-piedra hover:bg-crema border border-borde']">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex items-center justify-between">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar Matrícula' : 'Nueva Matrícula' }}</h2>
                        <button @click="modal = false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2" v-if="!editando">
                            <label class="lbl">Estudiante *</label>
                            <select v-model="form.cedula_estudiante" class="inp">
                                <option value="">Seleccionar estudiante...</option>
                                <option v-for="e in estudiantes" :key="e.cedula_estudiante" :value="e.cedula_estudiante">
                                    {{ e.apellidos }}, {{ e.nombres }} (C.I. {{ e.cedula_estudiante }})
                                </option>
                            </select>
                            <p v-if="errors.cedula_estudiante" class="err">{{ errors.cedula_estudiante[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" @change="cargarSecciones" class="inp">
                                <option value="">Seleccionar...</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                            </select>
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Sección *</label>
                            <select v-model="form.codigo_seccion" class="inp">
                                <option value="">Seleccionar...</option>
                                <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                                    {{ s.grado?.nombre }} — {{ s.letra }} ({{ s.cupos_disponibles ?? '?' }} cupos)
                                </option>
                            </select>
                            <p v-if="errors.codigo_seccion" class="err">{{ errors.codigo_seccion[0] }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Cédula Representante</label>
                            <input v-model="form.cedula_representante" type="text" class="inp" placeholder="Opcional" />
                        </div>
                        <div>
                            <label class="lbl">Fecha Matrícula *</label>
                            <input v-model="form.fecha_matricula" type="date" class="inp" />
                            <p v-if="errors.fecha_matricula" class="err">{{ errors.fecha_matricula[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">N° Lista</label>
                            <input v-model="form.numero_lista" type="number" class="inp" min="1" />
                            <p v-if="errors.numero_lista" class="err">{{ errors.numero_lista[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Condición de Ingreso *</label>
                            <select v-model="form.condicion_ingreso" class="inp">
                                <option v-for="c in condiciones" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                            <p v-if="errors.condicion_ingreso" class="err">{{ errors.condicion_ingreso[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Año Inicio Cursante</label>
                            <input v-model="form.ano_inicio_cursante" type="number" class="inp" placeholder="Ej: 2019" />
                            <p v-if="errors.ano_inicio_cursante" class="err">{{ errors.ano_inicio_cursante[0] }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Procedencia</label>
                            <input v-model="form.procedencia" type="text" class="inp" placeholder="Plantel de procedencia" />
                            <p v-if="errors.procedencia" class="err">{{ errors.procedencia[0] }}</p>
                        </div>
                        <div v-if="editando">
                            <label class="lbl">Estado</label>
                            <select v-model="form.estado_matricula" class="inp">
                                <option value="activa">Activa</option>
                                <option value="retirada">Retirada</option>
                                <option value="trasladada">Trasladada</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="2" class="inp"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal = false" class="btn-secondary">Cerrar</button>
                        <button v-if="canManageRecords" @click="guardar" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
            </AuthenticatedLayout>
        </template>

<style scoped>
.lbl        { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp        { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.inp-filter { @apply border border-borde rounded-[4px] px-3 py-[9px] text-[13px] bg-paper text-tinta focus:outline-none focus:border-rojo transition-colors; }
.err        { @apply text-rojo text-[11px] mt-1; }
.th         { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td         { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary      { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary    { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
