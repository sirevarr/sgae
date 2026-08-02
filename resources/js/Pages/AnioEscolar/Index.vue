<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol || 'docente').toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));
const lista = ref([]);
const modal = ref(false);
const editando = ref(false);
const viewing = ref(false);
const saving = ref(false);
const errors = ref({});
const successMsg = ref('');

const form = reactive({
    codigo_ano_escolar: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'planificado'
});

// ── Punto 6: Copiar configuración del año anterior ───────────
const modalCopia    = ref(false);
const copiando      = ref(false);
const loadingPreview = ref(false);
const copiaMsg      = ref('');
const copiaError    = ref('');
const copiaForm     = reactive({
    codigo_ano_origen:   '',
    codigo_ano_destino:  '',
    copiar_plan:         true,
    copiar_secciones:    true,
    copiar_asignaciones: false,
});
const preview = ref(null);
const copiaResultados = ref(null);

// Años destino posibles (solo planificados o sin datos)
const aniosDestino = computed(() =>
    lista.value.filter(a => a.codigo_ano_escolar !== copiaForm.codigo_ano_origen)
);

async function cargar() {
    const { data } = await axios.get('/api/anios-escolares');
    lista.value = data;
}

function abrir(item = null) {
    editando.value = !!item;
    viewing.value = false;
    const payload = item ? { ...item } : { codigo_ano_escolar: '', fecha_inicio: '', fecha_fin: '', estado: 'planificado' };
    if (payload.fecha_inicio) payload.fecha_inicio = String(payload.fecha_inicio).substring(0, 10);
    if (payload.fecha_fin) payload.fecha_fin = String(payload.fecha_fin).substring(0, 10);
    Object.assign(form, payload);
    errors.value = {};
    modal.value = true;
}

function ver(item) {
    editando.value = false;
    viewing.value = true;
    const payload = { ...item };
    if (payload.fecha_inicio) payload.fecha_inicio = String(payload.fecha_inicio).substring(0, 10);
    if (payload.fecha_fin) payload.fecha_fin = String(payload.fecha_fin).substring(0, 10);
    Object.assign(form, payload);
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/anios-escolares/${form.codigo_ano_escolar}`, form);
        else await axios.post('/api/anios-escolares', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Año escolar actualizado.' : 'Año escolar creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally { saving.value = false; }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar el año escolar "${item.codigo_ano_escolar}"?`)) return;
    try {
        await axios.delete(`/api/anios-escolares/${item.codigo_ano_escolar}`);
        successMsg.value = 'Año escolar eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.message ?? e.response?.data?.error ?? e.message);
    }
}

// ── Punto 6: funciones de copia ──────────────────────────────
function abrirCopia(item) {
    copiaForm.codigo_ano_origen = item.codigo_ano_escolar;
    copiaForm.codigo_ano_destino = '';
    copiaForm.copiar_plan = true;
    copiaForm.copiar_secciones = true;
    copiaForm.copiar_asignaciones = false;
    preview.value = null;
    copiaResultados.value = null;
    copiaMsg.value = '';
    copiaError.value = '';
    modalCopia.value = true;
}

async function cargarPreview() {
    if (!copiaForm.codigo_ano_destino) { copiaError.value = 'Selecciona un año destino.'; return; }
    loadingPreview.value = true;
    copiaError.value = '';
    try {
        const { data } = await axios.get('/api/anios-escolares/copiar-config/preview', {
            params: { codigo_ano_origen: copiaForm.codigo_ano_origen, codigo_ano_destino: copiaForm.codigo_ano_destino }
        });
        preview.value = data;
    } catch (e) {
        copiaError.value = e.response?.data?.error ?? e.response?.data?.message ?? e.message;
    } finally { loadingPreview.value = false; }
}

async function ejecutarCopia() {
    if (!confirm('¿Confirmar la copia de configuración? Los registros se crearán como borradores editables.')) return;
    copiando.value = true;
    copiaError.value = '';
    copiaMsg.value = '';
    try {
        const { data } = await axios.post('/api/anios-escolares/copiar-config', copiaForm);
        const totalNuevos = (data.resultados.plan_copiados || 0) + (data.resultados.secciones_copiadas || 0) + (data.resultados.asignaciones_copiadas || 0);
        if (totalNuevos > 0) {
            copiaMsg.value = `Nuevos registros creados: ${data.resultados.plan_copiados} materias, ${data.resultados.secciones_copiadas} secciones, ${data.resultados.asignaciones_copiadas} asignaciones.`;
        } else {
            copiaMsg.value = `No se crearon nuevos registros (0 copiados). Todas las materias, secciones y asignaciones ya existían en el año destino (${copiaForm.codigo_ano_destino}) y se evitó su duplicación.`;
        }
        if (data.resultados.errores?.length) {
            copiaMsg.value += `\n${data.resultados.errores.length} error(es) parciales.`;
        }
        cargar();
    } catch (e) {
        copiaError.value = e.response?.data?.error ?? e.response?.data?.message ?? e.message;
    } finally { copiando.value = false; }
}

onMounted(cargar);
</script>

<template>
    <Head title="Años Escolares — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Años Escolares</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Gestión de períodos académicos</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary ml-6">Nuevo Año Escolar</button>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="a in lista" :key="a.codigo_ano_escolar"
                :class="['bg-paper border border-borde rounded-[6px] p-5 flex flex-col justify-between',
                    a.estado === 'vigente' ? 'border-t-[3px] border-t-dorado' : '']">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-serif font-semibold text-tinta text-[17px]">{{ a.codigo_ano_escolar }}</h3>
                        <span :class="['badge',
                            a.estado === 'vigente'     ? 'badge-ok' :
                            a.estado === 'planificado' ? 'badge-alerta' :
                            'badge-neutral']">
                            {{ a.estado }}
                        </span>
                    </div>
                    <p class="text-[11px] text-piedra">Inicio: {{ a.fecha_inicio ?? '—' }}</p>
                    <p class="text-[11px] text-piedra">Fin: {{ a.fecha_fin ?? '—' }}</p>
                </div>
                <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-borde flex-wrap">
                    <button v-if="canManageRecords" @click="abrirCopia(a)" class="btn-table-action text-[#5B3E0E] hover:text-[#3D2A05]" title="Copiar configuración a otro año">
                        Copiar Configuración
                    </button>
                    <button v-if="canManageRecords" @click="abrir(a)" class="btn-table-action">Editar</button>
                    <button v-if="canManageRecords" @click="eliminar(a)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                    <button v-else @click="ver(a)" class="btn-table-action">Ver</button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-md', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">
                            {{ viewing ? 'Ver Año Escolar' : editando ? 'Editar Año Escolar' : 'Nuevo Año Escolar' }}
                        </h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando">
                            <label class="lbl">Código *</label>
                            <input v-model="form.codigo_ano_escolar" type="text" class="inp" placeholder="Ej: 2025-2026" />
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Fecha Inicio</label>
                            <input v-model="form.fecha_inicio" type="date" class="inp" />
                            <p v-if="errors.fecha_inicio" class="err">{{ errors.fecha_inicio[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Fecha Fin</label>
                            <input v-model="form.fecha_fin" type="date" class="inp" />
                            <p v-if="errors.fecha_fin" class="err">{{ errors.fecha_fin[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Estado *</label>
                            <select v-model="form.estado" class="inp">
                                <option value="planificado">Planificado</option>
                                <option value="vigente">Vigente (activo)</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cancelar</button>
                        <button v-if="canManageRecords && !viewing" @click="guardar" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL: Copiar Configuración (Punto 6) -->
        <Teleport to="body">
            <div v-if="modalCopia" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center sticky top-0 bg-paper z-10">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">Copiar Configuración del Año Anterior</h2>
                        <button @click="modalCopia=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>

                    <div class="p-5 space-y-5">
                        <!-- Alertas -->
                        <div v-if="copiaMsg" class="bg-[#E8F5E9] border border-[#A5D6A7] text-[#1b5e20] text-[12px] font-semibold px-4 py-3 rounded-[4px] whitespace-pre-line">
                            {{ copiaMsg }}
                        </div>
                        <div v-if="copiaError" class="bg-[#F4DEDA] border border-rojo/20 text-rojo-dark text-[12px] font-semibold px-4 py-3 rounded-[4px]">
                            {{ copiaError }}
                        </div>

                        <!-- Selectores de año -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="lbl">Año Origen</label>
                                <input :value="copiaForm.codigo_ano_origen" disabled class="inp bg-crema/60 cursor-not-allowed" />
                            </div>
                            <div>
                                <label class="lbl">Año Destino *</label>
                                <select v-model="copiaForm.codigo_ano_destino" class="inp" @change="preview=null; copiaResultados=null">
                                    <option value="">— Seleccionar —</option>
                                    <option v-for="a in aniosDestino" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">
                                        {{ a.codigo_ano_escolar }} ({{ a.estado }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="bg-crema rounded-[4px] border border-borde p-4">
                            <p class="text-[11px] font-semibold text-tinta-soft uppercase tracking-wider mb-3">¿Qué copiar?</p>
                            <label class="flex items-center gap-2 text-[13px] text-tinta mb-2 cursor-pointer">
                                <input type="checkbox" v-model="copiaForm.copiar_plan" class="accent-rojo" />
                                <span><strong>Plan de Estudios</strong> — materias × mención × grado</span>
                            </label>
                            <label class="flex items-center gap-2 text-[13px] text-tinta mb-2 cursor-pointer">
                                <input type="checkbox" v-model="copiaForm.copiar_secciones" class="accent-rojo" />
                                <span><strong>Secciones</strong> — mismas letras/grados, capacidad y turno</span>
                            </label>
                            <label class="flex items-center gap-2 text-[13px] text-tinta cursor-pointer">
                                <input type="checkbox" v-model="copiaForm.copiar_asignaciones" class="accent-rojo" />
                                <span><strong>Asignaciones Docentes</strong> — como sugerencia editable <em class="text-piedra">(los docentes pueden cambiar)</em></span>
                            </label>
                        </div>

                        <!-- Botón Vista Previa -->
                        <div class="flex gap-3">
                            <button @click="cargarPreview" :disabled="loadingPreview || !copiaForm.codigo_ano_destino" class="btn-secondary text-[13px] px-4 py-2">
                                {{ loadingPreview ? 'Cargando...' : 'Vista Previa' }}
                            </button>
                        </div>

                        <!-- Preview de lo que se copiará -->
                        <div v-if="preview" class="space-y-4">
                            <!-- Advertencia si destino ya tiene datos -->
                            <div v-if="preview.destino_existente.plan_estudios > 0 || preview.destino_existente.secciones > 0"
                                class="bg-dorado-soft border border-dorado/30 text-[#5B3E0E] text-[12px] px-4 py-3 rounded-[4px]">
                                El año destino <strong>{{ copiaForm.codigo_ano_destino }}</strong> ya tiene:
                                <span v-if="preview.destino_existente.plan_estudios > 0">{{ preview.destino_existente.plan_estudios }} entradas en plan de estudios</span>
                                <span v-if="preview.destino_existente.secciones > 0">, {{ preview.destino_existente.secciones }} secciones</span>
                                <span v-if="preview.destino_existente.asignaciones > 0">, {{ preview.destino_existente.asignaciones }} asignaciones</span>.
                                Los registros existentes no se duplicarán.
                            </div>

                            <!-- Resumen Plan -->
                            <div v-if="copiaForm.copiar_plan && preview.plan_estudios.total > 0" class="border border-borde rounded-[4px] overflow-hidden">
                                <div class="bg-crema px-4 py-2 border-b border-borde">
                                    <span class="text-[12px] font-semibold text-tinta">Plan de Estudios — {{ preview.plan_estudios.total }} materias</span>
                                </div>
                                <table class="w-full text-[11px]">
                                    <thead><tr class="border-b border-borde bg-crema/50">
                                        <th class="text-left px-3 py-1.5 font-semibold text-tinta-soft">Materia</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Grado</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Horas</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Tipo</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-borde">
                                        <tr v-for="f in preview.plan_estudios.filas" :key="f.siglas_materia + f.codigo_grado" class="hover:bg-crema/30">
                                            <td class="px-3 py-1.5 text-tinta">{{ f.nombre_materia }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra">{{ f.codigo_grado }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra">{{ f.horas_semanales ?? '—' }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra">{{ f.tipo_evaluacion }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Resumen Secciones -->
                            <div v-if="copiaForm.copiar_secciones && preview.secciones.total > 0" class="border border-borde rounded-[4px] overflow-hidden">
                                <div class="bg-crema px-4 py-2 border-b border-borde">
                                    <span class="text-[12px] font-semibold text-tinta">Secciones — {{ preview.secciones.total }} secciones</span>
                                </div>
                                <table class="w-full text-[11px]">
                                    <thead><tr class="border-b border-borde bg-crema/50">
                                        <th class="text-left px-3 py-1.5 font-semibold text-tinta-soft">Origen</th>
                                        <th class="text-left px-3 py-1.5 font-semibold text-tinta-soft">→ Nuevo Código</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Grado</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Turno</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Cap.</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-borde">
                                        <tr v-for="f in preview.secciones.filas" :key="f.codigo_seccion" class="hover:bg-crema/30">
                                            <td class="px-3 py-1.5 text-piedra font-mono text-[10px]">{{ f.codigo_seccion }}</td>
                                            <td class="px-3 py-1.5 text-tinta font-mono text-[10px] font-semibold">{{ f.nuevo_codigo }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra">{{ f.nombre_grado }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra">{{ f.turno }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra">{{ f.capacidad_maxima }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Resumen Asignaciones -->
                            <div v-if="copiaForm.copiar_asignaciones && preview.asignaciones.total > 0" class="border border-borde rounded-[4px] overflow-hidden">
                                <div class="bg-dorado-soft px-4 py-2 border-b border-dorado/20">
                                    <span class="text-[12px] font-semibold text-[#5B3E0E]">Asignaciones Docentes — {{ preview.asignaciones.total }} sugerencias</span>
                                    <span class="text-[10px] text-[#7A5A0E] ml-2">(editables después de copiar)</span>
                                </div>
                                <table class="w-full text-[11px]">
                                    <thead><tr class="border-b border-borde bg-crema/50">
                                        <th class="text-left px-3 py-1.5 font-semibold text-tinta-soft">Docente</th>
                                        <th class="text-left px-3 py-1.5 font-semibold text-tinta-soft">Materia</th>
                                        <th class="px-2 py-1.5 font-semibold text-tinta-soft">Sección</th>
                                    </tr></thead>
                                    <tbody class="divide-y divide-borde">
                                        <tr v-for="(f, i) in preview.asignaciones.filas" :key="i" class="hover:bg-crema/30">
                                            <td class="px-3 py-1.5 text-tinta">{{ f.nombre_docente }}</td>
                                            <td class="px-3 py-1.5 text-piedra">{{ f.nombre_materia }}</td>
                                            <td class="px-2 py-1.5 text-center text-piedra font-mono text-[10px]">{{ f.nombre_grado }} {{ f.letra_seccion }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Footer con botón de confirmar -->
                    <div class="px-6 py-4 border-t border-borde flex justify-between items-center sticky bottom-0 bg-paper">
                        <button @click="modalCopia=false" class="btn-secondary">Cerrar</button>
                        <button v-if="preview && !copiaResultados" @click="ejecutarCopia" :disabled="copiando"
                            class="btn-primary text-[13px] px-5 py-2.5 flex items-center gap-2">
                            {{ copiando ? 'Copiando...' : 'Confirmar Copia' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.err           { @apply text-rojo text-[11px] mt-1; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
    .read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
