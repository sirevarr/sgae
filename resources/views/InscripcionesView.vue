<template>
  <div class="container">
    <div class="header-actions">
      <h2>Gestión de Inscripciones</h2>
      <button @click="abrirModalCrear" class="btn-primary">+ Nueva Inscripción</button>
    </div>

    <div class="filters-bar">
      <div class="search-box">
        <input 
          v-model="busqueda" 
          type="text" 
          placeholder="Buscar por estudiante, cédula o materia..."
          class="search-input"
        />
      </div>
      <div class="filter-box">
        <select v-model="filtroEstado" class="status-select">
          <option value="">Todos los estados</option>
          <option value="activa">Activa</option>
          <option value="retirada">Retirada</option>
        </select>
      </div>
    </div>

    <div class="table-container">
      <table v-if="!inscripcionStore.loading">
        <thead>
          <tr>
            <th>Estudiante</th>
            <th>Materia</th>
            <th>Periodo</th>
            <th>Sección</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ins in inscripcionesFiltradas" :key="ins.id">
            <td>
              <strong>{{ ins.estudiante?.cedula }}</strong><br>
              <small>{{ ins.estudiante?.nombres }} {{ ins.estudiante?.apellidos }}</small>
            </td>
            <td>{{ ins.materia?.nombre }}</td>
            <td>{{ ins.periodo }}</td>
            <td>{{ ins.seccion }}</td>
            <td>
              <span :class="['badge', ins.estado]">{{ ins.estado }}</span>
            </td>
            <td>
              <button @click="prepararEdicion(ins)" class="btn-edit">Editar</button>
              <button @click="eliminar(ins.id)" class="btn-delete">Retirar</button>
            </td>
          </tr>
          <tr v-if="inscripcionesFiltradas.length === 0">
            <td colspan="6" class="text-center">No se encontraron inscripciones con esos filtros.</td>
          </tr>
        </tbody>
      </table>
      <div v-else class="loader">Procesando información...</div>
    </div>

    <div v-if="mostrarModal" class="modal-overlay">
      <div class="modal-content">
        <h3>{{ editandoId ? 'Modificar Inscripción' : 'Nueva Inscripción' }}</h3>
        <hr>
        <form @submit.prevent="guardarInscripcion">
          
          <div class="form-group">
            <label>Estudiante:</label>
            <select v-model="nuevaInscripcion.estudiante_id" :disabled="editandoId" required>
              <option value="">Seleccione un estudiante...</option>
              <option v-for="est in inscripcionStore.estudiantes" :key="est.id" :value="est.id">
                {{ est.cedula }} - {{ est.nombres }} {{ est.apellidos }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Materia:</label>
            <select v-model="nuevaInscripcion.materia_id" :disabled="editandoId" required>
              <option value="">Seleccione una materia...</option>
              <option v-for="mat in inscripcionStore.materias" :key="mat.id" :value="mat.id">
                {{ mat.codigo_materia }} - {{ mat.nombre }}
              </option>
            </select>
          </div>

          <div class="grid-inputs">
            <div class="form-group">
              <label>Periodo:</label>
              <input v-model="nuevaInscripcion.periodo" placeholder="Ej: 2026-1" required />
            </div>
            <div class="form-group">
              <label>Sección:</label>
              <input v-model="nuevaInscripcion.seccion" placeholder="Ej: A" required maxlength="10" />
            </div>
          </div>

          <div class="form-group">
            <label>Estado:</label>
            <select v-model="nuevaInscripcion.estado">
              <option value="activa">Activa</option>
              <option value="retirada">Retirada</option>
            </select>
          </div>

          <div class="modal-buttons">
            <button type="button" @click="cerrarModal" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-success" :disabled="inscripcionStore.loading">
              {{ editandoId ? 'Actualizar Cambios' : 'Confirmar Registro' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useInscripcionStore } from '../stores/inscripcionStore';
import api from '../services/api'; // Necesario para el PUT directo si no está en el store

const inscripcionStore = useInscripcionStore();
const mostrarModal = ref(false);
const editandoId = ref(null);
const busqueda = ref('');
const filtroEstado = ref('');

const nuevaInscripcion = ref({
  estudiante_id: '',
  materia_id: '',
  periodo: '2026-1',
  seccion: '',
  fecha_inscripcion: new Date().toISOString().substr(0, 10),
  estado: 'activa'
});

onMounted(async () => {
  await inscripcionStore.fetchInscripciones();
  await inscripcionStore.fetchFormData();
});

// LÓGICA DE FILTRADO COMBINADO
const inscripcionesFiltradas = computed(() => {
  return inscripcionStore.inscripciones.filter(ins => {
    const term = busqueda.value.toLowerCase();
    const nombreCompleto = `${ins.estudiante?.nombres} ${ins.estudiante?.apellidos}`.toLowerCase();
    const cedula = ins.estudiante?.cedula || '';
    const materia = ins.materia?.nombre?.toLowerCase() || '';
    
    const coincideBusqueda = nombreCompleto.includes(term) || cedula.includes(term) || materia.includes(term);
    const coincideEstado = filtroEstado.value === '' || ins.estado === filtroEstado.value;
    
    return coincideBusqueda && coincideEstado;
  });
});

const abrirModalCrear = () => {
  editandoId.value = null;
  nuevaInscripcion.value = { 
    estudiante_id: '', materia_id: '', periodo: '2026-1', 
    seccion: '', fecha_inscripcion: new Date().toISOString().substr(0, 10), estado: 'activa' 
  };
  mostrarModal.value = true;
};

const prepararEdicion = (ins) => {
  editandoId.value = ins.id;
  // Copiamos los datos para no modificar la tabla directamente
  nuevaInscripcion.value = { ...ins };
  mostrarModal.value = true;
};

const cerrarModal = () => {
  mostrarModal.value = false;
  editandoId.value = null;
};

const guardarInscripcion = async () => {
  try {
    if (editandoId.value) {
      // Petición de actualización
      await api.put(`/inscripciones/${editandoId.value}`, nuevaInscripcion.value);
      alert('Registro actualizado correctamente');
    } else {
      // Petición de creación
      await inscripcionStore.crearInscripcion(nuevaInscripcion.value);
      alert('Inscripción exitosa');
    }
    await inscripcionStore.fetchInscripciones(); // Recargar tabla
    cerrarModal();
  } catch (error) {
    const msg = error.response?.data?.error || 'Error al procesar la solicitud';
    alert(msg);
  }
};

const eliminar = async (id) => {
  if (confirm('¿Desea retirar esta inscripción? El registro permanecerá pero cambiará de estado.')) {
    try {
      await inscripcionStore.eliminarInscripcion(id);
    } catch (error) {
      alert('No se pudo completar la acción');
    }
  }
};
</script>