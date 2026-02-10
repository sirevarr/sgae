<template>
  <div class="estudiantes-container">
    <div class="header">
      <h1>Gestión de Estudiantes</h1>
      <button @click="abrirModalNuevo" class="btn-primary">
        + Nuevo Estudiante
      </button>
    </div>

    <div class="search-bar">
      <input 
        v-model="filtros.buscar" 
        @input="buscar"
        type="text" 
        placeholder="Buscar por nombre, cédula..."
        class="search-input"
      >
      <select v-model="filtros.estado" @change="buscar" class="filter-select">
        <option value="">Todos los estados</option>
        <option value="activo">Activo</option>
        <option value="inactivo">Inactivo</option>
      </select>
    </div>

    <div v-if="loading" class="loading">Cargando...</div>
    <div v-else class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Cédula / Género</th>
            <th>Nombre Completo</th>
            <th>Nacimiento</th>
            <th>Ubicación</th>
            <th>Contacto</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="estudiante in estudiantes" :key="estudiante.id">
            <td class="id-text">#{{ estudiante.id }}</td>
            <td>
              <div class="cell-data">
                <strong>{{ estudiante.cedula }}</strong>
                <span class="sub-text">Género: {{ estudiante.genero }}</span>
              </div>
            </td>
            <td>
              <div class="cell-data">
                <span class="main-name">{{ estudiante.nombres }} {{ estudiante.apellidos }}</span>
                <small class="text-muted">Registrado: {{ formatDate(estudiante.created_at) }}</small>
              </div>
            </td>
            <td>
              <div class="cell-data">
                <span>📅 {{ estudiante.fecha_nacimiento }}</span>
                <span class="sub-text">📍 {{ estudiante.lugar_nacimiento }}</span>
              </div>
            </td>
            <td>
              <div class="address-box" :title="estudiante.direccion">
                {{ estudiante.direccion }}
              </div>
            </td>
            <td>
              <div class="cell-data">
                <span>📞 {{ estudiante.telefono }}</span>
                <span class="sub-text">📧 {{ estudiante.email }}</span>
              </div>
            </td>
            <td>
              <span :class="['badge', `badge-${estudiante.estado}`]">
                {{ estudiante.estado }}
              </span>
            </td>
            <td class="actions">
              <button @click="editar(estudiante)" class="btn-edit">✏️</button>
              <button @click="eliminar(estudiante.id)" class="btn-delete">🗑️</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="mostrarFormulario" class="modal">
      <div class="modal-content">
        <h2>{{ estudianteEditando ? 'Editar' : 'Nuevo' }} Estudiante</h2>
        <form @submit.prevent="guardar" class="form-grid">
          <div class="form-row">
            <div class="form-group">
              <label>Cédula</label>
              <input v-model="form.cedula" type="text" required>
            </div>
            <div class="form-group">
              <label>Género</label>
              <select v-model="form.genero" required>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Nombres</label>
              <input v-model="form.nombres" type="text" required>
            </div>
            <div class="form-group">
              <label>Apellidos</label>
              <input v-model="form.apellidos" type="text" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Fecha Nac.</label>
              <input v-model="form.fecha_nacimiento" type="date" required>
            </div>
            <div class="form-group">
              <label>Lugar Nac.</label>
              <input v-model="form.lugar_nacimiento" type="text" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Teléfono</label>
              <input v-model="form.telefono" type="text" required>
            </div>
            <div class="form-group">
              <label>Email</label>
              <input v-model="form.email" type="email" required>
            </div>
          </div>
          <div class="form-group">
            <label>Dirección</label>
            <textarea v-model="form.direccion" rows="2" required></textarea>
          </div>
          <div class="form-actions">
            <button type="button" @click="cerrarFormulario" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Guardar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useEstudianteStore } from '../stores/estudianteStore'

const store = useEstudianteStore()
const mostrarFormulario = ref(false)
const estudianteEditando = ref(null)
const filtros = ref({ buscar: '', estado: '' })

const form = ref({
  cedula: '', nombres: '', apellidos: '', genero: 'M',
  fecha_nacimiento: '', lugar_nacimiento: '', direccion: '',
  email: '', telefono: '', estado: 'activo'
})

const estudiantes = computed(() => store.estudiantes)
const loading = computed(() => store.loading)

onMounted(() => store.fetchEstudiantes())
const buscar = () => store.fetchEstudiantes(filtros.value)
const cerrarFormulario = () => { mostrarFormulario.value = false; resetForm(); }
const resetForm = () => {
  form.value = {
    cedula: '', nombres: '', apellidos: '', genero: 'M',
    fecha_nacimiento: '', lugar_nacimiento: '', direccion: '',
    email: '', telefono: '', estado: 'activo'
  }
}

const abrirModalNuevo = () => { estudianteEditando.value = null; resetForm(); mostrarFormulario.value = true; }
const editar = (est) => { estudianteEditando.value = est; form.value = { ...est }; mostrarFormulario.value = true; }
const eliminar = async (id) => { if(confirm('¿Eliminar?')) await store.eliminarEstudiante(id) }

const guardar = async () => {
  if (estudianteEditando.value) await store.actualizarEstudiante(estudianteEditando.value.id, form.value)
  else await store.crearEstudiante(form.value)
  cerrarFormulario()
}

const formatDate = (dateStr) => {
  if (!dateStr) return 'N/A';
  return new Date(dateStr).toLocaleDateString();
}
</script>

<style scoped>
.estudiantes-container { padding: 1rem; max-width: 100%; }
.table-container { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
th { background: #f8f9fa; padding: 12px; border-bottom: 2px solid #dee2e6; text-align: left; white-space: nowrap; }
td { padding: 12px; border-bottom: 1px solid #eee; }

.cell-data { display: flex; flex-direction: column; gap: 2px; }
.sub-text { font-size: 0.75rem; color: #777; }
.main-name { font-weight: bold; color: #2c3e50; }
.id-text { color: #aaa; font-family: monospace; }

.address-box { 
  max-width: 200px; 
  font-size: 0.75rem; 
  color: #555; 
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.badge { padding: 4px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
.badge-activo { background: #d4edda; color: #155724; }

.modal { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: white; padding: 20px; border-radius: 10px; width: 550px; max-height: 95vh; overflow-y: auto; }
.form-row { display: flex; gap: 10px; margin-bottom: 10px; }
.form-group { flex: 1; display: flex; flex-direction: column; gap: 4px; }
input, select, textarea { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
</style>