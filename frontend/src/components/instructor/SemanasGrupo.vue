<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { CalendarRange, RefreshCw, BookOpen, Info } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import FilaSemana from './FilaSemana.vue'
import LiberarSemanaModal from './LiberarSemanaModal.vue'
import { useSemanasGrupo } from '@/composables/useSemanasGrupo'

// Pestaña "Semanas y proyectos" (Tarea I-4), la pantalla central del instructor: liberar semanas en
// orden y ver, por proyecto, quién entregó. Sin "Nuevo proyecto", filtros, "Aprobado" ni colores
// por rango de puntaje (PLAN I-4).
const props = defineProps({ grupo: { type: Object, required: true } })
// semana-liberada: la vista actualiza semana_actual del grupo (y con ella la cabecera)
const emit = defineEmits(['semana-liberada', 'ir-a'])
const route = useRoute()

const { cargando, error, cargar, semanas, entregasPorProyecto, guias } = useSemanasGrupo(() => props.grupo)

const actual = computed(() => props.grupo.semana_actual ?? 1)
const ultima = computed(() => props.grupo.ultima_semana ?? null)
const estudiantes = computed(() => props.grupo.estudiantes ?? [])
// Solo la siguiente a la actual, y solo si existe: sin saltar semanas ni pasarse de la última
const siguiente = computed(() => (ultima.value && actual.value < ultima.value ? actual.value + 1 : null))

// Semanas expandidas (varias a la vez) y el proyecto con sus entregas a la vista (uno a la vez)
const abiertas = ref(new Set())
const proyectoAbierto = ref(null)
const porLiberar = ref(null)

function alternar(numero) {
  const nuevas = new Set(abiertas.value)
  nuevas.has(numero) ? nuevas.delete(numero) : nuevas.add(numero)
  abiertas.value = nuevas
}

const alternarProyecto = (id) => { proyectoAbierto.value = proyectoAbierto.value === id ? null : id }

async function alLiberar(numero) {
  porLiberar.value = null
  emit('semana-liberada', numero)
  // La recién liberada queda abierta: el instructor ve al instante sus proyectos (ya estaban cargados)
  abiertas.value = new Set([...abiertas.value, numero])
  // El botón "Liberar" que tenía el foco desapareció: el foco pasa a la semana recién liberada
  await nextTick()
  document.querySelector(`[data-semana="${numero}"] .alternar`)?.focus()
}

// Enlace directo ?tab=semanas&proyecto=ID (lo usará la campanita, Tarea I-5): abre esa semana y
// ese proyecto y lo trae a la vista
async function abrirDesdeEnlace() {
  const id = Number(route.query.proyecto)
  if (!id) return
  const semana = semanas.value.find((s) => s.porGuia.some((g) => g.proyectos.some((p) => p.id === id)))
  if (!semana) return
  abiertas.value = new Set([...abiertas.value, semana.numero])
  proyectoAbierto.value = id
  await nextTick()
  document.getElementById(`proyecto-${id}`)?.scrollIntoView({ block: 'center' })
}

onMounted(async () => {
  abiertas.value = new Set([actual.value])
  await cargar()
  abrirDesdeEnlace()
})
</script>

<template>
  <section class="semanas-grupo" aria-labelledby="titulo-semanas">
    <header class="cabecera">
      <div>
        <h2 id="titulo-semanas"><CalendarRange :size="20" aria-hidden="true" /> Semanas y proyectos</h2>
        <p>Libera las semanas en orden y revisa quién entregó cada mini proyecto.</p>
      </div>
      <div class="acciones">
        <span class="actual">Semana actual: <strong>{{ actual }}</strong><template v-if="ultima"> de {{ ultima }}</template></span>
        <button type="button" class="btn btn-secundario btn-sm" :disabled="cargando" @click="cargar">
          <RefreshCw :size="15" aria-hidden="true" /> Actualizar
        </button>
      </div>
    </header>

    <EstadoVista :cargando="cargando" :error="error" :vacio="!guias.length" :icono="BookOpen"
      texto-cargando="Cargando las semanas del grupo…" texto-vacio="Asigna una guía para poder liberar semanas"
      descripcion-vacio="Las semanas y sus mini proyectos salen de las guías del grupo." @reintentar="cargar">
      <template #cargando><EsqueletoLista :filas="4" /></template>
      <template #vacio>
        <button type="button" class="btn btn-primario btn-sm" @click="emit('ir-a', 'guias')">Ir a Guías</button>
      </template>

      <p v-if="!siguiente" class="nota" role="note">
        <Info :size="16" aria-hidden="true" /> Ya liberaste todas las semanas de las guías del grupo.
      </p>

      <ol class="lista">
        <FilaSemana v-for="s in semanas" :key="s.numero" :semana="s" :estudiantes="estudiantes"
          :entregas-por-proyecto="entregasPorProyecto" :abierta="abiertas.has(s.numero)"
          :proyecto-abierto="proyectoAbierto" :liberable="s.numero === siguiente"
          @alternar="alternar(s.numero)" @liberar="porLiberar = $event" @alternar-proyecto="alternarProyecto" />
      </ol>
    </EstadoVista>

    <LiberarSemanaModal v-if="porLiberar" :grupo-id="grupo.id" :numero="porLiberar" @cerrar="porLiberar = null"
      @liberada="alLiberar" />
  </section>
</template>

<style scoped>
.semanas-grupo {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
  padding: 1.2rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

.cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--espacio-sm);
}

h2 {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: 1.1rem;
}

h2 svg {
  color: var(--color-primary-light);
}

.cabecera p,
.actual,
.nota {
  color: var(--color-text-secondary);
  font-size: .88rem;
}

.acciones {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
}

.actual strong {
  color: var(--color-text);
}

.nota {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md);
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-info) 12%, transparent);
}

.lista {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

@media (max-width: 520px) {
  .semanas-grupo {
    padding: var(--espacio-md);
  }
}
</style>
