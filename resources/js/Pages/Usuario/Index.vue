<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const lista = ref([]); const personal = ref([]);
const modal = ref(false); const editando = ref(false); const saving = ref(false); const errors = ref({});
const modalReset = ref(false); const selectedId = ref(null); const newPass = ref('');
const form = reactive({ id_usuario: null, codigo_usuario: '', cedula_personal: '', rol: 'docente', password: '', estado: 'activo' });

async function cargar() {
    const [u, p] = await Promise.all([axios.get('/api/usuarios'), axios.get('/api/personal-lista')]);
    lista.value = u.data; personal.value = p.data;
}
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ? { ...item, password: '' } : { id_usuario: null, codigo_usuario: '', cedula_personal: '', rol: 'docente', password: '', estado: 'activo' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/usuarios/${form.id_usuario}`, form);
        else await axios.post('/api/usuarios', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
async function resetPass() {
    if (!newPass.value || newPass.value.length < 8) { alert('La contraseña debe tener al menos 8 caracteres.'); return; }
    saving.value = true;
    try {
        await axios.post(`/api/usuarios/${selectedId.value}/reset-password`, { password: newPass.value });
        alert('Contraseña restablecida.'); modalReset.value = false; newPass.value = '';
    } catch (e) { alert(e.response?.data?.message ?? e.message); }
    finally { saving.value = false; }
}
onMounted(cargar);
</script>
<template>
    <Head title="Usuarios — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div><h1 class="text-xl font-black text-slate-800">🔑 Usuarios del Sistema</h1><p class="text-xs text-slate-500">Gestión de cuentas y roles de acceso</p></div>
                <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow">＋ Nuevo</button>
            </div>
        </template>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-800 text-white"><tr>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Personal</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Rol</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-black uppercase">Último Acceso</th>
                    <th class="px-4 py-3 text-xs font-black uppercase">Acciones</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="!lista.length"><td colspan="6" class="text-center py-10 text-slate-400">Sin usuarios.</td></tr>
                    <tr v-for="u in lista" :key="u.id_usuario" class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-mono font-bold text-slate-700">{{ u.codigo_usuario }}</td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ u.personal ? `${u.personal.apellidos}, ${u.personal.nombres}` : '—' }}</td>
                        <td class="px-4 py-3">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase',
                                u.rol === 'administrador' ? 'bg-purple-100 text-purple-700' :
                                u.rol === 'control_estudios' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600']">
                                {{ u.rol }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase',
                                u.estado === 'activo' ? 'bg-emerald-100 text-emerald-700' :
                                u.estado === 'bloqueado' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-500']">
                                {{ u.estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">{{ u.ultimo_acceso ?? '—' }}</td>
                        <td class="px-4 py-3 flex gap-2 justify-center">
                            <button @click="abrir(u)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button>
                            <button @click="() => { selectedId = u.id_usuario; newPass = ''; modalReset = true; }" class="text-amber-600 text-xs font-bold hover:underline">Reset</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal nuevo/editar -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nuevo' }} Usuario</h2><button @click="modal=false">✕</button></div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando"><label class="lbl">Código de Usuario *</label><input v-model="form.codigo_usuario" type="text" class="inp" /><p v-if="errors.codigo_usuario" class="err">{{ errors.codigo_usuario[0] }}</p></div>
                        <div v-if="!editando"><label class="lbl">Personal *</label>
                            <select v-model="form.cedula_personal" class="inp">
                                <option value="">—</option>
                                <option v-for="p in personal" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option>
                            </select>
                        </div>
                        <div><label class="lbl">Rol *</label><select v-model="form.rol" class="inp"><option value="administrador">Administrador</option><option value="control_estudios">Control de Estudios</option><option value="docente">Docente</option></select></div>
                        <div v-if="!editando"><label class="lbl">Contraseña *</label><input v-model="form.password" type="password" class="inp" /><p v-if="errors.password" class="err">{{ errors.password[0] }}</p></div>
                        <div v-if="editando"><label class="lbl">Estado</label><select v-model="form.estado" class="inp"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="bloqueado">Bloqueado</option></select></div>
                    </div>
                    <div class="p-5 border-t flex justify-end gap-3">
                        <button @click="modal=false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                        <button @click="guardar" :disabled="saving" class="px-5 py-2 bg-sky-600 text-white text-sm font-bold rounded-xl disabled:opacity-50">{{ saving ? '…' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
            <!-- Modal reset contraseña -->
            <div v-if="modalReset" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">Restablecer Contraseña</h2><button @click="modalReset=false">✕</button></div>
                    <div class="p-5 space-y-3">
                        <label class="lbl">Nueva Contraseña (mín. 8 caracteres)</label>
                        <input v-model="newPass" type="password" class="inp" />
                    </div>
                    <div class="p-5 border-t flex justify-end gap-3">
                        <button @click="modalReset=false" class="px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl">Cancelar</button>
                        <button @click="resetPass" :disabled="saving" class="px-5 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl disabled:opacity-50">Restablecer</button>
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
