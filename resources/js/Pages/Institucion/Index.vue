<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const page = usePage();
const userRole = computed(() => String(page.props.auth?.user?.rol ?? 'docente').trim().toLowerCase());
const canManageRecords = computed(() => !['docente'].includes(userRole.value));

const inst    = ref(null);
const personal = ref([]);
const modal   = ref(false);
const saving  = ref(false);
const errors  = ref({});
const viewing = ref(false);
const form    = reactive({
    codigo_dea: '', nombre: '', direccion: '', telefono: '',
    municipio: '', estado: '', zona_educativa: '',
    director_actual: '', coordinador_academico: '',
});

async function cargar() {
    try {
        const [i, p] = await Promise.all([
            axios.get('/api/institucion'),
            axios.get('/api/personal-lista'),
        ]);
        inst.value     = i.data;
        personal.value = p.data;
    } catch { inst.value = null; }
}

function abrir() {
    Object.assign(form, inst.value ? { ...inst.value } : {
        codigo_dea: '', nombre: '', direccion: '', telefono: '',
        municipio: '', estado: '', zona_educativa: '',
        director_actual: '', coordinador_academico: '',
    });
    errors.value = {};
    modal.value  = true;
}

async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (inst.value) await axios.put(`/api/institucion/${inst.value.codigo_dea}`, form);
        else await axios.post('/api/institucion', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}

onMounted(cargar);
</script>

<template>
    <Head title="Institución — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-6 w-full">
                <div>
                    <h1 class="font-serif font-semibold text-[20px] text-tinta leading-tight">Datos de la Institución</h1>
                    <p class="text-[11px] text-piedra mt-0.5">Información que aparece en los documentos y boletines</p>
                </div>
                <button v-if="canManageRecords" @click="viewing = false; abrir()" class="btn-primary">
                    {{ inst ? 'Editar' : 'Configurar' }}
                </button>
                <button v-else @click="viewing = true; Object.assign(form, inst ? { ...inst } : {}); modal = true" class="btn-primary">
                    Ver
                </button>
            </div>
        </template>

        <!-- Tarjeta de institución -->
        <div v-if="inst" class="bg-paper border border-borde rounded-[6px] p-8 max-w-4xl">
            <div class="flex items-center gap-5 mb-6 pb-6 border-b border-borde">
                <div class="relative w-12 h-12 shrink-0">
                    <div class="absolute inset-0 rounded-full border-2 border-dorado"></div>
                    <div class="absolute inset-[4px] rounded-full border border-rojo flex items-center justify-center overflow-hidden bg-paper">
                        <img src="/imagenes/SGAE.png" alt="SGAE" class="w-full h-full object-contain p-0.5" />
                    </div>
                </div>
                <div>
                    <h2 class="font-serif font-semibold text-tinta text-2xl leading-tight">{{ inst.nombre }}</h2>
                    <p class="text-piedra font-mono text-[12px] mt-0.5">Código DEA: {{ inst.codigo_dea }}</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-6 text-[13px]">
                <div><span class="lbl">Municipio</span><p class="font-semibold text-tinta mt-0.5">{{ inst.municipio ?? '—' }}</p></div>
                <div><span class="lbl">Estado</span><p class="font-semibold text-tinta mt-0.5">{{ inst.estado ?? '—' }}</p></div>
                <div><span class="lbl">Zona Educativa</span><p class="font-semibold text-tinta mt-0.5">{{ inst.zona_educativa ?? '—' }}</p></div>
                <div><span class="lbl">Teléfono</span><p class="font-semibold text-tinta mt-0.5">{{ inst.telefono ?? '—' }}</p></div>
                <div class="sm:col-span-2"><span class="lbl">Dirección</span><p class="font-semibold text-tinta mt-0.5">{{ inst.direccion ?? '—' }}</p></div>
                <div class="border-t border-borde sm:col-span-2 md:col-span-3 pt-4 mt-2 grid sm:grid-cols-2 gap-4">
                    <div>
                        <span class="lbl">Director(a)</span>
                        <p class="font-semibold text-tinta mt-0.5">{{ inst.director ? `${inst.director.apellidos}, ${inst.director.nombres}` : '—' }}</p>
                    </div>
                    <div>
                        <span class="lbl">Coordinador(a) Académico</span>
                        <p class="font-semibold text-tinta mt-0.5">{{ inst.coordinador ? `${inst.coordinador.apellidos}, ${inst.coordinador.nombres}` : '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="bg-paper border border-borde border-l-[3px] border-l-dorado rounded-[6px] p-8 text-center max-w-lg mx-auto mt-8">
            <p class="font-serif font-semibold text-tinta text-lg">Institución no configurada</p>
            <p class="text-piedra text-[12px] mt-2">Haz clic en "Configurar" para ingresar los datos de tu institución.<br>Esta información aparecerá en todos los documentos generados.</p>
        </div>

        <!-- MODAL -->
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-tinta/60 z-50 flex items-center justify-center p-4">
                <div :class="['bg-paper border border-borde rounded-[6px] shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto', { 'read-only': viewing || !canManageRecords }]">
                    <div class="px-6 py-4 border-b border-borde flex justify-between items-center">
                        <h2 class="font-serif font-semibold text-tinta text-[17px]">Datos de la Institución</h2>
                        <button @click="modal=false" class="text-piedra hover:text-tinta text-lg leading-none">&times;</button>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!inst" class="col-span-2"><label class="lbl">Código DEA *</label><input v-model="form.codigo_dea" type="text" class="inp" /><p v-if="errors.codigo_dea" class="err">{{ errors.codigo_dea[0] }}</p></div>
                        <div class="col-span-2"><label class="lbl">Nombre *</label><input v-model="form.nombre" type="text" class="inp" /><p v-if="errors.nombre" class="err">{{ errors.nombre[0] }}</p></div>
                        <div><label class="lbl">Municipio</label><input v-model="form.municipio" type="text" class="inp" /></div>
                        <div><label class="lbl">Estado</label><input v-model="form.estado" type="text" class="inp" placeholder="Ej: Miranda" /></div>
                        <div><label class="lbl">Zona Educativa</label><input v-model="form.zona_educativa" type="text" class="inp" /></div>
                        <div><label class="lbl">Teléfono</label><input v-model="form.telefono" type="text" class="inp" /></div>
                        <div class="col-span-2"><label class="lbl">Dirección</label><textarea v-model="form.direccion" rows="2" class="inp"></textarea></div>
                        <div><label class="lbl">Director(a) — Cédula</label>
                            <select v-model="form.director_actual" class="inp">
                                <option value="">—</option>
                                <option v-for="p in personal" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option>
                            </select>
                        </div>
                        <div><label class="lbl">Coordinador(a) — Cédula</label>
                            <select v-model="form.coordinador_academico" class="inp">
                                <option value="">—</option>
                                <option v-for="p in personal" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option>
                            </select>
                        </div>
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
.lbl           { @apply block text-[12px] font-semibold text-tinta-soft uppercase tracking-[0.04em]; }
.inp           { @apply w-full border border-borde rounded-[4px] px-3 py-[10px] text-[13px] bg-crema text-tinta focus:outline-none focus:border-rojo focus:bg-paper transition-colors mt-1; }
.err           { @apply text-rojo text-[11px] mt-1; }
.btn-primary   { @apply bg-rojo hover:bg-rojo-dark text-paper text-[13px] font-semibold px-4 py-2 rounded-[4px] transition-colors; }
.btn-secondary { @apply border border-borde text-tinta-soft text-[13px] font-semibold px-4 py-2 rounded-[4px] hover:bg-crema transition-colors; }
.read-only input, .read-only select, .read-only textarea { pointer-events: none; background-color: #f8f8f8; }
</style>
