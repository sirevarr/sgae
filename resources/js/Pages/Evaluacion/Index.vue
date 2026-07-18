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
    numero_momento: 1,
});

// ── Datos de la grilla ─────────────────────────────────────────────────
const plan        = ref([]);
const matriculas  = ref([]);
const evaluaciones = ref({});
const notaMinima  = ref(10);
const loading     = ref(false);
const saving      = ref(false);
const cargado     = ref(false);

// Notas temporales editadas en la grilla (cedula->siglas->nota)
const notasEdit = reactive({});

async function cargarCatalogos() {
    const [a] = await Promise.all([axios.get('/api/anios-escolares')]);
    anios.value = a.data;
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

async function cargarGrilla() {
    if (!filtro.codigo_seccion || !filtro.codigo_ano_escolar) return;
    loading.value = true;
    cargado.value = false;
    try {
        const { data } = await axios.get('/api/evaluaciones', { params: filtro });
        plan.value        = data.plan;
        matriculas.value  = data.matriculas;
        evaluaciones.value = data.evaluaciones;
        notaMinima.value  = data.nota_minima;
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
                    nota:                parseFloat(notaStr),
                });
            }
        }
    }
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
    const n = parseFloat(nota);
    if (n >= notaMinima.value) return 'text-emerald-700 font-bold';
    if (n >= notaMinima.value - 3) return 'text-amber-600 font-semibold';
    return 'text-red-600 font-bold';
}

onMounted(cargarCatalogos);
</script>

<template>
    <Head title="Evaluaciones — SGAE" />
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
                    <label class="text-xs font-black uppercase text-slate-500 tracking-wider">Año Escolar</label>
                    <select v-model="filtro.codigo_ano_escolar" @change="cargarSecciones"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option value="">Seleccionar…</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                </div>
                <!-- Sección -->
                <div>
                    <label class="text-xs font-black uppercase text-slate-500 tracking-wider">Sección</label>
                    <select v-model="filtro.codigo_seccion"
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
                    <label class="text-xs font-black uppercase text-slate-500 tracking-wider">Momento Evaluativo</label>
                    <select v-model="filtro.numero_momento"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm mt-1 focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option :value="1">1° Momento</option>
                        <option :value="2">2° Momento</option>
                        <option :value="3">3° Momento</option>
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
                            <span class="text-[10px] text-slate-400 font-mono mr-1">{{ mat.numero_lista ?? idx+1 }}.</span>
                            {{ mat.estudiante?.apellidos }}, {{ mat.estudiante?.nombres }}
                        </td>
                        <!-- Input de nota por materia -->
                        <td v-for="pe in plan" :key="mat.cedula_estudiante + pe.siglas_materia" class="px-2 py-1 text-center">
                            <input
                                v-model="notasEdit[mat.cedula_estudiante][pe.siglas_materia]"
                                type="number" min="0" max="20" step="0.1"
                                :class="['w-16 text-center border rounded-lg py-1 text-xs focus:outline-none focus:ring-1 focus:ring-sky-400 transition',
                                    colorNota(notasEdit[mat.cedula_estudiante][pe.siglas_materia]),
                                    notasEdit[mat.cedula_estudiante][pe.siglas_materia] === '' ? 'border-slate-200 bg-slate-50' :
                                    parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia]) >= notaMinima ? 'border-emerald-300 bg-emerald-50' : 'border-red-300 bg-red-50'
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
                                    const vals = plan.map(pe => parseFloat(notasEdit[mat.cedula_estudiante][pe.siglas_materia])).filter(n => !isNaN(n));
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
    </AuthenticatedLayout>
</template>
