<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const materias = ref([]);
const mostrarModal = ref(false);
const editandoId = ref(null);
const busqueda = ref('');
const filtroEstado = ref(''); 

const form = ref({
    codigo_materia: '', nombre: '', descripcion: '', creditos: 1, estado: 'activa'
});

const formatearFechaFull = (fecha) => {
    if (!fecha) return 'N/A';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const cargarMaterias = async () => {
    try {
        const res = await axios.get('/api/materias');
        materias.value = res.data.data || res.data;
    } catch (error) { console.error("Error al cargar:", error); }
};

// --- FILTRO INTELIGENTE ---
const materiasFiltradas = computed(() => {
    return materias.value.filter(m => {
        const buscar = busqueda.value.toLowerCase().trim();
        const cumpleNombre = String(m.nombre || '').toLowerCase().includes(buscar);
        const cumpleCodigo = String(m.codigo_materia || '').toLowerCase().includes(buscar);
        
        if (filtroEstado.value === '') return cumpleNombre || cumpleCodigo;

        // Comparamos por raíz de palabra para evitar errores entre activo/activa
        const estadoDb = String(m.estado || '').toLowerCase().substring(0, 4);
        const estadoFiltro = String(filtroEstado.value).toLowerCase().substring(0, 4);
        
        return (cumpleNombre || cumpleCodigo) && (estadoDb === estadoFiltro);
    });
});

const abrirModal = () => {
    editandoId.value = null;
    form.value = { codigo_materia: '', nombre: '', descripcion: '', creditos: 1, estado: 'activa' };
    mostrarModal.value = true;
};

const prepararEdicion = (mat) => {
    editandoId.value = mat.id;
    // Detectamos el estado actual para que el select lo muestre bien
    const st = String(mat.estado || '').toLowerCase();
    const estadoNormalizado = st.includes('inac') ? 'inactiva' : 'activa';

    form.value = { ...mat, estado: estadoNormalizado };
    mostrarModal.value = true;
};

const guardar = async () => {
    try {
        const payload = {
            ...form.value,
            codigo_materia: String(form.value.codigo_materia).toUpperCase().trim(),
            estado: form.value.estado.toLowerCase().trim()
        };

        if (editandoId.value) await axios.put(`/api/materias/${editandoId.value}`, payload);
        else await axios.post('/api/materias', payload);
        
        await cargarMaterias();
        mostrarModal.value = false;
        alert("¡Materia guardada!");
    } catch (e) {
        alert("Error de validación. Revisa que el código sea único.");
    }
};

const eliminar = async (id) => {
    if (confirm('¿Eliminar materia?')) {
        await axios.delete(`/api/materias/${id}`);
        cargarMaterias();
    }
};

onMounted(cargarMaterias);
</script>

<template>
    <Head title="Materias" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-sky-500 uppercase tracking-tight">Gestión de Materias</h2>
        </template>

        <div class="py-12 bg-sky-50/30 min-h-screen">
            <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm rounded-2xl border border-sky-100">
                    
                    <div class="flex flex-wrap items-center gap-4 mb-8">
                        <button @click="abrirModal" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded-xl font-black uppercase text-xs tracking-widest shadow-lg transition">
                            + Nueva Materia
                        </button>
                        
                        <input v-model="busqueda" type="text" placeholder="Buscar por nombre o código..." class="flex-1 border-sky-100 focus:ring-sky-500 rounded-xl px-4 py-3" />
                        
                        <select v-model="filtroEstado" class="border-sky-100 rounded-xl text-sky-700 font-bold focus:ring-sky-500 py-3 px-6">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-sky-100">
                            <thead class="bg-sky-50/50">
                                <tr class="text-left text-[11px] font-black text-sky-700 uppercase tracking-widest">
                                    <th class="px-6 py-4">Materia / Cód.</th>
                                    <th class="px-6 py-4">Descripción</th>
                                    <th class="px-6 py-4">Auditoría</th>
                                    <th class="px-6 py-4 text-center">Estado</th>
                                    <th class="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="mat in materiasFiltradas" :key="mat.id" class="hover:bg-sky-50/40 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-xs font-black text-sky-400 font-mono">{{ mat.codigo_materia }}</div>
                                        <div class="font-bold text-sky-900 uppercase text-sm">{{ mat.nombre }}</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">{{ mat.creditos }} Créditos</div>
                                    </td>
                                    
                                    <td class="px-6 py-4">
                                        <div class="max-w-[250px] text-xs text-gray-500 leading-relaxed italic">
                                            {{ mat.descripcion || 'Sin descripción registrada.' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-bold">CREADO</span>
                                                <span class="text-[10px] text-gray-500">{{ formatearFechaFull(mat.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-sky-100 text-sky-500 px-1.5 py-0.5 rounded font-bold">EDITADO</span>
                                                <span class="text-[10px] text-sky-600 font-medium">{{ formatearFechaFull(mat.updated_at) }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <span :class="String(mat.estado).toLowerCase().includes('acti') ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase border tracking-widest">
                                            {{ mat.estado }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <button @click="prepararEdicion(mat)" class="text-sky-500 font-black mr-4 text-xs uppercase hover:underline">Editar</button>
                                        <button @click="eliminar(mat.id)" class="text-red-400 font-black text-xs uppercase hover:underline">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModal" class="fixed inset-0 bg-sky-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-8 shadow-2xl border border-sky-100">
                <h3 class="text-2xl font-black text-sky-500 uppercase mb-6 text-center">Datos de Materia</h3>
                <form @submit.prevent="guardar" class="space-y-4">
                    <input v-model="form.codigo_materia" type="text" placeholder="Ej: MAT-01" class="w-full rounded-xl border-sky-100 bg-sky-50/30 uppercase font-bold" required />
                    <input v-model="form.nombre" type="text" placeholder="Ej: Matemática" class="w-full rounded-xl border-sky-100 bg-sky-50/30 uppercase font-bold" required />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Créditos</label>
                            <input v-model="form.creditos" type="number" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Estado</label>
                            <select v-model="form.estado" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-black text-sky-600">
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Descripción de la materia</label>
                        <textarea v-model="form.descripcion" rows="3" placeholder="Opcional..." class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-medium"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-sky-50">
                        <button type="button" @click="mostrarModal = false" class="text-sky-400 font-black uppercase text-xs px-4">Cancelar</button>
                        <button type="submit" class="bg-sky-500 text-white px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest shadow-lg">Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>