<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

const lista    = ref([]);
const modal    = ref(false);
const editando = ref(false);
const viewing  = ref(false);
const saving   = ref(false);
const errors   = ref({});
const successMsg = ref('');
const form     = reactive({
    cedula_personal: '', nombres: '', apellidos: '', cargo: '',
    telefono: '', correo: '', genero: '', fecha_nacimiento: '', fecha_ingreso: '',
    estado: 'activo', observaciones: '', especialidad: '', turno: ''
});

async function cargar() {
    const { data } = await axios.get('/api/personal');
    lista.value = data.data ?? data;
}

function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? {
        cedula_personal: '', nombres: '', apellidos: '', cargo: '',
        telefono: '', correo: '', genero: '', fecha_nacimiento: '', fecha_ingreso: '',
        estado: 'activo', observaciones: '', especialidad: '', turno: ''
    });
    errors.value = {};
    modal.value  = true;
}

async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/personal/${form.cedula_personal}`, form);
        else await axios.post('/api/personal', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Personal actualizado.' : 'Personal creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally { saving.value = false; }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar al personal "${item.nombres} ${item.apellidos}" (${item.cedula_personal})?`)) return;
    try {
        await axios.delete(`/api/personal/${item.cedula_personal}`);
        successMsg.value = 'Personal eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    }
}
onMounted(cargar);
</script>

<template>
    <Head title="Personal — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Personal</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Gestión del personal docente y administrativo</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary">Nuevo</button>
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
                        <th class="th">Cédula</th>
                        <th class="th">Nombre</th>
                        <th class="th">Cargo</th>
                        <th class="th">Especialidad</th>
                        <th class="th">Teléfono</th>
                        <th class="th">Estado</th>
                        <th class="th text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!lista.length">
                        <td colspan="7" class="text-center py-10 text-piedra text-[13px]">Sin personal registrado.</td>
                    </tr>
                    <tr v-for="p in lista" :key="p.cedula_personal" class="hover:bg-crema transition-colors">
                        <td class="td font-mono text-[12px] text-piedra">{{ p.cedula_personal }}</td>
                        <td class="td font-semibold text-[12.5px] text-tinta">{{ p.apellidos }}, {{ p.nombres }}</td>
                        <td class="td text-[12.5px] text-piedra">{{ p.cargo }}</td>
                        <td class="td text-[12px] text-piedra">{{ p.docente?.especialidad ?? '—' }}</td>
                        <td class="td text-[12px] text-piedra">{{ p.telefono ?? '—' }}</td>
                        <td class="td">
                            <span :class="['badge', p.estado === 'activo' ? 'badge-ok' : 'badge-neutral']">{{ p.estado }}</span>
                        </td>
                        <td class="td text-center">
                            <div class="flex justify-center gap-2">
                                    <button v-if="canManageRecords" @click="abrir(p)" class="btn-table-action">Editar</button>
                                    <button v-if="canManageRecords" @click="eliminar(p)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                                    <button v-else @click="(function(){ viewing.value=true; editando.value=false; Object.assign(form,p); modal.value=true; })()" class="btn-table-action">Ver</button>
                                </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar' : 'Nuevo' }} Personal</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!editando" class="col-span-2">
                            <label class="lbl">Cédula *</label>
                            <input v-model="form.cedula_personal" type="number" class="inp" />
                            <p v-if="errors.cedula_personal" class="err">{{ errors.cedula_personal[0] }}</p>
                        </div>
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
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cerrar</button>
                        <button v-if="canManageRecords" @click="guardar" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.err           { @apply text-rojo text-[11px] mt-1; }
.th            { @apply px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.04em] text-piedra; }
.td            { @apply px-4 py-3; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
</style>
