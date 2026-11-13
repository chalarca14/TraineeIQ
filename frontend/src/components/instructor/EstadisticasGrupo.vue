<script setup>
import { computed, onMounted } from 'vue'
import { GraduationCap, Send, Star, Rocket, Inbox, BarChart3, PieChart, CalendarRange, BookOpen } from 'lucide-vue-next'
import StatCard from '@/components/dashboard/StatCard.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import GraficoBarras from '@/components/ui/GraficoBarras.vue'
import BarraEstadoEntregas from './BarraEstadoEntregas.vue'
import EntregasRecientes from './EntregasRecientes.vue'
import { useSemanasGrupo } from '@/composables/useSemanasGrupo'
import { calcularEstadisticas } from '@/utils/estadisticasGrupo'

// Pestaña "Estadísticas" del grupo (Tarea I-7). Todo se calcula aquí con las entregas: reutiliza la carga
// de "Semanas y proyectos" (todas las páginas de entregas y los proyectos de cada guía).
// Sin Ranking (competitivo: contradice el Modo Grupo), sin gráfica en el tiempo, "+8%" ni Nivel/XP.
const props = defineProps({ grupo: { type: Object, required: true } })
const emit = defineEmits(['ir-a'])

const { cargando, error, cargar, guias, entregas } = useSemanasGrupo(() => props.grupo)

// Proyectos de las semanas ya liberadas (los demás no se esperan todavía)
const proyectosLiberados = computed(() => guias.value
  .flatMap((d) => d.proyectos)
  .filter((p) => (p.semana?.numero_semana ?? Infinity) <= (props.grupo.semana_actual ?? 1)).length)

const e = computed(() => calcularEstadisticas({
  entregas: entregas.value,
  totalEstudiantes: props.grupo.estudiantes?.length ?? 0,
  semanaActual: props.grupo.semana_actual ?? 1,
  proyectosLiberados: proyectosLiberados.value
}))

const cifras = computed(() => [
  { etiqueta: 'Estudiantes', valor: e.value.totalEstudiantes, detalle: 'del grupo', icono: GraduationCap, color: 'primary' },
  { etiqueta: 'Tasa de entrega', valor: `${e.value.tasaEntrega}%`, detalle: `${e.value.entregaron} de ${e.value.totalEstudiantes} estudiantes entregaron`, icono: Send, color: 'success' },
  { etiqueta: 'Promedio de puntaje', valor: e.value.promedio ?? '—', detalle: e.value.promedio == null ? 'aún sin evaluaciones · orientativo' : `de ${e.value.evaluadas} evaluadas · orientativo`, icono: Star, color: 'warning' },
  { etiqueta: 'Mini proyectos liberados', valor: e.value.proyectosLiberados, detalle: `hasta la semana ${props.grupo.semana_actual ?? 1}`, icono: Rocket, color: 'info' },
  { etiqueta: 'Entregas totales', valor: e.value.entregasTotales, detalle: 'repositorios guardados', icono: Inbox, color: 'primary' }
])

const porSemana = computed(() => e.value.porSemana.map((s) => ({
  etiqueta: `S${s.semana}`,
  valor: s.porcentaje,
  texto: `${s.porcentaje}%`,
  ayuda: `Semana ${s.semana}: ${s.estudiantes} de ${e.value.totalEstudiantes} estudiantes entregaron (${s.porcentaje}%)`
})))

const distribucion = computed(() => e.value.distribucion.map((d) => ({ etiqueta: d.etiqueta, valor: d.cantidad })))

onMounted(cargar)
</script>

<template>
  <EstadoVista :cargando="cargando" :error="error" :vacio="!guias.length" :icono="BookOpen"
    texto-cargando="Calculando las estadísticas del grupo…" texto-vacio="Aún no hay estadísticas"
    descripcion-vacio="Asigna una guía y libera semanas: las estadísticas salen de las entregas de tus aprendices."
    @reintentar="cargar">
    <template #cargando>
      <div class="cifras"><EsqueletoTarjeta v-for="n in 5" :key="n" /></div>
      <div class="graficas">
        <EsqueletoTarjeta :lineas="4" :con-icono="false" alto="260px" />
        <EsqueletoTarjeta :lineas="4" :con-icono="false" alto="260px" />
      </div>
    </template>
    <template #vacio>
      <button type="button" class="btn btn-primario btn-sm" @click="emit('ir-a', 'guias')">Ir a Guías</button>
    </template>

    <div class="estadisticas">
      <section class="cifras" aria-label="Resumen del grupo">
        <StatCard v-for="c in cifras" :key="c.etiqueta" v-bind="c" />
      </section>

      <div class="graficas">
        <PanelTarjeta titulo="Entregas por semana" :icono="CalendarRange">
          <p class="ayuda">Porcentaje del grupo que entregó algún mini proyecto de cada semana liberada.</p>
          <GraficoBarras :datos="porSemana" :maximo="100" columna-etiqueta="Semana" columna-valor="Entregaron"
            descripcion="Porcentaje de estudiantes que entregaron algún mini proyecto en cada semana liberada" />
        </PanelTarjeta>

        <PanelTarjeta titulo="Distribución de puntajes" :icono="BarChart3">
          <p class="ayuda">
            Cuántas entregas evaluadas hay en cada rango. Puntaje orientativo de la IA, no una nota.
          </p>
          <p v-if="!e.evaluadas" class="vacio">Aún no hay entregas evaluadas.</p>
          <GraficoBarras v-else :datos="distribucion" columna-etiqueta="Rango de puntaje" columna-valor="Entregas"
            descripcion="Cantidad de entregas evaluadas por rango de puntaje" />
        </PanelTarjeta>
      </div>

      <PanelTarjeta titulo="Estado de las entregas" :icono="PieChart">
        <BarraEstadoEntregas :estado="e.estado" />
      </PanelTarjeta>

      <EntregasRecientes :grupos="[{ id: grupo.id, nombre: grupo.nombre }]" :cuantas="5" titulo="Actividad reciente" />
    </div>
  </EstadoVista>
</template>

<style scoped>
.estadisticas {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
}

.cifras {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: var(--espacio-md);
}

.graficas {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--espacio-md);
  align-items: start;
}

@media (max-width: 900px) {
  .graficas {
    grid-template-columns: minmax(0, 1fr);
  }
}

.ayuda,
.vacio {
  color: var(--color-text-secondary);
  font-size: .85rem;
}
</style>
