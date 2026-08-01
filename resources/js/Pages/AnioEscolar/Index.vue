<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol || 'docente').toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));
const lista = ref([]);
const modal = ref(false);
const editando = ref(false);
const viewing = ref(false);
const saving = ref(false);
const errors = ref({});
const successMsg = ref('');

const form = reactive({
    codigo_ano_escolar: '',
    fecha_inicio: '',
    fecha_fin: '',
    estado: 'planificado'
});

async function cargar() {
    const { data } = await axios.get('/api/anios-escolares');
    lista.value = data;
}

function abrir(item = null) {
    editando.value = !!item;
    viewing.value = false;
    Object.assign(form, item ?? { codigo_ano_escolar: '', fecha_inicio: '', fecha_fin: '', estado: 'planificado' });
    errors.value = {};
    modal.value = true;
}

function ver(item) {
    editando.value = false;
    viewing.value = true;
    Object.assign(form, item);
    errors.value = {};
    modal.value = true;
}

async function guardar() {
    saving.value = true;
    errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/anios-escolares/${form.codigo_ano_escolar}`, form);
        else await axios.post('/api/anios-escolares', form);
        modal.value = false;
        successMsg.value = editando.value ? 'Año escolar actualizado.' : 'Año escolar creado.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert('Error: ' + (e.response?.data?.message ?? e.message));
    } finally { saving.value = false; }
}

async function eliminar(item) {
    if (!confirm(`¿Eliminar el año escolar "${item.codigo_ano_escolar}"?`)) return;
    try {
        await axios.delete(`/api/anios-escolares/${item.codigo_ano_escolar}`);
        successMsg.value = 'Año escolar eliminado correctamente.';
        setTimeout(() => successMsg.value = '', 3000);
        cargar();
    } catch (e) {
        alert(e.response?.data?.message ?? e.response?.data?.error ?? e.message);
    }
}

onMounted(cargar);
</script>

<template>
    <Head title="Años Escolares — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Años Escolares</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Gestión de períodos académicos</p>
                </div>
                <button v-if="canManageRecords" @click="abrir()" class="btn-primary ml-6">Nuevo Año Escolar</button>
            </div>
        </template>

        <div v-if="successMsg" class="mb-4 bg-[#E6EEE0] border border-ok/30 text-ok text-[12px] font-semibold px-4 py-2.5 rounded-[4px] flex justify-between items-center">
            <span>{{ successMsg }}</span>
            <button @click="successMsg = ''" class="text-ok/70 hover:text-ok ml-4">&times;</button>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="a in lista" :key="a.codigo_ano_escolar"
                :class="['bg-paper border border-borde rounded-[6px] p-5 flex flex-col justify-between',
                    a.estado === 'vigente' ? 'border-t-[3px] border-t-dorado' : '']">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-serif font-semibold text-tinta text-[17px]">{{ a.codigo_ano_escolar }}</h3>
                        <span :class="['badge',
                            a.estado === 'vigente'     ? 'badge-ok' :
                            a.estado === 'planificado' ? 'badge-alerta' :
                            'badge-neutral']">
                            {{ a.estado }}
                        </span>
                    </div>
                    <p class="text-[11px] text-piedra">Inicio: {{ a.fecha_inicio ?? '—' }}</p>
                    <p class="text-[11px] text-piedra">Fin: {{ a.fecha_fin ?? '—' }}</p>
                </div>
                <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-borde">
                    <button v-if="canManageRecords" @click="abrir(a)" class="btn-table-action">Editar</button>
                    <button v-if="canManageRecords" @click="eliminar(a)" class="btn-table-action text-rojo hover:text-rojo-dark">Eliminar</button>
                    <button v-else @click="ver(a)" class="btn-table-action">Ver</button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-md', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">
                            {{ viewing ? 'Ver Año Escolar' : editando ? 'Editar Año Escolar' : 'Nuevo Año Escolar' }}
                        </h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="!editando">
                            <label class="lbl">Código *</label>
                            <input v-model="form.codigo_ano_escolar" type="text" class="inp" placeholder="Ej: 2025-2026" />
                            <p v-if="errors.codigo_ano_escolar" class="err">{{ errors.codigo_ano_escolar[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Fecha Inicio</label>
                            <input v-model="form.fecha_inicio" type="date" class="inp" />
                            <p v-if="errors.fecha_inicio" class="err">{{ errors.fecha_inicio[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Fecha Fin</label>
                            <input v-model="form.fecha_fin" type="date" class="inp" />
                            <p v-if="errors.fecha_fin" class="err">{{ errors.fecha_fin[0] }}</p>
                        </div>
                        <div>
                            <label class="lbl">Estado *</label>
                            <select v-model="form.estado" class="inp">
                                <option value="planificado">Planificado</option>
                                <option value="vigente">Vigente (activo)</option>
                                <option value="cerrado">Cerrado</option>
                            </select>
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
    </AuthenticatedLayout>
</template>

<style scoped>
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.err           { @apply text-rojo text-[11px] mt-1; }
.badge         { @apply inline-flex items-center rounded-[20px] px-[9px] py-[3px] text-[10.5px] font-semibold; }
.badge-ok      { @apply bg-[#E6EEE0] text-ok; }
.badge-alerta  { @apply bg-dorado-soft text-[#7A5A0E]; }
.badge-neutral { @apply bg-crema text-piedra; }
.btn-primary       { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary     { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.btn-table-action  { @apply text-[12px] font-semibold text-piedra hover:text-tinta transition-colors px-2 py-1 rounded-[3px] hover:bg-crema; }
    .read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
