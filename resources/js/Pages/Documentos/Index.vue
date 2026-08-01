<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol || 'docente').toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

// ── Catálogos ──────────────────────────────────────────────────
const anios       = ref([]);
const secciones   = ref([]);
const estudiantes = ref([]);
const documentos  = ref([]);
const loading     = ref(false);
const loadingDoc  = ref(false);
const errorMsg    = ref('');
const tab         = ref('estudiante');

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
    { key: 'boletin',                label: 'Boletín de Calificaciones',   momentos: true  },
    { key: 'constancia_estudio',     label: 'Constancia de Estudios',      momentos: false },
    { key: 'constancia_conducta',    label: 'Constancia de Buena Conducta', momentos: false },
    { key: 'constancia_prosecucion', label: 'Constancia de Prosecución',   momentos: false },
    { key: 'constancia_asistencia',  label: 'Constancia de Asistencia',    momentos: false },
];

// ── Tipos de documentos por sección ──────────────────────────
const tiposSeccion = [
    { key: 'lista_seccion',   label: 'Lista de Sección',               momentos: false },
    { key: 'resumen_seccion', label: 'Resumen de Calificaciones',      momentos: true  },
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
    anios.value       = aniosData.data ?? aniosData;
    estudiantes.value = estudiantesData.data ?? estudiantesData;
    secciones.value   = seccionesData.data ?? seccionesData;
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
            <div>
                <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Generación de Documentos</h1>
                <p class="text-[11px] text-piedra mt-0.5">Boletines, constancias y resúmenes oficiales en formato MPPE</p>
            </div>
        </template>

        <!-- TAB SELECTOR -->
        <div class="flex gap-6 mb-5 border-b border-borde">
            <button @click="tab = 'estudiante'; errorMsg = ''"
                :class="['pb-3 text-[13px] font-semibold transition-colors border-b-2 -mb-px',
                    tab === 'estudiante' ? 'border-dorado text-tinta' : 'border-transparent text-piedra hover:text-tinta']">
                Por Estudiante
            </button>
            <button @click="tab = 'seccion'; errorMsg = ''"
                :class="['pb-3 text-[13px] font-semibold transition-colors border-b-2 -mb-px',
                    tab === 'seccion' ? 'border-dorado text-tinta' : 'border-transparent text-piedra hover:text-tinta']">
                Por Sección
            </button>
        </div>

        <!-- ERROR MSG -->
        <div v-if="errorMsg" class="mb-4 bg-[#F4DEDA] border border-rojo/20 text-rojo-dark text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ errorMsg }}</span>
            <button @click="errorMsg = ''" class="text-rojo-dark hover:text-rojo ml-4">&times;</button>
        </div>

        <!-- TAB: POR ESTUDIANTE -->
        <div v-if="tab === 'estudiante'" class="grid lg:grid-cols-5 gap-6">

            <!-- Panel izquierdo -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Selector estudiante -->
                <div class="bg-paper border border-borde rounded-[6px] p-5">
                    <label class="lbl mb-2">1. Estudiante</label>
                    <select id="est-select" v-model="filtro.cedula_estudiante" @change="onEstudianteChange" class="inp">
                        <option value="">Seleccionar estudiante...</option>
                        <option v-for="est in estudiantes" :key="est.cedula_estudiante" :value="est.cedula_estudiante">
                            {{ est.apellidos }}, {{ est.nombres }} ({{ est.tipo_documento }}-{{ est.cedula_estudiante }})
                        </option>
                    </select>
                    <div v-if="filtro.nombre_completo" class="mt-3 p-3 bg-crema border border-borde rounded-[4px] text-[12px]">
                        <p class="font-semibold text-tinta">{{ filtro.nombre_completo }}</p>
                        <p class="text-piedra mt-0.5">Año: {{ filtro.codigo_ano_escolar || '—' }}</p>
                        <div class="mt-2 flex gap-2">
                            <button @click="cargarDocumentos" class="btn-primary text-[11px] py-1 px-3">Ver historial</button>
                            <button @click="resetEstudiante" class="btn-secondary text-[11px] py-1 px-3">Cambiar</button>
                        </div>
                    </div>
                </div>

                <!-- Año escolar -->
                <div class="bg-paper border border-borde rounded-[6px] p-5">
                    <label class="lbl mb-2">2. Año Escolar</label>
                    <select v-model="filtro.codigo_ano_escolar" @change="cargarDocumentos" class="inp">
                        <option value="">Seleccionar...</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                            {{ a.codigo_ano_escolar }}
                        </option>
                    </select>
                </div>

                <!-- Documentos por estudiante -->
                <div class="bg-paper border border-borde rounded-[6px] p-5">
                    <label class="lbl mb-3">3. Generar PDF</label>
                    <div class="space-y-3">
                        <div v-for="tipo in tiposEstudiante" :key="tipo.key" class="p-3 border border-borde rounded-[4px] bg-crema">
                            <p class="text-[13px] font-semibold text-tinta mb-2">{{ tipo.label }}</p>

                            <!-- Selector de momento para boletín -->
                            <div v-if="tipo.momentos" class="flex gap-1 mb-2">
                                <button v-for="m in [null, 1, 2, 3]" :key="m" @click="selMomento = m"
                                    :class="['flex-1 py-1 text-[11px] font-semibold rounded-[3px] border transition-colors',
                                        selMomento === m ? 'bg-rojo text-paper border-rojo' : 'bg-paper text-piedra border-borde hover:bg-crema']">
                                    {{ m === null ? 'Final' : `${m}°` }}
                                </button>
                            </div>

                            <!-- Motivo para constancia de estudio -->
                            <div v-if="tipo.key === 'constancia_estudio'" class="mb-2">
                                <input v-model="selMotivo" type="text" placeholder="Motivo (opcional)" class="inp text-[12px] py-1" />
                            </div>

                            <button @click="descargar(tipo)"
                                :disabled="loadingDoc || !filtro.cedula_estudiante || !filtro.codigo_ano_escolar"
                                class="w-full btn-primary text-[12px] py-2">
                                {{ loadingDoc ? 'Generando PDF...' : 'Descargar PDF' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho: Historial -->
            <div class="lg:col-span-3">
                <div class="bg-paper border border-borde rounded-[6px] overflow-hidden">
                    <div class="px-5 py-4 border-b border-borde flex items-center justify-between">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">Documentos Emitidos</h2>
                        <span class="text-[11px] text-piedra-soft">{{ documentos.length }} registros</span>
                    </div>

                    <div v-if="loading" class="p-10 text-center text-piedra text-[13px]">Cargando historial...</div>
                    <div v-else-if="!filtro.cedula_estudiante" class="p-16 text-center text-piedra text-[13px]">
                        Selecciona un estudiante para consultar su historial de emisión.
                    </div>
                    <div v-else-if="!documentos.length" class="p-10 text-center text-piedra text-[13px]">
                        Sin documentos emitidos para este estudiante.
                    </div>
                    <div v-else>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-borde">
                                    <th class="th">Tipo</th>
                                    <th class="th">Folio</th>
                                    <th class="th">Año / Momento</th>
                                    <th class="th">Fecha</th>
                                    <th class="th">Emitido por</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-borde">
                                <tr v-for="doc in documentos" :key="doc.id_documento" class="hover:bg-crema transition-colors">
                                    <td class="td">
                                        <span class="badge badge-neutral">
                                            {{ tipoLabel[doc.tipo_documento] ?? doc.tipo_documento }}
                                        </span>
                                    </td>
                                    <td class="td font-mono text-[12px] text-piedra">{{ doc.folio }}</td>
                                    <td class="td text-[12px] text-piedra">
                                        {{ doc.codigo_ano_escolar }}
                                        <span v-if="doc.numero_momento"> · {{ doc.numero_momento }}° M.</span>
                                    </td>
                                    <td class="td text-[12px] text-piedra">{{ doc.fecha_emision }}</td>
                                    <td class="td text-[12px] text-piedra">
                                        {{ doc.usuario_emisor?.personal?.nombre_completo ?? doc.usuario_emisor?.codigo_usuario ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: POR SECCIÓN -->
        <div v-if="tab === 'seccion'" class="grid lg:grid-cols-5 gap-6">

            <!-- Panel izquierdo: configuración -->
            <div class="lg:col-span-2 space-y-4">

                <div class="bg-paper border border-borde rounded-[6px] p-5">
                    <label class="lbl mb-2">1. Sección</label>
                    <select v-model="filtroSec.codigo_seccion" class="inp">
                        <option value="">Seleccionar sección...</option>
                        <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                            {{ s.grado?.nombre ?? s.codigo_grado }} — Sección {{ s.letra }} ({{ s.codigo_ano_escolar }})
                        </option>
                    </select>
                    <div v-if="seccionLabel" class="mt-2 text-[12px] font-semibold text-tinta">
                        {{ seccionLabel }}
                    </div>
                </div>

                <div class="bg-paper border border-borde rounded-[6px] p-5">
                    <label class="lbl mb-2">2. Año Escolar</label>
                    <select v-model="filtroSec.codigo_ano_escolar" class="inp">
                        <option value="">Seleccionar...</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                            {{ a.codigo_ano_escolar }}
                        </option>
                    </select>
                </div>

                <div class="bg-paper border border-borde rounded-[6px] p-5">
                    <label class="lbl mb-3">3. Generar PDF</label>
                    <div class="space-y-3">
                        <div v-for="tipo in tiposSeccion" :key="tipo.key" class="p-3 border border-borde rounded-[4px] bg-crema">
                            <p class="text-[13px] font-semibold text-tinta mb-2">{{ tipo.label }}</p>

                            <!-- Selector de momento para resumen -->
                            <div v-if="tipo.momentos" class="flex gap-1 mb-2">
                                <button v-for="m in [null, 1, 2, 3]" :key="m" @click="filtroSec.numero_momento = m"
                                    :class="['flex-1 py-1 text-[11px] font-semibold rounded-[3px] border transition-colors',
                                        filtroSec.numero_momento === m ? 'bg-rojo text-paper border-rojo' : 'bg-paper text-piedra border-borde hover:bg-crema']">
                                    {{ m === null ? 'Final' : `${m}°` }}
                                </button>
                            </div>

                            <button @click="descargarSeccion(tipo)"
                                :disabled="loadingDoc || !filtroSec.codigo_seccion || !filtroSec.codigo_ano_escolar"
                                class="w-full btn-primary text-[12px] py-2">
                                {{ loadingDoc ? 'Generando PDF...' : 'Descargar PDF' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho: info -->
            <div class="lg:col-span-3">
                <div class="bg-paper border border-borde rounded-[6px] p-8 flex flex-col items-center justify-center min-h-[300px] text-center">
                    <h3 class="font-serif font-semibold text-tinta text-lg mb-2">Reportes Consolidados de Sección</h3>
                    <p class="text-piedra text-[12px] max-w-md leading-relaxed">
                        Genera la <strong>Lista de Sección</strong> oficial o el <strong>Resumen de Calificaciones</strong> con el consolidado por momento evaluativo.
                    </p>
                    <div v-if="filtroSec.codigo_seccion && filtroSec.codigo_ano_escolar"
                        class="mt-6 p-4 bg-crema border border-borde border-l-[3px] border-l-dorado rounded-[4px] w-full max-w-xs text-left">
                        <p class="text-tinta font-semibold text-[13px]">{{ seccionLabel }}</p>
                        <p class="text-piedra text-[11px] mt-0.5">Año Escolar: {{ filtroSec.codigo_ano_escolar }}</p>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors; }
.th            { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td            { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary   { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors disabled:opacity-40; }
.btn-secondary { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
</style>
