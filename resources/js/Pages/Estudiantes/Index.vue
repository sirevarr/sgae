<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { useEstudianteStore } from '@/stores/estudianteStore';

// Inicialización del Store
const store = useEstudianteStore();
const mostrarFormulario = ref(false);
const estudianteEditando = ref(null);
const filtros = ref({ buscar: '', estado: '' });

// Estructura del formulario (Todos tus campos de la DB)
const form = ref({
    cedula: '', nombres: '', apellidos: '', genero: 'M',
    fecha_nacimiento: '', lugar_nacimiento: '', direccion: '',
    email: '', telefono: '', estado: 'activo'
});

// Computed properties desde el store
const estudiantes = computed(() => store.estudiantes);
const loading = computed(() => store.loading);

// Acciones
onMounted(() => store.fetchEstudiantes());
const buscar = () => store.fetchEstudiantes(filtros.value);

const abrirModalNuevo = () => {
    estudianteEditando.value = null;
    resetForm();
    mostrarFormulario.value = true;
};

const editar = (est) => {
    estudianteEditando.value = est;
    form.value = { ...est };
    mostrarFormulario.value = true;
};

const cerrarFormulario = () => {
    mostrarFormulario.value = false;
    resetForm();
};

const resetForm = () => {
    form.value = {
        cedula: '', nombres: '', apellidos: '', genero: 'M',
        fecha_nacimiento: '', lugar_nacimiento: '', direccion: '',
        email: '', telefono: '', estado: 'activo'
    };
};

const guardar = async () => {
    if (estudianteEditando.value) {
        await store.actualizarEstudiante(estudianteEditando.value.id, form.value);
    } else {
        await store.crearEstudiante(form.value);
    }
    cerrarFormulario();
};

const eliminar = async (id) => {
    if (confirm('¿Estás seguro de eliminar este registro?')) {
        await store.eliminarEstudiante(id);
    }
};
</script>

<template>
    <Head title="Estudiantes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Módulo de Gestión de Estudiantes
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    
                    <div class="flex flex-col md:flex-row justify-between mb-6 gap-4">
                        <button @click="abrirModalNuevo" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold transition">
                            + Nuevo Estudiante
                        </button>
                        
                        <div class="flex gap-2">
                            <input 
                                v-model="filtros.buscar" 
                                @input="buscar"
                                type="text" 
                                placeholder="Cédula o nombre..." 
                                class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <select v-model="filtros.estado" @change="buscar" class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estudiante</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cédula/Género</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="est in estudiantes" :key="est.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900">{{ est.nombres }} {{ est.apellidos }}</div>
                                        <div class="text-xs text-gray-500">Nacido en: {{ est.lugar_nacimiento }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ est.cedula }}</div>
                                        <div class="text-xs text-gray-500">Género: {{ est.genero }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">📞 {{ est.telefono }}</div>
                                        <div class="text-xs text-gray-500">📧 {{ est.email }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${est.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
                                            {{ est.estado }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <button @click="editar(est)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                        <button @click="eliminar(est.id)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarFormulario" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-bottom flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">{{ estudianteEditando ? 'Actualizar' : 'Registrar' }} Estudiante</h3>
                    <button @click="cerrarFormulario" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                </div>
                
                <form @submit.prevent="guardar" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-600">Cédula</label>
                        <input v-model="form.cedula" type="text" class="mt-1 border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-600">Género</label>
                        <select v-model="form.genero" class="mt-1 border-gray-300 rounded-md shadow-sm">
                            <option value="M">Masculino</option>
                            <option value="F">Femenino</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-600">Nombres</label>
                        <input v-model="form.nombres" type="text" class="mt-1 border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-600">Apellidos</label>
                        <input v-model="form.apellidos" type="text" class="mt-1 border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-600">Lugar de Nacimiento</label>
                        <input v-model="form.lugar_nacimiento" type="text" class="mt-1 border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-600">Teléfono</label>
                        <input v-model="form.telefono" type="text" class="mt-1 border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div class="flex flex-col md:col-span-2">
                        <label class="text-sm font-semibold text-gray-600">Dirección</label>
                        <textarea v-model="form.direccion" rows="2" class="mt-1 border-gray-300 rounded-md shadow-sm" required></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-3 mt-4">
                        <button type="button" @click="cerrarFormulario" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 shadow-md transition">Guardar Estudiante</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>