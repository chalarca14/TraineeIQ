<script setup>
import { ref, computed, onMounted } from 'vue'
import { History, Rocket } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import FiltrosHistorial from '@/components/historial/FiltrosHistorial.vue'
import ItemHistorial from '@/components/historial/ItemHistorial.vue'
import { useHistorial } from '@/composables/useHistorial'
import { dentroDeUltimosDias } from '@/utils/fechas'

const { entradas, cargando, error, aviso, hayMas, cargandoMas, errorMas, cargar, cargarMas } = useHistorial()

const pestana = ref('todos')
const dias = ref(30) // como en el boceto: "Últimos 30 días"
const busqueda = ref('')

// Los filtros solo recortan la lista que ya está en memoria: no vuelven a llamar al backend.
const visibles = computed(() => {
  const texto = busqueda.value.trim().toLowerCase()
  return entradas.value.filter((e) =>
    (pestana.value === 'todos' || e.categoria === pestana.value) &&
    dentroDeUltimosDias(e.fecha, dias.value) &&
    (!texto || `${e.titulo} ${e.descripcion} ${e.etiqueta}`.toLowerCase().includes(texto))
  )
})

// Mini proyectos: todavía no hay datos reales (ver nota en la plantilla).
const enMiniProyectos = computed(() => pestana.value === 'miniproyecto')

onMounted(cargar)
</script>

<template>
  <main class="historial">
    <EncabezadoPagina titulo="Mi historial" :icono="History"
      descripcion="Consulta tu actividad de aprendizaje, retoma lo que necesites y sigue avanzando en tu camino." />

    <EstadoVista :cargando="cargando" :error="error" :vacio="entradas.length === 0" :icono="History"
      texto-cargando="Cargando tu historial..." texto-vacio="Aún no tienes actividad."
      descripcion-vacio="Cuando converses con la IA o pidas recomendaciones, aparecerá aquí." @reintentar="cargar">
      <section class="contenido">
        <p v-if="aviso" class="aviso">{{ aviso }}</p>

        <FiltrosHistorial v-model:pestana="pestana" v-model:dias="dias" v-model:busqueda="busqueda" />

        <!-- Los mini proyectos del aprendiz se conectan cuando el backend filtre GET /mini-proyectos
             por usuario; hoy traerlos exigiría una petición por guía. -->
        <div v-if="enMiniProyectos" class="pronto">
          <Rocket :size="28" />
          <p>Pronto verás aquí tus mini proyectos.</p>
        </div>

        <p v-else-if="visibles.length === 0" class="vacio-filtro">
          {{ busqueda ? 'Nada coincide con tu búsqueda.' : 'No hay actividad en este filtro. Prueba con otro rango de fechas.' }}
        </p>

        <ul v-else class="lista">
          <li v-for="e in visibles" :key="e.clave"><ItemHistorial :entrada="e" /></li>
        </ul>

        <div v-if="hayMas && !enMiniProyectos" class="mas">
          <p v-if="errorMas" class="error-mas" role="alert">{{ errorMas }}</p>
          <button type="button" class="btn btn-secundario" :disabled="cargandoMas" @click="cargarMas">
            {{ cargandoMas ? 'Cargando...' : 'Cargar más' }}
          </button>
        </div>
      </section>
    </EstadoVista>
  </main>
</template>

<style scoped>
.historial {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  max-width: 1000px;
  margin: 0 auto;
  padding: clamp(1rem, 4vw, 2.5rem);
}

.contenido {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.lista {
  display: flex;
  flex-direction: column;
  gap: .8rem;
  list-style: none;
}

.vacio-filtro,
.pronto {
  color: var(--color-text-secondary);
}

.pronto {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
  padding: 2rem 1rem;
  border: 1px dashed var(--color-card);
  border-radius: 16px;
  text-align: center;
}

.aviso {
  padding: .6rem .8rem;
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-warning) 14%, transparent);
  color: var(--color-warning);
  font-size: .85rem;
}

.mas {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
}

.error-mas {
  color: var(--color-danger);
  font-size: .85rem;
}
</style>
