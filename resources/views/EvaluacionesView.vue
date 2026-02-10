<template>
  <div class="container">
    <div class="header-actions">
      <h2>Gestión de Evaluaciones</h2>
      <button @click="abrirModal" class="btn-primary">+ Registrar Calificación</button>
    </div>

    <div class="filters-bar">
      <input v-model="busqueda" type="text" placeholder="Buscar estudiante o materia..." class="search-input" />
      <select v-model="filtroEstado" class="status-select">
        <option value="">Todos los estados</option>
        <option value="aprobado">Aprobados</option>
        <option value="reprobado">Reprobados</option>
      </select>
    </div>

    <div class="table-container">
      <table v-if="evaluaciones.length > 0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Estudiante / Materia</th>
            <th>Fecha</th>
            <th>P1</th>
            <th>P2</th>
            <th>Final</th>
            <th>Promedio</th>
            <th>Estado</th>
            <th>Observaciones</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in evaluacionesFiltradas" :key="item.id">
            <td class="text-muted">#{{ item.id }}</td>
            <td>
              <div class="cell-info">
                <strong>{{ item.inscripcion?.estudiante?.nombres }} {{ item.inscripcion?.estudiante?.apellidos }}</strong>
                <small>{{ item.inscripcion?.materia?.nombre }}</small>
              </div>
            </td>
            <td>{{ item.fecha }}</td>
            <td>{{ item.nota_parcial1 }}</td>
            <td>{{ item.nota_parcial2 }}</td>
            <td>{{ item.nota_final }}</td>
            <td><strong>{{ item.promedio }}</strong></td>
            <td>
              <span :class="['badge', item.estado]">{{ item.estado }}</span>
            </td>
            <td>
              <p class="obs-text" :title="item.observaciones">{{ item.observaciones || '-' }}</p>
            </td>
            <td class="actions">
              <button @click="prepararEdicion(item)" class="btn-edit">✏️</button>
              <button @click="eliminar(item.id)" class="btn-delete">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty-state">No hay registros encontrados.</div>
    </div>

    <div v-if="mostrarModal" class="modal-overlay">
      <div class="modal-content">
        <h3>{{ editandoId ? 'Editar Evaluación' : 'Nueva Evaluación' }}</h3>
        <hr />
        <form @submit.prevent="guardar">
          
          <div class="form-row" v-if="!editandoId">
            <div class="form-group">
              <label>Estudiante:</label>
              <select v-model="idEstudianteSeleccionado" @change="cargarMateriasAlumno" required>
                <option value="">Seleccione alumno...</option>
                <option v-for="est in estudiantes" :key="est.id" :value="est.id">
                  {{ est.cedula }} - {{ est.nombres }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group" v-if="!editandoId">
              <label>Materia:</label>
              <select v-model="form.inscripcion_id" :disabled="!idEstudianteSeleccionado" required>
                <option value="">Seleccione materia...</option>
                <option v-for="ins in inscripcionesAlumno" :key="ins.id" :value="ins.id">
                  {{ ins.materia.nombre }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Fecha:</label>
              <input type="date" v-model="form.fecha" required>
            </div>
          </div>

          <div class="grid-inputs">
            <div class="form-group">
              <label>P1</label>
              <input type="number" step="0.1" v-model.number="form.nota_parcial1" min="0" max="20">
            </div>
            <div class="form-group">
              <label>P2</label>
              <input type="number" step="0.1" v-model.number="form.nota_parcial2" min="0" max="20">
            </div>
            <div class="form-group">
              <label>Final</label>
              <input type="number" step="0.1" v-model.number="form.nota_final" min="0" max="20">
            </div>
          </div>

          <div class="form-group">
            <label>Observaciones:</label>
            <textarea v-model="form.observaciones" placeholder="Detalles adicionales..." rows="2"></textarea>
          </div>

          <div class="promedio-preview">
            <strong>Promedio: {{ promedioRealTime }}</strong>
          </div>

          <div class="modal-buttons">
            <button type="button" @click="cerrarModal" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-success">
              {{ editandoId ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../services/api';

const evaluaciones = ref([]);
const estudiantes = ref([]);
const inscripcionesAlumno = ref([]);
const mostrarModal = ref(false);
const editandoId = ref(null);
const busqueda = ref('');
const filtroEstado = ref('');
const idEstudianteSeleccionado = ref('');

const form = ref({
  inscripcion_id: '',
  fecha: new Date().toISOString().substr(0, 10),
  nota_parcial1: 0,
  nota_parcial2: 0,
  nota_final: 0,
  observaciones: ''
});

// Promedio en tiempo real
const promedioRealTime = computed(() => {
  const n1 = parseFloat(form.value.nota_parcial1) || 0;
  const n2 = parseFloat(form.value.nota_parcial2) || 0;
  const nf = parseFloat(form.value.nota_final) || 0;
  return ((n1 + n2 + nf) / 3).toFixed(2);
});

const cargarDatos = async () => {
  try {
    const resEval = await api.get('/evaluaciones');
    evaluaciones.value = resEval.data.data || [];
    const resEst = await api.get('/estudiantes');
    estudiantes.value = Array.isArray(resEst.data) ? resEst.data : resEst.data.data;
  } catch (error) {
    console.error("Error al cargar:", error);
  }
};

const cargarMateriasAlumno = async () => {
  if (!idEstudianteSeleccionado.value) return;
  try {
    const res = await api.get(`/evaluaciones/inscripciones-alumno/${idEstudianteSeleccionado.value}`);
    inscripcionesAlumno.value = res.data.data || [];
  } catch (error) {
    console.error("Error al cargar materias:", error);
  }
};

const evaluacionesFiltradas = computed(() => {
  return evaluaciones.value.filter(e => {
    const nombre = `${e.inscripcion?.estudiante?.nombres} ${e.inscripcion?.estudiante?.apellidos}`.toLowerCase();
    const materia = (e.inscripcion?.materia?.nombre || '').toLowerCase();
    const coincideBusqueda = nombre.includes(busqueda.value.toLowerCase()) || materia.includes(busqueda.value.toLowerCase());
    const coincideEstado = filtroEstado.value === '' || e.estado === filtroEstado.value;
    return coincideBusqueda && coincideEstado;
  });
});

const abrirModal = () => {
  editandoId.value = null;
  resetForm();
  mostrarModal.value = true;
};

const prepararEdicion = (item) => {
  editandoId.value = item.id;
  form.value = { ...item };
  mostrarModal.value = true;
};

// FUNCIÓN CERRAR MODAL (CORREGIDA)
const cerrarModal = () => {
  mostrarModal.value = false;
  editandoId.value = null;
  idEstudianteSeleccionado.value = '';
  resetForm();
};

const resetForm = () => {
  form.value = {
    inscripcion_id: '',
    fecha: new Date().toISOString().substr(0, 10),
    nota_parcial1: 0,
    nota_parcial2: 0,
    nota_final: 0,
    observaciones: ''
  };
};

const guardar = async () => {
  try {
    if (editandoId.value) {
      await api.put(`/evaluaciones/${editandoId.value}`, form.value);
    } else {
      await api.post('/evaluaciones', form.value);
    }
    await cargarDatos();
    cerrarModal(); // Cerramos después de guardar éxito
  } catch (error) {
    alert("Error al guardar: " + (error.response?.data?.message || "Revisa los campos"));
  }
};

const eliminar = async (id) => {
  if (confirm('¿Eliminar evaluación?')) {
    await api.delete(`/evaluaciones/${id}`);
    await cargarDatos();
  }
};

onMounted(cargarDatos);
</script>

<style scoped>
.container { padding: 20px; font-family: sans-serif; }
.header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.filters-bar { display: flex; gap: 10px; margin-bottom: 20px; }
.search-input { flex: 1; padding: 10px; border-radius: 8px; border: 1px solid #ddd; }
.status-select { padding: 10px; border-radius: 8px; border: 1px solid #ddd; }

.table-container { background: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }

.obs-text { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #666; font-size: 0.8rem; margin: 0; }
.badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
.badge.aprobado { background: #d4edda; color: #155724; }
.badge.reprobado { background: #f8d7da; color: #721c24; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 30px; border-radius: 15px; width: 550px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.form-row { display: flex; gap: 15px; margin-bottom: 15px; }
.form-group { flex: 1; display: flex; flex-direction: column; gap: 5px; }
.grid-inputs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }

input, select, textarea { padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
.promedio-preview { background: #f9f9f9; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px dashed #007bff; }
.modal-buttons { display: flex; justify-content: flex-end; gap: 10px; }

.btn-primary { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
.btn-secondary { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
.btn-success { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
.btn-edit { background: none; border: none; cursor: pointer; font-size: 1.2rem; }
.btn-delete { background: none; border: none; cursor: pointer; font-size: 1.2rem; }
</style>