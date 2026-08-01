<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

const lista = ref([]);
const modal = ref(false);
const viewing = ref(false);
const editando = ref(false);
const saving = ref(false);
const errors = ref({});
const buscar = ref('');
const pagination = ref({});
const successMsg = ref('');
const form = reactive({
    cedula_representante: '', nacionalidad: 'V', nombres: '', apellidos: '',
    parentesco: '', ocupacion: '', direccion: '', telefono: '', correo: '',
    es_representante_legal: true
});

async function cargar(page = 1) {
    const { data } = await axios.get('/api/representantes', { params: { buscar: buscar.value, page } });
    lista.value = data.data ?? data;
    pagination.value = data;
}

function abrir(item = null) {
    if (!canManageRecords.value) return;
    editando.value = !!item;
    Object.assign(form, item ?? {
        cedula_representante: '', nacionalidad: 'V', nombres: '', apellidos: '',
        parentesco: '', ocupacion: '', direccion: '', telefono: '', correo: '',
        es_representante_legal: true
    });
    errors.value = {};
    modal.value = true;
}

function ver(item) {
    viewing.value = true;
    editando.value = false;
    Object.assign(form, item ?? {
        cedula_representante: '', nacionalidad: 'V', nombres: '', apellidos: '',
        parentesco: '', ocupacion: '', direccion: '', telefono: '', correo: '',
        es_representante_legal: true
    });
}

async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/representantes/${form.cedula_representante}`, form);
        else await axios.post('/api/representantes', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Representante actualizado.' : 'Representante creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar al representante "${item.nombres} ${item.apellidos}" (${item.cedula_representante})?`)) return;
    try {
        await axios.delete(`/api/representantes/${item.cedula_representante}`);
        successMsg.value = 'Representante eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    }
}

let timer;
function onBuscar() { clearTimeout(timer); timer = setTimeout(() => cargar(), 400); }
onMounted(cargar);
</script>

<template>
    <Head title="Representantes — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Representantes</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Responsables legales de los estudiantes</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary">Nuevo</button>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="mb-4">
            <input v-model="buscar" @input="onBuscar" type="text"
                placeholder="Buscar representante..."
                class="inp max-w-md" />
        </div>

        <div class="bg-paper border border-borde rounded-[6px] overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Cédula</th>
                        <th class="th">Nombre</th>
                        <th class="th">Parentesco</th>
                        <th class="th">Teléfono</th>
                        <th class="th text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!lista.length">
                        <td colspan="5" class="text-center py-10 text-piedra text-[13px]">Sin representantes registrados.</td>
                    </tr>
                    <tr v-for="r in lista" :key="r.cedula_representante" class="hover:bg-crema transition-colors">
                        <td class="td font-mono text-[12px] text-piedra">{{ r.nacionalidad }}-{{ r.cedula_representante }}</td>
                        <td class="td font-semibold text-[12.5px] text-tinta">{{ r.apellidos }}, {{ r.nombres }}</td>
                        <td class="td text-[12px] text-piedra">{{ r.parentesco }}</td>
                        <td class="td text-[12px] text-piedra">{{ r.telefono ?? '—' }}</td>
                        <td class="td text-center">
                            <div class="flex justify-center gap-2">
                                <button v-if="canManageRecords" @click="abrir(r)" class="btn-table-action">Editar</button>
                                <button v-if="canManageRecords" @click="eliminar(r)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                                <button v-else @click="ver(r)" class="btn-table-action">Ver</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div v-if="modal && canManageRecords" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar' : 'Nuevo' }} Representante</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!editando" class="col-span-2 grid grid-cols-3 gap-3">
                            <div><label class="lbl">Nac.</label><select v-model="form.nacionalidad" class="inp"><option value="V">V</option><option value="E">E</option></select></div>
                            <div class="col-span-2"><label class="lbl">N° Cédula *</label><input v-model="form.cedula_representante" type="number" class="inp" /></div>
                        </div>
                        <div><label class="lbl">Nombres *</label><input v-model="form.nombres" type="text" class="inp" /><p v-if="errors.nombres" class="err">{{ errors.nombres[0] }}</p></div>
                        <div><label class="lbl">Apellidos *</label><input v-model="form.apellidos" type="text" class="inp" /><p v-if="errors.apellidos" class="err">{{ errors.apellidos[0] }}</p></div>
                        <div><label class="lbl">Parentesco *</label><input v-model="form.parentesco" type="text" class="inp" placeholder="Madre, Padre, Tutor..." /><p v-if="errors.parentesco" class="err">{{ errors.parentesco[0] }}</p></div>
                        <div><label class="lbl">Ocupación</label><input v-model="form.ocupacion" type="text" class="inp" /><p v-if="errors.ocupacion" class="err">{{ errors.ocupacion[0] }}</p></div>
                        <div><label class="lbl">Teléfono</label><input v-model="form.telefono" type="text" class="inp" /><p v-if="errors.telefono" class="err">{{ errors.telefono[0] }}</p></div>
                        <div><label class="lbl">Correo</label><input v-model="form.correo" type="email" class="inp" /><p v-if="errors.correo" class="err">{{ errors.correo[0] }}</p></div>
                        <div class="col-span-2"><label class="lbl">Dirección</label><input v-model="form.direccion" type="text" class="inp" /><p v-if="errors.direccion" class="err">{{ errors.direccion[0] }}</p></div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cancelar</button>
                        <button @click="guardar" :disabled="saving" class="btn-primary">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- VIEW ONLY MODAL -->
        <Teleport to="body">
            <div v-if="viewing" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div class="bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">Ver Representante</h2>
                        <button @click="viewing=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div class="col-span-2 grid grid-cols-3 gap-3" v-if="!editando">
                            <div><label class="lbl">Nac.</label><select v-model="form.nacionalidad" class="inp" disabled><option value="V">V</option><option value="E">E</option></select></div>
                            <div class="col-span-2"><label class="lbl">N° Cédula</label><input v-model="form.cedula_representante" type="number" class="inp" disabled /></div>
                        </div>
                        <div><label class="lbl">Nombres</label><input v-model="form.nombres" type="text" class="inp" disabled /></div>
                        <div><label class="lbl">Apellidos</label><input v-model="form.apellidos" type="text" class="inp" disabled /></div>
                        <div><label class="lbl">Parentesco</label><input v-model="form.parentesco" type="text" class="inp" disabled /></div>
                        <div><label class="lbl">Ocupación</label><input v-model="form.ocupacion" type="text" class="inp" disabled /></div>
                        <div><label class="lbl">Teléfono</label><input v-model="form.telefono" type="text" class="inp" disabled /></div>
                        <div><label class="lbl">Correo</label><input v-model="form.correo" type="email" class="inp" disabled /></div>
                        <div class="col-span-2"><label class="lbl">Dirección</label><input v-model="form.direccion" type="text" class="inp" disabled /></div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="viewing=false" class="btn-secondary">Cerrar</button>
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
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
</style>
