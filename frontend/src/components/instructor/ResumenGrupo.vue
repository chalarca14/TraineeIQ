<script setup>
import { ref, onMounted } from 'vue'
import { Info, BookOpen } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import EstudiantesGrupo from './EstudiantesGrupo.vue'
import UltimasEntregas from './UltimasEntregas.vue'
import { listarGuiasDeGrupo } from '@/services/guiaService'
import { formatearFecha } from '@/utils/fechas'

// Pestaña "Resumen" del grupo (instructor). Solo datos que el backend tiene (PLAN I-2): sin "Editar",
// ficha ni programa, sin el anillo de progreso (eso se calcula en Estadísticas, I-7) y sin las
// "Acciones rápidas" tachadas en el boceto.
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
  <div class="resumen">
    <PanelTarjeta titulo="Información del grupo" :icono="Info">
      <dl class="info">
        <div class="info__completa">
          <dt>Descripción</dt>
          <dd>{{ grupo.descripcion || 'Sin descripción.' }}</dd>
        </div>
        <div>
          <dt>Código de acceso</dt>
          <dd>{{ grupo.codigo_acceso }}</dd>
        </div>
        <div>
          <dt>Creado</dt>
          <dd>{{ formatearFecha(grupo.created_at) || '—' }}</dd>
        </div>
        <div>
          <dt>Semana liberada</dt>
          <dd>{{ grupo.semana_actual }}<template v-if="grupo.ultima_semana"> de {{ grupo.ultima_semana }}</template></dd>
        </div>
      </dl>
    </PanelTarjeta>

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

    <EstudiantesGrupo :estudiantes="grupo.estudiantes ?? []" />
    <UltimasEntregas :grupo-id="grupo.id" />
  </div>
</template>

<style scoped>
/* Dos columnas en escritorio; una en tablet y celular */
.resumen {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--espacio-md);
  align-items: start;
}

@media (max-width: 900px) {
  .resumen {
    grid-template-columns: minmax(0, 1fr);
  }
}

.info {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--espacio-md);
  margin: 0;
}

.info__completa {
  grid-column: 1 / -1;
}

.info dt {
  color: var(--color-text-secondary);
  font-size: .8rem;
}

.info dd {
  margin: .15rem 0 0;
  overflow-wrap: anywhere;
  white-space: pre-line;
}

@media (max-width: 480px) {
  .info {
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
