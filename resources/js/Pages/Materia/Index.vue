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
const editando = ref(false);
const viewing = ref(false);
const saving = ref(false);
const errors = ref({});
const successMsg = ref('');

const form = reactive({ siglas: '', nombre: '', area_formacion: '' });

async function cargar() {
    const { data } = await axios.get('/api/materias');
    lista.value = data.data ?? data;
}

function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { siglas: '', nombre: '', area_formacion: '' });
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/materias/${form.siglas}`, form);
        else await axios.post('/api/materias', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Materia actualizada.' : 'Materia creada.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally {
        saving.value = false;
    }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar la materia "${item.nombre}" (${item.siglas})?`)) return;
    try {
        await axios.delete(`/api/materias/${item.siglas}`);
        successMsg.value = 'Materia eliminada correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.message ?? e.response?.data?.error ?? e.message);
    }
}

onMounted(cargar);
</script>

<template>
    <Head title="Materias — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Materias</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Catálogo de áreas de formación académica</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary">Nueva Materia</button>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="bg-paper border border-borde rounded-[6px] overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-borde">
                        <th class="th">Siglas</th>
                        <th class="th">Nombre</th>
                        <th class="th">Área de Formación</th>
                        <th class="th text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-borde">
                    <tr v-if="!lista.length">
                        <td colspan="4" class="text-center py-10 text-piedra text-[13px]">Sin materias registradas.</td>
                    </tr>
                    <tr v-for="m in lista" :key="m.siglas" class="hover:bg-crema transition-colors">
                        <td class="td font-mono font-semibold text-tinta text-[12.5px]">{{ m.siglas }}</td>
                        <td class="td font-semibold text-tinta text-[12.5px]">{{ m.nombre }}</td>
                        <td class="td text-piedra text-[12px]">{{ m.area_formacion ?? '—' }}</td>
                        <td class="td text-center">
                            <div class="flex justify-center gap-2">
                                    <button v-if="canManageRecords" @click="abrir(m)" class="btn-table-action">Editar</button>
                                    <button v-if="canManageRecords" @click="eliminar(m)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                                    <button v-else @click="viewing = true; editando = false; Object.assign(form, m); modal = true" class="btn-table-action">Ver</button>
                                </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-md',{ 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar' : 'Nueva' }} Materia</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando">
                            <label class="lbl">Siglas *</label>
                            <input v-model="form.siglas" type="text" class="inp" placeholder="Ej: MAT" />
                            <p v-if="errors.siglas" class="err">{{ errors.siglas[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Nombre *</label>
                            <input v-model="form.nombre" type="text" class="inp" placeholder="Ej: Matemática" />
                            <p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Área de Formación</label>
                            <input v-model="form.area_formacion" type="text" class="inp" placeholder="Ej: Ciencias Naturales" />
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-borde flex justify-end gap-3">
                        <button @click="modal=false" class="btn-secondary">Cerrar</button>
                        <button v-if="canManageRecords" @click="guardar" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
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
