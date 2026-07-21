<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

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
const saving      = ref(false);
const errors      = ref({});
const successMsg  = ref('');

// ── Form de sección ───────────────────────────────────────────
const form = reactive({
    codigo_seccion:    '', letra: '', codigo_grado: '',
    codigo_ano_escolar: '', id_mencion: '',
    cedula_docente_guia: '', capacidad_maxima: 35,
    turno: 'mañana', aula_asignada: '',
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
    editando.value = !!item;
    Object.assign(form, item ?? {
        codigo_seccion: '', letra: '', codigo_grado: '',
        codigo_ano_escolar: filtroAnio.value || '',
        id_mencion: '', cedula_docente_guia: '',
        capacidad_maxima: 35, turno: 'mañana', aula_asignada: '',
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
    try {
        await axios.delete(`/api/secciones/${s.codigo_seccion}`);
        successMsg.value = 'Sección eliminada.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.error ?? e.message);
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
        // Recargar asignaciones
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
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-800">🗂️ Secciones</h1>
                    <p class="text-xs text-slate-500">Organización de grupos, asignaciones docente por sección y año escolar</p>
                </div>
                <div class="flex gap-3">
                    <select v-model="filtroAnio" @change="cargar"
                        class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                        <option value="">Todos los años</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                            {{ a.codigo_ano_escolar }}
                        </option>
                    </select>
                    <button @click="abrir()"
                        class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">
                        ＋ Nueva Sección
                    </button>
                </div>
            </div>
        </template>

        <!-- ÉXITO -->
        <div v-if="successMsg" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700 font-medium">
            ✅ {{ successMsg }}
        </div>

        <!-- GRID DE SECCIONES -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-if="!secciones.length" class="col-span-full text-center py-10 text-slate-400">
                No hay secciones.
            </div>
            <div v-for="s in secciones" :key="s.codigo_seccion"
                class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition group">
                <!-- Letra y código -->
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center text-2xl font-black text-violet-700">
                        {{ s.letra }}
                    </div>
                    <span class="text-xs font-mono text-slate-400">{{ s.codigo_seccion }}</span>
                </div>
                <!-- Info principal -->
                <p class="font-black text-slate-800">{{ s.grado?.nombre ?? s.codigo_grado }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ s.mencion?.nombre ?? 'Sin mención' }}</p>
                <p class="text-xs text-slate-400 mt-1">
                    {{ s.turno }} · Aula: {{ s.aula_asignada ?? '—' }}
                </p>
                <p class="text-xs text-slate-400">
                    Cap: {{ s.capacidad_maxima }}
                    <span v-if="s.docente_guia?.personal"> · Guía: {{ s.docente_guia.personal.apellidos }}</span>
                </p>
                <!-- Asignaciones badge -->
                <div v-if="s.asignaciones_docente?.length" class="mt-2">
                    <span class="px-2 py-0.5 bg-violet-50 text-violet-700 text-[10px] font-black rounded-full">
                        {{ s.asignaciones_docente.length }} asignación(es)
                    </span>
                </div>
                <!-- Acciones -->
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-50 gap-1">
                    <button @click="abrirAsignaciones(s)"
                        class="text-violet-600 text-xs font-bold hover:underline hover:bg-violet-50 px-2 py-1 rounded-lg transition">
                        Asignaciones
                    </button>
                    <div class="flex gap-1">
                        <button @click="abrir(s)"
                            class="text-sky-600 text-xs font-bold hover:underline hover:bg-sky-50 px-2 py-1 rounded-lg transition">
                            Editar
                        </button>
                        <button @click="eliminar(s)"
                            class="text-red-500 text-xs font-bold hover:underline hover:bg-red-50 px-2 py-1 rounded-lg transition">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CREAR/EDITAR SECCIÓN -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex justify-between">
                        <h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nueva' }} Sección</h2>
                        <button @click="modal=false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
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
                        </div>
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" class="inp">
                                <option value="">—</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                                    {{ a.codigo_ano_escolar }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Grado *</label>
                            <select v-model="form.codigo_grado" class="inp">
                                <option value="">—</option>
                                <option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option>
                            </select>
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
                                <option value="mañana">Mañana</option>
                                <option value="tarde">Tarde</option>
                                <option value="nocturno">Nocturno</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Aula Asignada</label>
                            <input v-model="form.aula_asignada" type="text" class="inp" placeholder="Ej: Aula 3B" />
                        </div>
                    </div>
                    <div class="p-5 border-t flex justify-end gap-3">
                        <button @click="modal=false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">
                            Cancelar
                        </button>
                        <button @click="guardar" :disabled="saving"
                            class="px-5 py-2 bg-sky-600 text-white text-sm font-bold rounded-xl disabled:opacity-50">
                            {{ saving ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL ASIGNACIONES DOCENTE -->
        <Teleport to="body">
            <div v-if="showAsignaciones" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex items-center justify-between">
                        <div>
                            <h2 class="font-black text-slate-800">📋 Asignaciones — Sección {{ seccionSelec?.letra }}</h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ seccionSelec?.grado?.nombre ?? seccionSelec?.codigo_grado }} · {{ seccionSelec?.codigo_ano_escolar }}
                            </p>
                        </div>
                        <button @click="showAsignaciones = false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>

                    <!-- Formulario nueva asignación -->
                    <div class="p-5 border-b bg-slate-50">
                        <p class="text-xs font-black uppercase text-slate-500 tracking-widest mb-3">Nueva Asignación</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-3 sm:col-span-1">
                                <label class="lbl">Docente *</label>
                                <select v-model="asigForm.cedula_docente" class="inp mt-1">
                                    <option value="">Seleccionar…</option>
                                    <option v-for="p in docentesFiltrados" :key="p.cedula_personal" :value="p.cedula_personal">
                                        {{ p.apellidos }}, {{ p.nombres }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <label class="lbl">Materia *</label>
                                <select v-model="asigForm.siglas_materia" class="inp mt-1">
                                    <option value="">Seleccionar…</option>
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
                            <button @click="guardarAsignacion" :disabled="savingAsig"
                                class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl disabled:opacity-50 transition">
                                {{ savingAsig ? 'Guardando…' : '＋ Agregar Asignación' }}
                            </button>
                        </div>
                    </div>

                    <!-- Listado de asignaciones actuales -->
                    <div class="p-5">
                        <div v-if="loadingAsig" class="py-8 text-center text-slate-400">Cargando asignaciones…</div>
                        <div v-else-if="!asignaciones.length" class="py-8 text-center text-slate-400 text-sm">
                            No hay asignaciones registradas para esta sección.
                        </div>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-violet-50 border-b border-violet-100">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-black text-violet-700 uppercase">Docente</th>
                                    <th class="px-3 py-2 text-left text-xs font-black text-violet-700 uppercase">Materia</th>
                                    <th class="px-3 py-2 text-center text-xs font-black text-violet-700 uppercase">Horas</th>
                                    <th class="px-3 py-2 text-left text-xs font-black text-violet-700 uppercase">Año Escolar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="a in asignaciones" :key="a.id_asignacion" class="hover:bg-slate-50">
                                    <td class="px-3 py-2 font-semibold">
                                        {{ a.docente?.personal?.nombre_completo ?? nombreDocente(a.cedula_docente) }}
                                    </td>
                                    <td class="px-3 py-2">
                                        {{ a.materia?.nombre ?? nombreMateria(a.siglas_materia) }}
                                        <span class="text-slate-400 text-xs">({{ a.siglas_materia }})</span>
                                    </td>
                                    <td class="px-3 py-2 text-center">{{ a.horas_asignadas }}</td>
                                    <td class="px-3 py-2 text-xs text-slate-500">{{ a.codigo_ano_escolar }}</td>
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
.lbl { @apply text-xs font-bold text-slate-600 uppercase; }
.inp { @apply w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 mt-1 bg-white; }
.err { @apply text-red-500 text-xs mt-1; }
</style>
