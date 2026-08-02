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

const form = reactive({
    codigo_grado: '',
    nombre: '',
    nivel_educativo: 'Educación Media General',
    numero_ano: 1,
    estado: 'activo'
});

async function cargar() {
    const { data } = await axios.get('/api/grados');
    lista.value = data;
}

function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? {
        codigo_grado: '', nombre: '', nivel_educativo: 'Educación Media General', numero_ano: 1, estado: 'activo'
    });
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/grados/${form.codigo_grado}`, form);
        else await axios.post('/api/grados', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Grado actualizado.' : 'Grado creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar el grado "${item.nombre}" (${item.codigo_grado})?`)) return;
    try {
        await axios.delete(`/api/grados/${item.codigo_grado}`);
        successMsg.value = 'Grado eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.message ?? e.response?.data?.error ?? e.message);
    }
}

onMounted(cargar);
</script>

<template>
    <Head title="Grados — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Grados</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Niveles académicos de la institución</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary ml-6">Nuevo Grado</button>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="g in lista" :key="g.codigo_grado"
                class="bg-paper border border-borde rounded-[6px] p-5 flex flex-col justify-between hover:border-dorado transition-colors">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <div class="w-9 h-9 border border-dorado rounded-[4px] flex items-center justify-center font-serif font-semibold text-[15px] text-tinta">
                            {{ g.numero_ano }}°
                        </div>
                        <span class="text-[11px] font-mono text-piedra-soft">{{ g.codigo_grado }}</span>
                    </div>
                    <p class="font-semibold text-tinta text-[14px]">{{ g.nombre }}</p>
                    <p class="text-[11px] text-piedra mt-0.5">{{ g.nivel_educativo }}</p>
                </div>
                <div class="flex justify-between items-center mt-4 pt-3 border-t border-borde">
                    <span :class="['badge', g.estado === 'activo' ? 'badge-ok' : 'badge-neutral']">{{ g.estado }}</span>
                    <div class="flex gap-2">
                        <button v-if="canManageRecords" @click="abrir(g)" class="btn-table-action">Editar</button>
                        <button v-if="canManageRecords" @click="eliminar(g)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                        <button v-else @click="viewing = true; editando = false; Object.assign(form, g); modal = true" class="btn-table-action">Ver</button>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-md',{ 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">{{ editando ? 'Editar' : 'Nuevo' }} Grado</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando">
                            <label class="lbl">Código *</label>
                            <input v-model="form.codigo_grado" type="text" class="inp" placeholder="Ej: 1ER" />
                            <p v-if="errors.codigo_grado" class="err">{{ errors.codigo_grado[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Nombre *</label>
                            <input v-model="form.nombre" type="text" class="inp" placeholder="Ej: Primer Año" />
                            <p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Nivel Educativo *</label>
                            <input v-model="form.nivel_educativo" type="text" class="inp" />
                        </div>
                        <div>
                            <label class="lbl">Número de Año *</label>
                            <input v-model="form.numero_ano" type="number" min="1" max="6" class="inp" />
                        </div>
                        <div>
                            <label class="lbl">Estado</label>
                            <select v-model="form.estado" class="inp">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
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
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
