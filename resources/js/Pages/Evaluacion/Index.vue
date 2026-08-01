<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol || 'docente').toLowerCase());
const canManageRecords = computed(() => ['administrador','control_estudios','docente'].includes(userRole.value));

// ── Catálogos ─────────────────────────────────────────────────────────
const anios    = ref([]);
const secciones = ref([]);

// ── Filtros de vista ──────────────────────────────────────────────────
const filtro = reactive({
    codigo_ano_escolar: '',
    codigo_seccion: '',
    numero_momento: null,
});

// ── Datos de la grilla ─────────────────────────────────────────────────
const plan        = ref([]);
const matriculas  = ref([]);
const evaluaciones = ref({});
const momentos    = ref([]);
const notaMinima  = ref(10);
const loading     = ref(false);
const saving      = ref(false);
const cargado     = ref(false);

// Notas temporales editadas en la grilla (cedula->siglas->nota)
const notasEdit   = reactive({});
const esRevision  = reactive({});

// ── Modal Materias Pendientes ─────────────────────────────────────────
const showPendientes    = ref(false);
const pendienteEst      = ref(null);
const pendientesData    = ref([]);
const loadingPend       = ref(false);
const materiasDisp      = ref([]);
const pendienteForm     = reactive({
    siglas_materia: '',
    codigo_grado: '',
    codigo_ano_escolar_origen: '',
    estado: 'pendiente',
    nota_final: '',
    fecha_resolucion: '',
});
const savingPend = ref(false);

async function abrirPendientes(mat) {
    pendienteEst.value = mat;
    showPendientes.value = true;
    loadingPend.value = true;
    Object.assign(pendienteForm, {
        siglas_materia: '', codigo_grado: seccionActual.value?.codigo_grado ?? '',
        codigo_ano_escolar_origen: filtro.codigo_ano_escolar,
        estado: 'pendiente', nota_final: '', fecha_resolucion: '',
    });
    try {
        const { data } = await axios.get('/api/materias-pendientes', {
            params: { cedula_estudiante: mat.cedula_estudiante }
        });
        pendientesData.value = data.data ?? data;
        const r = await axios.get('/api/materias');
        materiasDisp.value = r.data?.data ?? r.data;
    } finally {
        loadingPend.value = false;
    }
}

async function guardarPendiente() {
    savingPend.value = true;
    try {
        await axios.post('/api/materias-pendientes', {
            cedula_estudiante: pendienteEst.value.cedula_estudiante,
            siglas_materia: pendienteForm.siglas_materia,
            codigo_grado: pendienteForm.codigo_grado || seccionActual.value?.codigo_grado,
            codigo_ano_escolar_origen: pendienteForm.codigo_ano_escolar_origen,
            estado: pendienteForm.estado,
            nota_final: pendienteForm.nota_final || null,
            fecha_resolucion: pendienteForm.fecha_resolucion || null,
            id_mencion: seccionActual.value?.id_mencion ?? null,
        });
        const { data } = await axios.get('/api/materias-pendientes', {
            params: { cedula_estudiante: pendienteEst.value.cedula_estudiante }
        });
        pendientesData.value = data.data ?? data;
        Object.assign(pendienteForm, { siglas_materia: '', estado: 'pendiente', nota_final: '', fecha_resolucion: '' });
    } catch(e) {
        alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally {
        savingPend.value = false;
    }
}

async function actualizarEstadoPendiente(pend, nuevoEstado) {
    try {
        await axios.put(`/api/materias-pendientes/${pend.id_materia_pendiente}`, { estado: nuevoEstado });
        const idx = pendientesData.value.findIndex(p => p.id_materia_pendiente === pend.id_materia_pendiente);
        if (idx >= 0) pendientesData.value[idx].estado = nuevoEstado;
    } catch(e) {
        alert('Error: ' + (e.response?.data?.message ?? e.message));
    }
}

async function cargarCatalogos() {
    const { data } = await axios.get('/api/anios-escolares');
    anios.value = data;
}

async function cargarSecciones() {
    if (!filtro.codigo_ano_escolar) return;
    const { data } = await axios.get('/api/secciones', {
        params: { codigo_ano_escolar: filtro.codigo_ano_escolar }
    });
    secciones.value = data;
    filtro.codigo_seccion = '';
    cargado.value = false;
}

async function cargarGrilla(forceNoMomento = false) {
    if (!filtro.codigo_seccion || !filtro.codigo_ano_escolar) return;
    loading.value = true;
    cargado.value = false;
    try {
        const params = {
            codigo_ano_escolar: filtro.codigo_ano_escolar,
            codigo_seccion: filtro.codigo_seccion,
        };
        if (!forceNoMomento && filtro.numero_momento) {
            params.numero_momento = filtro.numero_momento;
        }
        const { data } = await axios.get('/api/evaluaciones', { params });
        plan.value         = data.plan;
        matriculas.value   = data.matriculas;
        evaluaciones.value = data.evaluaciones;
        momentos.value     = data.momentos;
        notaMinima.value   = data.nota_minima;

        const momentoValido = momentos.value.some(m => m.numero_momento === filtro.numero_momento);
        if (!momentos.value.length) {
            filtro.numero_momento = null;
        } else if (!momentoValido) {
            filtro.numero_momento = momentos.value[0].numero_momento;
            if (!forceNoMomento) return cargarGrilla(true);
        }

        for (const mat of data.matriculas) {
            const ced = mat.cedula_estudiante;
            notasEdit[ced] = {};
            for (const pe of data.plan) {
                const s = pe.siglas_materia;
                const nota = data.evaluaciones?.[ced]?.[s]
                    ?.find(e => e.numero_momento === filtro.numero_momento)?.nota;
                notasEdit[ced][s] = nota !== undefined ? String(nota) : '';
            }
        }
        cargado.value = true;
    } finally {
        loading.value = false;
    }
}

const seccionActual = computed(() =>
    secciones.value.find(s => s.codigo_seccion === filtro.codigo_seccion)
);

async function guardarTodo() {
    saving.value = true;
    const lote = [];
    for (const mat of matriculas.value) {
        const ced = mat.cedula_estudiante;
        for (const pe of plan.value) {
            const s = pe.siglas_materia;
            const notaStr = notasEdit[ced]?.[s];
            if (notaStr !== '' && notaStr !== undefined && notaStr !== null) {
                lote.push({
                    cedula_estudiante:   ced,
                    siglas_materia:      s,
                    id_mencion:          seccionActual.value?.id_mencion,
                    codigo_grado:        seccionActual.value?.codigo_grado,
                    codigo_ano_escolar:  filtro.codigo_ano_escolar,
                    numero_momento:      filtro.numero_momento,
                    nota:                Number.parseFloat(notaStr),
                    es_revision:         esRevision[ced]?.[s] ?? false,
                });
            }
        }
    }
    if (!filtro.numero_momento) { alert('Debe seleccionar un momento evaluativo válido.'); saving.value = false; return; }
    if (!lote.length) { alert('No hay notas para guardar.'); saving.value = false; return; }
    try {
        const { data } = await axios.post('/api/evaluaciones/guardar-lote', { notas: lote });
        alert(data.message);
        cargarGrilla();
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message ?? e.message));
    } finally {
        saving.value = false;
    }
}

function colorNota(nota) {
    if (nota === '' || nota === null || nota === undefined) return '';
    const n = Number.parseFloat(nota);
    if (n >= notaMinima.value) return 'text-ok font-semibold';
    if (n >= notaMinima.value - 3) return 'text-alerta font-semibold';
    return 'text-rojo font-semibold';
}

onMounted(cargarCatalogos);
</script>

<template>
    <Head>
        <title>Evaluaciones — SGAE</title>
    </Head>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Evaluaciones</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Ingreso de notas por sección y momento evaluativo</p>
                </div>
                <button v-if="cargado && canManageRecords" @click="guardarTodo" :disabled="saving"
                    class="btn-primary">
                    {{ saving ? 'Guardando...' : 'Guardar Todas las Notas' }}
                </button>
                <div v-else-if="cargado" class="border border-borde bg-crema px-3 py-2 rounded-[4px] text-[12px] text-piedra font-semibold">
                    Modo docente: solo visualización y exportación.
                </div>
            </div>
        </template>

        <!-- Filtros -->
        <div class="bg-paper border border-borde rounded-[6px] p-5 mb-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="filtro-anio" class="lbl">Año Escolar</label>
                    <select id="filtro-anio" v-model="filtro.codigo_ano_escolar" @change="cargarSecciones" class="inp mt-1">
                        <option value="">Seleccionar...</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                </div>
                <div>
                    <label for="filtro-seccion" class="lbl">Sección</label>
                    <select id="filtro-seccion" v-model="filtro.codigo_seccion"
                        class="inp mt-1" :disabled="!secciones.length">
                        <option value="">Seleccionar...</option>
                        <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                            {{ s.grado?.nombre }} — {{ s.letra }} ({{ s.turno }})
                        </option>
                    </select>
                </div>
                <div>
                    <label for="filtro-momento" class="lbl">Momento Evaluativo</label>
                    <select id="filtro-momento" v-model="filtro.numero_momento" class="inp mt-1">
                        <option value="" disabled hidden>Seleccionar...</option>
                        <option v-if="!momentos.length" disabled value="">Sin momentos para este año</option>
                        <option v-for="momento in momentos" :key="momento.numero_momento" :value="momento.numero_momento">
                            {{ momento.numero_momento }}° Momento
                        </option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button @click="cargarGrilla" :disabled="!filtro.codigo_seccion || loading"
                        class="w-full btn-primary disabled:opacity-40">
                        {{ loading ? 'Cargando...' : 'Cargar Grilla' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Info de sección -->
        <div v-if="cargado && seccionActual"
            class="bg-paper border border-borde border-l-[3px] border-l-dorado rounded-[6px] px-5 py-3 mb-4 flex items-center gap-4">
            <span class="font-semibold text-tinta text-[13px]">{{ seccionActual.grado?.nombre }} — {{ seccionActual.letra }}</span>
            <span class="text-piedra text-[12px]">{{ seccionActual.mencion?.nombre }}</span>
            <span class="text-piedra text-[12px]">{{ matriculas.length }} estudiantes · Nota mínima: <strong>{{ notaMinima }}</strong></span>
        </div>

        <!-- Grilla de notas -->
        <div v-if="cargado" class="bg-paper border border-borde rounded-[6px] overflow-x-auto">
            <div v-if="!canManageRecords" class="border-b border-borde bg-crema px-4 py-2.5 text-[12px] font-semibold text-piedra">
                El docente solo puede consultar las calificaciones y exportar reportes; no puede registrar ni modificar notas.
            </div>
            <table class="min-w-full text-xs border-collapse">
                <thead>
                    <tr>
                        <th class="sticky left-0 bg-tinta text-paper px-3 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] z-10" style="min-width:180px">Estudiante</th>
                        <th v-for="pe in plan" :key="pe.siglas_materia"
                            class="bg-tinta-soft text-paper px-2 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.04em] whitespace-nowrap"
                            style="min-width:90px">
                            <span :title="pe.materia?.nombre">{{ pe.siglas_materia }}</span>
                        </th>
                        <th class="bg-tinta text-paper px-3 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.04em]" style="min-width:80px">Prom.</th>
                    </tr>
                    <!-- Nombre completo de materia -->
                    <tr class="bg-crema">
                        <td class="sticky left-0 bg-crema px-3 py-1 text-[10px] text-piedra z-10"></td>
                        <td v-for="pe in plan" :key="pe.siglas_materia + '_n'"
                            class="px-2 py-1 text-[10px] text-piedra text-center truncate" :title="pe.materia?.nombre">
                            {{ pe.materia?.nombre }}
                        </td>
                        <td></td>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-for="(mat, idx) in matriculas" :key="mat.cedula_estudiante"
                        class="hover:bg-crema transition-colors">
                        <!-- Nombre del estudiante -->
                        <td class="sticky left-0 bg-paper px-3 py-2 font-semibold text-tinta z-10 whitespace-nowrap border-r border-borde text-[12.5px]">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] text-piedra font-mono">{{ mat.numero_lista ?? idx+1 }}.</span>
                                <span class="flex-1">{{ mat.estudiante?.apellidos }}, {{ mat.estudiante?.nombres }}</span>
                                <button v-if="canManageRecords" @click="abrirPendientes(mat)"
                                    title="Materias pendientes"
                                    class="text-[11px] text-alerta hover:text-tinta px-1 py-0.5 rounded hover:bg-crema transition-colors">
                                    Pend.
                                </button>
                            </div>
                        </td>
                        <!-- Input de nota por materia -->
                        <td v-for="pe in plan" :key="mat.cedula_estudiante + pe.siglas_materia" class="px-2 py-1 text-center">
                            <input
                                v-if="canManageRecords"
                                :id="`nota-${mat.cedula_estudiante}-${pe.siglas_materia}`"
                                v-model="notasEdit[mat.cedula_estudiante][pe.siglas_materia]"
                                type="number" min="0" max="20" step="0.1"
                                :class="['w-14 text-center border rounded-[4px] py-1 text-[12px] focus:outline-none focus:border-rojo transition-colors',
                                    colorNota(notasEdit[mat.cedula_estudiante][pe.siglas_materia]),
                                    notasEdit[mat.cedula_estudiante][pe.siglas_materia] === '' ? 'border-borde bg-crema' :
                                    Number.parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia]) >= notaMinima ? 'border-ok/40 bg-[#E6EEE0]' : 'border-rojo/30 bg-[#F4DEDA]'
                                ]"
                                placeholder="—"
                            />
                            <span v-else class="text-piedra text-[12px]">
                                {{ notasEdit[mat.cedula_estudiante][pe.siglas_materia] || '—' }}
                            </span>
                        </td>
                        <!-- Promedio de la fila -->
                        <td class="px-3 py-2 text-center">
                            <span :class="['font-semibold text-[12.5px]', (() => {
                                const vals = plan.map(pe => parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia])).filter(n => !isNaN(n));
                                if (!vals.length) return 'text-piedra';
                                const avg = vals.reduce((a,b) => a+b, 0) / vals.length;
                                return avg >= notaMinima ? 'text-ok' : 'text-rojo';
                            })()]">
                                {{ (() => {
                                    const vals = plan.map(pe => Number.parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia])).filter(n => !isNaN(n));
                                    return vals.length ? (vals.reduce((a,b) => a+b, 0) / vals.length).toFixed(2) : '—';
                                })() }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado vacío -->
        <div v-else-if="!loading && !cargado"
            class="bg-paper border border-borde rounded-[6px] p-16 text-center text-piedra">
            <p class="font-semibold text-tinta text-[14px]">Selecciona el año escolar, la sección y el momento evaluativo</p>
            <p class="text-[12px] mt-1">Luego haz clic en <strong>Cargar Grilla</strong> para ingresar las notas.</p>
        </div>

        <!-- MODAL MATERIAS PENDIENTES POR ESTUDIANTE -->
        <Teleport to="body">
            <div v-if="showPendientes" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="px-5 py-4 border-b border-borde flex items-center justify-between">
                        <div>
                            <h2 class="font-serif font-semibold text-tinta text-[16px]">Materias Pendientes</h2>
                            <p class="text-[11px] text-piedra mt-0.5">
                                {{ pendienteEst?.estudiante?.apellidos }}, {{ pendienteEst?.estudiante?.nombres }}
                            </p>
                        </div>
                        <button @click="showPendientes = false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>

                    <!-- Formulario nueva pendiente -->
                    <div v-if="canManageRecords" class="p-5 border-b border-borde bg-crema">
                        <p class="text-[11px] font-semibold uppercase text-piedra tracking-[0.06em] mb-3">Registrar Nueva Pendiente</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="lbl">Materia *</label>
                                <select v-model="pendienteForm.siglas_materia" class="inp mt-1">
                                    <option value="">Seleccionar...</option>
                                    <option v-for="m in materiasDisp" :key="m.siglas" :value="m.siglas">
                                        {{ m.nombre }} ({{ m.siglas }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="lbl">Año Escolar Origen</label>
                                <input v-model="pendienteForm.codigo_ano_escolar_origen" type="text" class="inp mt-1" />
                            </div>
                            <div>
                                <label class="lbl">Estado</label>
                                <select v-model="pendienteForm.estado" class="inp mt-1">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aprobada">Aprobada</option>
                                    <option value="no_aprobada">No aprobada</option>
                                </select>
                            </div>
                            <div>
                                <label class="lbl">Nota Final</label>
                                <input v-model="pendienteForm.nota_final" type="number" min="0" max="20" step="0.1" class="inp mt-1" />
                            </div>
                            <div>
                                <label class="lbl">Fecha Resolución</label>
                                <input v-model="pendienteForm.fecha_resolucion" type="date" class="inp mt-1" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button @click="guardarPendiente" :disabled="!pendienteForm.siglas_materia || savingPend"
                                class="btn-primary">
                                {{ savingPend ? 'Guardando...' : 'Registrar Pendiente' }}
                            </button>
                        </div>
                    </div>

                    <!-- Lista de pendientes -->
                    <div class="p-5">
                        <div v-if="loadingPend" class="py-8 text-center text-piedra text-[13px]">Cargando...</div>
                        <div v-else-if="!pendientesData.length" class="py-8 text-center text-piedra text-[13px]">
                            Sin materias pendientes registradas.
                        </div>
                        <table v-else class="w-full">
                            <thead>
                                <tr class="border-b border-borde">
                                    <th class="th">Materia</th>
                                    <th class="th">Año Origen</th>
                                    <th class="th">Estado</th>
                                    <th class="th text-center">Nota</th>
                                    <th class="th">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-borde">
                                <tr v-for="p in pendientesData" :key="p.id_materia_pendiente" class="hover:bg-crema">
                                    <td class="td font-semibold text-[12.5px]">{{ p.materia?.nombre ?? p.siglas_materia }}</td>
                                    <td class="td text-[12px] text-piedra">{{ p.codigo_ano_escolar_origen }}</td>
                                    <td class="td">
                                        <span :class="['badge',
                                            p.estado === 'pendiente'     ? 'badge-alerta' :
                                            p.estado === 'aprobada'      ? 'badge-ok' :
                                            'badge-neutral']">
                                            {{ p.estado }}
                                        </span>
                                    </td>
                                    <td class="td text-center text-[12.5px]">{{ p.nota_final ?? '—' }}</td>
                                    <td class="td">
                                        <button v-if="p.estado === 'pendiente'"
                                            @click="actualizarEstadoPendiente(p, 'aprobada')"
                                            class="text-[12px] px-2 py-1 bg-[#E6EEE0] text-ok border border-ok/30 rounded-[4px] hover:bg-[#d4e8d0] transition-colors">
                                            Aprobar
                                        </button>
                                    </td>
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
.inp  { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors; }
.th   { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td   { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary   { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors disabled:opacity-50; }
</style>
