<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
const lista = ref([]); const modal = ref(false); const editando = ref(false);
const saving = ref(false); const errors = ref({}); const buscar = ref(''); const pagination = ref({});
const form = reactive({ cedula_representante: '', nacionalidad: 'V', nombres: '', apellidos: '', parentesco: '', ocupacion: '', direccion: '', telefono: '', correo: '', es_representante_legal: true });
async function cargar(page = 1) {
    const { data } = await axios.get('/api/representantes', { params: { buscar: buscar.value, page } });
    lista.value = data.data ?? data; pagination.value = data;
}
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { cedula_representante: '', nacionalidad: 'V', nombres: '', apellidos: '', parentesco: '', ocupacion: '', direccion: '', telefono: '', correo: '', es_representante_legal: true });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/representantes/${form.cedula_representante}`, form);
        else await axios.post('/api/representantes', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
let timer; function onBuscar() { clearTimeout(timer); timer = setTimeout(() => cargar(), 400); }
onMounted(cargar);
</script>
<template>
    <Head title="Representantes — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">👨‍👩‍👦 Representantes</h1><p class="text-xs text-slate-500">Responsables legales de los estudiantes</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow">＋ Nuevo</button>
            </div>
        </template>
        <div class="mb-4"><input v-model="buscar" @input="onBuscar" type="text" placeholder="Buscar representante…" class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white shadow-sm w-full max-w-md" /></div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-white"><tr>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Cédula</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Nombre</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Parentesco</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Teléfono</th>
                    <th class="px-4 py-3 text-xs font-black uppercase">Acción</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!lista.length"><td colspan="5" class="text-center py-10 text-slate-400">Sin representantes.</td></tr>
                    <tr v-for="r in lista" :key="r.cedula_representante" class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-mono text-xs">{{ r.nacionalidad }}-{{ r.cedula_representante }}</td>
                        <td class="px-4 py-3 font-semibold">{{ r.apellidos }}, {{ r.nombres }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ r.parentesco }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ r.telefono ?? '—' }}</td>
                        <td class="px-4 py-3 text-center"><button @click="abrir(r)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nuevo' }} Representante</h2><button @click="modal=false">✕</button></div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!editando" class="col-span-2 grid grid-cols-3 gap-3">
                            <div><label class="lbl">Nac.</label><select v-model="form.nacionalidad" class="inp"><option value="V">V</option><option value="E">E</option></select></div>
                            <div class="col-span-2"><label class="lbl">N° Cédula *</label><input v-model="form.cedula_representante" type="number" class="inp" /></div>
                        </div>
                        <div><label class="lbl">Nombres *</label><input v-model="form.nombres" type="text" class="inp" /><p v-if="errors.nombres" class="err">{{ errors.nombres[0] }}</p></div>
                        <div><label class="lbl">Apellidos *</label><input v-model="form.apellidos" type="text" class="inp" /><p v-if="errors.apellidos" class="err">{{ errors.apellidos[0] }}</p></div>
                        <div><label class="lbl">Parentesco *</label><input v-model="form.parentesco" type="text" class="inp" placeholder="Madre, Padre, Tutor…" /></div>
                        <div><label class="lbl">Ocupación</label><input v-model="form.ocupacion" type="text" class="inp" /></div>
                        <div><label class="lbl">Teléfono</label><input v-model="form.telefono" type="text" class="inp" /></div>
                        <div><label class="lbl">Correo</label><input v-model="form.correo" type="email" class="inp" /></div>
                        <div class="col-span-2"><label class="lbl">Dirección</label><input v-model="form.direccion" type="text" class="inp" /></div>
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
