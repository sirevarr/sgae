<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

// ── Datos ──────────────────────────────────────────────────────
const inscripciones  = ref([]);
const estudiantes    = ref([]);
const secciones      = ref([]);
const anios          = ref([]);
const representantes = ref([]);
const loading        = ref(false);
const saving         = ref(false);
const errorMsg       = ref('');
const successMsg     = ref('');

// ── Filtros ────────────────────────────────────────────────────
const filtros = reactive({
    codigo_ano_escolar: '',
    codigo_seccion: '',
});

// ── Modal ──────────────────────────────────────────────────────
const showModal = ref(false);
const editMode  = ref(false);
const form = reactive({
    id_matricula:         null,
    estudiante_id:        '',
    codigo_ano_escolar:   '',
    codigo_seccion:       '',
    cedula_representante: '',
    fecha_matricula:      '',
    numero_lista:         '',
    condicion_ingreso:    '',
    procedencia:          '',
    ano_inicio_cursante:  '',
    estado_matricula:     'activa',
    observaciones:        '',
});

const condicionesIngreso = ['nuevo_ingreso', 'repitiente', 'traslado', 'reingreso'];
const estadosMatricula   = ['activa', 'retirada', 'trasladada'];

// ── Cargar datos ───────────────────────────────────────────────
async function cargarTodo() {
    loading.value = true;
    try {
        const [
            { data: estData },
            { data: secData },
            { data: anioData },
            { data: repData },
        ] = await Promise.all([
            axios.get('/api/estudiantes'),
            axios.get('/api/secciones'),
            axios.get('/api/anios-escolares'),
            axios.get('/api/representantes'),
        ]);
        estudiantes.value    = estData.data ?? estData;
        secciones.value      = secData.data ?? secData;
        anios.value          = anioData.data ?? anioData;
        representantes.value = repData.data ?? repData;
        await cargarInscripciones();
    } finally {
        loading.value = false;
    }
}

async function cargarInscripciones() {
    const params = {};
    if (filtros.codigo_ano_escolar) params.codigo_ano_escolar = filtros.codigo_ano_escolar;
    if (filtros.codigo_seccion)     params.codigo_seccion     = filtros.codigo_seccion;
    const { data } = await axios.get('/api/inscripciones', { params });
    inscripciones.value = data.data ?? data;
}

// ── Helpers de fecha ──────────────────────────────────────────
const hoy = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().split('T')[0];
};

const formatFecha = (f) => {
    if (!f) return '—';
    const p = f.split('T')[0];
    const [y, m, d] = p.split('-');
    return `${d}/${m}/${y}`;
};

// ── Modal helpers ──────────────────────────────────────────────
function abrirNueva() {
    editMode.value = false;
    Object.assign(form, {
        id_matricula: null, estudiante_id: '', codigo_ano_escolar: '',
        codigo_seccion: '', cedula_representante: '', fecha_matricula: hoy(),
        numero_lista: '', condicion_ingreso: '', procedencia: '',
        ano_inicio_cursante: new Date().getFullYear(), estado_matricula: 'activa', observaciones: '',
    });
    errorMsg.value = '';
    showModal.value = true;
}

function abrirEditar(ins) {
    editMode.value = true;
    Object.assign(form, {
        id_matricula:         ins.id_matricula,
        estudiante_id:        ins.cedula_estudiante,
        codigo_ano_escolar:   ins.codigo_ano_escolar,
        codigo_seccion:       ins.codigo_seccion,
        cedula_representante: ins.cedula_representante ?? '',
        fecha_matricula:      ins.fecha_matricula ? ins.fecha_matricula.split('T')[0] : '',
        numero_lista:         ins.numero_lista ?? '',
        condicion_ingreso:    ins.condicion_ingreso ?? '',
        procedencia:          ins.procedencia ?? '',
        ano_inicio_cursante:  ins.ano_inicio_cursante ?? '',
        estado_matricula:     ins.estado_matricula ?? 'activa',
        observaciones:        ins.observaciones ?? '',
    });
    errorMsg.value = '';
    showModal.value = true;
}

function cerrarModal() {
    showModal.value = false;
    errorMsg.value = '';
}

// ── CRUD ───────────────────────────────────────────────────────
async function guardar() {
    saving.value = true;
    errorMsg.value = '';
    try {
        const payload = {
            estudiante_id:        form.estudiante_id,
            codigo_ano_escolar:   form.codigo_ano_escolar,
            codigo_seccion:       form.codigo_seccion,
            cedula_representante: form.cedula_representante || null,
            fecha_matricula:      form.fecha_matricula,
            numero_lista:         form.numero_lista || null,
            condicion_ingreso:    form.condicion_ingreso || null,
            procedencia:          form.procedencia || null,
            ano_inicio_cursante:  form.ano_inicio_cursante || null,
            estado_matricula:     form.estado_matricula,
            observaciones:        form.observaciones || null,
        };

        if (editMode.value) {
            await axios.put(`/api/inscripciones/${form.id_matricula}`, payload);
            successMsg.value = 'Inscripción actualizada correctamente.';
        } else {
            await axios.post('/api/inscripciones', payload);
            successMsg.value = 'Inscripción registrada correctamente.';
        }
        cerrarModal();
        await cargarInscripciones();
        setTimeout(() => successMsg.value = '', 3500);
    } catch (e) {
        const errData = e.response?.data;
        if (errData?.errors) {
            errorMsg.value = Object.values(errData.errors).flat().join(' | ');
        } else {
            errorMsg.value = errData?.error ?? errData?.message ?? 'Error desconocido';
        }
    } finally {
        saving.value = false;
    }
}

async function eliminar(ins) {
    if (!confirm(`¿Eliminar la inscripción de "${ins.estudiante?.nombres ?? ins.cedula_estudiante}"?`)) return;
    try {
        await axios.delete(`/api/inscripciones/${ins.id_matricula}`);
        successMsg.value = 'Inscripción eliminada.';
        await cargarInscripciones();
        setTimeout(() => successMsg.value = '', 3000);
    } catch (e) {
        alert('No se pudo eliminar: ' + (e.response?.data?.error ?? e.message));
    }
}

// ── Helpers visuales ──────────────────────────────────────────
const estadoBadge = {
    activa:     'bg-emerald-100 text-emerald-700',
    retirada:   'bg-red-100 text-red-700',
    trasladada: 'bg-amber-100 text-amber-700',
};

const nombreEst = (ins) => {
    const e = ins.estudiante;
    return e ? `${e.apellidos}, ${e.nombres}` : ins.cedula_estudiante;
};

const nombreSec = (ins) => {
    const s = ins.seccion;
    return s ? `${s.grado?.nombre ?? s.codigo_grado} — Secc. ${s.letra}` : ins.codigo_seccion;
};

const nombreRep = (ins) => {
    const r = ins.representante;
    return r ? `${r.nombres} ${r.apellidos}` : '—';
};

onMounted(cargarTodo);
</script>

<template>
    <Head title="Inscripciones — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-black text-slate-800">📝 Inscripciones</h1>
            <p class="text-xs text-slate-500 mt-0.5">Registro y gestión de inscripciones de estudiantes por año escolar</p>
        </template>

        <!-- MENSAJES -->
        <div v-if="successMsg" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700 font-medium flex items-center gap-2">
            ✅ {{ successMsg }}
        </div>

        <!-- BARRA DE ACCIONES -->
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <div class="flex flex-wrap gap-3">
                <select v-model="filtros.codigo_ano_escolar" @change="cargarInscripciones"
                    class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                    <option value="">Todos los años</option>
                    <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                        {{ a.codigo_ano_escolar }}
                    </option>
                </select>
                <select v-model="filtros.codigo_seccion" @change="cargarInscripciones"
                    class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                    <option value="">Todas las secciones</option>
                    <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                        {{ s.grado?.nombre ?? s.codigo_grado }} — Secc. {{ s.letra }} ({{ s.codigo_ano_escolar }})
                    </option>
                </select>
            </div>
            <button @click="abrirNueva"
                class="px-5 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm transition shadow">
                + Nueva Inscripción
            </button>
        </div>

        <!-- TABLA -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div v-if="loading" class="p-12 text-center text-slate-400">Cargando inscripciones…</div>
            <div v-else-if="!inscripciones.length" class="p-16 text-center">
                <div class="text-5xl mb-3 opacity-30">📝</div>
                <p class="text-slate-400 text-sm">No hay inscripciones con ese filtro.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">#</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Cédula</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Apellidos y Nombres</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Año Escolar</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Sección</th>
                            <th class="px-3 py-3 text-center text-xs font-black text-slate-500 uppercase whitespace-nowrap">N° Lista</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Cond. Ingreso</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Representante</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Estado</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Fecha</th>
                            <th class="px-3 py-3 text-left text-xs font-black text-slate-500 uppercase whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="ins in inscripciones" :key="ins.id_matricula" class="hover:bg-slate-50 transition">
                            <td class="px-3 py-3 text-slate-400 text-xs">{{ ins.id_matricula }}</td>
                            <td class="px-3 py-3 font-mono text-xs">
                                {{ ins.estudiante?.tipo_documento ?? 'V' }}-{{ ins.cedula_estudiante }}
                            </td>
                            <td class="px-3 py-3 font-semibold text-slate-800 whitespace-nowrap">{{ nombreEst(ins) }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ ins.codigo_ano_escolar }}</td>
                            <td class="px-3 py-3 text-slate-600 text-xs whitespace-nowrap">{{ nombreSec(ins) }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ ins.numero_lista ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 text-xs capitalize">{{ ins.condicion_ingreso ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 text-xs whitespace-nowrap">{{ nombreRep(ins) }}</td>
                            <td class="px-3 py-3">
                                <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase whitespace-nowrap',
                                    estadoBadge[ins.estado_matricula] ?? 'bg-slate-100 text-slate-500']">
                                    {{ ins.estado_matricula ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-500 whitespace-nowrap">{{ formatFecha(ins.fecha_matricula) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex gap-1.5">
                                    <button @click="abrirEditar(ins)"
                                        class="px-2 py-1 text-xs font-bold text-sky-700 bg-sky-50 border border-sky-200 rounded-lg hover:bg-sky-100 transition">
                                        Editar
                                    </button>
                                    <button @click="eliminar(ins)"
                                        class="px-2 py-1 text-xs font-bold text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL CREAR / EDITAR -->
        <Teleport to="body">
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-lg font-black text-slate-800">
                            {{ editMode ? '✏️ Editar Inscripción' : '+ Nueva Inscripción' }}
                        </h2>
                        <button @click="cerrarModal" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
                    </div>

                    <div v-if="errorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
                        ⚠️ {{ errorMsg }}
                    </div>

                    <form @submit.prevent="guardar" class="space-y-4">
                        <!-- Fila 1 -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Estudiante *</label>
                                <select v-model="form.estudiante_id" required :disabled="editMode"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 disabled:bg-slate-50">
                                    <option value="">Seleccionar…</option>
                                    <option v-for="e in estudiantes" :key="e.cedula_estudiante" :value="e.cedula_estudiante">
                                        {{ e.apellidos }}, {{ e.nombres }} ({{ e.cedula_estudiante }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Año Escolar *</label>
                                <select v-model="form.codigo_ano_escolar" required
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                                    <option value="">Seleccionar…</option>
                                    <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                                        {{ a.codigo_ano_escolar }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Fila 2 -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Sección *</label>
                                <select v-model="form.codigo_seccion" required
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                                    <option value="">Seleccionar…</option>
                                    <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                                        {{ s.grado?.nombre ?? s.codigo_grado }} — Secc. {{ s.letra }} ({{ s.codigo_ano_escolar }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Representante</label>
                                <select v-model="form.cedula_representante"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                                    <option value="">Sin asignar</option>
                                    <option v-for="r in representantes" :key="r.cedula_representante" :value="r.cedula_representante">
                                        {{ r.nombres }} {{ r.apellidos }} ({{ r.cedula_representante }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Fila 3 -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Fecha Matrícula *</label>
                                <input v-model="form.fecha_matricula" type="date" required
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">N° de Lista</label>
                                <input v-model="form.numero_lista" type="number" min="1"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Año Inicio Cursante</label>
                                <input v-model="form.ano_inicio_cursante" type="number" min="2000" max="2099"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" />
                            </div>
                        </div>

                        <!-- Fila 4 -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Condición de Ingreso</label>
                                <select v-model="form.condicion_ingreso"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                                    <option value="">Sin especificar</option>
                                    <option v-for="c in condicionesIngreso" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Estado</label>
                                <select v-model="form.estado_matricula"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                                    <option v-for="e in estadosMatricula" :key="e" :value="e">{{ e }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Fila 5 -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Procedencia</label>
                            <input v-model="form.procedencia" type="text"
                                placeholder="Escuela o institución de procedencia"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400" />
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="2"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 resize-none"></textarea>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                            <button type="button" @click="cerrarModal"
                                class="px-5 py-2 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="saving"
                                class="px-5 py-2 rounded-xl text-sm font-bold text-white bg-sky-600 hover:bg-sky-700 disabled:opacity-50 transition">
                                <span v-if="saving">Guardando…</span>
                                <span v-else>{{ editMode ? 'Actualizar' : 'Registrar' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>