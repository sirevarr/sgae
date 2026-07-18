<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const lista    = ref([]);
const modal    = ref(false);
const editando = ref(false);
const saving   = ref(false);
const errors   = ref({});
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
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div>
                    <h1 class="text-xl font-black text-slate-800">📋 Plan de Estudios</h1>
                    <p class="text-xs text-slate-500">Materias por grado, mención y año escolar</p>
                </div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow">＋ Agregar Materia</button>
            </div>
        </template>

        <!-- Filtros -->
        <div class="bg-white rounded-2xl border border-slate-100 p-5 mb-5 shadow-sm">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Año Escolar</label>
                    <select v-model="filtro.codigo_ano_escolar" @change="cargar" class="inp mt-1">
                        <option value="">Todos</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Grado</label>
                    <select v-model="filtro.codigo_grado" @change="cargar" class="inp mt-1">
                        <option value="">Todos</option>
                        <option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Mención</label>
                    <select v-model="filtro.id_mencion" @change="cargar" class="inp mt-1">
                        <option value="">Todas</option>
                        <option v-for="m in menciones" :key="m.id_mencion" :value="m.id_mencion">{{ m.nombre }}</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button @click="cargar" class="w-full bg-slate-700 hover:bg-slate-800 text-white text-sm font-bold py-2 rounded-xl transition">🔍 Filtrar</button>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Materia</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Siglas</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Grado</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Mención</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Año Esc.</th>
                        <th class="px-4 py-3 text-center text-xs font-black uppercase">Hrs/Sem</th>
                        <th class="px-4 py-3 text-center text-xs font-black uppercase">Oblig.</th>
                        <th class="px-4 py-3 text-center text-xs font-black uppercase">Repara</th>
                        <th class="px-4 py-3 text-center text-xs font-black uppercase">Tipo</th>
                        <th class="px-4 py-3 text-xs font-black uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!lista.length">
                        <td colspan="10" class="text-center py-12 text-slate-400">
                            <div class="text-4xl mb-3">📋</div>
                            <p>No hay materias en el plan. Selecciona filtros o agrega una nueva.</p>
                        </td>
                    </tr>
                    <tr v-for="pe in lista" :key="`${pe.siglas_materia}-${pe.id_mencion}-${pe.codigo_grado}-${pe.codigo_ano_escolar}`"
                        class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ pe.materia?.nombre ?? pe.siglas_materia }}</td>
                        <td class="px-4 py-3 font-mono text-sky-700 text-xs font-bold">{{ pe.siglas_materia }}</td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ pe.grado?.nombre }}</td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ pe.mencion?.nombre }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ pe.codigo_ano_escolar }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ pe.horas_semanales ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="pe.obligatoria ? 'text-emerald-600' : 'text-slate-400'">{{ pe.obligatoria ? '✓' : '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="pe.se_repara ? 'text-amber-600' : 'text-slate-400'">{{ pe.se_repara ? '✓' : '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="['px-2 py-0.5 rounded text-[10px] font-black', pe.tipo_evaluacion === 'N' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700']">
                                {{ pe.tipo_evaluacion === 'N' ? 'Numérica' : 'Literal' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 flex gap-2 justify-center">
                            <button @click="abrir(pe)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button>
                            <button @click="eliminar(pe)" class="text-red-500 text-xs font-bold hover:underline">Quitar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex justify-between">
                        <h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Agregar' }} Materia al Plan</h2>
                        <button @click="modal=false" class="text-slate-400">✕</button>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="lbl">Materia *</label>
                            <select v-model="form.siglas_materia" class="inp" :disabled="editando">
                                <option value="">Seleccionar…</option>
                                <option v-for="m in materias" :key="m.siglas" :value="m.siglas">{{ m.nombre }} ({{ m.siglas }})</option>
                            </select>
                            <p v-if="errors.siglas_materia" class="err">{{ errors.siglas_materia[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Grado *</label>
                            <select v-model="form.codigo_grado" class="inp" :disabled="editando">
                                <option value="">—</option>
                                <option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Mención *</label>
                            <select v-model="form.id_mencion" class="inp" :disabled="editando">
                                <option value="">—</option>
                                <option v-for="m in menciones" :key="m.id_mencion" :value="m.id_mencion">{{ m.nombre }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Año Escolar *</label>
                            <select v-model="form.codigo_ano_escolar" class="inp" :disabled="editando">
                                <option value="">—</option>
                                <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Horas Semanales</label>
                            <input v-model="form.horas_semanales" type="number" min="1" class="inp" />
                        </div>
                        <div>
                            <label class="lbl">Tipo Evaluación</label>
                            <select v-model="form.tipo_evaluacion" class="inp">
                                <option value="N">Numérica (0–20)</option>
                                <option value="L">Literal (A/B/C)</option>
                            </select>
                        </div>
                        <div>
                            <label class="lbl">Créditos</label>
                            <input v-model="form.creditos" type="number" class="inp" />
                        </div>
                        <div class="flex items-center gap-4 col-span-2 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" v-model="form.obligatoria" class="rounded" />
                                <span class="text-sm font-semibold text-slate-700">Obligatoria</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" v-model="form.se_repara" class="rounded" />
                                <span class="text-sm font-semibold text-slate-700">Se puede reparar</span>
                            </label>
                        </div>
                    </div>
                    <div class="p-5 border-t flex justify-end gap-3">
                        <button @click="modal=false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                        <button @click="guardar" :disabled="saving" class="px-5 py-2 bg-sky-600 text-white text-sm font-bold rounded-xl disabled:opacity-50">{{ saving ? '…' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.lbl { @apply text-xs font-bold text-slate-600 uppercase; }
.inp { @apply w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400; }
.err { @apply text-red-500 text-xs mt-1; }
</style>
