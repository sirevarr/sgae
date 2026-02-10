<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// --- ESTADOS ---
const estudiantes = ref([]);
const mostrarModal = ref(false);
const editandoId = ref(null);
const busqueda = ref('');
const filtroEstado = ref('');
const filtroGrado = ref('');
const filtroSeccion = ref(''); // <--- AÑADIDO

const opcionesGrados = [
    { value: '1er Año', label: '1er Año' },
    { value: '2do Año', label: '2do Año' },
    { value: '3er Año', label: '3er Año' },
    { value: '4to Año', label: '4to Año' },
    { value: '5to Año', label: '5to Año' },
];

const form = ref({
    cedula: '', nombres: '', apellidos: '', genero: 'M',
    fecha_nacimiento: '', lugar_nacimiento: '', direccion: '',
    email: '', telefono: '', estado: 'Activo',
    grado: '', seccion: ''
});

// --- FUNCIONES ---
const formatearFechaFull = (fecha) => {
    if (!fecha) return 'N/A';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric'
    });
};

const cargarEstudiantes = async () => {
    try {
        const res = await axios.get('/api/estudiantes');
        estudiantes.value = res.data.data || res.data;
    } catch (error) { 
        console.error("Error al cargar:", error); 
    }
};

const estudiantesFiltrados = computed(() => {
    return estudiantes.value.filter(e => {
        const nombreCompleto = `${e.nombres} ${e.apellidos}`.toLowerCase();
        const coincideBusqueda = nombreCompleto.includes(busqueda.value.toLowerCase()) || 
                                 String(e.cedula).includes(busqueda.value);
        
        const coincideEstado = filtroEstado.value === '' || 
                               String(e.estado).toLowerCase() === String(filtroEstado.value).toLowerCase();
        
        const coincideGrado = filtroGrado.value === '' || e.grado === filtroGrado.value;
        
        // Lógica de filtro de sección añadida
        const coincideSeccion = filtroSeccion.value === '' || e.seccion === filtroSeccion.value;

        return coincideBusqueda && coincideEstado && coincideGrado && coincideSeccion;
    });
});

const abrirModal = () => {
    editandoId.value = null;
    form.value = { 
        genero: 'M', estado: 'Activo', cedula: '', nombres: '', apellidos: '', 
        fecha_nacimiento: '', lugar_nacimiento: '', direccion: '', 
        email: '', telefono: '', grado: '', seccion: '' 
    };
    mostrarModal.value = true;
};

const prepararEdicion = (est) => {
    editandoId.value = est.id;
    const fechaLimpia = est.fecha_nacimiento ? est.fecha_nacimiento.split('T')[0] : '';
    const estadoNormalizado = est.estado 
        ? (est.estado.charAt(0).toUpperCase() + est.estado.slice(1).toLowerCase()) 
        : 'Activo';

    form.value = { 
        ...est, 
        fecha_nacimiento: fechaLimpia,
        estado: estadoNormalizado,
        grado: est.grado || '',
        seccion: est.seccion || ''
    };
    mostrarModal.value = true;
};

const guardar = async () => {
    if (!form.value.cedula || !form.value.nombres || !form.value.apellidos || !form.value.grado) {
        alert("Por favor, complete los campos obligatorios (*)");
        return;
    }

    try {
        if (editandoId.value) {
            await axios.put(`/api/estudiantes/${editandoId.value}`, form.value);
        } else {
            await axios.post('/api/estudiantes', form.value);
        }
        await cargarEstudiantes();
        mostrarModal.value = false;
        alert("¡Registro guardado con éxito!");
    } catch (e) {
        alert(e.response?.data?.message || "Error al procesar la solicitud");
    }
};

const eliminar = async (id) => {
    if (confirm('¿Está seguro de eliminar este estudiante definitivamente?')) {
        try {
            const res = await axios.delete(`/api/estudiantes/${id}`);
            await cargarEstudiantes();
            alert(res.data.message || "Eliminado correctamente");
        } catch (e) {
            const msg = e.response?.data?.message || "No se pudo eliminar.";
            alert(msg);
        }
    }
};

onMounted(cargarEstudiantes);
</script>

<template>
    <Head title="Estudiantes" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-sky-500 uppercase tracking-tight">Gestión de Estudiantes</h2>
        </template>

        <div class="py-12 bg-sky-50/30 min-h-screen">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm rounded-2xl border border-sky-100">
                    
                    <div class="flex flex-wrap items-center gap-4 mb-8">
                        <button @click="abrirModal" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded-xl font-black transition shadow-lg uppercase text-xs tracking-widest">
                            + Nuevo Estudiante
                        </button>
                        <div class="flex-1 min-w-[200px]">
                            <input v-model="busqueda" type="text" placeholder="Buscar por nombre o cédula..." class="w-full border-sky-100 focus:border-sky-500 focus:ring-sky-500 rounded-xl px-4 py-2" />
                        </div>
                        
                        <select v-model="filtroGrado" class="border-sky-100 rounded-xl text-sky-700 font-bold text-sm">
                            <option value="">Todos los grados</option>
                            <option v-for="g in opcionesGrados" :key="g.value" :value="g.value">{{ g.label }}</option>
                        </select>

                        <select v-model="filtroSeccion" class="border-sky-100 rounded-xl text-sky-700 font-bold text-sm">
                            <option value="">Todas las secciones</option>
                            <option value="A">Sección A</option>
                            <option value="B">Sección B</option>
                            <option value="C">Sección C</option>
                            <option value="U">Única</option>
                        </select>

                        <select v-model="filtroEstado" class="border-sky-100 rounded-xl text-sky-700 font-bold text-sm">
                            <option value="">Todos los estados</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-sky-50">
                        <table class="min-w-[1600px] w-full divide-y divide-sky-100">
                            <thead class="bg-sky-50/50">
                                <tr class="text-left text-[11px] font-black text-sky-700 uppercase tracking-widest">
                                    <th class="px-4 py-4">Cédula</th>
                                    <th class="px-4 py-4">Estudiante / Año</th>
                                    <th class="px-4 py-4 text-center">Género</th>
                                    <th class="px-4 py-4">Nacimiento (Fecha/Lugar)</th>
                                    <th class="px-4 py-4">Dirección</th>
                                    <th class="px-4 py-4">Contacto</th>
                                    <th class="px-4 py-4">Auditoría</th>
                                    <th class="px-4 py-4 text-center">Estado</th>
                                    <th class="px-4 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="est in estudiantesFiltrados" :key="est.id" class="hover:bg-sky-50/40 transition">
                                    <td class="px-4 py-4 font-mono text-sky-400 font-black text-xs">V-{{ est.cedula }}</td>
                                    <td class="px-4 py-4">
                                        <div class="font-bold text-sky-900 uppercase text-sm leading-tight">{{ est.nombres }} {{ est.apellidos }}</div>
                                        <div class="mt-1">
                                            <span class="bg-sky-100 text-sky-600 px-2 py-0.5 rounded text-[9px] font-black uppercase border border-sky-200">
                                                {{ est.grado || 'S/G' }} - {{ est.seccion || 'S/S' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center text-[10px] font-black text-gray-400 uppercase">{{ est.genero }}</td>
                                    <td class="px-4 py-4">
                                        <div class="text-[11px] font-bold text-gray-700">{{ formatearFechaFull(est.fecha_nacimiento) }}</div>
                                        <div class="text-[10px] text-gray-400 uppercase italic">{{ est.lugar_nacimiento || 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-[10px] uppercase text-gray-600 max-w-[200px] break-words leading-tight">{{ est.direccion || 'S/D' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-sky-600 font-bold text-[11px]">{{ est.email || 'Sin correo' }}</div>
                                        <div class="text-gray-400 text-[11px]">{{ est.telefono || 'Sin teléfono' }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-bold uppercase">CREADO</span>
                                                <span class="text-[10px] text-gray-500">{{ formatearFechaFull(est.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-sky-100 text-sky-500 px-1 py-0.5 rounded font-bold uppercase">EDITADO</span>
                                                <span class="text-[10px] text-sky-600">{{ formatearFechaFull(est.updated_at) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span :class="est.estado === 'Activo' || est.estado === 'activo' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-3 py-1 rounded-full text-[10px] font-black uppercase border border-current">
                                            {{ est.estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-right whitespace-nowrap">
                                        <button @click="prepararEdicion(est)" class="text-sky-500 hover:text-sky-700 font-black mr-4 uppercase text-xs hover:underline">Editar</button>
                                        <button @click="eliminar(est.id)" class="text-red-400 hover:text-red-600 font-black uppercase text-xs hover:underline">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModal" class="fixed inset-0 bg-sky-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl max-w-4xl w-full max-h-[95vh] overflow-y-auto p-8 shadow-2xl border border-sky-100">
                <h3 class="text-2xl font-black text-sky-500 uppercase mb-6 tracking-tight">
                    {{ editandoId ? 'Actualizar Ficha' : 'Nuevo Registro' }}
                </h3>

                <form @submit.prevent="guardar" class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Cédula</label>
                        <input v-model="form.cedula" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" required />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Nombres</label>
                        <input v-model="form.nombres" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 uppercase font-bold text-sm" required />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Apellidos</label>
                        <input v-model="form.apellidos" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 uppercase font-bold text-sm" required />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Género</label>
                        <select v-model="form.genero" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Fecha Nacimiento</label>
                        <input v-model="form.fecha_nacimiento" type="date" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Lugar Nacimiento</label>
                        <input v-model="form.lugar_nacimiento" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm uppercase" />
                    </div>

                    <div class="space-y-1 md:col-span-2">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Dirección</label>
                        <input v-model="form.direccion" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm uppercase" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Teléfono</label>
                        <input v-model="form.telefono" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" />
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Grado</label>
                        <select v-model="form.grado" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" required>
                            <option value="" disabled>Seleccione...</option>
                            <option v-for="g in opcionesGrados" :key="g.value" :value="g.value">{{ g.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Sección</label>
                        <select v-model="form.seccion" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" required>
                            <option value="" disabled>Seleccione...</option>
                            <option value="A">Sección A</option>
                            <option value="B">Sección B</option>
                            <option value="C">Sección C</option>
                            <option value="U">Única</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-700 uppercase ml-1">Estado</label>
                        <select v-model="form.estado" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-black text-sky-600 text-sm">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="col-span-full flex justify-end gap-3 mt-8 border-t border-sky-50 pt-6">
                        <button type="button" @click="mostrarModal = false" class="px-6 py-2 text-sky-400 font-black uppercase text-xs">Cancelar</button>
                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-10 py-3 rounded-xl font-black shadow-lg uppercase text-xs">
                            {{ editandoId ? 'Guardar Cambios' : 'Registrar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>