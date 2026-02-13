<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';

// --- ESTADOS ---
const evaluaciones = ref([]);
const inscripciones = ref([]);
const mostrarModal = ref(false);
const editandoId = ref(null);
const busqueda = ref('');
const filtroEstado = ref(''); 
const filtroGrado = ref(''); 
const filtroSeccion = ref(''); 
const filtroPeriodo = ref('');

const form = ref({
    inscripcion_id: '', 
    nota_parcial1: 0, 
    nota_parcial2: 0, 
    nota_final: 0, 
    promedio: 0, 
    estado: 'pendiente', 
    fecha: new Date().toISOString().split('T')[0], 
    observaciones: ''
});

// --- AYUDANTES DE FORMATO ---
const formatearFecha = (fecha) => {
    if (!fecha) return 'N/A';
    const d = new Date(fecha);
    const dia = String(d.getUTCDate()).padStart(2, '0');
    const mes = String(d.getUTCMonth() + 1).padStart(2, '0');
    const anio = d.getUTCFullYear();
    return `${dia}/${mes}/${anio}`;
};

// Corrige la nomenclatura de los grados (1er, 2do, etc.)
const obtenerNombreGrado = (n) => {
    const sufijos = { 1: '1er', 2: '2do', 3: '3er', 4: '4to', 5: '5to' };
    return `${sufijos[n]} Año`;
};

// --- CÁLCULO AUTOMÁTICO ---
watch([() => form.value.nota_parcial1, () => form.value.nota_parcial2, () => form.value.nota_final], () => {
    const p1 = parseFloat(form.value.nota_parcial1) || 0;
    const p2 = parseFloat(form.value.nota_parcial2) || 0;
    const nf = parseFloat(form.value.nota_final) || 0;
    const resultado = (p1 + p2 + nf) / 3;
    form.value.promedio = resultado.toFixed(2);
    form.value.estado = resultado >= 9.5 ? 'aprobado' : 'reprobado';
});

const cargarDatos = async () => {
    try {
        const [resEva, resIns] = await Promise.all([
            axios.get('/api/evaluaciones'),
            axios.get('/api/inscripciones')
        ]);
        evaluaciones.value = resEva.data.data || resEva.data;
        // FILTRAR: Solo inscripciones ACTIVAS
        inscripciones.value = (resIns.data.data || resIns.data).filter(i =>
            String(i.estado).toLowerCase().startsWith('act')
        );
    } catch (e) { 
        console.error("Error al cargar datos", e); 
    }
};

// PDF export removed: function deleted

// Periodos únicos desde las inscripciones para poblar el filtro
const periodosDisponibles = computed(() => {
    const set = new Set((inscripciones.value || []).map(i => i.periodo).filter(Boolean));
    return Array.from(set).sort();
});

const evaluacionesFiltradas = computed(() => {
    return evaluaciones.value.filter(item => {
        const buscar = busqueda.value.toLowerCase().trim();
        const est = item.inscripcion?.estudiante;
        const ins = item.inscripcion;
        const info = `${est?.nombres} ${est?.apellidos} ${est?.cedula} ${item.inscripcion?.materia?.nombre}`.toLowerCase();

        // Preferimos leer grado/sección históricos desde la inscripción;
        // si no existen, caemos al estudiante por compatibilidad.
        const gradoIns = ins?.grado ?? est?.grado;
        const seccionIns = ins?.seccion ?? est?.seccion;

        const coincideTexto = info.includes(buscar);
        const coincideEstado = filtroEstado.value === '' || String(item.estado).toLowerCase() === filtroEstado.value.toLowerCase();
        const coincideGrado = filtroGrado.value === '' || gradoIns === filtroGrado.value;
        const coincideSeccion = filtroSeccion.value === '' || seccionIns === filtroSeccion.value;
        const coincidePeriodo = filtroPeriodo.value === '' || (item.inscripcion?.periodo || '') === filtroPeriodo.value;

        return coincideTexto && coincideEstado && coincideGrado && coincideSeccion && coincidePeriodo;
    });
});

const abrirModal = () => {
    editandoId.value = null;
    form.value = { 
        inscripcion_id: '', nota_parcial1: 0, nota_parcial2: 0, 
        nota_final: 0, promedio: 0, estado: 'pendiente', 
        fecha: new Date().toISOString().split('T')[0], 
        observaciones: '' 
    };
    mostrarModal.value = true;
};

const prepararEdicion = (item) => {
    editandoId.value = item.id;
    form.value = { 
        ...item,
        fecha: item.fecha ? item.fecha.split('T')[0] : new Date().toISOString().split('T')[0],
        observaciones: item.observaciones || ''
    };
    mostrarModal.value = true;
};

const guardar = async () => {
    try {
        if (!form.value.inscripcion_id) {
            alert('Debes seleccionar una inscripción.');
            return;
        }

        const payload = { ...form.value, estado: form.value.estado.toLowerCase() };
        if (editandoId.value) {
            await axios.put(`/api/evaluaciones/${editandoId.value}`, payload);
            alert('Evaluación actualizada con éxito');
        } else {
            const response = await axios.post('/api/evaluaciones', payload);
            alert('Evaluación creada exitosamente');
        }
        await cargarDatos();
        mostrarModal.value = false;
    } catch (e) { 
        console.error('Error:', e);
        if (e.response?.status === 422) {
            const errorMsg = e.response.data?.error || 
                           (e.response.data?.errors?.inscripcion_id?.[0]) ||
                           'Error al procesar la solicitud';
            
            // MENSAJE ESPECÍFICO para evaluación duplicada
            if (errorMsg.includes('unique')) {
                alert('Evaluación duplicada para este estudiante en esta materia');
            } else {
                alert(errorMsg);
            }
        } else {
            alert('Error al procesar la solicitud');
        }
    }
};

const eliminar = async (id) => {
    if (confirm('¿Está seguro de eliminar este registro de notas?')) {
        try {
            await axios.delete(`/api/evaluaciones/${id}`);
            await cargarDatos();
            alert('Evaluación eliminada con éxito');
        } catch (e) {
            alert("No se pudo eliminar el registro.");
        }
    }
};

onMounted(cargarDatos);
</script>

<template>
    <Head title="Evaluaciones" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-sky-500 uppercase tracking-tight">Registro Académico</h2>
            </div>
        </template>

        <div class="py-12 bg-sky-50/30 min-h-screen">
            <div class="max-w-[1550px] mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm rounded-2xl border border-sky-100">
                    
                    <div class="flex flex-wrap items-center gap-4 mb-8">
                        <button @click="abrirModal" class="bg-sky-500 hover:bg-sky-600 text-white px-6 py-3 rounded-xl font-black uppercase text-xs tracking-widest shadow-lg transition-all">
                            + Cargar Notas
                        </button>
                        
                        <input v-model="busqueda" type="text" placeholder="Buscar por nombre o cédula..." class="flex-1 border-sky-100 focus:ring-sky-500 rounded-xl px-4 py-3 shadow-sm min-w-[200px]" />
                        
                        <select v-model="filtroGrado" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 px-4 shadow-sm text-sm">
                            <option value="">Todos los grados</option>
                            <option v-for="n in 5" :key="n" :value="obtenerNombreGrado(n)">{{ obtenerNombreGrado(n) }}</option>
                        </select>

                        <select v-model="filtroSeccion" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 px-4 shadow-sm text-sm">
                            <option value="">Todas las secciones</option>
                            <option v-for="letra in ['A', 'B', 'C', 'D', 'U']" :key="letra" :value="letra">Sección {{ letra }}</option>
                        </select>

                        <select v-model="filtroPeriodo" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 px-4 shadow-sm text-sm">
                            <option value="">Todos los períodos</option>
                            <option v-for="p in periodosDisponibles" :key="p" :value="p">{{ p }}</option>
                        </select>

                        <select v-model="filtroEstado" class="border-sky-100 rounded-xl text-sky-700 font-bold py-3 px-4 shadow-sm text-sm">
                            <option value="">Todos los estados</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="reprobado">Reprobado</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-sky-100">
                            <thead class="bg-sky-50/50">
                                <tr class="text-left text-[11px] font-black text-sky-700 uppercase tracking-widest">
                                    <th class="px-6 py-4">Estudiante / Grupo</th>
                                    <th class="px-6 py-4 text-center">Calificaciones</th>
                                    <th class="px-6 py-4 text-center">Promedio</th>
                                    <th class="px-6 py-4">Materia / Fecha</th>
                                    <th class="px-6 py-4">Auditoría</th>
                                    <th class="px-6 py-4 text-center">Estado</th>
                                    <th class="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 bg-white">
                                <tr v-for="item in evaluacionesFiltradas" :key="item.id" class="hover:bg-sky-50/40 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] font-black text-sky-400 font-mono tracking-tighter uppercase mb-0.5">
                                            V-{{ item.inscripcion?.estudiante?.cedula }}
                                        </div>
                                        <div class="font-bold text-sky-900 text-sm">
                                            {{ item.inscripcion?.estudiante?.nombres }} {{ item.inscripcion?.estudiante?.apellidos }}
                                        </div>
                                        <div class="mt-1">
                                            <span class="bg-sky-100 text-sky-600 px-2 py-0.5 rounded text-[9px] font-black uppercase border border-sky-200">
                                                {{ item.inscripcion?.grado ?? item.inscripcion?.estudiante?.grado }} - "{{ item.inscripcion?.seccion ?? item.inscripcion?.estudiante?.seccion }}"
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="bg-slate-50 border px-2 py-1 rounded text-[11px] font-mono font-bold mr-1">P1: {{ item.nota_parcial1 }}</span>
                                        <span class="bg-slate-50 border px-2 py-1 rounded text-[11px] font-mono font-bold mr-1">P2: {{ item.nota_parcial2 }}</span>
                                        <span class="bg-sky-50 border border-sky-100 px-2 py-1 rounded text-[11px] font-mono font-black text-sky-600">F: {{ item.nota_final }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-black text-lg text-sky-600">{{ item.promedio }}</td>
                                    <td class="px-6 py-4">
                                        <div class="text-[10px] text-sky-400 font-black tracking-tighter">{{ item.inscripcion?.materia?.nombre }}</div>
                                        <div class="text-[10px] font-bold text-gray-400">{{ formatearFecha(item.fecha) }}</div>
                                        <div v-if="item.observaciones" class="mt-2 text-sm text-gray-600 italic max-w-[380px] break-words">
                                            {{ item.observaciones }}
                                        </div>
                                        <div v-else class="mt-2 text-sm text-gray-400 italic">-</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-bold uppercase">CREADO</span>
                                                <span class="text-[10px] text-gray-500">{{ formatearFecha(item.created_at) }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] bg-sky-100 text-sky-500 px-1 py-0.5 rounded font-bold uppercase">EDITADO</span>
                                                <span class="text-[10px] text-sky-600">{{ formatearFecha(item.updated_at) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="String(item.estado).toLowerCase() === 'aprobado' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200'" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase border tracking-widest">
                                            {{ item.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap text-xs font-black uppercase">
                                        <button @click="prepararEdicion(item)" class="text-sky-500 mr-3 hover:underline">EDITAR</button>
                                        <button @click="eliminar(item.id)" class="text-red-400 hover:underline">ELIMINAR</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModal" class="fixed inset-0 bg-sky-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl max-w-lg w-full p-8 shadow-2xl border border-sky-100">
                <h3 class="text-2xl font-black text-sky-500 uppercase mb-6 text-center">Panel de Calificaciones</h3>
                <form @submit.prevent="guardar" class="space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Alumno e Inscripción</label>
                        <select v-model="form.inscripcion_id" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-sm" required :disabled="!!editandoId">
                            <option value="">Seleccione Estudiante</option>
                            <option v-for="i in inscripciones" :key="i.id" :value="i.id">
                                [{{ i.estudiante?.cedula }}] — {{ i.estudiante?.apellidos }}, {{ i.estudiante?.nombres }} — {{ i.materia?.codigo_materia }} {{ i.materia?.nombre }} — {{ i.periodo }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Fecha de Evaluación</label>
                            <input v-model="form.fecha" type="date" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold" required />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Promedio Automático</label>
                            <div class="w-full rounded-xl bg-sky-50 py-2.5 text-center font-black text-sky-600 text-xl border border-sky-100">{{ form.promedio }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase text-center block">Parcial 1</label>
                            <input v-model="form.nota_parcial1" type="number" step="0.1" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-center" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-gray-400 uppercase text-center block">Parcial 2</label>
                            <input v-model="form.nota_parcial2" type="number" step="0.1" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-bold text-center" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-sky-500 uppercase text-center block">Nota Final</label>
                            <input v-model="form.nota_final" type="number" step="0.1" class="w-full rounded-xl border-sky-100 bg-sky-50/30 font-black text-center" />
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-sky-400 uppercase ml-1">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="2" class="w-full rounded-xl border-sky-100 bg-sky-50/30 text-sm" placeholder="Opcional..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-sky-50">
                        <button type="button" @click="mostrarModal = false" class="text-sky-400 font-black uppercase text-xs hover:text-sky-600 transition">Cerrar</button>
                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 text-white px-8 py-3 rounded-xl font-black uppercase text-xs shadow-lg transition-all">
                            {{ editandoId ? 'Actualizar Nota' : 'Guardar Registro' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>