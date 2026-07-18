<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const lista = ref([]); const modal = ref(false); const editando = ref(false);
const saving = ref(false); const errors = ref({});
const form = reactive({ codigo_ano_escolar: '', fecha_inicio: '', fecha_fin: '', estado: 'planificado' });

async function cargar() { const { data } = await axios.get('/api/anios-escolares'); lista.value = data; }
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { codigo_ano_escolar: '', fecha_inicio: '', fecha_fin: '', estado: 'planificado' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/anios-escolares/${form.codigo_ano_escolar}`, form);
        else await axios.post('/api/anios-escolares', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally { saving.value = false; }
}
onMounted(cargar);
</script>
<template>
    <Head title="Años Escolares — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">📅 Años Escolares</h1><p class="text-xs text-slate-500">Gestión de períodos académicos</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">＋ Nuevo</button>
            </div>
        </template>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="a in lista" :key="a.codigo_ano_escolar"
                :class="['bg-white rounded-2xl border-l-4 p-5 shadow-sm transition hover:shadow-md',
                    a.estado === 'vigente' ? 'border-emerald-500' : a.estado === 'planificado' ? 'border-amber-400' : 'border-slate-300']">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-black text-slate-800 text-lg">{{ a.codigo_ano_escolar }}</h3>
                    <span :class="['px-2 py-1 rounded-full text-[10px] font-black uppercase',
                        a.estado === 'vigente' ? 'bg-emerald-100 text-emerald-700' :
                        a.estado === 'planificado' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500']">
                        {{ a.estado }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">Inicio: {{ a.fecha_inicio ?? '—' }}</p>
                <p class="text-xs text-slate-500 mb-4">Fin: {{ a.fecha_fin ?? '—' }}</p>
                <button @click="abrir(a)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nuevo' }} Año Escolar</h2><button @click="modal=false" class="text-slate-400">✕</button></div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando"><label class="lbl">Código *</label><input v-model="form.codigo_ano_escolar" type="text" class="inp" placeholder="Ej: 2025-2026" /><p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p></div>
                        <div><label class="lbl">Fecha Inicio</label><input v-model="form.fecha_inicio" type="date" class="inp" /></div>
                        <div><label class="lbl">Fecha Fin</label><input v-model="form.fecha_fin" type="date" class="inp" /></div>
                        <div><label class="lbl">Estado *</label>
                            <select v-model="form.estado" class="inp">
                                <option value="planificado">Planificado</option>
                                <option value="vigente">Vigente (activo)</option>
                                <option value="finalizado">Finalizado</option>
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
