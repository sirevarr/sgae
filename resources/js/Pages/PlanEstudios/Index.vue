<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

const lista    = ref([]);
const modal    = ref(false);
const editando = ref(false);
const saving   = ref(false);
const errors   = ref({});
const viewing  = ref(false);
const anios    = ref([]);
const grados   = ref([]);
const menciones = ref([]);
const materias  = ref([]);

const filtro = reactive({ codigo_ano_escolar: '', codigo_grado: '', id_mencion: '' });
const form   = reactive({
    siglas_materia: '', id_mencion: '', codigo_grado: '', codigo_ano_escolar: '',
    horas_semanales: '', obligatoria: true, tipo_evaluacion: 'N', se_repara: true, creditos: '', estado: 'activo',
});

async function cargar() {
    const { data } = await axios.get('/api/plan-estudios', { params: filtro });
    lista.value = data;
}

async function cargarCatalogos() {
    const [a, g, m, mat] = await Promise.all([
        axios.get('/api/anios-escolares'),
        axios.get('/api/grados'),
        axios.get('/api/menciones'),
        axios.get('/api/materias'),
    ]);
    anios.value    = a.data;
    grados.value   = g.data;
    menciones.value = m.data;
    materias.value = mat.data;
}

function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? {
        siglas_materia: '', id_mencion: filtro.id_mencion || '',
        codigo_grado: filtro.codigo_grado || '', codigo_ano_escolar: filtro.codigo_ano_escolar || '',
        horas_semanales: '', obligatoria: true, tipo_evaluacion: 'N', se_repara: true, creditos: '', estado: 'activo',
    });
    errors.value = {};
    modal.value  = true;
}

async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put('/api/plan-estudios', form);
        else await axios.post('/api/plan-estudios', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar ${item.materia?.nombre ?? item.siglas_materia} del plan?`)) return;
    try {
        await axios.delete('/api/plan-estudios', { data: {
            siglas_materia: item.siglas_materia, id_mencion: item.id_mencion,
            codigo_grado: item.codigo_grado, codigo_ano_escolar: item.codigo_ano_escolar,
        }});
        cargar();
    } catch (e) { alert(e.response?.data?.message ?? e.message); }
}

onMounted(async () => { await cargarCatalogos(); cargar(); });
</script>

<template>
    <Head title="Plan de Estudios — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 flex-wrap w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Plan de Estudios</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Materias por grado, mención y año escolar</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary">Agregar Materia</button>
                <button v-else @click="viewing = true; modal = true" class="btn-primary">Ver</button>
            </div>
        </template>

        <!-- Filtros -->
        <div class="bg-paper border border-borde rounded-[6px] p-5 mb-5">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="lbl">Año Escolar</label>
                    <select v-model="filtro.codigo_ano_escolar" @change="cargar" class="inp mt-1">
                        <option value="">Todos</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                </div>
                <div>
                    <label class="lbl">Grado</label>
                    <select v-model="filtro.codigo_grado" @change="cargar" class="inp mt-1">
                        <option value="">Todos</option>
                        <option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option>
                    </select>
                </div>
                <div>
                    <label class="lbl">Mención</label>
                    <select v-model="filtro.id_mencion" @change="cargar" class="inp mt-1">
                        <option value="">Todas</option>
                        <option v-for="m in menciones" :key="m.id_mencion" :value="m.id_mencion">{{ m.nombre }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button @click="cargar" class="w-full btn-secondary">Filtrar</button>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-paper border border-borde rounded-[6px] overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Materia</th>
                        <th class="th">Siglas</th>
                        <th class="th">Grado</th>
                        <th class="th">Mención</th>
                        <th class="th">Año Esc.</th>
                        <th class="th text-center">Hrs/Sem</th>
                        <th class="th text-center">Oblig.</th>
                        <th class="th text-center">Repara</th>
                        <th class="th text-center">Tipo</th>
                        <th class="th text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!lista.length">
                        <td colspan="10" class="text-center py-12 text-piedra text-[13px]">
                            No hay materias en el plan. Selecciona filtros o agrega una nueva.
                        </td>
                    </tr>
                    <tr v-for="pe in lista" :key="`${pe.siglas_materia}-${pe.id_mencion}-${pe.codigo_grado}-${pe.codigo_ano_escolar}`"
                        class="hover:bg-crema transition-colors">
                        <td class="td font-semibold text-tinta text-[12.5px]">{{ pe.materia?.nombre ?? pe.siglas_materia }}</td>
                        <td class="td font-mono font-semibold text-tinta text-[12px]">{{ pe.siglas_materia }}</td>
                        <td class="td text-piedra text-[12px]">{{ pe.grado?.nombre }}</td>
                        <td class="td text-piedra text-[12px]">{{ pe.mencion?.nombre }}</td>
                        <td class="td font-mono text-[12px] text-piedra">{{ pe.codigo_ano_escolar }}</td>
                        <td class="td text-center text-piedra text-[12px]">{{ pe.horas_semanales ?? '—' }}</td>
                        <td class="td text-center text-[12px]">
                            <span :class="pe.obligatoria ? 'text-ok font-semibold' : 'text-piedra-soft'">{{ pe.obligatoria ? 'Sí' : 'No' }}</span>
                        </td>
                        <td class="td text-center text-[12px]">
                            <span :class="pe.se_repara ? 'text-alerta font-semibold' : 'text-piedra-soft'">{{ pe.se_repara ? 'Sí' : 'No' }}</span>
                        </td>
                        <td class="td text-center">
                            <span class="badge badge-neutral">
                                {{ pe.tipo_evaluacion === 'N' ? 'Numérica' : 'Literal' }}
                            </span>
                        </td>
                        <td class="td text-center">
                            <div class="flex justify-center gap-2">
                                <button v-if="canManageRecords" @click="abrir(pe)" class="btn-table-action">Editar</button>
                                <button v-if="canManageRecords" @click="eliminar(pe)" class="btn-table-action text-rojo hover:text-rojo-dark">Quitar</button>
                                <button v-else @click="viewing = true; editando = false; Object.assign(form, pe); modal = true" class="btn-table-action">Ver</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar' : 'Agregar' }} Materia al Plan</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="lbl">Materia *</label>
                            <select v-model="form.siglas_materia" class="inp mt-1" :disabled="editando">
                                <option value="">Seleccionar...</option>
                                <option v-for="m in materias" :key="m.siglas" :value="m.siglas">{{ m.nombre }} ({{ m.siglas }})</option>
                            </select>
                            <p v-if="errors.siglas_materia" class="err">{{ errors.siglas_materia[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Grado *</label>
                            <select v-model="form.codigo_grado" class="inp mt-1" :disabled="editando">
                                <option value="">—</option>
                                <option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option>
                            </select>
                            <p v-if="errors.codigo_grado" class="err">{{ errors.codigo_grado[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Mención *</label>
                            <select v-model="form.id_mencion" class="inp mt-1" :disabled="editando">
                                <option value="">—</option>
                                <option v-for="m in menciones" :key="m.id_mencion" :value="m.id_mencion">{{ m.nombre }}</option>
                            </select>
                            <p v-if="errors.id_mencion" class="err">{{ errors.id_mencion[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" class="inp mt-1" :disabled="editando">
                                <option value="">—</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                            </select>
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Horas Semanales</label>
                            <input v-model="form.horas_semanales" type="number" min="1" class="inp mt-1" />
                            <p v-if="errors.horas_semanales" class="err">{{ errors.horas_semanales[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Tipo Evaluación</label>
                            <select v-model="form.tipo_evaluacion" class="inp mt-1">
                                <option value="N">Numérica (0–20)</option>
                                <option value="L">Literal (A/B/C)</option>
                            </select>
                            <p v-if="errors.tipo_evaluacion" class="err">{{ errors.tipo_evaluacion[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Créditos</label>
                            <input v-model="form.creditos" type="number" class="inp mt-1" />
                            <p v-if="errors.creditos" class="err">{{ errors.creditos[0] }}</p>
                        </div>
                        <div class="flex items-center gap-4 col-span-2 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" v-model="form.obligatoria" class="rounded-[2px] border-borde text-rojo focus:ring-rojo" />
                                <span class="text-[12px] font-semibold text-tinta-soft">Obligatoria</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" v-model="form.se_repara" class="rounded-[2px] border-borde text-rojo focus:ring-rojo" />
                                <span class="text-[12px] font-semibold text-tinta-soft">Se puede reparar</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cerrar</button>
                        <button v-if="canManageRecords" @click="guardar" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors; }
.err           { @apply text-rojo text-[11px] mt-1; }
.th            { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td            { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
</style>
