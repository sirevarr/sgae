<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const estudiantes = ref([]);
const loading     = ref(false);
const buscar      = ref('');
const pagination  = ref({});

const modal = ref(false);
const editando = ref(false);
const form = reactive({
    cedula_estudiante: '', tipo_documento: 'V', nacionalidad: 'Venezolana',
    nombres: '', apellidos: '', genero: 'M',
    fecha_nacimiento: '', lugar_nacimiento: '', estado_nacimiento: '',
    municipio_nacimiento: '', direccion: '', telefono: '', correo: '',
    condiciones_medicas: '', medicamentos: '', estado_estudiante: 'activo',
});
const errors = ref({});
const saving = ref(false);

async function cargar(page = 1) {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/estudiantes', {
            params: { buscar: buscar.value, page }
        });
        estudiantes.value = data.data;
        pagination.value  = data;
    } finally {
        loading.value = false;
    }
}

function abrirNuevo() {
    editando.value = false;
    Object.assign(form, {
        cedula_estudiante: '', tipo_documento: 'V', nacionalidad: 'Venezolana',
        nombres: '', apellidos: '', genero: 'M',
        fecha_nacimiento: '', lugar_nacimiento: '', estado_nacimiento: '',
        municipio_nacimiento: '', direccion: '', telefono: '', correo: '',
        condiciones_medicas: '', medicamentos: '', estado_estudiante: 'activo',
    });
    errors.value = {};
    modal.value  = true;
}

function abrirEditar(est) {
    editando.value = true;
    Object.assign(form, { ...est });
    errors.value = {};
    modal.value  = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) {
            await axios.put(`/api/estudiantes/${form.cedula_estudiante}`, form);
        } else {
            await axios.post('/api/estudiantes', form);
        }
        modal.value = false;
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert('Error al guardar: ' + (e.response?.data?.message ?? e.message));
    } finally {
        saving.value = false;
    }
}

let timer;
function onBuscar() {
    clearTimeout(timer);
    timer = setTimeout(() => cargar(), 400);
}

onMounted(() => cargar());
</script>

<template>
    <Head title="Estudiantes — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-black text-slate-800">🧑‍🎓 Estudiantes</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Registro y gestión del censo estudiantil</p>
                </div>
                <button @click="abrirNuevo"
                    class="flex items-center gap-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">
                    <span>＋</span> Nuevo Estudiante
                </button>
            </div>
        </template>

        <!-- Buscador -->
        <div class="mb-4 flex gap-3">
            <input v-model="buscar" @input="onBuscar" type="text"
                placeholder="Buscar por nombre, apellido o cédula…"
                class="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white shadow-sm" />
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div v-if="loading" class="p-12 text-center text-slate-400 text-sm">Cargando estudiantes…</div>
            <table v-else class="w-full text-sm">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Cédula</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Apellidos y Nombres</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Género</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Sección Actual</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-xs font-black uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!estudiantes.length">
                        <td colspan="6" class="text-center py-10 text-slate-400 text-sm">No se encontraron estudiantes.</td>
                    </tr>
                    <tr v-for="est in estudiantes" :key="est.cedula_estudiante"
                        class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">
                            {{ est.tipo_documento }}-{{ est.cedula_estudiante }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-800">
                            {{ est.apellidos }}, {{ est.nombres }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ est.genero === 'M' ? '♂ Masc.' : '♀ Fem.' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            {{ est.matricula_actual?.seccion?.grado?.nombre ?? '—' }}
                            {{ est.matricula_actual?.seccion?.letra ? ' ' + est.matricula_actual.seccion.letra : '' }}
                        </td>
                        <td class="px-4 py-3">
                            <span :class="[
                                'px-2 py-1 rounded-full text-[10px] font-black uppercase',
                                est.estado_estudiante === 'activo' ? 'bg-emerald-100 text-emerald-700' :
                                est.estado_estudiante === 'retirado' ? 'bg-red-100 text-red-700' :
                                'bg-slate-100 text-slate-500'
                            ]">{{ est.estado_estudiante }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="abrirEditar(est)"
                                class="text-sky-600 hover:text-sky-800 font-semibold text-xs px-3 py-1 rounded-lg hover:bg-sky-50 transition">
                                Editar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginación -->
            <div v-if="pagination.last_page > 1" class="flex justify-between items-center px-4 py-3 bg-slate-50 border-t border-slate-100">
                <span class="text-xs text-slate-500">
                    Mostrando {{ pagination.from }}–{{ pagination.to }} de {{ pagination.total }}
                </span>
                <div class="flex gap-1">
                    <button v-for="p in pagination.last_page" :key="p"
                        @click="cargar(p)"
                        :class="['w-8 h-8 rounded-lg text-xs font-bold transition', p === pagination.current_page ? 'bg-sky-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-100']">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── MODAL ──────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h2 class="font-black text-slate-800 text-lg">
                            {{ editando ? 'Editar Estudiante' : 'Nuevo Estudiante' }}
                        </h2>
                        <button @click="modal = false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <!-- Cédula (solo en nuevo) -->
                        <div v-if="!editando" class="col-span-2 grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs font-bold text-slate-600 uppercase">Tipo Doc.</label>
                                <select v-model="form.tipo_documento" class="inp">
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                </select>
                                <p v-if="errors.tipo_documento" class="err">{{ errors.tipo_documento[0] }}</p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-xs font-bold text-slate-600 uppercase">Número de Cédula *</label>
                                <input v-model="form.cedula_estudiante" type="text" class="inp" placeholder="12345678" />
                                <p v-if="errors.cedula_estudiante" class="err">{{ errors.cedula_estudiante[0] }}</p>
                            </div>
                        </div>
                        <!-- Nombres -->
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Nombres *</label>
                            <input v-model="form.nombres" type="text" class="inp" />
                            <p v-if="errors.nombres" class="err">{{ errors.nombres[0] }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Apellidos *</label>
                            <input v-model="form.apellidos" type="text" class="inp" />
                            <p v-if="errors.apellidos" class="err">{{ errors.apellidos[0] }}</p>
                        </div>
                        <!-- Género -->
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Género *</label>
                            <select v-model="form.genero" class="inp">
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <!-- Fecha nacimiento -->
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Fecha de Nacimiento</label>
                            <input v-model="form.fecha_nacimiento" type="date" class="inp" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Lugar de Nacimiento</label>
                            <input v-model="form.lugar_nacimiento" type="text" class="inp" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Municipio Nacimiento</label>
                            <input v-model="form.municipio_nacimiento" type="text" class="inp" />
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs font-bold text-slate-600 uppercase">Dirección</label>
                            <input v-model="form.direccion" type="text" class="inp" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Teléfono</label>
                            <input v-model="form.telefono" type="text" class="inp" />
                        </div>
                        <div>
                            <label class="text-xs font-bold text-slate-600 uppercase">Correo</label>
                            <input v-model="form.correo" type="email" class="inp" />
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs font-bold text-slate-600 uppercase">Condiciones Médicas</label>
                            <textarea v-model="form.condiciones_medicas" rows="2" class="inp"></textarea>
                        </div>
                        <!-- Estado (solo en edición) -->
                        <div v-if="editando">
                            <label class="text-xs font-bold text-slate-600 uppercase">Estado</label>
                            <select v-model="form.estado_estudiante" class="inp">
                                <option value="activo">Activo</option>
                                <option value="retirado">Retirado</option>
                                <option value="graduado">Graduado</option>
                                <option value="trasladado">Trasladado</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                        <button @click="modal = false" class="px-5 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                            Cancelar
                        </button>
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
.inp {
    @apply w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 mt-1 bg-white;
}
.err { @apply text-red-500 text-xs mt-1; }
</style>
