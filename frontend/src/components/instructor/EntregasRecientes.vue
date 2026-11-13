<script setup>
import { ref, watch } from 'vue'
import { Inbox, ChevronRight } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import { getEntregasGrupo } from '@/services/grupoService'
import { ESTADO_ENTREGA_INSTRUCTOR } from '@/utils/estadoEntrega'
import { fechaRelativa, horaCorta } from '@/utils/fechas'

// Las entregas más recientes de uno o varios grupos (en lugar de la "Actividad reciente" de los bocetos,
// que el backend no tiene). Lo usan el Resumen de un grupo y el dashboard del instructor (todos sus grupos).
// El backend manda las entregas de la más nueva a la más vieja: basta la primera página de cada grupo.
// Cada entrega lleva a "Semanas y proyectos" de su grupo con ese proyecto abierto.
const props = defineProps({
  // [{ id, nombre }]
  grupos: { type: Array, required: true },
  cuantas: { type: Number, default: 5 },
  titulo: { type: String, default: 'Últimas entregas' }
})

const entregas = ref([])
const cargando = ref(true)
const error = ref('')

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    const porGrupo = await Promise.all(props.grupos.map(async (g) => {
      const pagina = await getEntregasGrupo(g.id)
      // Se anota el grupo en cada entrega: la respuesta no lo trae y hace falta para el enlace
      return (pagina.data ?? []).map((e) => ({ ...e, grupo: g }))
    }))
    entregas.value = porGrupo.flat()
      .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
      .slice(0, props.cuantas)
  } catch {
    error.value = 'No se pudieron cargar las entregas.'
  } finally {
    cargando.value = false
  }
}

const destino = (e) => ({
  name: 'InstructorGrupoDetalle',
  params: { id: e.grupo.id },
  query: { tab: 'semanas', proyecto: e.mini_proyecto?.id }
})

// Se vuelve a pedir si cambian los grupos (por ejemplo, el dashboard termina de cargarlos)
watch(() => props.grupos.map((g) => g.id).join(','), cargar, { immediate: true })
</script>

<template>
  <PanelTarjeta :titulo="titulo" :icono="Inbox">
    <CargaEsqueleto v-if="cargando" texto="Cargando las entregas…">
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
      <li v-for="e in entregas" :key="`${e.grupo.id}-${e.id}`">
        <RouterLink :to="destino(e)" class="entrega">
          <span class="datos">
            <span class="fila">
              <strong>{{ e.estudiante?.name }}</strong>
              <span class="estado" :class="`estado--${e.estado}`">{{ ESTADO_ENTREGA_INSTRUCTOR[e.estado] ?? e.estado }}</span>
            </span>
            <span class="proyecto">
              {{ e.mini_proyecto?.titulo }}
              <template v-if="e.mini_proyecto?.semana"> · Semana {{ e.mini_proyecto.semana.numero_semana }}</template>
              <!-- Con varios grupos se dice de cuál es -->
              <template v-if="grupos.length > 1"> · {{ e.grupo.nombre }}</template>
            </span>
            <span class="fila">
              <!-- Un solo color para el puntaje, sin rangos: es orientativo, no una nota (PLAN, regla 5) -->
              <span v-if="e.puntaje != null" class="puntaje">{{ e.puntaje }} / 100 · orientativo</span>
              <span v-else class="puntaje puntaje--sin">Sin puntaje aún</span>
              <time :datetime="e.updated_at">{{ fechaRelativa(e.updated_at) }}, {{ horaCorta(e.updated_at) }}</time>
            </span>
          </span>
          <ChevronRight :size="18" class="flecha" aria-hidden="true" />
        </RouterLink>
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

.lista li + li {
  border-top: 1px solid var(--color-border);
}

/* Toda la entrega es el enlace: área grande para tocar en el celular */
.entrega {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  margin: 0 calc(var(--espacio-sm) * -1);
  padding: var(--espacio-sm);
  border-radius: 10px;
  color: inherit;
  text-decoration: none;
}

.entrega:hover,
.entrega:focus-visible {
  background: color-mix(in srgb, var(--color-card) 60%, transparent);
}

.datos {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: .2rem;
  min-width: 0;
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

.flecha {
  flex-shrink: 0;
  color: var(--color-text-secondary);
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
