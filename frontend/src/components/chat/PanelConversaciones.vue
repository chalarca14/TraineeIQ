<script setup>
import { ref, computed } from 'vue'
import { X, History, Trash2 } from 'lucide-vue-next'
import NuevaConversacion from './NuevaConversacion.vue'
import ItemConversacion from './ItemConversacion.vue'
import BorrarConversacionModal from './BorrarConversacionModal.vue'
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'

// Panel lateral de conversaciones (como el de ChatGPT). En escritorio es una columna fija;
// en pantallas angostas es un cajón que abre y cierra la vista (props/eventos). La lista llega por
// props; lo único propio es qué ventana de borrado está abierta y el tipo de chat elegido arriba.
const props = defineProps({
  conversaciones: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  activaId: { type: Number, default: null },
  abierto: { type: Boolean, default: false }
})
const emit = defineEmits(['cerrar', 'reintentar', 'eliminada', 'historial-borrado'])

const MAX_VISIBLES = 10
const visibles = computed(() => props.conversaciones.slice(0, MAX_VISIBLES))

// Tipo elegido arriba (Chat libre / Chat guía): "Borrar historial" borra solo las de ese tipo,
// y solo aparece si en la lista hay alguna de ese tipo
const tipoChat = ref('libre')
const hayDelTipo = computed(() => props.conversaciones.some((c) => c.tipo === tipoChat.value))
const nombreTipo = computed(() => (tipoChat.value === 'guiada' ? 'chat guía' : 'chat libre'))

// Qué se va a borrar: { conversacion } (una) o { tipo } (todo el historial). null = ventana cerrada
const aBorrar = ref(null)

// La ventana ya borró en el backend: se cierra y se avisa a la vista para que refresque la lista
function alBorrar({ id, tipo }) {
  aBorrar.value = null
  if (id) emit('eliminada', id)
  else emit('historial-borrado', tipo)
}

// La vista llama a esto al abrir el cajón para llevar el foco adentro (accesibilidad con teclado)
const nueva = ref(null)
defineExpose({ enfocar: () => nueva.value?.enfocar() })
</script>

<template>
  <aside id="panel-conversaciones" class="panel" :class="{ abierto }" aria-label="Conversaciones">
    <header class="panel__cabecera">
      <h2>Conversaciones</h2>
      <button type="button" class="panel__cerrar" aria-label="Cerrar el panel de conversaciones" @click="$emit('cerrar')">
        <X :size="18" />
      </button>
    </header>

    <!-- Elegir Chat libre o Chat guía (y la guía) antes de empezar: ver NuevaConversacion.vue -->
    <NuevaConversacion ref="nueva" v-model:tipo="tipoChat" @cerrar="$emit('cerrar')" />

    <!-- Filas con título y fecha, como las conversaciones reales (sin ícono: el panel es angosto) -->
    <CargaEsqueleto v-if="cargando" texto="Cargando conversaciones…">
      <EsqueletoLista :filas="4" :con-icono="false" />
    </CargaEsqueleto>

    <div v-else-if="error" class="panel__estado panel__estado--error">
      <p>No se pudieron cargar.</p>
      <button type="button" class="btn btn-secundario btn-sm" @click="$emit('reintentar')">Reintentar</button>
    </div>

    <p v-else-if="!visibles.length" class="panel__estado">Aún no tienes conversaciones.</p>

    <ul v-else class="panel__lista">
      <li v-for="c in visibles" :key="c.id">
        <ItemConversacion :conversacion="c" :activa="c.id === activaId" @abrir="$emit('cerrar')"
          @borrar="aBorrar = { conversacion: c }" />
      </li>
    </ul>

    <!-- Al navegar se cierra el cajón (en celular) igual que con las conversaciones de la lista -->
    <div class="panel__pie">
      <RouterLink :to="{ name: 'Historial' }" class="panel__historial" @click="$emit('cerrar')">
        <History :size="15" /> Ver todo el historial
      </RouterLink>
      <!-- Borra las del tipo elegido arriba; la ventana dice cuántas son antes de confirmar -->
      <button v-if="hayDelTipo" type="button" class="panel__borrar" :aria-label="`Borrar el historial del ${nombreTipo}`"
        :title="`Borrar el historial del ${nombreTipo}`" @click="aBorrar = { tipo: tipoChat }">
        <Trash2 :size="15" aria-hidden="true" /> Borrar historial
      </button>
    </div>

    <!-- Se pinta en <body> (Teleport de ModalBase), así el cajón no la recorta -->
    <BorrarConversacionModal v-if="aBorrar" :conversacion="aBorrar.conversacion ?? null" :tipo="aBorrar.tipo ?? null"
      @cerrar="aBorrar = null" @borrado="alBorrar" />
  </aside>
</template>

<style scoped>
.panel {
  display: flex;
  flex-direction: column;
  gap: .8rem;
  min-height: 0;
  padding: .9rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

.panel__cabecera {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

h2 { font-size: .95rem; }

/* El botón de cerrar solo existe en el cajón (pantallas angostas) */
.panel__cerrar {
  display: none;
  padding: .3rem;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
}

.panel__cerrar:hover { color: var(--color-text); }

.panel__estado {
  color: var(--color-text-secondary);
  font-size: .85rem;
  text-align: center;
}

.panel__estado--error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
  color: var(--color-danger);
}

/* Solo la lista hace scroll: el botón de arriba y el pie de abajo se quedan fijos */
.panel__lista {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: .25rem;
  overflow-y: auto;
  list-style: none;
}

/* "Ver todo el historial" a la izquierda y "Borrar historial" a la derecha, sobre la misma línea.
   margin-top: auto lo deja abajo del panel aunque la lista esté vacía */
.panel__pie {
  margin-top: auto;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: .4rem .8rem;
  padding-top: .7rem;
  border-top: 1px solid var(--color-border);
}

.panel__historial,
.panel__borrar {
  display: flex;
  align-items: center;
  gap: .4rem;
  color: var(--color-text-secondary);
  font-size: .8rem;
  text-decoration: none;
  transition: color .2s;
}

.panel__historial:hover {
  color: var(--color-text);
}

/* Enlace discreto, como "Ver todo el historial": el rojo solo aparece al apuntarlo */
.panel__borrar {
  padding: .25rem 0;
  border: none;
  background: transparent;
  font: inherit;
  font-size: .8rem;
  cursor: pointer;
}

.panel__borrar:hover,
.panel__borrar:focus-visible {
  color: var(--color-danger);
}

/* Cajón: bajo 900 px el panel no cabe junto al chat, así que sale desde la izquierda.
   visibility con retraso: cerrado no debe poder alcanzarse con Tab, pero sí animarse al cerrar. */
@media (max-width: 900px) {
  .panel {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 20;
    width: min(85%, 320px);
    visibility: hidden;
    transform: translateX(-105%);
    transition: transform .25s ease, visibility 0s linear .25s;
    box-shadow: 0 0 40px rgba(0, 0, 0, .5);
  }

  .panel.abierto {
    visibility: visible;
    transform: translateX(0);
    transition: transform .25s ease, visibility 0s;
  }

  .panel__cerrar { display: block; }
}
</style>
