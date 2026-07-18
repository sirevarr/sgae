<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const anios      = ref([]);
const secciones  = ref([]);
const estudiantes = ref([]);
const documentos = ref([]);
const loading    = ref(false);
const loadingDoc = ref(false);
const buscadorEst = ref('');

const filtro = reactive({
    cedula_estudiante: '',
    codigo_ano_escolar: '',
    nombre_completo: '',
});

const tiposDoc = [
    { key: 'boletin',              icon: '📊', label: 'Boletín de Calificaciones', color: 'sky',     momentos: true },
    { key: 'constancia_estudio',   icon: '📄', label: 'Constancia de Estudios',    color: 'emerald', momentos: false },
    { key: 'constancia_conducta',  icon: '✅', label: 'Constancia de Conducta',    color: 'violet',  momentos: false },
];

const selMomento = ref(null);
const selMotivo  = ref('');

async function cargarCatalogos() {
    const { data } = await axios.get('/api/anios-escolares');
    anios.value = data;
}

async function buscarEstudiantes() {
    if (buscadorEst.value.length < 2) { estudiantes.value = []; return; }
    const { data } = await axios.get('/api/estudiantes', { params: { buscar: buscadorEst.value } });
    estudiantes.value = data.data ?? data;
}

function seleccionarEstudiante(est) {
    filtro.cedula_estudiante = est.cedula_estudiante;
    filtro.nombre_completo   = `${est.apellidos}, ${est.nombres} (${est.tipo_documento}-${est.cedula_estudiante})`;
    estudiantes.value = [];
    buscadorEst.value = '';
    cargarDocumentos();
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

async function descargar(tipo) {
    if (!filtro.cedula_estudiante || !filtro.codigo_ano_escolar) {
        alert('Debes seleccionar un estudiante y un año escolar.');
        return;
    }

    loadingDoc.value = true;
    let url = `/api/documentos/${tipo.key.replace('_', '-')}/${filtro.cedula_estudiante}/${filtro.codigo_ano_escolar}`;
    const params = new URLSearchParams();

    if (tipo.momentos && selMomento.value) params.append('momento', selMomento.value);
    if (tipo.key === 'constancia_estudio' && selMotivo.value) params.append('motivo', selMotivo.value);
    if (params.toString()) url += '?' + params.toString();

    try {
        const response = await axios.get(url, { responseType: 'blob' });
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const link = document.createElement('a');
        link.href   = URL.createObjectURL(blob);
        link.download = `${tipo.key}_${filtro.cedula_estudiante}.pdf`;
        link.click();
        cargarDocumentos();
    } catch (e) {
        const msg = e.response?.status === 404
            ? 'El estudiante no está matriculado en ese año escolar.'
            : 'Error al generar el documento: ' + (e.response?.data?.message ?? e.message);
        alert(msg);
    } finally {
        loadingDoc.value = false;
    }
}

const tipoLabel = { boletin: 'Boletín', constancia_estudio: 'Const. Estudio', constancia_conducta: 'Const. Conducta' };

onMounted(cargarCatalogos);
</script>

<template>
    <Head title="Documentos — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-black text-slate-800">🖨️ Generación de Documentos</h1>
            <p class="text-xs text-slate-500 mt-0.5">Boletines y constancias en formato MPPE</p>
        </template>

        <div class="grid lg:grid-cols-5 gap-6">
            <!-- ── PANEL IZQUIERDO: Configuración ───────────────────── -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Buscador de estudiante -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">1. Seleccionar Estudiante</h2>
                    <div class="relative">
                        <input v-model="buscadorEst" @input="buscarEstudiantes" type="text"
                            placeholder="Buscar por nombre o cédula…"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" />
                        <div v-if="estudiantes.length" class="absolute top-full left-0 right-0 bg-white rounded-xl shadow-xl border border-slate-100 z-20 max-h-48 overflow-y-auto mt-1">
                            <button v-for="est in estudiantes" :key="est.cedula_estudiante"
                                @click="seleccionarEstudiante(est)"
                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-sky-50 transition border-b border-slate-50 last:border-0">
                                <p class="font-semibold text-slate-800">{{ est.apellidos }}, {{ est.nombres }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ est.tipo_documento }}-{{ est.cedula_estudiante }}</p>
                            </button>
                        </div>
                    </div>
                    <div v-if="filtro.nombre_completo" class="mt-3 p-3 bg-sky-50 border border-sky-200 rounded-xl text-sm">
                        <p class="font-semibold text-sky-800">{{ filtro.nombre_completo }}</p>
                        <button @click="filtro.cedula_estudiante = ''; filtro.nombre_completo = ''; documentos = []"
                            class="text-xs text-sky-500 hover:text-sky-700 mt-1">Cambiar estudiante</button>
                    </div>
                </div>

                <!-- Año escolar -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-3">2. Año Escolar</h2>
                    <select v-model="filtro.codigo_ano_escolar" @change="cargarDocumentos"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option value="">Seleccionar…</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                </div>

                <!-- Tipos de documentos -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
                    <h2 class="text-xs font-black uppercase text-slate-400 tracking-widest mb-4">3. Generar Documento</h2>
                    <div class="space-y-3">
                        <div v-for="tipo in tiposDoc" :key="tipo.key">
                            <!-- Boletín: selector de momento -->
                            <div v-if="tipo.momentos" class="space-y-2">
                                <p class="text-sm font-bold text-slate-700">{{ tipo.icon }} {{ tipo.label }}</p>
                                <div class="flex gap-2">
                                    <button v-for="m in [null, 1, 2, 3]" :key="m"
                                        @click="selMomento = m"
                                        :class="['flex-1 py-1.5 text-xs font-bold rounded-lg border transition',
                                            selMomento === m ? 'bg-sky-600 text-white border-sky-600' : 'border-slate-200 text-slate-600 hover:border-sky-400']">
                                        {{ m === null ? 'Final' : `${m}°` }}
                                    </button>
                                </div>
                            </div>
                            <!-- Constancia estudio: motivo -->
                            <div v-if="tipo.key === 'constancia_estudio'" class="mt-2">
                                <input v-model="selMotivo" type="text"
                                    placeholder="Motivo (opcional)"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-sky-400" />
                            </div>
                            <button @click="descargar(tipo)"
                                :disabled="loadingDoc || !filtro.cedula_estudiante || !filtro.codigo_ano_escolar"
                                :class="['w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold shadow transition disabled:opacity-40 mt-1',
                                    `bg-${tipo.color}-600 hover:bg-${tipo.color}-700 text-white`]">
                                <span v-if="loadingDoc">⏳</span>
                                <span v-else>{{ tipo.icon }}</span>
                                Descargar {{ tipo.label }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── PANEL DERECHO: Historial ──────────────────────────── -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-black text-slate-800">Documentos Emitidos</h2>
                        <span class="text-xs text-slate-400">{{ documentos.length }} registros</span>
                    </div>

                    <div v-if="loading" class="p-10 text-center text-slate-400 text-sm">Cargando historial…</div>
                    <div v-else-if="!filtro.cedula_estudiante" class="p-16 text-center">
                        <div class="text-5xl mb-4 opacity-30">📄</div>
                        <p class="text-slate-400 text-sm">Selecciona un estudiante para ver su historial de documentos</p>
                    </div>
                    <div v-else-if="!documentos.length" class="p-10 text-center text-slate-400 text-sm">
                        Aún no se han generado documentos para este estudiante.
                    </div>
                    <div v-else>
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 border-b border-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Folio</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Año / Momento</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Fecha Emisión</th>
                                    <th class="px-4 py-3 text-left text-xs font-black text-slate-500 uppercase">Emitido por</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="doc in documentos" :key="doc.id_documento" class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-[10px] font-black bg-sky-100 text-sky-700 uppercase">
                                            {{ tipoLabel[doc.tipo_documento] ?? doc.tipo_documento }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ doc.folio }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        {{ doc.codigo_ano_escolar }}
                                        <span v-if="doc.numero_momento"> · {{ doc.numero_momento }}° Momento</span>
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
    </AuthenticatedLayout>
</template>
