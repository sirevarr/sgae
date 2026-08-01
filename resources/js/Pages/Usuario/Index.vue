<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol || 'docente').toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));
const lista = ref([]);
const personal = ref([]);
const modal = ref(false);
const editando = ref(false);
const viewing = ref(false);
const saving = ref(false);
const errors = ref({});
const modalReset = ref(false);
const selectedId = ref(null);
const newPass = ref('');
const successMsg = ref('');
const form = reactive({
    id_usuario: null,
    codigo_usuario: '',
    cedula_personal: '',
    rol: 'docente',
    password: '',
    estado: 'activo'
});

async function cargar() {
    const [u, p] = await Promise.all([axios.get('/api/usuarios'), axios.get('/api/personal-lista')]);
    lista.value = u.data;
    personal.value = p.data;
}

function abrir(item = null, viewOnly = false) {
    editando.value = !!item && !viewOnly;
    viewing.value = viewOnly;
    Object.assign(form, item ? { ...item, password: '' } : { id_usuario: null, codigo_usuario: '', cedula_personal: '', rol: 'docente', password: '', estado: 'activo' });
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) {
            await axios.put(`/api/usuarios/${form.id_usuario}`, form);
        } else {
            await axios.post('/api/usuarios', form);
        }
        modal.value = false;
        successMsg.value = editando.value ? 'Usuario actualizado.' : 'Usuario creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {};
        } else {
            alert(e.response?.data?.message ?? e.message);
        }
    } finally {
        saving.value = false;
    }
}

async function resetPass() {
    if (!newPass.value || newPass.value.length < 8) {
        alert('La contraseña debe tener al menos 8 caracteres.');
        return;
    }
    saving.value = true;
    try {
        await axios.post(`/api/usuarios/${selectedId.value}/reset-password`, { password: newPass.value });
        alert('Contraseña restablecida.');
        modalReset.value = false;
        newPass.value = '';
    } catch (e) {
        alert(e.response?.data?.message ?? e.message);
    } finally {
        saving.value = false;
    }
}

async function eliminar(item) {
    if (!confirm(`¿Desactivar/Eliminar al usuario "${item.codigo_usuario}"?`)) return;
    try {
        await axios.delete(`/api/usuarios/${item.id_usuario}`);
        successMsg.value = 'Usuario desactivado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    }
}

onMounted(cargar);
</script>

<template>
    <Head title="Usuarios — SGAE" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center gap-8 w-full">
                <div class="pr-8">
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Usuarios del Sistema</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Gestión de cuentas y roles de acceso</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary ml-6">Nuevo Usuario</button>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="bg-paper border border-borde rounded-[6px] overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Usuario</th>
                        <th class="th">Personal</th>
                        <th class="th">Rol</th>
                        <th class="th">Estado</th>
                        <th class="th">Último Acceso</th>
                        <th class="th text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!lista.length">
                        <td colspan="6" class="text-center py-10 text-piedra text-[13px]">Sin usuarios.</td>
                    </tr>
                    <tr v-for="u in lista" :key="u.id_usuario" class="hover:bg-crema transition-colors">
                        <td class="td font-mono font-semibold text-tinta text-[12.5px]">{{ u.codigo_usuario }}</td>
                        <td class="td text-piedra text-[12px]">{{ u.personal ? `${u.personal.apellidos}, ${u.personal.nombres}` : '—' }}</td>
                        <td class="td">
                            <span class="badge badge-neutral">
                                {{ u.rol }}
                            </span>
                        </td>
                        <td class="td">
                            <span :class="['badge',
                                u.estado === 'activo'    ? 'badge-ok' :
                                u.estado === 'bloqueado' ? 'badge-alerta' :
                                'badge-neutral']">
                                {{ u.estado }}
                            </span>
                        </td>
                        <td class="td text-[12px] text-piedra-soft font-mono">{{ u.ultimo_acceso ?? '—' }}</td>
                        <td class="td text-center">
                            <div class="flex justify-center gap-2">
                                <button v-if="canManageRecords" @click="abrir(u)" class="btn-table-action">Editar</button>
                                <button v-else @click="abrir(u, true)" class="btn-table-action">Ver</button>
                                <button v-if="canManageRecords" @click="selectedId = u.id_usuario; newPass = ''; modalReset = true;" class="btn-table-action font-semibold text-tinta">Reset</button>
                                <button v-if="canManageRecords" @click="eliminar(u)" class="btn-table-action text-rojo hover:text-rojo-dark">Desactivar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal nuevo/editar -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-md', { 'read-only': viewing }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">
                            {{ viewing ? 'Ver Usuario' : editando ? 'Editar Usuario' : 'Nuevo Usuario' }}
                        </h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando">
                            <label class="lbl">Código de Usuario *</label>
                            <input v-model="form.codigo_usuario" type="text" class="inp" />
                            <p v-if="errors.codigo_usuario" class="err">{{ errors.codigo_usuario[0] }}</p>
                        </div>
                        <div v-if="!editando">
                            <label class="lbl">Personal *</label>
                            <select v-model="form.cedula_personal" class="inp">
                                <option value="">—</option>
                                <option v-for="p in personal" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option>
                            </select>
                            <p v-if="errors.cedula_personal" class="err">{{ errors.cedula_personal[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Rol *</label>
                            <select v-model="form.rol" class="inp">
                                <option value="administrador">Administrador</option>
                                <option value="control_estudios">Control de Estudios</option>
                                <option value="docente">Docente</option>
                            </select>
                            <p v-if="errors.rol" class="err">{{ errors.rol[0] }}</p>
                        </div>
                        <div v-if="!editando">
                            <label class="lbl">Contraseña *</label>
                            <input v-model="form.password" type="password" class="inp" />
                            <p v-if="errors.password" class="err">{{ errors.password[0] }}</p>
                        </div>
                        <div v-if="editando">
                            <label class="lbl">Estado</label>
                            <select v-model="form.estado" class="inp">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                            <p v-if="errors.estado" class="err">{{ errors.estado[0] }}</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cancelar</button>
                        <button v-if="canManageRecords && !viewing" @click="guardar" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal reset contraseña -->
        <Teleport to="body">
            <div v-if="modalReset" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-sm">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">Restablecer Contraseña</h2>
                        <button @click="modalReset=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <label class="lbl">Nueva Contraseña (mín. 8 caracteres)</label>
                        <input v-model="newPass" type="password" class="inp" />
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modalReset=false" class="btn-secondary">Cancelar</button>
                        <button @click="resetPass" :disabled="saving" class="btn-primary">Restablecer</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.err           { @apply text-rojo text-[11px] mt-1; }
.th            { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td            { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
