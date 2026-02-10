<template>
  <div class="container">
    <div class="header-actions">
      <h2>Gestión de Materias</h2>
      <button @click="abrirModalCrear" class="btn-primary">+ Nueva Materia</button>
    </div>

    <div class="filters-bar">
      <input 
        v-model="filtroNombre" 
        type="text" 
        placeholder="Buscar por nombre o código..."
        class="search-input"
      />
      <select v-model="filtroEstado" class="status-select">
        <option value="">Todos los estados</option>
        <option value="activa">Activa</option>
        <option value="inactiva">Inactiva</option>
      </select>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Créditos</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="materia in materiasFiltradas" :key="materia.id">
            <td><strong>{{ materia.codigo_materia }}</strong></td>
            <td>{{ materia.nombre }}</td>
            <td>{{ materia.creditos }}</td>
            <td>{{ materia.descripcion || 'N/A' }}</td>
            <td>
              <span :class="['badge', materia.estado]">
                {{ materia.estado }}
              </span>
            </td>
            <td>
              <button @click="prepararEdicion(materia)" class="btn-edit">Editar</button>
              <button @click="eliminar(materia.id)" class="btn-delete">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="mostrarModal" class="modal-overlay">
      <div class="modal-content">
        <h3>{{ editandoId ? 'Editar Materia' : 'Nueva Materia' }}</h3>
        <form @submit.prevent="guardarMateria">
          <div class="form-group">
            <label>Código:</label>
            <input v-model="nuevaMateria.codigo_materia" :disabled="editandoId" required />
          </div>
          <div class="form-group">
            <label>Nombre:</label>
            <input v-model="nuevaMateria.nombre" required />
          </div>
          <div class="form-group">
            <label>Descripción:</label>
            <textarea v-model="nuevaMateria.descripcion"></textarea>
          </div>
          <div class="form-group">
            <label>Créditos:</label>
            <input type="number" v-model.number="nuevaMateria.creditos" required />
          </div>
          <div class="form-group">
            <label>Estado:</label>
            <select v-model="nuevaMateria.estado">
              <option value="activa">Activa</option>
              <option value="inactiva">Inactiva</option>
            </select>
          </div>
          <div class="modal-buttons">
            <button type="button" @click="cerrarModal" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-success">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useMateriaStore } from '../stores/materiaStore';

const materiaStore = useMateriaStore();
const mostrarModal = ref(false);
const editandoId = ref(null);

// Filtros
const filtroNombre = ref('');
const filtroEstado = ref('');

const nuevaMateria = ref({
  codigo_materia: '',
  nombre: '',
  descripcion: '',
  creditos: 1,
  estado: 'activa'
});

onMounted(() => {
  materiaStore.fetchMaterias();
});

// Lógica de filtrado reactivo (No necesita llamar al API cada vez)
const materiasFiltradas = computed(() => {
  return materiaStore.materias.filter(m => {
    const matchNombre = m.nombre.toLowerCase().includes(filtroNombre.value.toLowerCase()) || 
                        m.codigo_materia.toLowerCase().includes(filtroNombre.value.toLowerCase());
    const matchEstado = filtroEstado.value === '' || m.estado === filtroEstado.value;
    return matchNombre && matchEstado;
  });
});

const abrirModalCrear = () => {
  editandoId.value = null;
  nuevaMateria.value = { codigo_materia: '', nombre: '', descripcion: '', creditos: 1, estado: 'activa' };
  mostrarModal.value = true;
};

const prepararEdicion = (materia) => {
  editandoId.value = materia.id;
  nuevaMateria.value = { ...materia };
  mostrarModal.value = true;
};

const guardarMateria = async () => {
  if (editandoId.value) {
    await materiaStore.actualizarMateria(editandoId.value, nuevaMateria.value);
  } else {
    await materiaStore.crearMateria(nuevaMateria.value);
  }
  cerrarModal();
};

const eliminar = async (id) => {
  if (confirm('¿Eliminar materia?')) {
    await materiaStore.eliminarMateria(id);
  }
};

const cerrarModal = () => {
  mostrarModal.value = false;
};
</script>