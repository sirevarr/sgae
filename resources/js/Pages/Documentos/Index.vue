<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

// ── Catálogos ──────────────────────────────────────────────────
const anios       = ref([]);
const secciones   = ref([]);
const estudiantes = ref([]);
const documentos  = ref([]);
const loading     = ref(false);
const loadingDoc  = ref(false);
const errorMsg    = ref('');
const tab         = ref('estudiante'); // 'estudiante' | 'seccion'

// ── Formulario por estudiante ─────────────────────────────────
const filtro = reactive({
    cedula_estudiante: '',
    codigo_ano_escolar: '',
    nombre_completo: '',
});
const selMomento = ref(null);
const selMotivo  = ref('');

// ── Formulario por sección ────────────────────────────────────
const filtroSec = reactive({
    codigo_seccion: '',
    codigo_ano_escolar: '',
    numero_momento: null,
});

// ── Tipos de documentos por estudiante ───────────────────────
const tiposEstudiante = [
    { key: 'boletin',                label: 'Boletín de Calificaciones',  icon: '📊', color: '#0ea5e9', momentos: true  },
    { key: 'constancia_estudio',     label: 'Constancia de Estudios',     icon: '📄', color: '#10b981', momentos: false },
    { key: 'constancia_conducta',    label: 'Constancia de Buena Conducta', icon: '✅', color: '#8b5cf6', momentos: false },
    { key: 'constancia_prosecucion', label: 'Constancia de Prosecución',  icon: '🎓', color: '#f59e0b', momentos: false },
    { key: 'constancia_asistencia',  label: 'Constancia de Asistencia',   icon: '📅', color: '#6366f1', momentos: false },
];

// ── Tipos de documentos por sección ──────────────────────────
const tiposSeccion = [
    { key: 'lista_seccion',   label: 'Lista de Sección',              icon: '📋', color: '#0ea5e9', momentos: false },
    { key: 'resumen_seccion', label: 'Resumen de Calificaciones',     icon: '📈', color: '#ef4444', momentos: true  },
];

// Mapa etiquetas para historial
const tipoLabel = {
    boletin:                'Boletín',
    constancia_estudio:     'Const. Estudios',
    constancia_conducta:    'Const. Conducta',
    constancia_prosecucion: 'Const. Prosecución',
    constancia_asistencia:  'Const. Asistencia',
};

// ── Funciones de carga ────────────────────────────────────────
async function cargarCatalogos() {
    const [{ data: aniosData }, { data: estudiantesData }, { data: seccionesData }] = await Promise.all([
        axios.get('/api/anios-escolares'),
        axios.get('/api/estudiantes'),
        axios.get('/api/secciones'),
    ]);
    anios.value      = aniosData.data ?? aniosData;
    estudiantes.value = estudiantesData.data ?? estudiantesData;
    secciones.value  = seccionesData.data ?? seccionesData;
}

async function cargarDocumentos() {
    if (!filtro.cedula_estudiante) return;
    loading.value = true;
    try {
        const { data } = await axios.get('/api/documentos', {
            params: {
                cedula_estudiante:  filtro.cedula_estudiante,
                codigo_ano_escolar: filtro.codigo_ano_escolar || undefined,
            }
        });
        documentos.value = data.data ?? data;
    } finally {
        loading.value = false;
    }
}

function onEstudianteChange() {
    const est = estudiantes.value.find(e => e.cedula_estudiante === filtro.cedula_estudiante);
    if (!est) {
        filtro.nombre_completo = '';
        filtro.codigo_ano_escolar = '';
        documentos.value = [];
        return;
    }
    filtro.nombre_completo = `${est.apellidos}, ${est.nombres} (${est.tipo_documento}-${est.cedula_estudiante})`;
    filtro.codigo_ano_escolar = est.matricula_actual?.codigo_ano_escolar ?? filtro.codigo_ano_escolar;
    documentos.value = [];
}

function resetEstudiante() {
    filtro.cedula_estudiante = '';
    filtro.nombre_completo = '';
    filtro.codigo_ano_escolar = '';
    documentos.value = [];
    errorMsg.value = '';
}

// ── Descarga de documentos ────────────────────────────────────
async function descargar(tipo) {
    if (!filtro.cedula_estudiante || !filtro.codigo_ano_escolar) {
        errorMsg.value = 'Debes seleccionar un estudiante y un año escolar.';
        return;
    }
    errorMsg.value = '';
    loadingDoc.value = true;

    // Convertir key con guiones bajos a guiones medios para la URL
    const urlKey = tipo.key.replace(/_/g, '-');
    let url = `/api/documentos/${urlKey}/${filtro.cedula_estudiante}/${filtro.codigo_ano_escolar}`;
    const params = new URLSearchParams();

    if (tipo.momentos && selMomento.value) params.append('momento', selMomento.value);
    if (tipo.key === 'constancia_estudio' && selMotivo.value) params.append('motivo', selMotivo.value);
    if (params.toString()) url += '?' + params.toString();

    try {
        const response = await axios.get(url, { responseType: 'blob' });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href     = URL.createObjectURL(blob);
        link.download = `${tipo.key}_${filtro.cedula_estudiante}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
        await cargarDocumentos();
    } catch (e) {
        if (e.response?.status === 404) {
            errorMsg.value = 'El estudiante no está matriculado en ese año escolar.';
        } else {
            let errorText = '';
            if (e.response?.data instanceof Blob) {
                try {
                    const text = await e.response.data.text();
                    const parsed = JSON.parse(text);
                    errorText = parsed.message || parsed.error || '';
                } catch (err) {}
            }
            errorMsg.value = 'Error al generar el documento: ' + (errorText || e.response?.data?.message || e.message);
        }
    } finally {
        loadingDoc.value = false;
    }
}

async function descargarSeccion(tipo) {
    if (!filtroSec.codigo_seccion || !filtroSec.codigo_ano_escolar) {
        errorMsg.value = 'Debes seleccionar una sección y un año escolar.';
        return;
    }
    errorMsg.value = '';
    loadingDoc.value = true;
    const urlKey = tipo.key.replace(/_/g, '-');
    let url = `/api/documentos/${urlKey}/${filtroSec.codigo_seccion}/${filtroSec.codigo_ano_escolar}`;
    const params = new URLSearchParams();
    if (tipo.momentos && filtroSec.numero_momento) params.append('momento', filtroSec.numero_momento);
    if (params.toString()) url += '?' + params.toString();

    try {
        const response = await axios.get(url, { responseType: 'blob' });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href     = URL.createObjectURL(blob);
        link.download = `${tipo.key}_${filtroSec.codigo_seccion}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    } catch (e) {
        let errorText = '';
        if (e.response?.data instanceof Blob) {
            try {
                const text = await e.response.data.text();
                const parsed = JSON.parse(text);
                errorText = parsed.message || parsed.error || '';
            } catch (err) {}
        }
        errorMsg.value = 'Error al generar el documento: ' + (errorText || e.response?.data?.message || e.message);
    } finally {
        loadingDoc.value = false;
    }
}

const seccionLabel = computed(() => {
    if (!filtroSec.codigo_seccion) return '';
    const s = secciones.value.find(s => s.codigo_seccion === filtroSec.codigo_seccion);
    return s ? `${s.grado?.nombre ?? ''} - Sección ${s.letra}` : filtroSec.codigo_seccion;
});

onMounted(cargarCatalogos);
</script>

<template>
    <Head title="Documentos — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-black text-slate-800">🖨️ Generación de Documentos</h1>
            <p class="text-xs text-slate-500 mt-0.5">Boletines, constancias y resúmenes en formato MPPE</p>
        </template>

        <!-- TAB SELECTOR -->
        <div class="flex gap-2 mb-6">
            <button @click="tab = 'estudiante'; errorMsg = ''"
                :class="['px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-sm',
                    tab === 'estudiante' ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-sky-300']">
                👤 Por Estudiante
            </button>
            <button @click="tab = 'seccion'; errorMsg = ''"
                :class="['px-5 py-2.5 rounded-xl text-sm font-bold transition shadow-sm',
                    tab === 'seccion' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-indigo-300']">
                🏫 Por Sección
            </button>
        </div>

        <!-- ERROR MSG -->
        <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 font-medium">
            ⚠️ {{ errorMsg }}
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB: POR ESTUDIANTE                                 -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="tab === 'estudiante'" class="grid lg:grid-cols-5 gap-6">

            <!-- Panel izquierdo -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Selector estudiante -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">1. Estudiante</h2>
                    <div class="space-y-3">
                        <select id="est-select" v-model="filtro.cedula_estudiante" @change="onEstudianteChange"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                            <option value="">Seleccionar estudiante…</option>
                            <option v-for="est in estudiantes" :key="est.cedula_estudiante" :value="est.cedula_estudiante">
                                {{ est.apellidos }}, {{ est.nombres }} ({{ est.tipo_documento }}-{{ est.cedula_estudiante }})
                            </option>
                        </select>
                        <div v-if="filtro.nombre_completo" class="p-3 bg-sky-50 border border-sky-200 rounded-xl text-sm">
                            <p class="font-semibold text-sky-800">{{ filtro.nombre_completo }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Año: {{ filtro.codigo_ano_escolar || '—' }}</p>
                            <div class="mt-2 flex gap-2">
                                <button @click="cargarDocumentos"
                                    class="px-3 py-1.5 text-xs font-bold text-white bg-sky-600 rounded-lg hover:bg-sky-700 transition">
                                    Ver historial
                                </button>
                                <button @click="resetEstudiante"
                                    class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
                                    Cambiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Año escolar -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">2. Año Escolar</h2>
                    <select v-model="filtro.codigo_ano_escolar" @change="cargarDocumentos"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option value="">Seleccionar…</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                            {{ a.codigo_ano_escolar }}
                        </option>
                    </select>
                </div>

                <!-- Documentos por estudiante -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">3. Generar</h2>
                    <div class="space-y-3">
                        <div v-for="tipo in tiposEstudiante" :key="tipo.key" class="rounded-xl border border-slate-100 p-3">
                            <p class="text-sm font-bold text-slate-700 mb-2">{{ tipo.icon }} {{ tipo.label }}</p>

                            <!-- Selector de momento para boletín -->
                            <div v-if="tipo.momentos" class="flex gap-1 mb-2">
                                <button v-for="m in [null, 1, 2, 3]" :key="m" @click="selMomento = m"
                                    :class="['flex-1 py-1 text-xs font-bold rounded-lg border transition',
                                        selMomento === m ? 'bg-sky-600 text-white border-sky-600' : 'border-slate-200 text-slate-600 hover:border-sky-400']">
                                    {{ m === null ? 'Final' : `${m}°` }}
                                </button>
                            </div>

                            <!-- Motivo para constancia de estudio -->
                            <div v-if="tipo.key === 'constancia_estudio'" class="mb-2">
                                <input v-model="selMotivo" type="text" placeholder="Motivo (opcional)"
                                    class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-sky-400" />
                            </div>

                            <button @click="descargar(tipo)"
                                :disabled="loadingDoc || !filtro.cedula_estudiante || !filtro.codigo_ano_escolar"
                                class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-bold text-white shadow transition disabled:opacity-40"
                                :style="{ background: tipo.color }">
                                <span v-if="loadingDoc">⏳</span>
                                <span v-else>⬇</span>
                                Descargar PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho: Historial -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-black text-slate-800">Documentos Emitidos</h2>
                        <span class="text-xs text-slate-400">{{ documentos.length }} registros</span>
                    </div>

                    <div v-if="loading" class="p-10 text-center text-slate-400 text-sm">Cargando historial…</div>
                    <div v-else-if="!filtro.cedula_estudiante" class="p-16 text-center">
                        <div class="text-5xl mb-4 opacity-30">📄</div>
                        <p class="text-slate-400 text-sm">Selecciona un estudiante para ver su historial</p>
                    </div>
                    <div v-else-if="!documentos.length" class="p-10 text-center text-slate-400 text-sm">
                        Sin documentos emitidos para este estudiante.
                    </div>
                    <div v-else>
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Folio</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Año / Momento</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Emitido por</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="doc in documentos" :key="doc.id_documento" class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-sky-100 text-sky-700 uppercase whitespace-nowrap">
                                            {{ tipoLabel[doc.tipo_documento] ?? doc.tipo_documento }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ doc.folio }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ doc.codigo_ano_escolar }}
                                        <span v-if="doc.numero_momento"> · {{ doc.numero_momento }}° M.</span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ doc.fecha_emision }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ doc.usuario_emisor?.personal?.nombre_completo ?? doc.usuario_emisor?.codigo_usuario ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB: POR SECCIÓN                                    -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="tab === 'seccion'" class="grid lg:grid-cols-5 gap-6">

            <!-- Panel izquierdo: configuración -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Selección de sección -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">1. Sección</h2>
                    <select v-model="filtroSec.codigo_seccion"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Seleccionar sección…</option>
                        <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                            {{ s.grado?.nombre ?? s.codigo_grado }} — Sección {{ s.letra }} ({{ s.codigo_ano_escolar }})
                        </option>
                    </select>
                    <div v-if="seccionLabel" class="mt-2 text-sm font-semibold text-indigo-700">
                        📍 {{ seccionLabel }}
                    </div>
                </div>

                <!-- Año escolar para sección -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">2. Año Escolar</h2>
                    <select v-model="filtroSec.codigo_ano_escolar"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Seleccionar…</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                            {{ a.codigo_ano_escolar }}
                        </option>
                    </select>
                </div>

                <!-- Documentos por sección -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">3. Generar</h2>
                    <div class="space-y-3">
                        <div v-for="tipo in tiposSeccion" :key="tipo.key" class="rounded-xl border border-slate-100 p-3">
                            <p class="text-sm font-bold text-slate-700 mb-2">{{ tipo.icon }} {{ tipo.label }}</p>

                            <!-- Selector de momento para resumen -->
                            <div v-if="tipo.momentos" class="flex gap-1 mb-2">
                                <button v-for="m in [null, 1, 2, 3]" :key="m" @click="filtroSec.numero_momento = m"
                                    :class="['flex-1 py-1 text-xs font-bold rounded-lg border transition',
                                        filtroSec.numero_momento === m ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-200 text-slate-600 hover:border-indigo-400']">
                                    {{ m === null ? 'Final' : `${m}°` }}
                                </button>
                            </div>

                            <button @click="descargarSeccion(tipo)"
                                :disabled="loadingDoc || !filtroSec.codigo_seccion || !filtroSec.codigo_ano_escolar"
                                class="w-full flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-bold text-white shadow transition disabled:opacity-40"
                                :style="{ background: tipo.color }">
                                <span v-if="loadingDoc">⏳</span>
                                <span v-else>⬇</span>
                                Descargar PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho: info -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex flex-col items-center justify-center min-h-[300px]">
                    <div class="text-5xl mb-4 opacity-40">🏫</div>
                    <h3 class="text-slate-700 font-bold text-lg mb-1">Reportes de Sección</h3>
                    <p class="text-slate-400 text-sm text-center max-w-sm">
                        Genera la <strong>Lista de Sección</strong> (landscape) con conteo de varones/hembras,
                        o el <strong>Resumen de Calificaciones</strong> tipo libro con todas las materias y momentos.
                    </p>
                    <div v-if="filtroSec.codigo_seccion && filtroSec.codigo_ano_escolar"
                        class="mt-6 p-4 bg-indigo-50 border border-indigo-200 rounded-xl w-full max-w-xs text-center">
                        <p class="text-indigo-800 font-bold text-sm">{{ seccionLabel }}</p>
                        <p class="text-indigo-600 text-xs mt-0.5">Año: {{ filtroSec.codigo_ano_escolar }}</p>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
