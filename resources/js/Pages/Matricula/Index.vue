<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

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
const saving  = ref(false);
const errors  = ref({});

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
        const { data } = await axios.get('/api/matriculas', {
            params: { ...filtros, page }
        });
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
    editando.value = true;
    Object.assign(form, {
        ...m,
        cedula_representante: m.cedula_representante ?? '',
        id_matricula: m.id_matricula,
    });
    errors.value = {};
    cargarSecciones();
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) {
            await axios.put(`/api/matriculas/${form.id_matricula}`, form);
        } else {
            await axios.post('/api/matriculas', form);
        }
        modal.value = false;
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else if (e.response?.data?.error) alert(e.response.data.error);
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally {
        saving.value = false;
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-slate-800">📝 Matrículas</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Inscripción de estudiantes en secciones del año escolar</p>
                </div>
                <button @click="abrirNuevo"
                    class="flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">
                    ＋ Nueva Matrícula
                </button>
            </div>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap gap-3">
            <select v-model="filtros.codigo_ano_escolar" @change="() => { cargarSecciones(); onFiltro(); }"
                class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white shadow-sm">
                <option value="">Todos los años</option>
                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                    {{ a.codigo_ano_escolar }}
                </option>
            </select>
            <select v-model="filtros.codigo_seccion" @change="onFiltro"
                class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white shadow-sm">
                <option value="">Todas las secciones</option>
                <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                    {{ s.grado?.nombre }} {{ s.letra }}
                </option>
            </select>
            <input v-model="filtros.buscar" @input="onFiltro" type="text"
                placeholder="Buscar estudiante…"
                class="flex-1 min-w-48 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white shadow-sm" />
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div v-if="loading" class="p-12 text-center text-slate-400 text-sm">Cargando matrículas…</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Estudiante</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Sección / Grado</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Año Escolar</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Condición</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!matriculas.length">
                        <td colspan="7" class="text-center py-10 text-slate-400 text-sm">No hay matrículas registradas.</td>
                    </tr>
                    <tr v-for="m in matriculas" :key="m.id_matricula" class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ m.numero_lista ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800">{{ m.estudiante?.apellidos }}, {{ m.estudiante?.nombres }}</p>
                            <p class="text-[10px] text-slate-400 font-mono">{{ m.estudiante?.tipo_documento }}-{{ m.estudiante?.cedula_estudiante }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <p class="font-semibold">{{ m.seccion?.grado?.nombre }} — {{ m.seccion?.letra }}</p>
                            <p class="text-xs text-slate-400">{{ m.seccion?.turno }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ m.codigo_ano_escolar }}</td>
                        <td class="px-4 py-3 text-xs">
                            {{ condiciones.find(c => c.value === m.condicion_ingreso)?.label ?? m.condicion_ingreso }}
                        </td>
                        <td class="px-4 py-3">
                            <span :class="['px-2 py-1 rounded-full text-[10px] font-black uppercase',
                                m.estado_matricula === 'activa' ? 'bg-emerald-100 text-emerald-700' :
                                m.estado_matricula === 'retirada' ? 'bg-red-100 text-red-700' :
                                'bg-slate-100 text-slate-500']">
                                {{ m.estado_matricula }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="abrirEditar(m)"
                                class="text-sky-600 hover:text-sky-800 font-semibold text-xs px-3 py-1 rounded-lg hover:bg-sky-50 transition">
                                Editar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="pagination.last_page > 1" class="flex justify-between items-center px-4 py-3 bg-slate-50 border-t border-slate-100">
                <span class="text-xs text-slate-500">{{ pagination.from }}–{{ pagination.to }} de {{ pagination.total }}</span>
                <div class="flex gap-1">
                    <button v-for="p in pagination.last_page" :key="p" @click="cargar(p)"
                        :class="['w-8 h-8 rounded-lg text-xs font-bold transition',
                            p === pagination.current_page ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100']">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-black text-slate-800 text-lg">{{ editando ? 'Editar Matrícula' : 'Nueva Matrícula' }}</h2>
                        <button @click="modal = false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <!-- Estudiante -->
                        <div class="col-span-2" v-if="!editando">
                            <label class="lbl">Estudiante (cédula) *</label>
                            <input v-model="form.cedula_estudiante" type="text" class="inp" placeholder="V-12345678 o solo el número" />
                            <p v-if="errors.cedula_estudiante" class="err">{{ errors.cedula_estudiante[0] }}</p>
                        </div>
                        <!-- Año escolar -->
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" @change="cargarSecciones" class="inp">
                                <option value="">Seleccionar…</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                            </select>
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <!-- Sección -->
                        <div>
                            <label class="lbl">Sección *</label>
                            <select v-model="form.codigo_seccion" class="inp">
                                <option value="">Seleccionar…</option>
                                <option v-for="s in secciones" :key="s.codigo_seccion" :value="s.codigo_seccion">
                                    {{ s.grado?.nombre }} — {{ s.letra }} ({{ s.cupos_disponibles ?? '?' }} cupos)
                                </option>
                            </select>
                            <p v-if="errors.codigo_seccion" class="err">{{ errors.codigo_seccion[0] }}</p>
                        </div>
                        <!-- Representante -->
                        <div class="col-span-2">
                            <label class="lbl">Cédula Representante</label>
                            <input v-model="form.cedula_representante" type="text" class="inp" placeholder="Opcional" />
                        </div>
                        <div>
                            <label class="lbl">Fecha Matrícula *</label>
                            <input v-model="form.fecha_matricula" type="date" class="inp" />
                        </div>
                        <div>
                            <label class="lbl">N° Lista</label>
                            <input v-model="form.numero_lista" type="number" class="inp" min="1" />
                        </div>
                        <div>
                            <label class="lbl">Condición de Ingreso *</label>
                            <select v-model="form.condicion_ingreso" class="inp">
                                <option v-for="c in condiciones" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Año Inicio Cursante</label>
                            <input v-model="form.ano_inicio_cursante" type="number" class="inp" placeholder="Ej: 2019" />
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Procedencia</label>
                            <input v-model="form.procedencia" type="text" class="inp" placeholder="Plantel de procedencia" />
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
                    <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button @click="modal = false" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                        <button @click="guardar" :disabled="saving"
                            class="px-6 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold rounded-xl shadow transition disabled:opacity-50">
                            {{ saving ? 'Guardando…' : 'Guardar' }}
                        </button>
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
