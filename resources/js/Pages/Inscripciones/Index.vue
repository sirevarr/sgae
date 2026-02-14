<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

// --- FUNCIONES DE UTILIDAD ---

// Obtiene la fecha actual local en formato YYYY-MM-DD
const obtenerFechaLocal = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().split('T')[0];
};

// Formatea la fecha para evitar el desfase de un día
const formatearFechaFull = (fecha) => {
    if (!fecha) return 'N/A';
    const parteFecha = fecha.split('T')[0];
    const [year, month, day] = parteFecha.split('-');
    return `${day}/${month}/${year}`;
};

// Mapeo para nombres correctos de grados
const nombresGrados = {
    1: '1er Año',
    2: '2do Año',
    3: '3er Año',
    4: '4to Año',
    5: '5to Año'
};

// --- ESTADOS ---
const inscripciones = ref([]);
const estudiantes = ref([]);
const materias = ref([]);
const mostrarModal = ref(false);
const editandoId = ref(null);
const busqueda = ref('');
const filtroEstado = ref(''); 
const filtroGrado = ref(''); 
const filtroSeccion = ref('');
const filtroPeriodo = ref('');

const form = ref({
    estudiante_id: '', 
    materia_id: '', 
    periodo: '2026-1', 
    fecha_inscripcion: obtenerFechaLocal(), 
    estado: 'activa'
});

// --- CARGA DE DATOS ---
const cargarDatos = async () => {
    try {
        const [resIns, resEst, resMat] = await Promise.all([
            axios.get('/api/inscripciones'),
            axios.get('/api/estudiantes'),
            axios.get('/api/materias')
        ]);
        inscripciones.value = resIns.data.data || resIns.data;
        estudiantes.value = resEst.data.data || resEst.data;
        // Filtrar solo materias ACTIVAS
            materias.value = (resMat.data.data || resMat.data).filter(m => 
                String(m.estado).toLowerCase().startsWith('act')
            );
    } catch (error) {
        console.error("Error al cargar datos:", error);
    }
};

// --- LÓGICA DE FILTRADO ---
const inscripcionesFiltradas = computed(() => {
        return inscripciones.value.filter(ins => {
            // Excluir inscripciones cuya materia esté inactiva (defensa doble en frontend)
            const materiaActiva = String(ins.materia?.estado || '').toLowerCase().startsWith('act');
            if (!materiaActiva) return false;
        const buscar = busqueda.value.toLowerCase().trim();
        const est = ins.estudiante;
        
        const info = `${est?.nombres} ${est?.apellidos} ${est?.cedula} ${ins.materia?.nombre}`.toLowerCase();
        const coincideTexto = info.includes(buscar);
        const coincideGrado = filtroGrado.value === '' || String(est?.grado) === String(filtroGrado.value);
        const coincideSeccion = filtroSeccion.value === '' || est?.seccion === filtroSeccion.value;
        const coincidePeriodo = filtroPeriodo.value === '' || (ins.periodo || '') === filtroPeriodo.value;
        
        let coincideEstado = true;
        if (filtroEstado.value !== '') {
            const estadoDb = String(ins.estado || '').toLowerCase();
            const estadoBusqueda = String(filtroEstado.value).toLowerCase();
            coincideEstado = estadoDb.startsWith(estadoBusqueda.substring(0,4));
        }

        return coincideTexto && coincideGrado && coincideSeccion && coincideEstado && coincidePeriodo;
    }).sort((a, b) => a.estudiante.apellidos.localeCompare(b.estudiante.apellidos));
});

// Periodos disponibles a partir de las inscripciones
const periodosInscripciones = computed(() => {
    const set = new Set((inscripciones.value || []).map(i => i.periodo).filter(Boolean));
    return Array.from(set).sort();
});

// Materias filtradas según estudiante seleccionado (solo activas)
const materiasDisponibles = computed(() => {
    return materias.value.filter(m => String(m.estado).toLowerCase().startsWith('act'));
});

// --- ACCIONES ---
const abrirModal = () => {
    editandoId.value = null;
    form.value = { 
        estudiante_id: '', 
        materia_id: '', 
        periodo: '2026-1', 
        fecha_inscripcion: obtenerFechaLocal(), 
        estado: 'activa' 
    };
    mostrarModal.value = true;
};

const prepararEdicion = (ins) => {
    editandoId.value = ins.id;
    const fechaLimpia = ins.fecha_inscripcion ? ins.fecha_inscripcion.split('T')[0] : '';
    
    form.value = { 
        estudiante_id: ins.estudiante_id,
        materia_id: ins.materia_id,
        periodo: ins.periodo,
        fecha_inscripcion: fechaLimpia,
            estado: String(ins.estado).toLowerCase().startsWith('act') ? 'activa' : 'inactiva'
    };
    mostrarModal.value = true;
};

const guardar = async () => {
    try {
        const payload = {
            estudiante_id: Number(form.value.estudiante_id),
            materia_id: Number(form.value.materia_id),
            periodo: String(form.value.periodo).trim(),
            fecha_inscripcion: form.value.fecha_inscripcion,
            estado: form.value.estado
        };

        if (editandoId.value) {
            await axios.put(`/api/inscripciones/${editandoId.value}`, payload);
            alert("Inscripción actualizada exitosamente");
        } else {
            await axios.post('/api/inscripciones', payload);
            alert("Inscripción registrada exitosamente");
        }
        
        await cargarDatos();
        mostrarModal.value = false;
    } catch (e) {
        const msg = e.response?.data?.error || 
                   e.response?.data?.message || 
                   "Error al procesar la solicitud";
        alert(msg);
    }
};

const eliminar = async (id) => {
    if (confirm('¿Desea retirar esta inscripción?')) {
        try {
            await axios.delete(`/api/inscripciones/${id}`);
            await cargarDatos();
            alert("Inscripción retirada correctamente");
        } catch (error) {
            alert("Error al retirar la inscripción");
        }
    }
};

onMounted(cargarDatos);
</script>

<template>
    <Head title="Inscripciones" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-sky-500 uppercase tracking-tight">Gestión de Inscripciones</h2>
        </template>

        <div class="py-12 bg-sky-50/30 min-h-screen">
            <div class="max-w-[1450px] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm rounded-2xl border border-sky-100">
                    
                    <div class="flex flex-wrap items-center gap-4 mb-8">
                        <button @click="abrirModal" class="bg-sky-500 text-white px-6 py-3 rounded-xl font-black uppercase text-xs shadow-lg transition hover:bg-sky-600">
                            + Nueva Inscripción
                        </button>

                        <input v-model="busqueda" type="text" placeholder="Buscar por nombre o cédula..." class="flex-1 min-w-[200px] border-sky-100 rounded-xl px-4 py-3 shadow-sm focus:ring-sky-500 focus:border-sky-500" />
                        
                        <select v-model="filtroGrado" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 text-sm">
                            <option value="">Todos los grados</option>
                            <option v-for="(nombre, num) in nombresGrados" :key="num" :value="num">
                                {{ nombre }}
                            </option>
                        </select>

                        <select v-model="filtroSeccion" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 text-sm">
                            <option value="">Todas las secciones</option>
                            <option value="A">Sección A</option>
                            <option value="B">Sección B</option>
                            <option value="C">Sección C</option>
                            <option value="U">Única</option>
                        </select>

                        <select v-model="filtroEstado" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 text-sm">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                        <select v-model="filtroPeriodo" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 text-sm">
                            <option value="">Todos los períodos</option>
                            <option v-for="p in periodosInscripciones" :key="p" :value="p">{{ p }}</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-sky-50">
                        <table class="min-w-full divide-y divide-sky-100">
                            <thead class="bg-sky-50/50">
                                <tr class="text-left text-[11px] font-black text-sky-700 uppercase tracking-widest">
                                    <th class="px-6 py-4">Estudiante / Año</th>
                                    <th class="px-6 py-4">Materia</th>
                                    <th class="px-6 py-4 text-center">Periodo / Fecha</th>
                                    <th class="px-6 py-4">Auditoría</th>
                                    <th class="px-6 py-4 text-center">Estado</th>
                                    <th class="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="ins in inscripcionesFiltradas" :key="ins.id" class="hover:bg-sky-50/40 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] font-black text-sky-400 font-mono">V-{{ ins.estudiante?.cedula }}</div>
                                            <div class="font-bold text-sky-900 text-sm">{{ ins.estudiante?.nombres }} {{ ins.estudiante?.apellidos }}</div>
                                        <div class="mt-1">
                                            <span class="bg-sky-100 text-sky-600 px-2 py-0.5 rounded text-[9px] font-black uppercase border border-sky-200">
                                                {{ ins.grado || ins.estudiante?.grado }} - SECC: {{ ins.seccion || ins.estudiante?.seccion }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                            <div class="text-xs font-bold text-gray-600">{{ ins.materia?.codigo_materia }}</div>
                                        <div class="text-xs font-bold text-gray-600">{{ ins.materia?.nombre }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-xs font-black text-sky-500">{{ ins.periodo }}</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">{{ formatearFechaFull(ins.fecha_inscripcion) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-bold uppercase">CREADO</span>
                                                <span class="text-[10px] text-gray-500">{{ formatearFechaFull(ins.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-sky-100 text-sky-500 px-1 py-0.5 rounded font-bold uppercase">EDITADO</span>
                                                <span class="text-[10px] text-sky-600">{{ formatearFechaFull(ins.updated_at) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="(String(ins.materia?.estado || ins.estado || '').toLowerCase().startsWith('act') && String(ins.estado || '').toLowerCase().startsWith('act')) ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase border tracking-widest">
                                            {{ (ins.estado || '').toString().toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <button @click="prepararEdicion(ins)" class="text-sky-500 font-black mr-4 text-xs uppercase hover:underline">Editar</button>
                                        <button @click="eliminar(ins.id)" class="text-red-400 font-black text-xs uppercase hover:underline">Eliminar</button>
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
                <h3 class="text-2xl font-black text-sky-500 uppercase mb-6 text-center">
                    {{ editandoId ? 'Editar Inscripción' : 'Nueva Inscripción' }}
                </h3>
                <form @submit.prevent="guardar" class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-sky-400 uppercase">Estudiante *</label>
                        <select v-model="form.estudiante_id" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold" required :disabled="!!editandoId">
                            <option value="">Seleccione Estudiante</option>
                            <option v-for="e in estudiantes.filter(s => String(s.estado).toLowerCase().includes('acti'))" :key="e.id" :value="e.id">
                                [{{ e.grado }} - {{ e.seccion }}] {{ e.apellidos }}, {{ e.nombres }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-sky-400 uppercase">Materia *</label>
                        <select v-model="form.materia_id" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold" required :disabled="!!editandoId">
                            <option value="">Seleccione Materia</option>
                            <option v-for="m in materiasDisponibles" :key="m.id" :value="m.id">
                                [{{ m.codigo_materia }}] {{ m.nombre }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black text-sky-400 uppercase">Periodo</label>
                            <input v-model="form.periodo" type="text" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold" required />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-sky-400 uppercase">Fecha Inscripción</label>
                            <input v-model="form.fecha_inscripcion" type="date" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold" required />
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-sky-400 uppercase">Estado</label>
                        <select v-model="form.estado" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-black text-sky-600">
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-sky-50">
                        <button type="button" @click="mostrarModal = false" class="text-sky-400 font-black text-xs uppercase">Cancelar</button>
                        <button type="submit" class="bg-sky-500 text-white px-8 py-3 rounded-xl font-black text-xs uppercase shadow-lg">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>