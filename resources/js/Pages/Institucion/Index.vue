<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

// ── Institución ──────────────────────────────────────────────────────
const inst    = ref(null);
const personal = ref([]);
const modal   = ref(false);
const saving  = ref(false);
const errors  = ref({});
const form    = reactive({
    codigo_dea: '', nombre: '', direccion: '', telefono: '',
    municipio: '', estado: '', zona_educativa: '',
    director_actual: '', coordinador_academico: '',
});

async function cargar() {
    try {
        const [i, p] = await Promise.all([
            axios.get('/api/institucion'),
            axios.get('/api/personal-lista'),
        ]);
        inst.value     = i.data;
        personal.value = p.data;
    } catch { inst.value = null; }
}

function abrir() {
    Object.assign(form, inst.value ? { ...inst.value } : {
        codigo_dea: '', nombre: '', direccion: '', telefono: '',
        municipio: '', estado: '', zona_educativa: '',
        director_actual: '', coordinador_academico: '',
    });
    errors.value = {};
    modal.value  = true;
}

async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (inst.value) await axios.put(`/api/institucion/${inst.value.codigo_dea}`, form);
        else await axios.post('/api/institucion', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}

onMounted(cargar);
</script>
<template>
    <Head title="Institución — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">🏫 Datos de la Institución</h1>
                <p class="text-xs text-slate-500">Información que aparece en los documentos y boletines</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">
                    {{ inst ? '✏️ Editar' : '＋ Configurar' }}
                </button>
            </div>
        </template>

        <!-- Tarjeta de institución -->
        <div v-if="inst" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 max-w-2xl">
            <div class="flex items-center gap-5 mb-6">
                <div class="w-16 h-16 bg-sky-100 rounded-2xl flex items-center justify-center text-3xl">🏫</div>
                <div>
                    <h2 class="font-black text-slate-800 text-xl">{{ inst.nombre }}</h2>
                    <p class="text-sky-600 font-mono text-sm">DEA: {{ inst.codigo_dea }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                <div><span class="text-[10px] font-black text-slate-400 uppercase">Municipio</span><p class="font-semibold text-slate-700">{{ inst.municipio ?? '—' }}</p></div>
                <div><span class="text-[10px] font-black text-slate-400 uppercase">Estado</span><p class="font-semibold text-slate-700">{{ inst.estado ?? '—' }}</p></div>
                <div><span class="text-[10px] font-black text-slate-400 uppercase">Zona Educativa</span><p class="font-semibold text-slate-700">{{ inst.zona_educativa ?? '—' }}</p></div>
                <div><span class="text-[10px] font-black text-slate-400 uppercase">Teléfono</span><p class="font-semibold text-slate-700">{{ inst.telefono ?? '—' }}</p></div>
                <div class="col-span-2"><span class="text-[10px] font-black text-slate-400 uppercase">Dirección</span><p class="font-semibold text-slate-700">{{ inst.direccion ?? '—' }}</p></div>
                <div class="border-t border-slate-100 col-span-2 pt-3 mt-1">
                    <span class="text-[10px] font-black text-slate-400 uppercase">Director(a)</span>
                    <p class="font-semibold text-slate-700">{{ inst.director ? `${inst.director.apellidos}, ${inst.director.nombres}` : '—' }}</p>
                </div>
                <div><span class="text-[10px] font-black text-slate-400 uppercase">Coordinador(a)</span>
                    <p class="font-semibold text-slate-700">{{ inst.coordinador ? `${inst.coordinador.apellidos}, ${inst.coordinador.nombres}` : '—' }}</p>
                </div>
            </div>
        </div>

        <div v-else class="bg-amber-50 border border-amber-200 rounded-2xl p-10 text-center max-w-lg mx-auto mt-8">
            <p class="text-3xl mb-4">⚠️</p>
            <p class="font-black text-amber-800 text-lg">Institución no configurada</p>
            <p class="text-amber-600 text-sm mt-2">Haz clic en "Configurar" para ingresar los datos de tu institución.<br>Esta información aparecerá en todos los documentos generados.</p>
        </div>

        <!-- MODAL -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">Datos de la Institución</h2><button @click="modal=false" class="text-slate-400">✕</button></div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!inst" class="col-span-2"><label class="lbl">Código DEA *</label><input v-model="form.codigo_dea" type="text" class="inp" /><p v-if="errors.codigo_dea" class="err">{{ errors.codigo_dea[0] }}</p></div>
                        <div class="col-span-2"><label class="lbl">Nombre *</label><input v-model="form.nombre" type="text" class="inp" /><p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p></div>
                        <div><label class="lbl">Municipio</label><input v-model="form.municipio" type="text" class="inp" /></div>
                        <div><label class="lbl">Estado</label><input v-model="form.estado" type="text" class="inp" placeholder="Ej: Miranda" /></div>
                        <div><label class="lbl">Zona Educativa</label><input v-model="form.zona_educativa" type="text" class="inp" /></div>
                        <div><label class="lbl">Teléfono</label><input v-model="form.telefono" type="text" class="inp" /></div>
                        <div class="col-span-2"><label class="lbl">Dirección</label><textarea v-model="form.direccion" rows="2" class="inp"></textarea></div>
                        <div><label class="lbl">Director(a) — Cédula</label>
                            <select v-model="form.director_actual" class="inp">
                                <option value="">—</option>
                                <option v-for="p in personal" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option>
                            </select>
                        </div>
                        <div><label class="lbl">Coordinador(a) — Cédula</label>
                            <select v-model="form.coordinador_academico" class="inp">
                                <option value="">—</option>
                                <option v-for="p in personal" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option>
                            </select>
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
.inp { @apply w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 mt-1; }
.err { @apply text-red-500 text-xs mt-1; }
</style>
