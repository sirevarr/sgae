<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';

const estudiantes = ref([]);
const loading     = ref(false);
const buscar      = ref('');
const pagination  = ref({});

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

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
const successMsg = ref('');

async function eliminar(est) {
    if (!canManageRecords.value) return alert('No tienes permiso para eliminar estudiantes.');
    if (!confirm(`Eliminar al estudiante "${est.nombres} ${est.apellidos}" (${est.cedula_estudiante})?`)) return;
    try {
        await axios.delete(`/api/estudiantes/${est.cedula_estudiante}`);
        successMsg.value = 'Estudiante eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    }
}

// ── Modal Ficha Antropométrica / Pendientes ──────────────────────────
const showDetalle = ref(false);
const detalleEst  = ref(null);
const detalleTab  = ref('ficha');
const fichas      = ref([]);
const pendientes  = ref([]);
const loadingDetalle = ref(false);
const aniosEsc    = ref([]);
const fichaForm   = reactive({
    codigo_ano_escolar: '',
    estatura: '', peso: '',
    talla_camisa: '', talla_pantalon: '', talla_zapatos: '',
    fecha_medicion: '',
});
const savingFicha = ref(false);

async function cargarAnios() {
    const { data } = await axios.get('/api/anios-escolares');
    aniosEsc.value = data.data ?? data;
}

async function abrirDetalle(est) {
    detalleEst.value = est;
    detalleTab.value = 'ficha';
    showDetalle.value = true;
    loadingDetalle.value = true;
    try {
        const { data } = await axios.get(`/api/estudiantes/${est.cedula_estudiante}`);
        detalleEst.value = { ...detalleEst.value, ...data };
        fichas.value    = data.fichas_antropometricas ?? [];
        pendientes.value = data.materias_pendientes ?? [];
        const vigente = aniosEsc.value.find(a => a.vigente);
        if (vigente) fichaForm.codigo_ano_escolar = vigente.codigo_ano_escolar;
        const fichaActual = fichas.value.find(f => f.codigo_ano_escolar === fichaForm.codigo_ano_escolar);
        if (fichaActual) {
            Object.assign(fichaForm, {
                codigo_ano_escolar: fichaActual.codigo_ano_escolar,
                estatura: fichaActual.estatura ?? '',
                peso: fichaActual.peso ?? '',
                talla_camisa: fichaActual.talla_camisa ?? '',
                talla_pantalon: fichaActual.talla_pantalon ?? '',
                talla_zapatos: fichaActual.talla_zapatos ?? '',
                fecha_medicion: fichaActual.fecha_medicion ?? '',
            });
        }
    } finally {
        loadingDetalle.value = false;
    }
}

async function guardarFicha() {
    if (!canManageRecords.value) { alert('No tienes permiso para modificar la ficha.'); return; }
    savingFicha.value = true;
    try {
        await axios.post(`/api/estudiantes/${detalleEst.value.cedula_estudiante}/ficha`, fichaForm);
        const { data } = await axios.get(`/api/estudiantes/${detalleEst.value.cedula_estudiante}`);
        fichas.value = data.fichas_antropometricas ?? [];
        alert('Ficha actualizada correctamente.');
    } catch (e) {
        alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally {
        savingFicha.value = false;
    }
}

function onFichaAnioChange() {
    const fichaActual = fichas.value.find(f => f.codigo_ano_escolar === fichaForm.codigo_ano_escolar);
    if (fichaActual) {
        Object.assign(fichaForm, {
            estatura: fichaActual.estatura ?? '',
            peso: fichaActual.peso ?? '',
            talla_camisa: fichaActual.talla_camisa ?? '',
            talla_pantalon: fichaActual.talla_pantalon ?? '',
            talla_zapatos: fichaActual.talla_zapatos ?? '',
            fecha_medicion: fichaActual.fecha_medicion ?? '',
        });
    } else {
        Object.assign(fichaForm, { estatura: '', peso: '', talla_camisa: '', talla_pantalon: '', talla_zapatos: '', fecha_medicion: '' });
    }
}

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
    if (!canManageRecords.value) return;
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
    if (!canManageRecords.value) return;
    editando.value = true;
    Object.assign(form, { ...est });
    errors.value = {};
    modal.value  = true;
}

function editarDesdeDetalle() {
    if (!canManageRecords.value || !detalleEst.value) return;
    showDetalle.value = false;
    abrirEditar(detalleEst.value);
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

onMounted(() => { cargar(); cargarAnios(); });
</script>

<template>
    <Head title="Estudiantes — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Estudiantes</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Registro y gestión del censo estudiantil</p>
                </div>
                <button v-if="canManageRecords" @click="abrirNuevo" class="btn-primary">Nuevo estudiante</button>
            </div>
        </template>

        <!-- Mensaje de éxito -->
        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <!-- Buscador -->
        <div class="mb-4 flex gap-3">
            <input v-model="buscar" @input="onBuscar" type="text"
                placeholder="Buscar por nombre, apellido o cédula..."
                class="inp flex-1" />
        </div>

        <!-- Tabla -->
        <div class="bg-paper border border-borde rounded-[6px] overflow-hidden">
            <div v-if="loading" class="p-10 text-center text-piedra text-[13px]">Cargando...</div>
            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Cédula</th>
                        <th class="th">Apellidos y Nombres</th>
                        <th class="th">Género</th>
                        <th class="th">Sección Actual</th>
                        <th class="th">Estado</th>
                        <th class="th text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!estudiantes.length">
                        <td colspan="6" class="text-center py-10 text-piedra text-[13px]">No se encontraron estudiantes.</td>
                    </tr>
                    <tr v-for="est in estudiantes" :key="est.cedula_estudiante" class="hover:bg-crema transition-colors">
                        <td class="td font-mono text-[12px] text-piedra">
                            {{ est.tipo_documento }}-{{ est.cedula_estudiante }}
                        </td>
                        <td class="td font-semibold text-tinta text-[12.5px]">
                            {{ est.apellidos }}, {{ est.nombres }}
                        </td>
                        <td class="td text-[12.5px] text-piedra">
                            {{ est.genero === 'M' ? 'Masc.' : 'Fem.' }}
                        </td>
                        <td class="td text-[12px] text-piedra">
                            {{ est.matricula_actual?.seccion?.grado?.nombre ?? '—' }}
                            {{ est.matricula_actual?.seccion?.letra ? ' ' + est.matricula_actual.seccion.letra : '' }}
                        </td>
                        <td class="td">
                            <span :class="[
                                'badge',
                                est.estado_estudiante === 'activo'    ? 'badge-ok' :
                                est.estado_estudiante === 'retirado'  ? 'badge-alerta' :
                                'badge-neutral'
                            ]">{{ est.estado_estudiante }}</span>
                        </td>
                        <td class="td text-center">
                            <div class="flex gap-2 justify-center">
                                <button v-if="canManageRecords" @click="abrirEditar(est)" class="btn-table-action">Editar</button>
                                <button @click="abrirDetalle(est)" class="btn-table-action">Ver</button>
                                <button v-if="canManageRecords" @click="eliminar(est)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginación -->
            <div v-if="pagination.last_page > 1" class="flex justify-between items-center px-5 py-3 border-t border-borde bg-crema">
                <span class="text-[11px] text-piedra">
                    Mostrando {{ pagination.from }}–{{ pagination.to }} de {{ pagination.total }}
                </span>
                <div class="flex gap-1">
                    <button v-for="p in pagination.last_page" :key="p"
                        @click="cargar(p)"
                        :class="['w-7 h-7 rounded-[4px] text-[11px] font-semibold transition-colors',
                            p === pagination.current_page
                                ? 'bg-rojo text-paper'
                                : 'bg-paper text-piedra hover:bg-crema border border-borde']">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL CRUD -->
        <Teleport to="body">
            <div v-if="modal && canManageRecords" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b border-borde flex items-center justify-between">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">
                            {{ editando ? 'Editar Estudiante' : 'Nuevo Estudiante' }}
                        </h2>
                        <button @click="modal = false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <!-- Cédula (solo en nuevo) -->
                        <div v-if="!editando" class="col-span-2 grid grid-cols-3 gap-2">
                            <div>
                                <label class="lbl">Tipo Doc.</label>
                                <select v-model="form.tipo_documento" class="inp">
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                </select>
                                <p v-if="errors.tipo_documento" class="err">{{ errors.tipo_documento[0] }}</p>
                            </div>
                            <div class="col-span-2">
                                <label class="lbl">Número de cédula *</label>
                                <input v-model="form.cedula_estudiante" type="text" class="inp" placeholder="12345678" />
                                <p v-if="errors.cedula_estudiante" class="err">{{ errors.cedula_estudiante[0] }}</p>
                            </div>
                        </div>
                        <!-- Nombres -->
                        <div>
                            <label class="lbl">Nombres *</label>
                            <input v-model="form.nombres" type="text" class="inp" />
                            <p v-if="errors.nombres" class="err">{{ errors.nombres[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Apellidos *</label>
                            <input v-model="form.apellidos" type="text" class="inp" />
                            <p v-if="errors.apellidos" class="err">{{ errors.apellidos[0] }}</p>
                        </div>
                        <!-- Género -->
                        <div>
                            <label class="lbl">Género *</label>
                            <select v-model="form.genero" class="inp">
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                            <p v-if="errors.genero" class="err">{{ errors.genero[0] }}</p>
                        </div>
                        <!-- Fecha nacimiento -->
                        <div>
                            <label class="lbl">Fecha de nacimiento</label>
                            <input v-model="form.fecha_nacimiento" type="date" class="inp" />
                            <p v-if="errors.fecha_nacimiento" class="err">{{ errors.fecha_nacimiento[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Lugar de nacimiento</label>
                            <input v-model="form.lugar_nacimiento" type="text" class="inp" />
                            <p v-if="errors.lugar_nacimiento" class="err">{{ errors.lugar_nacimiento[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Municipio nacimiento</label>
                            <input v-model="form.municipio_nacimiento" type="text" class="inp" />
                            <p v-if="errors.municipio_nacimiento" class="err">{{ errors.municipio_nacimiento[0] }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Dirección</label>
                            <input v-model="form.direccion" type="text" class="inp" />
                            <p v-if="errors.direccion" class="err">{{ errors.direccion[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Teléfono</label>
                            <input v-model="form.telefono" type="text" class="inp" />
                            <p v-if="errors.telefono" class="err">{{ errors.telefono[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Correo</label>
                            <input v-model="form.correo" type="email" class="inp" />
                            <p v-if="errors.correo" class="err">{{ errors.correo[0] }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="lbl">Condiciones médicas</label>
                            <textarea v-model="form.condiciones_medicas" rows="2" class="inp"></textarea>
                            <p v-if="errors.condiciones_medicas" class="err">{{ errors.condiciones_medicas[0] }}</p>
                        </div>
                        <!-- Estado (solo en edición) -->
                        <div v-if="editando">
                            <label class="lbl">Estado</label>
                            <select v-model="form.estado_estudiante" class="inp">
                                <option value="activo">Activo</option>
                                <option value="retirado">Retirado</option>
                                <option value="graduado">Graduado</option>
                            </select>
                            <p v-if="errors.estado_estudiante" class="err">{{ errors.estado_estudiante[0] }}</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal = false" class="btn-secondary">Cancelar</button>
                        <button @click="guardar" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL FICHA ANTROPOMÉTRICA + PENDIENTES -->
        <Teleport to="body">
            <div v-if="showDetalle" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="px-5 py-4 border-b border-borde flex items-center justify-between">
                        <div>
                            <h2 class="font-serif font-semibold text-tinta text-[16px]">
                                {{ detalleEst?.apellidos }}, {{ detalleEst?.nombres }}
                            </h2>
                            <p class="text-[11px] text-piedra mt-0.5">{{ detalleEst?.tipo_documento }}-{{ detalleEst?.cedula_estudiante }}</p>
                        </div>
                        <button @click="showDetalle = false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>

                    <div class="px-5 py-4 border-b border-borde grid grid-cols-2 gap-4 text-[13px] text-piedra">
                        <div>
                            <span class="lbl">Nombres</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.nombres }}</p>
                        </div>
                        <div>
                            <span class="lbl">Apellidos</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.apellidos }}</p>
                        </div>
                        <div>
                            <span class="lbl">Correo</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.correo ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="lbl">Teléfono</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.telefono ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="lbl">Dirección</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.direccion ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="lbl">Género</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.genero === 'M' ? 'Masculino' : detalleEst?.genero === 'F' ? 'Femenino' : '—' }}</p>
                        </div>
                        <div>
                            <span class="lbl">Fecha nacimiento</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.fecha_nacimiento ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="lbl">Lugar nacimiento</span>
                            <p class="mt-1 font-semibold text-tinta">{{ detalleEst?.lugar_nacimiento ?? '—' }}</p>
                        </div>
                    </div>

                    <div class="px-5 py-4 border-b border-borde flex justify-end gap-3">
                        <button v-if="canManageRecords" @click="editarDesdeDetalle" class="btn-primary">Editar como administrador</button>
                    </div>

                    <!-- TABS -->
                    <div class="flex border-b border-borde px-5 gap-5">
                        <button @click="detalleTab = 'ficha'"
                            :class="['py-3 text-[13px] font-semibold border-b-2 -mb-px transition-colors',
                                detalleTab === 'ficha'
                                    ? 'border-dorado text-tinta'
                                    : 'border-transparent text-piedra hover:text-tinta']">
                            Ficha Antropométrica
                        </button>
                        <button @click="detalleTab = 'pendientes'"
                            :class="['py-3 text-[13px] font-semibold border-b-2 -mb-px transition-colors',
                                detalleTab === 'pendientes'
                                    ? 'border-dorado text-tinta'
                                    : 'border-transparent text-piedra hover:text-tinta']">
                            Materias Pendientes
                        </button>
                    </div>

                    <div v-if="loadingDetalle" class="p-10 text-center text-piedra text-[13px]">Cargando...</div>

                    <!-- TAB: FICHA ANTROPOMÉTRICA -->
                    <div v-else-if="detalleTab === 'ficha'" class="p-5 space-y-4">
                        <div>
                            <label class="lbl">Año Escolar</label>
                            <select v-model="fichaForm.codigo_ano_escolar" @change="onFichaAnioChange" class="inp mt-1">
                                <option value="">Seleccionar...</option>
                                <option v-for="a in aniosEsc" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                                    {{ a.codigo_ano_escolar }}
                                </option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="lbl">Estatura (m)</label>
                                <input :disabled="!canManageRecords" v-model="fichaForm.estatura" type="number" step="0.01" min="0.3" max="2.5" class="inp mt-1" />
                            </div>
                            <div>
                                <label class="lbl">Peso (kg)</label>
                                <input :disabled="!canManageRecords" v-model="fichaForm.peso" type="number" step="0.1" min="1" max="250" class="inp mt-1" />
                            </div>
                            <div>
                                <label class="lbl">Talla Camisa</label>
                                <input :disabled="!canManageRecords" v-model="fichaForm.talla_camisa" type="text" class="inp mt-1" placeholder="XS, S, M, L..." />
                            </div>
                            <div>
                                <label class="lbl">Talla Pantalón</label>
                                <input :disabled="!canManageRecords" v-model="fichaForm.talla_pantalon" type="text" class="inp mt-1" />
                            </div>
                            <div>
                                <label class="lbl">Talla Zapatos</label>
                                <input :disabled="!canManageRecords" v-model="fichaForm.talla_zapatos" type="text" class="inp mt-1" />
                            </div>
                            <div>
                                <label class="lbl">Fecha Medición</label>
                                <input :disabled="!canManageRecords" v-model="fichaForm.fecha_medicion" type="date" class="inp mt-1" />
                            </div>
                        </div>
                        <div class="border-t border-borde pt-3 flex items-center gap-3">
                            <p class="text-[11px] text-piedra flex-1">
                                Fichas registradas: {{ fichas.length }}
                                <span v-if="fichas.length"> — {{ fichas.map(f => f.codigo_ano_escolar).join(', ') }}</span>
                            </p>
                            <button v-if="canManageRecords" @click="guardarFicha" :disabled="!fichaForm.codigo_ano_escolar || savingFicha"
                                class="btn-primary text-[12px]">
                                {{ savingFicha ? 'Guardando...' : 'Guardar Ficha' }}
                            </button>
                        </div>
                    </div>

                    <!-- TAB: MATERIAS PENDIENTES -->
                    <div v-else-if="detalleTab === 'pendientes'" class="p-5">
                        <div v-if="!pendientes.length" class="py-8 text-center text-piedra text-[13px]">
                            Sin materias pendientes registradas.
                        </div>
                        <table v-else class="w-full">
                            <thead>
                                <tr class="border-b border-borde">
                                    <th class="th">Materia</th>
                                    <th class="th">Año Origen</th>
                                    <th class="th">Estado</th>
                                    <th class="th">Nota Final</th>
                                    <th class="th">Resolución</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-borde">
                                <tr v-for="p in pendientes" :key="p.id_materia_pendiente" class="hover:bg-crema">
                                    <td class="td font-semibold text-[12.5px]">{{ p.materia?.nombre ?? p.siglas_materia }}</td>
                                    <td class="td text-[12px] text-piedra">{{ p.codigo_ano_escolar_origen }}</td>
                                    <td class="td">
                                        <span :class="[
                                            'badge',
                                            p.estado === 'aprobada'  ? 'badge-ok' :
                                            p.estado === 'pendiente' ? 'badge-alerta' :
                                            'badge-neutral'
                                        ]">{{ p.estado }}</span>
                                    </td>
                                    <td class="td text-[12.5px] text-center">{{ p.nota_final ?? '—' }}</td>
                                    <td class="td text-[12px] text-piedra">{{ p.fecha_resolucion ?? '—' }}</td>
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
.inp {
    @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta
           focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1;
}
.lbl   { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.err   { @apply text-rojo text-[11px] mt-1; }
.th    { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td    { @apply px-4 py-3; }
.badge { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary   { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
</style>
