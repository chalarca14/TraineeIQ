<script setup>
import { ref, computed, onMounted } from 'vue'
import { History, Trash2 } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoBloque from '@/components/ui/EsqueletoBloque.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import FiltrosHistorial from '@/components/historial/FiltrosHistorial.vue'
import ItemHistorial from '@/components/historial/ItemHistorial.vue'
import BorrarConversacionModal from '@/components/chat/BorrarConversacionModal.vue'
import { useHistorial } from '@/composables/useHistorial'
import { dentroDeUltimosDias } from '@/utils/fechas'

const { entradas, cargando, error, hayMas, cargandoMas, errorMas, cargar, cargarMas, quitar } = useHistorial()

// Conversación que se va a borrar (abre la misma ventana del chat). null = ventana cerrada
const aBorrar = ref(null)

// La ventana ya la borró en el backend: se cierra y se quita de la lista
function alBorrar({ id }) {
  aBorrar.value = null
  quitar(id)
}

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
      <!-- Filtros arriba y la lista de chats, como el contenido real -->
      <template #cargando>
        <EsqueletoBloque alto="2.6rem" radio="12px" />
        <EsqueletoLista :filas="5" />
      </template>
      <section class="contenido">
        <FiltrosHistorial v-model:pestana="pestana" v-model:dias="dias" v-model:busqueda="busqueda" />

        <p v-if="visibles.length === 0" class="vacio-filtro">
          {{ busqueda ? 'Nada coincide con tu búsqueda.' : 'No hay actividad en este filtro. Prueba con otro rango de fechas.' }}
        </p>

        <ul v-else class="lista">
          <li v-for="e in visibles" :key="e.clave" class="fila">
            <ItemHistorial :entrada="e" class="fila__item" />
            <!-- Al lado de la tarjeta y no dentro: un botón no puede ir dentro de un enlace -->
            <button type="button" class="fila__borrar" title="Borrar conversación"
              :aria-label="`Borrar la conversación: ${e.descripcion}`" @click="aBorrar = { id: e.id }">
              <Trash2 :size="18" aria-hidden="true" />
            </button>
          </li>
        </ul>
        <BorrarConversacionModal v-if="aBorrar" :conversacion="aBorrar" @cerrar="aBorrar = null" @borrado="alBorrar" />

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

/* Tarjeta a la izquierda y papelera a la derecha, centrada en su alto */
.fila {
  display: flex;
  align-items: center;
  gap: .6rem;
}

.fila__item {
  flex: 1;
  min-width: 0;
}

/* Misma papelera que las tarjetas de guía y de grupo (GuiaCard, GrupoInstructorCard) */
.fila__borrar {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--color-card);
  border-radius: 12px;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: .2s;
}

.fila__borrar:hover,
.fila__borrar:focus-visible {
  border-color: var(--color-danger);
  color: var(--color-danger);
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
