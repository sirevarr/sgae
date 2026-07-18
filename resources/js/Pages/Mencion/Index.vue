<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
const lista = ref([]); const modal = ref(false); const editando = ref(false);
const saving = ref(false); const errors = ref({});
const form = reactive({ id_mencion: null, nombre: '', estado: 'activo' });
async function cargar() { const { data } = await axios.get('/api/menciones'); lista.value = data; }
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { id_mencion: null, nombre: '', estado: 'activo' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/menciones/${form.id_mencion}`, form);
        else await axios.post('/api/menciones', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
onMounted(cargar);
</script>
<template>
    <Head title="Menciones — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">🎓 Menciones</h1><p class="text-xs text-slate-500">Especialidades académicas disponibles</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow">＋ Nueva</button>
            </div>
        </template>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div v-for="m in lista" :key="m.id_mencion" class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition text-center group">
                <div class="text-3xl mb-2">🎓</div>
                <p class="font-black text-slate-800">{{ m.nombre }}</p>
                <p class="text-xs text-slate-400 mt-1"># {{ m.id_mencion }}</p>
                <button @click="abrir(m)" class="mt-3 text-sky-600 text-xs font-bold hover:underline opacity-0 group-hover:opacity-100 transition">Editar</button>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nueva' }} Mención</h2><button @click="modal=false">✕</button></div>
                    <div class="p-5 space-y-4">
                        <div><label class="lbl">Nombre *</label><input v-model="form.nombre" type="text" class="inp" /><p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p></div>
                        <div><label class="lbl">Estado</label><select v-model="form.estado" class="inp"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
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
