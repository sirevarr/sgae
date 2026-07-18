<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const secciones = ref([]); const anios = ref([]); const personal = ref([]);
const modal = ref(false); const editando = ref(false); const saving = ref(false); const errors = ref({});
const filtroAnio = ref('');
const form = reactive({ codigo_seccion: '', letra: '', codigo_grado: '', codigo_ano_escolar: '',
    id_mencion: '', cedula_docente_guia: '', capacidad_maxima: 35, turno: 'mañana', aula_asignada: '' });
const grados = ref([]); const menciones = ref([]);

async function cargar() {
    const { data } = await axios.get('/api/secciones', { params: filtroAnio.value ? { codigo_ano_escolar: filtroAnio.value } : {} });
    secciones.value = data;
}
async function cargarCatalogos() {
    const [a, g, m, p] = await Promise.all([
        axios.get('/api/anios-escolares'), axios.get('/api/grados'),
        axios.get('/api/menciones'), axios.get('/api/personal'),
    ]);
    anios.value = a.data; grados.value = g.data; menciones.value = m.data; personal.value = p.data?.data ?? p.data;
}
function abrir(item = null) {
    editando.value = !!item;
    Object.assign(form, item ?? { codigo_seccion: '', letra: '', codigo_grado: '', codigo_ano_escolar: filtroAnio.value || '',
        id_mencion: '', cedula_docente_guia: '', capacidad_maxima: 35, turno: 'mañana', aula_asignada: '' });
    errors.value = {}; modal.value = true;
}
async function guardar() {
    saving.value = true; errors.value = {};
    try {
        if (editando.value) await axios.put(`/api/secciones/${form.codigo_seccion}`, form);
        else await axios.post('/api/secciones', form);
        modal.value = false; cargar();
    } catch (e) {
        if (e.response?.status === 422) errors.value = e.response.data.errors ?? {};
        else alert(e.response?.data?.error ?? e.response?.data?.message ?? e.message);
    } finally { saving.value = false; }
}
onMounted(async () => { await cargarCatalogos(); cargar(); });
</script>
<template>
    <Head title="Secciones — SGAE" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center flex-wrap gap-3">
                <div><h1 class="text-xl font-black text-slate-800">🗂️ Secciones</h1><p class="text-xs text-slate-500">Organización de grupos por grado y año escolar</p></div>
                <div class="flex gap-3">
                    <select v-model="filtroAnio" @change="cargar" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                        <option value="">Todos los años</option>
                        <option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option>
                    </select>
                    <button @click="abrir()" class="bg-sky-600 hover:bg-sky-700 text-white text-sm font-bold px-4 py-2 rounded-xl shadow transition">＋ Nueva Sección</button>
                </div>
            </div>
        </template>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-if="!secciones.length" class="col-span-full text-center py-10 text-slate-400">No hay secciones.</div>
            <div v-for="s in secciones" :key="s.codigo_seccion"
                class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center text-2xl font-black text-violet-700">{{ s.letra }}</div>
                    <span class="text-xs font-mono text-slate-400">{{ s.codigo_seccion }}</span>
                </div>
                <p class="font-black text-slate-800">{{ s.grado?.nombre }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ s.mencion?.nombre ?? 'Sin mención' }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ s.turno }} · Aula: {{ s.aula_asignada ?? '—' }}</p>
                <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-50">
                    <span class="text-xs text-slate-500">Cap: {{ s.capacidad_maxima }}</span>
                    <button @click="abrir(s)" class="text-sky-600 text-xs font-bold hover:underline">Editar</button>
                </div>
            </div>
        </div>
        <Teleport to="body">
            <div v-if="modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                    <div class="p-5 border-b flex justify-between"><h2 class="font-black text-slate-800">{{ editando ? 'Editar' : 'Nueva' }} Sección</h2><button @click="modal=false" class="text-slate-400">✕</button></div>
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div v-if="!editando" class="col-span-2"><label class="lbl">Código Sección *</label><input v-model="form.codigo_seccion" type="text" class="inp" placeholder="Ej: 1A-2025" /><p v-if="errors.codigo_seccion" class="err">{{ errors.codigo_seccion[0] }}</p></div>
                        <div><label class="lbl">Letra *</label><input v-model="form.letra" type="text" maxlength="1" class="inp" placeholder="A" /></div>
                        <div><label class="lbl">Año Escolar *</label><select v-model="form.codigo_ano_escolar" class="inp"><option value="">—</option><option v-for="a in anios" :key="a.codigo_ano_escolar" :value="a.codigo_ano_escolar">{{ a.codigo_ano_escolar }}</option></select></div>
                        <div><label class="lbl">Grado *</label><select v-model="form.codigo_grado" class="inp"><option value="">—</option><option v-for="g in grados" :key="g.codigo_grado" :value="g.codigo_grado">{{ g.nombre }}</option></select></div>
                        <div><label class="lbl">Mención</label><select v-model="form.id_mencion" class="inp"><option value="">—</option><option v-for="m in menciones" :key="m.id_mencion" :value="m.id_mencion">{{ m.nombre }}</option></select></div>
                        <div><label class="lbl">Docente Guía</label><select v-model="form.cedula_docente_guia" class="inp"><option value="">—</option><option v-for="p in personal.filter(x=>x.docente)" :key="p.cedula_personal" :value="p.cedula_personal">{{ p.apellidos }}, {{ p.nombres }}</option></select></div>
                        <div><label class="lbl">Capacidad Máx.</label><input v-model="form.capacidad_maxima" type="number" class="inp" /></div>
                        <div><label class="lbl">Turno *</label><select v-model="form.turno" class="inp"><option value="mañana">Mañana</option><option value="tarde">Tarde</option><option value="nocturno">Nocturno</option></select></div>
                        <div class="col-span-2"><label class="lbl">Aula Asignada</label><input v-model="form.aula_asignada" type="text" class="inp" placeholder="Ej: Aula 3B" /></div>
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
