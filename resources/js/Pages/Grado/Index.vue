<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const lista = ref([]); const modal = ref(false); const editando = ref(false);
const saving = ref(false); const errors = ref({});
const form = reactive({ codigo_grado: '', nombre: '', nivel_educativo: 'Educación Media General', numero_ano: 1, estado: 'activo' });

async function cargar() { const { data } = await axios.get('/api/grados'); lista.value = data; }
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { codigo_grado: '', nombre: '', nivel_educativo: 'Educación Media General', numero_ano: 1, estado: 'activo' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/grados/${form.codigo_grado}`, form);
        else await axios.post('/api/grados', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
onMounted(cargar);
</script>
<template>
    <Head title="Grados — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">📊 Grados</h1><p class="text-xs text-slate-500">Niveles académicos de la institución</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow">＋ Nuevo</button>
            </div>
        </template>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="g in lista" :key="g.codigo_grado" class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center font-black text-indigo-700">{{ g.numero_ano }}°</div>
                    <span class="text-xs font-mono text-slate-400">{{ g.codigo_grado }}</span>
                </div>
                <p class="font-black text-slate-800">{{ g.nombre }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ g.nivel_educativo }}</p>
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-50">
                    <span :class="['text-[10px] font-black uppercase px-2 py-0.5 rounded-full', g.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500']">{{ g.estado }}</span>
                    <button @click="abrir(g)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button>
                </div>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nuevo' }} Grado</h2><button @click="modal=false">✕</button></div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando"><label class="lbl">Código *</label><input v-model="form.codigo_grado" type="text" class="inp" placeholder="Ej: 1ER" /></div>
                        <div><label class="lbl">Nombre *</label><input v-model="form.nombre" type="text" class="inp" placeholder="Ej: Primer Año" /></div>
                        <div><label class="lbl">Nivel Educativo *</label><input v-model="form.nivel_educativo" type="text" class="inp" /></div>
                        <div><label class="lbl">Número de Año *</label><input v-model="form.numero_ano" type="number" min="1" max="6" class="inp" /></div>
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
</style>
