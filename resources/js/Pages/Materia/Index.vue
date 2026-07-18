<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const lista = ref([]); const modal = ref(false); const editando = ref(false);
const saving = ref(false); const errors = ref({});
const form = reactive({ siglas: '', nombre: '', area_formacion: '' });

async function cargar() { const { data } = await axios.get('/api/materias'); lista.value = data; }
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { siglas: '', nombre: '', area_formacion: '' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/materias/${form.siglas}`, form);
        else await axios.post('/api/materias', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
onMounted(cargar);
</script>
<template>
    <Head title="Materias — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">📚 Materias</h1><p class="text-xs text-slate-500">Catálogo de áreas de formación académica</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">＋ Nueva</button>
            </div>
        </template>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-white"><tr>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Siglas</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Área de Formación</th>
                    <th class="px-4 py-3 text-xs font-black uppercase">Acción</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!lista.length"><td colspan="4" class="text-center py-10 text-slate-400">Sin materias.</td></tr>
                    <tr v-for="m in lista" :key="m.siglas" class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-mono font-bold text-sky-700">{{ m.siglas }}</td>
                        <td class="px-4 py-3 font-semibold">{{ m.nombre }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ m.area_formacion ?? '—' }}</td>
                        <td class="px-4 py-3 text-center"><button @click="abrir(m)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nueva' }} Materia</h2><button @click="modal=false" class="text-slate-400">✕</button></div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando"><label class="lbl">Siglas *</label><input v-model="form.siglas" type="text" class="inp" placeholder="Ej: MAT" /><p v-if="errors.siglas" class="err">{{ errors.siglas[0] }}</p></div>
                        <div><label class="lbl">Nombre *</label><input v-model="form.nombre" type="text" class="inp" /><p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p></div>
                        <div><label class="lbl">Área de Formación</label><input v-model="form.area_formacion" type="text" class="inp" /></div>
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
