<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageSections = computed(() => !['docente'].includes(userRole.value));

// ── Catálogos ─────────────────────────────────────────────────
const secciones  = ref([]);
const anios      = ref([]);
const grados     = ref([]);
const menciones  = ref([]);
const personal   = ref([]);
const materias   = ref([]);

// ── Filtro y estado principal ─────────────────────────────────
const filtroAnio  = ref('');
const modal       = ref(false);
const editando    = ref(false);
const viewing     = ref(false);
const saving      = ref(false);
const errors      = ref({});
const successMsg  = ref('');
const errorMsg    = ref('');

// ── Form de sección ───────────────────────────────────────────
const form = reactive({
    codigo_seccion:    '', letra: '', codigo_grado: '',
    codigo_ano_escolar: '', id_mencion: '',
    cedula_docente_guia: '', capacidad_maxima: 35,
    turno: 'M', aula_asignada: '',
});

// ── Modal de asignaciones ─────────────────────────────────────
const showAsignaciones = ref(false);
const seccionSelec     = ref(null);
const asignaciones     = ref([]);
const loadingAsig      = ref(false);
const savingAsig       = ref(false);
const asigForm = reactive({
    cedula_docente: '',
    siglas_materia: '',
    horas_asignadas: 2,
});

const docentesFiltrados = computed(() =>
    personal.value.filter(p => p.docente)
);

// ── Carga de datos ────────────────────────────────────────────
async function cargar() {
    const params = filtroAnio.value ? { codigo_ano_escolar: filtroAnio.value } : {};
    const { data } = await axios.get('/api/secciones', { params });
    secciones.value = data.data ?? data;
}

async function cargarCatalogos() {
    const [a, g, m, p, mat] = await Promise.all([
        axios.get('/api/anios-escolares'),
        axios.get('/api/grados'),
        axios.get('/api/menciones'),
        axios.get('/api/personal'),
        axios.get('/api/materias'),
    ]);
    anios.value    = a.data?.data ?? a.data;
    grados.value   = g.data?.data ?? g.data;
    menciones.value = m.data?.data ?? m.data;
    personal.value = p.data?.data ?? p.data;
    materias.value = mat.data?.data ?? mat.data;
}

// ── CRUD Sección ─────────────────────────────────────────────
function abrir(item = null) {
    viewing.value = false;
    editando.value = !!item;
    Object.assign(form, item ?? {
        codigo_seccion: '', letra: '', codigo_grado: '',
        codigo_ano_escolar: filtroAnio.value || '',
        id_mencion: '', cedula_docente_guia: '',
        capacidad_maxima: 35, turno: 'M', aula_asignada: '',
    });
    errors.value = {};
    modal.value = true;
}

function ver(item) {
    viewing.value = true;
    editando.value = false;
    Object.assign(form, item ?? {
        codigo_seccion: '', letra: '', codigo_grado: '',
        codigo_ano_escolar: filtroAnio.value || '',
        id_mencion: '', cedula_docente_guia: '',
        capacidad_maxima: 35, turno: 'M', aula_asignada: '',
    });
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/secciones/${form.codigo_seccion}`, form);
        else await axios.post('/api/secciones', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Sección actualizada.' : 'Sección creada.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    } finally {
        saving.value = false;
    }
}

async function eliminar(s) {
    if (!confirm(`¿Eliminar sección ${s.letra} (${s.codigo_seccion})?`)) return;
    errorMsg.value = '';
    successMsg.value = '';
    try {
        await axios.delete(`/api/secciones/${s.codigo_seccion}`);
        successMsg.value = 'Sección eliminada.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        const msg = e.response?.data?.error ?? e.response?.data?.message ?? e.message;
        errorMsg.value = msg;
        alert(msg);
    }
}

// ── Asignaciones Docente ──────────────────────────────────────
async function abrirAsignaciones(sec) {
    seccionSelec.value = sec;
    showAsignaciones.value = true;
    loadingAsig.value = true;
    Object.assign(asigForm, { cedula_docente: '', siglas_materia: '', horas_asignadas: 2 });
    try {
        const { data } = await axios.get(`/api/secciones/${sec.codigo_seccion}`);
        asignaciones.value = data.asignaciones ?? data.asignaciones_docente ?? [];
    } finally {
        loadingAsig.value = false;
    }
}

async function guardarAsignacion() {
    if (!asigForm.cedula_docente || !asigForm.siglas_materia) {
        alert('Selecciona docente y materia.');
        return;
    }
    savingAsig.value = true;
    try {
        await axios.post(`/api/secciones/${seccionSelec.value.codigo_seccion}/asignaciones`, {
            cedula_docente:  asigForm.cedula_docente,
            siglas_materia:  asigForm.siglas_materia,
            horas_asignadas: asigForm.horas_asignadas,
            codigo_ano_escolar: seccionSelec.value.codigo_ano_escolar,
            codigo_grado: seccionSelec.value.codigo_grado,
            id_mencion:   seccionSelec.value.id_mencion,
        });
        const { data } = await axios.get(`/api/secciones/${seccionSelec.value.codigo_seccion}`);
        asignaciones.value = data.asignaciones ?? data.asignaciones_docente ?? [];
        Object.assign(asigForm, { cedula_docente: '', siglas_materia: '', horas_asignadas: 2 });
    } catch (e) {
        alert('Error: ' + (e.response?.data?.error ?? e.response?.data?.message ?? e.message));
    } finally {
        savingAsig.value = false;
    }
}

const nombreDocente = (ced) => {
    const p = personal.value.find(x => x.cedula_personal == ced);
    return p ? `${p.apellidos}, ${p.nombres}` : ced;
};

const nombreMateria = (sig) => {
    const m = materias.value.find(x => x.siglas === sig);
    return m ? m.nombre : sig;
};

onMounted(async () => { await cargarCatalogos(); cargar(); });
</script>

<template>
    <Head title="Secciones — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full flex-wrap">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Secciones</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Organización de grupos, asignaciones docente por sección y año escolar</p>
                </div>
                <div class="flex gap-3">
                    <select v-model="filtroAnio" @change="cargar" class="inp-filter">
                        <option value="">Todos los años</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                            {{ a.codigo_ano_escolar }}
                        </option>
                    </select>
                    <button v-if="canManageSections" @click="abrir()" class="btn-primary ml-4">Nueva sección</button>
                </div>
            </div>
        </template>

        <!-- Éxito -->
        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <!-- Error -->
        <div v-if="errorMsg" class="mb-4 bg-[#F4DEDA] border border-rojo/20 text-rojo-dark text-[12px] font-semibold px-4 py-3 rounded-[4px] flex justify-between items-center">
            <span>{{ errorMsg }}</span>
            <button @click="errorMsg = ''" class="text-rojo-dark/70 hover:text-rojo-dark ml-4">&times;</button>
        </div>

        <!-- Grid de secciones -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-if="!secciones.length" class="col-span-full text-center py-10 text-piedra text-[13px]">
                No hay secciones.
            </div>
            <div v-if="!canManageSections" class="col-span-full bg-crema border border-borde rounded-[6px] p-4 text-[13px] text-piedra">
                Acceso de solo lectura: solo los roles de administrador y control de estudios pueden crear, editar o asignar docentes en secciones.
            </div>
            <div v-for="s in secciones" :key="s.codigo_seccion"
                class="bg-paper border border-borde rounded-[6px] p-5 hover:border-dorado transition-colors">
                <!-- Letra y código -->
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 border border-dorado rounded-[4px] flex items-center justify-center font-serif font-semibold text-[20px] text-tinta">
                        {{ s.letra }}
                    </div>
                    <span class="text-[11px] font-mono text-piedra-soft">{{ s.codigo_seccion }}</span>
                </div>
                <!-- Info -->
                <p class="font-semibold text-tinta text-[13px]">{{ s.grado?.nombre ?? s.codigo_grado }}</p>
                <p class="text-[11px] text-piedra mt-0.5">{{ s.mencion?.nombre ?? 'Sin mención' }}</p>
                <p class="text-[11px] text-piedra-soft mt-1">{{ s.turno }} · Aula: {{ s.aula_asignada ?? '—' }}</p>
                <p class="text-[11px] text-piedra-soft">
                    Cap: {{ s.capacidad_maxima }}
                    <span v-if="s.docente_guia?.personal"> · Guía: {{ s.docente_guia.personal.apellidos }}</span>
                </p>
                <!-- Asignaciones -->
                <div v-if="s.asignaciones_docente?.length" class="mt-2">
                    <span class="badge badge-neutral">{{ s.asignaciones_docente.length }} asignación(es)</span>
                </div>
                <!-- Acciones -->
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-borde gap-1">
                    <button @click="abrirAsignaciones(s)" class="btn-table-action font-semibold">Asignaciones</button>
                    <div class="flex gap-1">
                        <button v-if="canManageSections" @click="abrir(s)" class="btn-table-action">Editar</button>
                        <button v-if="canManageSections" @click="eliminar(s)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                        <button v-else @click="ver(s)" class="btn-table-action">Ver</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CREAR/EDITAR SECCIÓN -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto', { 'read-only': viewing || !canManageSections }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar' : 'Nueva' }} Sección</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!editando" class="col-span-2">
                            <label class="lbl">Código Sección *</label>
                            <input v-model="form.codigo_seccion" type="text" class="inp" placeholder="Ej: 1A-2025" />
                            <p v-if="errors.codigo_seccion" class="err">{{ errors.codigo_seccion[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Letra *</label>
                            <input v-model="form.letra" type="text" maxlength="1" class="inp" placeholder="A" />
                            <p v-if="errors.letra" class="err">{{ errors.letra[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" class="inp">
                                <option value="">—</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                                    {{ a.codigo_ano_escolar }}
                                </option>
                            </select>
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Grado *</label>
                            <select v-model="form.codigo_grado" class="inp">
                                <option value="">—</option>
                                <option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option>
                            </select>
                            <p v-if="errors.codigo_grado" class="err">{{ errors.codigo_grado[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Mención</label>
                            <select v-model="form.id_mencion" class="inp">
                                <option value="">—</option>
                                <option v-for="m in menciones" :key="m.id_mencion" :value="m.id_mencion">{{ m.nombre }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Docente Guía</label>
                            <select v-model="form.cedula_docente_guia" class="inp">
                                <option value="">—</option>
                                <option v-for="p in docentesFiltrados" :key="p.cedula_personal" :value="p.cedula_personal">
                                    {{ p.apellidos }}, {{ p.nombres }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Capacidad Máx.</label>
                            <input v-model="form.capacidad_maxima" type="number" class="inp" />
                        </div>
                        <div>
                            <label class="lbl">Turno *</label>
                            <select v-model="form.turno" class="inp">
                                <option value="M">Mañana</option>
                                <option value="T">Tarde</option>
                                <option value="N">Nocturno</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Aula Asignada</label>
                            <input v-model="form.aula_asignada" type="text" class="inp" placeholder="Ej: Aula 3B" />
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal = false" class="btn-secondary">Cerrar</button>
                        <button v-if="canManageSections" @click="guardar" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL ASIGNACIONES DOCENTE -->
        <Teleport to="body">
            <div v-if="showAsignaciones" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="px-5 py-4 border-b border-borde flex items-center justify-between">
                        <div>
                            <h2 class="font-serif font-semibold text-tinta text-[17px]">Asignaciones — Sección {{ seccionSelec?.letra }}</h2>
                            <p class="text-[11px] text-piedra mt-0.5">
                                {{ seccionSelec?.grado?.nombre ?? seccionSelec?.codigo_grado }} · {{ seccionSelec?.codigo_ano_escolar }}
                            </p>
                        </div>
                        <button @click="showAsignaciones = false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>

                    <!-- Formulario nueva asignación -->
                    <div class="p-5 border-b border-borde bg-crema">
                        <p class="text-[11px] font-semibold uppercase text-piedra tracking-[0.06em] mb-3">Nueva Asignación</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-3 sm:col-span-1">
                                <label class="lbl">Docente *</label>
                                <select v-model="asigForm.cedula_docente" class="inp mt-1">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="p in docentesFiltrados" :key="p.cedula_personal" :value="p.cedula_personal">
                                        {{ p.apellidos }}, {{ p.nombres }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <label class="lbl">Materia *</label>
                                <select v-model="asigForm.siglas_materia" class="inp mt-1">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="m in materias" :key="m.siglas" :value="m.siglas">
                                        {{ m.nombre }} ({{ m.siglas }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <label class="lbl">Horas Asignadas</label>
                                <input v-model="asigForm.horas_asignadas" type="number" min="1" max="40" class="inp mt-1" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button v-if="canManageSections" @click="guardarAsignacion" :disabled="savingAsig" class="btn-primary">
                                {{ savingAsig ? 'Guardando...' : 'Agregar Asignación' }}
                            </button>
                        </div>
                    </div>

                    <!-- Listado de asignaciones actuales -->
                    <div class="p-5">
                        <div v-if="loadingAsig" class="py-8 text-center text-piedra text-[13px]">Cargando asignaciones...</div>
                        <div v-else-if="!asignaciones.length" class="py-8 text-center text-piedra text-[13px]">
                            No hay asignaciones registradas para esta sección.
                        </div>
                        <table v-else class="w-full">
                            <thead>
                                <tr class="border-b border-borde">
                                    <th class="th">Docente</th>
                                    <th class="th">Materia</th>
                                    <th class="th text-center">Horas</th>
                                    <th class="th">Año Escolar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-borde">
                                <tr v-for="a in asignaciones" :key="a.id_asignacion" class="hover:bg-crema">
                                    <td class="td font-semibold text-[12.5px]">
                                        {{ a.docente?.personal?.nombre_completo ?? nombreDocente(a.cedula_docente) }}
                                    </td>
                                    <td class="td text-[12.5px]">
                                        {{ a.materia?.nombre ?? nombreMateria(a.siglas_materia) }}
                                        <span class="text-piedra-soft text-[11px]"> ({{ a.siglas_materia }})</span>
                                    </td>
                                    <td class="td text-center text-[12.5px]">{{ a.horas_asignadas }}</td>
                                    <td class="td text-[12px] text-piedra">{{ a.codigo_ano_escolar }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.lbl  { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp  { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.inp-filter { @apply border border-borde rounded-[4px] px-3 py-[9px] text-[13px] bg-paper text-tinta focus:outline-none focus:border-rojo transition-colors; }
.err  { @apply text-rojo text-[11px] mt-1; }
.th   { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td   { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
