<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const lista = ref([]); const anios = ref([]); const saving = ref(false); const errors = ref({});
const modal = ref(false); const editando = ref(false);
const filtroAnio = ref('');
const form = reactive({ numero_momento: 1, codigo_ano_escolar: '', nombre: '', fecha_inicio: '', fecha_fin: '', porcentaje: '', estado: 'por_iniciar' });

async function cargar() {
    const { data } = await axios.get('/api/momentos', { params: { codigo_ano_escolar: filtroAnio.value || undefined } });
    lista.value = data;
}
async function cargarAnios() { const { data } = await axios.get('/api/anios-escolares'); anios.value = data; }
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { numero_momento: 1, codigo_ano_escolar: filtroAnio.value || '', nombre: '', fecha_inicio: '', fecha_fin: '', porcentaje: '', estado: 'por_iniciar' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put('/api/momentos', form);
        else await axios.post('/api/momentos', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
onMounted(async () => { await cargarAnios(); cargar(); });
</script>
<template>
    <Head title="Momentos Evaluativos — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div><h1 class="text-xl font-black text-slate-800">⏱️ Momentos Evaluativos</h1><p class="text-xs text-slate-500">Períodos de evaluación del año escolar</p></div>
                <div class="flex gap-3">
                    <select v-model="filtroAnio" @change="cargar" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-sky-400">
                        <option value="">Todos los años</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                    <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow">＋ Nuevo</button>
                </div>
            </div>
        </template>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="m in lista" :key="`${m.numero_momento}-${m.codigo_ano_escolar}`"
                :class="['bg-white rounded-2xl border-l-4 p-5 shadow-sm transition hover:shadow-md',
                    m.estado === 'activo' ? 'border-emerald-500' : m.estado === 'por_iniciar' ? 'border-amber-400' : 'border-slate-300']">
                <div class="flex justify-between items-start mb-2">
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg',
                        m.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : m.estado === 'por_iniciar' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500']">
                        {{ m.numero_momento }}°
                    </div>
                    <span :class="['text-[10px] font-black px-2 py-0.5 rounded-full uppercase',
                        m.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : m.estado === 'por_iniciar' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500']">
                        {{ m.estado }}
                    </span>
                </div>
                <p class="font-black text-slate-800">{{ m.nombre }}</p>
                <p class="text-xs text-slate-500 font-mono">{{ m.codigo_ano_escolar }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ m.fecha_inicio ?? '—' }} → {{ m.fecha_fin ?? '—' }}</p>
                <p v-if="m.porcentaje" class="text-xs text-sky-600 font-semibold mt-1">{{ m.porcentaje }}% de la nota</p>
                <button @click="abrir(m)" class="mt-3 text-sky-600 text-xs font-bold hover:underline">Editar</button>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nuevo' }} Momento</h2><button @click="modal=false">✕</button></div>
                    <div class="p-5 space-y-4">
                        <div><label class="lbl">Año Escolar *</label><select v-model="form.codigo_ano_escolar" class="inp"><option value="">—</option><option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option></select></div>
                        <div><label class="lbl">Momento *</label><select v-model="form.numero_momento" class="inp"><option :value="1">1° Momento</option><option :value="2">2° Momento</option><option :value="3">3° Momento</option></select></div>
                        <div><label class="lbl">Nombre *</label><input v-model="form.nombre" type="text" class="inp" placeholder="Ej: Primer Momento" /></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="lbl">Fecha Inicio</label><input v-model="form.fecha_inicio" type="date" class="inp" /></div>
                            <div><label class="lbl">Fecha Fin</label><input v-model="form.fecha_fin" type="date" class="inp" /></div>
                        </div>
                        <div><label class="lbl">Porcentaje (%)</label><input v-model="form.porcentaje" type="number" min="0" max="100" class="inp" /></div>
                        <div><label class="lbl">Estado *</label><select v-model="form.estado" class="inp"><option value="por_iniciar">Por iniciar</option><option value="activo">Activo</option><option value="finalizado">Finalizado</option></select></div>
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
</style>
