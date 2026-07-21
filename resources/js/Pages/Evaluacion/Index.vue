<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

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
const esRevision  = reactive({}); // cedula->siglas->bool

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
        // Cargar materias disponibles
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
            if (!forceNoMomento) {
                return cargarGrilla(true);
            }
        }

        // Inicializar notas editables
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
    if (n >= notaMinima.value) return 'text-emerald-700 font-bold';
    if (n >= notaMinima.value - 3) return 'text-amber-600 font-semibold';
    return 'text-red-600 font-bold';
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
                    <h1 class="text-xl font-black text-slate-800">📈 Evaluaciones</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Ingreso de notas por sección y momento evaluativo</p>
                </div>
                <button v-if="cargado" @click="guardarTodo" :disabled="saving"
                    class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow transition disabled:opacity-50">
                    {{ saving ? 'Guardando…' : '💾 Guardar Todas las Notas' }}
                </button>
            </div>
        </template>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Año escolar -->
                <div>
                    <label for="filtro-anio" class="text-xs font-black uppercase text-slate-500 tracking-wider">Año Escolar</label>
                    <select id="filtro-anio" v-model="filtro.codigo_ano_escolar" @change="cargarSecciones"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option value="">Seleccionar…</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                </div>
                <!-- Sección -->
                <div>
                    <label for="filtro-seccion" class="text-xs font-black uppercase text-slate-500 tracking-wider">Sección</label>
                    <select id="filtro-seccion" v-model="filtro.codigo_seccion"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-sky-400"
                        :disabled="!secciones.length">
                        <option value="">Seleccionar…</option>
                        <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                            {{ s.grado?.nombre }} — {{ s.letra }} ({{ s.turno }})
                        </option>
                    </select>
                </div>
                <!-- Momento -->
                <div>
                    <label for="filtro-momento" class="text-xs font-black uppercase text-slate-500 tracking-wider">Momento Evaluativo</label>
                    <select id="filtro-momento" v-model="filtro.numero_momento"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option value="" disabled hidden>Seleccionar…</option>
                        <option v-if="!momentos.length" disabled value="">Sin momentos para este año</option>
                        <option v-for="momento in momentos" :key="momento.numero_momento" :value="momento.numero_momento">
                            {{ momento.numero_momento }}° Momento
                        </option>
                    </select>
                </div>
                <!-- Botón cargar -->
                <div class="flex items-end">
                    <button @click="cargarGrilla" :disabled="!filtro.codigo_seccion || loading"
                        class="w-full bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm py-2.5 rounded-xl transition disabled:opacity-40">
                        {{ loading ? 'Cargando…' : 'Cargar Grilla' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Info de sección -->
        <div v-if="cargado && seccionActual"
            class="bg-sky-50 border border-sky-200 rounded-xl px-5 py-3 mb-4 flex items-center gap-4 text-sm">
            <span class="font-black text-sky-700">{{ seccionActual.grado?.nombre }} — {{ seccionActual.letra }}</span>
            <span class="text-sky-600">{{ seccionActual.mencion?.nombre }}</span>
            <span class="text-sky-500 text-xs">{{ matriculas.length }} estudiantes · Nota mínima: <b>{{ notaMinima }}</b></span>
        </div>

        <!-- Grilla de notas -->
        <div v-if="cargado" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-x-auto">
            <table class="min-w-full text-xs border-collapse">
                <thead>
                    <tr>
                        <th class="sticky left-0 bg-slate-800 text-white px-3 py-3 text-left font-black uppercase tracking-wider z-10" style="min-width:180px">Estudiante</th>
                        <th v-for="pe in plan" :key="pe.siglas_materia"
                            class="bg-slate-700 text-white px-2 py-3 text-center font-black uppercase tracking-wider whitespace-nowrap"
                            style="min-width:90px">
                            <span :title="pe.materia?.nombre">{{ pe.siglas_materia }}</span>
                        </th>
                        <th class="bg-slate-600 text-white px-3 py-3 text-center font-black uppercase tracking-wider" style="min-width:80px">Promedio</th>
                    </tr>
                    <!-- Nombre completo de materia -->
                    <tr class="bg-slate-100">
                        <td class="sticky left-0 bg-slate-100 px-3 py-1 text-[10px] text-slate-400 font-semibold z-10"></td>
                        <td v-for="pe in plan" :key="pe.siglas_materia + '_n'"
                            class="px-2 py-1 text-[9px] text-slate-400 text-center truncate" :title="pe.materia?.nombre">
                            {{ pe.materia?.nombre }}
                        </td>
                        <td></td>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="(mat, idx) in matriculas" :key="mat.cedula_estudiante"
                        :class="['hover:bg-sky-50 transition', idx % 2 === 0 ? '' : 'bg-slate-50/40']">
                        <!-- Nombre del estudiante -->
                        <td class="sticky left-0 bg-white px-3 py-2 font-semibold text-slate-700 z-10 whitespace-nowrap border-r border-slate-100">
                            <div class="flex items-center gap-1">
                                <span class="text-[10px] text-slate-400 font-mono">{{ mat.numero_lista ?? idx+1 }}.</span>
                                <span class="flex-1">{{ mat.estudiante?.apellidos }}, {{ mat.estudiante?.nombres }}</span>
                                <button @click="abrirPendientes(mat)"
                                    title="Materias pendientes"
                                    class="text-amber-500 hover:text-amber-700 text-xs hover:bg-amber-50 px-1 py-0.5 rounded transition">⚠</button>
                            </div>
                        </td>
                        <!-- Input de nota por materia -->
                        <td v-for="pe in plan" :key="mat.cedula_estudiante + pe.siglas_materia" class="px-2 py-1 text-center">
                            <input
                                :id="`nota-${mat.cedula_estudiante}-${pe.siglas_materia}`"
                                v-model="notasEdit[mat.cedula_estudiante][pe.siglas_materia]"
                                type="number" min="0" max="20" step="0.1"
                                :class="['w-16 text-center border rounded-lg py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-400 transition',
                                    colorNota(notasEdit[mat.cedula_estudiante][pe.siglas_materia]),
                                    notasEdit[mat.cedula_estudiante][pe.siglas_materia] === '' ? 'border-slate-200 bg-slate-50' :
                                    Number.parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia]) >= notaMinima ? 'border-emerald-300 bg-emerald-50' : 'border-red-300 bg-red-50'
                                ]"
                                placeholder="—"
                            />
                        </td>
                        <!-- Promedio de la fila -->
                        <td class="px-3 py-2 text-center">
                            <span :class="['font-black text-sm', (() => {
                                const vals = plan.map(pe => parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia])).filter(n => !isNaN(n));
                                if (!vals.length) return 'text-slate-400';
                                const avg = vals.reduce((a,b) => a+b, 0) / vals.length;
                                return avg >= notaMinima ? 'text-emerald-700' : 'text-red-600';
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
            class="bg-white rounded-2xl shadow-sm border border-slate-100 p-16 text-center text-slate-400">
            <div class="text-5xl mb-4">📈</div>
            <p class="font-semibold text-slate-600">Selecciona el año escolar, la sección y el momento evaluativo</p>
            <p class="text-sm mt-1">Luego haz clic en <strong>Cargar Grilla</strong> para ingresar las notas.</p>
        </div>

        <!-- MODAL MATERIAS PENDIENTES POR ESTUDIANTE -->
        <Teleport to="body">
            <div v-if="showPendientes" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex items-center justify-between">
                        <div>
                            <h2 class="font-black text-slate-800 text-base">⚠️ Materias Pendientes</h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ pendienteEst?.estudiante?.apellidos }}, {{ pendienteEst?.estudiante?.nombres }}
                            </p>
                        </div>
                        <button @click="showPendientes = false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>

                    <!-- Formulario nueva pendiente -->
                    <div class="p-5 bg-amber-50 border-b border-amber-100">
                        <p class="text-xs font-black uppercase text-amber-700 tracking-widest mb-3">Registrar Nueva Pendiente</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="text-xs font-bold text-slate-600 uppercase">Materia *</label>
                                <select v-model="pendienteForm.siglas_materia"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 mt-1">
                                    <option value="">Seleccionar…</option>
                                    <option v-for="m in materiasDisp" :key="m.siglas" :value="m.siglas">
                                        {{ m.nombre }} ({{ m.siglas }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">Año Escolar Origen</label>
                                <input v-model="pendienteForm.codigo_ano_escolar_origen" type="text"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 mt-1" />
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">Estado</label>
                                <select v-model="pendienteForm.estado"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 mt-1">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="aprobada">Aprobada</option>
                                    <option value="no_presentada">No presentada</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">Nota Final</label>
                                <input v-model="pendienteForm.nota_final" type="number" min="0" max="20" step="0.1"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 mt-1" />
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">Fecha Resolución</label>
                                <input v-model="pendienteForm.fecha_resolucion" type="date"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 mt-1" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button @click="guardarPendiente" :disabled="!pendienteForm.siglas_materia || savingPend"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl disabled:opacity-50 transition">
                                {{ savingPend ? 'Guardando…' : '＋ Registrar Pendiente' }}
                            </button>
                        </div>
                    </div>

                    <!-- Lista de pendientes -->
                    <div class="p-5">
                        <div v-if="loadingPend" class="py-8 text-center text-slate-400">Cargando…</div>
                        <div v-else-if="!pendientesData.length" class="py-8 text-center text-slate-400 text-sm">
                            Sin materias pendientes registradas.
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-amber-50 border-b border-amber-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-black text-amber-700 uppercase">Materia</th>
                                    <th class="px-3 py-2 text-left text-xs font-black text-amber-700 uppercase">Año Origen</th>
                                    <th class="px-3 py-2 text-left text-xs font-black text-amber-700 uppercase">Estado</th>
                                    <th class="px-3 py-2 text-center text-xs font-black text-amber-700 uppercase">Nota</th>
                                    <th class="px-3 py-2 text-xs font-black text-amber-700 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="p in pendientesData" :key="p.id_materia_pendiente" class="hover:bg-slate-50">
                                    <td class="px-3 py-2 font-semibold">{{ p.materia?.nombre ?? p.siglas_materia }}</td>
                                    <td class="px-3 py-2 text-xs">{{ p.codigo_ano_escolar_origen }}</td>
                                    <td class="px-3 py-2">
                                        <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase',
                                            p.estado === 'pendiente'     ? 'bg-red-100 text-red-700' :
                                            p.estado === 'aprobada'      ? 'bg-emerald-100 text-emerald-700' :
                                            'bg-slate-100 text-slate-500']">
                                            {{ p.estado }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ p.nota_final ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <button v-if="p.estado === 'pendiente'"
                                            @click="actualizarEstadoPendiente(p, 'aprobada')"
                                            class="text-xs px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition">
                                            ✓ Aprobar
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
