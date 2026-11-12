<script setup>
import { ref, onMounted } from 'vue'
import { Inbox } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import { getEntregasGrupo } from '@/services/grupoService'
import { ESTADO_ENTREGA_INSTRUCTOR } from '@/utils/estadoEntrega'
import { fechaRelativa, horaCorta } from '@/utils/fechas'

// Las 5 entregas más recientes del grupo (en lugar de las "Últimas actividades" del boceto, que el
// backend no tiene). El backend ya las manda de la más nueva a la más vieja: solo se toma la primera
// página. La lista completa, por semana y proyecto, es la pestaña "Semanas y proyectos" (Tarea I-4).
const props = defineProps({ grupoId: { type: Number, required: true } })

const CUANTAS = 5
const entregas = ref([])
const cargando = ref(true)
const error = ref('')

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    const pagina = await getEntregasGrupo(props.grupoId)
    entregas.value = (pagina.data ?? []).slice(0, CUANTAS)
  } catch {
    error.value = 'No se pudieron cargar las entregas.'
  } finally {
    cargando.value = false
  }
}

onMounted(cargar)
</script>

<template>
  <PanelTarjeta titulo="Últimas entregas" :icono="Inbox">
    <CargaEsqueleto v-if="cargando" texto="Cargando las últimas entregas…">
      <EsqueletoLista :filas="3" :con-icono="false" />
    </CargaEsqueleto>

    <div v-else-if="error" class="error" role="alert">
      <p>{{ error }}</p>
      <button type="button" class="btn btn-secundario btn-sm" @click="cargar">Reintentar</button>
    </div>

    <p v-else-if="!entregas.length" class="vacio">
      Aún no hay entregas. Aparecen cuando un estudiante guarda el repositorio de un mini proyecto.
    </p>

    <ul v-else class="lista">
      <li v-for="e in entregas" :key="e.id">
        <div class="fila">
          <strong>{{ e.estudiante?.name }}</strong>
          <span class="estado" :class="`estado--${e.estado}`">{{ ESTADO_ENTREGA_INSTRUCTOR[e.estado] ?? e.estado }}</span>
        </div>
        <span class="proyecto">
          {{ e.mini_proyecto?.titulo }}
          <template v-if="e.mini_proyecto?.semana"> · Semana {{ e.mini_proyecto.semana.numero_semana }}</template>
        </span>
        <div class="fila fila--pie">
          <!-- Un solo color para el puntaje, sin rangos: es orientativo, no una nota (PLAN, regla 5) -->
          <span v-if="e.puntaje != null" class="puntaje">{{ e.puntaje }} / 100 · orientativo</span>
          <span v-else class="puntaje puntaje--sin">Sin puntaje aún</span>
          <time :datetime="e.updated_at">{{ fechaRelativa(e.updated_at) }}, {{ horaCorta(e.updated_at) }}</time>
        </div>
      </li>
    </ul>
  </PanelTarjeta>
</template>

<style scoped>
.vacio,
.proyecto,
time {
  color: var(--color-text-secondary);
  font-size: .85rem;
}

.error {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
  color: var(--color-danger);
  font-size: .9rem;
}

.lista {
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
  list-style: none;
}

.lista li {
  display: flex;
  flex-direction: column;
  gap: .2rem;
  padding: var(--espacio-sm) 0;
  border-bottom: 1px solid var(--color-border);
}

.lista li:last-child {
  border-bottom: none;
}

.fila {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--espacio-xs) var(--espacio-sm);
}

.proyecto {
  overflow-wrap: anywhere;
}

.estado {
  padding: .15rem .6rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-info) 18%, transparent);
  color: var(--color-info);
  font-size: .75rem;
  font-weight: 600;
}

.estado--evaluado {
  background: color-mix(in srgb, var(--color-success) 18%, transparent);
  color: var(--color-success);
}

.estado--error {
  background: color-mix(in srgb, var(--color-danger) 18%, transparent);
  color: var(--color-danger);
}

.puntaje {
  color: var(--color-primary-light);
  font-size: .85rem;
  font-weight: 600;
}

.puntaje--sin {
  color: var(--color-text-secondary);
  font-weight: 400;
}
</style>
