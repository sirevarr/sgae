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
const form     = reactive({ cedula_personal: '', nombres: '', apellidos: '', cargo: '',
    telefono: '', correo: '', genero: '', fecha_nacimiento: '', fecha_ingreso: '',
    estado: 'activo', observaciones: '', especialidad: '', turno: '' });

async function cargar() {
    const { data } = await axios.get('/api/personal');
    lista.value = data.data ?? data;
}

function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { cedula_personal: '', nombres: '', apellidos: '', cargo: '',
        telefono: '', correo: '', genero: '', fecha_nacimiento: '', fecha_ingreso: '',
        estado: 'activo', observaciones: '', especialidad: '', turno: '' });
    errors.value = {};
    modal.value  = true;
}

async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/personal/${form.cedula_personal}`, form);
        else await axios.post('/api/personal', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally { saving.value = false; }
}
onMounted(cargar);
</script>
<template>
    <Head title="Personal — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">👤 Personal</h1>
                <p class="text-xs text-slate-500">Gestión del personal docente y administrativo</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">＋ Nuevo</button>
            </div>
        </template>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Cédula</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Cargo</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Especialidad</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Teléfono</th>
                        <th class="px-4 py-3 text-left text-xs font-black uppercase">Estado</th>
                        <th class="px-4 py-3 text-xs font-black uppercase">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!lista.length"><td colspan="7" class="text-center py-10 text-slate-400">Sin personal registrado.</td></tr>
                    <tr v-for="p in lista" :key="p.cedula_personal" class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-mono text-xs">{{ p.cedula_personal }}</td>
                        <td class="px-4 py-3 font-semibold">{{ p.apellidos }}, {{ p.nombres }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ p.cargo }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ p.docente?.especialidad ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ p.telefono ?? '—' }}</td>
                        <td class="px-4 py-3"><span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase', p.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500']">{{ p.estado }}</span></td>
                        <td class="px-4 py-3 text-center"><button @click="abrir(p)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nuevo' }} Personal</h2><button @click="modal=false" class="text-slate-400">✕</button></div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!editando" class="col-span-2"><label class="lbl">Cédula *</label><input v-model="form.cedula_personal" type="number" class="inp" /><p v-if="errors.cedula_personal" class="err">{{ errors.cedula_personal[0] }}</p></div>
                        <div><label class="lbl">Nombres *</label><input v-model="form.nombres" type="text" class="inp" /><p v-if="errors.nombres" class="err">{{ errors.nombres[0] }}</p></div>
                        <div><label class="lbl">Apellidos *</label><input v-model="form.apellidos" type="text" class="inp" /><p v-if="errors.apellidos" class="err">{{ errors.apellidos[0] }}</p></div>
                        <div><label class="lbl">Cargo *</label><input v-model="form.cargo" type="text" class="inp" /><p v-if="errors.cargo" class="err">{{ errors.cargo[0] }}</p></div>
                        <div><label class="lbl">Género</label><select v-model="form.genero" class="inp"><option value="">—</option><option value="M">Masculino</option><option value="F">Femenino</option></select></div>
                        <div><label class="lbl">Teléfono</label><input v-model="form.telefono" type="text" class="inp" /></div>
                        <div><label class="lbl">Correo</label><input v-model="form.correo" type="email" class="inp" /></div>
                        <div><label class="lbl">Especialidad (Docente)</label><input v-model="form.especialidad" type="text" class="inp" /></div>
                        <div><label class="lbl">Turno</label><select v-model="form.turno" class="inp"><option value="">—</option><option value="mañana">Mañana</option><option value="tarde">Tarde</option><option value="nocturno">Nocturno</option></select></div>
                        <div><label class="lbl">F. Ingreso</label><input v-model="form.fecha_ingreso" type="date" class="inp" /></div>
                        <div><label class="lbl">Estado</label><select v-model="form.estado" class="inp"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="jubilado">Jubilado</option></select></div>
                        <div class="col-span-2"><label class="lbl">Observaciones</label><textarea v-model="form.observaciones" rows="2" class="inp"></textarea></div>
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
