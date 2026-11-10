<script setup>
import { ref, computed, onMounted } from 'vue'
import { History } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import FiltrosHistorial from '@/components/historial/FiltrosHistorial.vue'
import ItemHistorial from '@/components/historial/ItemHistorial.vue'
import { useHistorial } from '@/composables/useHistorial'
import { dentroDeUltimosDias } from '@/utils/fechas'

const { entradas, cargando, error, hayMas, cargandoMas, errorMas, cargar, cargarMas } = useHistorial()

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

onMounted(cargar)
</script>

<template>
  <main class="historial">
    <EncabezadoPagina titulo="Mi historial" :icono="History"
      descripcion="Consulta tus conversaciones con la IA, retómalas donde las dejaste y sigue aprendiendo." />

    <EstadoVista :cargando="cargando" :error="error" :vacio="entradas.length === 0" :icono="History"
      texto-cargando="Cargando tu historial..." texto-vacio="Aún no tienes actividad."
      descripcion-vacio="Cuando converses con la IA, tus chats aparecerán aquí." @reintentar="cargar">
      <section class="contenido">
        <FiltrosHistorial v-model:pestana="pestana" v-model:dias="dias" v-model:busqueda="busqueda" />

        <p v-if="visibles.length === 0" class="vacio-filtro">
          {{ busqueda ? 'Nada coincide con tu búsqueda.' : 'No hay actividad en este filtro. Prueba con otro rango de fechas.' }}
        </p>

        <ul v-else class="lista">
          <li v-for="e in visibles" :key="e.clave"><ItemHistorial :entrada="e" /></li>
        </ul>

        <!-- "Cargar más" solo aparece si el backend trae otra página (next_page_url) -->
        <div v-if="hayMas" class="mas">
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

.vacio-filtro {
  color: var(--color-text-secondary);
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
