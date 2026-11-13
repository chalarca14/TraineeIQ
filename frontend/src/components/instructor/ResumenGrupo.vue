<script setup>
import { ref, onMounted } from 'vue'
import { BookOpen } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import EstudiantesGrupo from './EstudiantesGrupo.vue'
import EntregasRecientes from './EntregasRecientes.vue'
import { listarGuiasDeGrupo } from '@/services/guiaService'
import { formatearFecha } from '@/utils/fechas'

// Pestaña "Resumen" del grupo (instructor). Solo datos que el backend tiene (PLAN I-2): sin "Editar",
// ficha ni programa, sin el anillo de progreso (eso se calcula en Estadísticas, I-7) y sin las
// "Acciones rápidas" tachadas en el boceto. Tampoco repite "Información del grupo": descripción,
// código, fecha y semana ya están en la cabecera, que se ve en todas las pestañas.
const props = defineProps({ grupo: { type: Object, required: true } })
// "Ver todas" de las guías cambia a la pestaña Guías: la vista es la que maneja las pestañas
const emit = defineEmits(['ir-a'])

// Cada bloque carga lo suyo: si las guías fallan, el resto del resumen se sigue viendo
const guias = ref([])
const cargandoGuias = ref(true)
const errorGuias = ref('')

async function cargarGuias() {
  cargandoGuias.value = true
  errorGuias.value = ''
  try {
    guias.value = await listarGuiasDeGrupo(props.grupo.id)
  } catch {
    errorGuias.value = 'No se pudieron cargar las guías.'
  } finally {
    cargandoGuias.value = false
  }
}

onMounted(cargarGuias)
</script>

<template>
  <!-- Dos columnas independientes (no filas de una cuadrícula): así cada tarjeta queda pegada a la de
       arriba aunque la de al lado sea más alta, sin huecos -->
  <div class="resumen">
    <div class="columna">
      <PanelTarjeta :titulo="cargandoGuias || errorGuias ? 'Guías asignadas' : `Guías asignadas (${guias.length})`"
        :icono="BookOpen">
        <template v-if="guias.length" #accion>
          <button type="button" class="enlace" @click="emit('ir-a', 'guias')">Ver todas</button>
        </template>

        <CargaEsqueleto v-if="cargandoGuias" texto="Cargando las guías del grupo…">
          <EsqueletoLista :filas="2" :lineas="2" />
        </CargaEsqueleto>
        <div v-else-if="errorGuias" class="error" role="alert">
          <p>{{ errorGuias }}</p>
          <button type="button" class="btn btn-secundario btn-sm" @click="cargarGuias">Reintentar</button>
        </div>
        <p v-else-if="!guias.length" class="vacio">
          Este grupo aún no tiene guías. Podrás asignarlas en la pestaña Guías.
        </p>
        <ul v-else class="guias">
          <li v-for="g in guias" :key="g.id">
            <span class="guias__icono" aria-hidden="true"><BookOpen :size="18" /></span>
            <div class="guias__texto">
              <strong>{{ g.nombre }}</strong>
              <span>
                {{ g.semanas_totales }} {{ g.semanas_totales === 1 ? 'semana' : 'semanas' }}
                <template v-if="g.created_at"> · asignada el {{ formatearFecha(g.created_at) }}</template>
              </span>
            </div>
          </li>
        </ul>
      </PanelTarjeta>

      <EntregasRecientes :grupos="[{ id: grupo.id, nombre: grupo.nombre }]" />
    </div>

    <div class="columna">
      <EstudiantesGrupo :estudiantes="grupo.estudiantes ?? []" />
    </div>
  </div>
</template>

<style scoped>
/* Dos columnas en escritorio; en tablet y celular se apilan (guías, entregas y luego estudiantes) */
.resumen {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--espacio-md);
  align-items: start;
}

.columna {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
  min-width: 0;
}

@media (max-width: 900px) {
  .resumen {
    grid-template-columns: minmax(0, 1fr);
  }
}

.enlace {
  border: none;
  background: none;
  color: var(--color-primary-light);
  font: inherit;
  font-size: .88rem;
  cursor: pointer;
}

.enlace:hover {
  text-decoration: underline;
}

.vacio {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.error {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
  color: var(--color-danger);
  font-size: .9rem;
}

.guias {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

.guias li {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
}

.guias__icono {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-primary) 25%, var(--color-card));
  color: var(--color-primary-light);
}

.guias__texto {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.guias__texto span {
  color: var(--color-text-secondary);
  font-size: .82rem;
}
</style>
