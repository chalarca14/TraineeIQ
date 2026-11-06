<script setup>
import { ref, computed } from 'vue'
import { Plus, X, MessageSquare, BookOpen, History } from 'lucide-vue-next'
import { fechaRelativa, horaCorta } from '@/utils/fechas'

// Panel lateral de conversaciones (como el de ChatGPT). En escritorio es una columna fija;
// en pantallas angostas es un cajón que abre y cierra la vista (props/eventos), por eso este
// componente no guarda estado propio: solo muestra lo que recibe.
const props = defineProps({
  conversaciones: { type: Array, default: () => [] },
  cargando: { type: Boolean, default: false },
  error: { type: Boolean, default: false },
  activaId: { type: Number, default: null },
  abierto: { type: Boolean, default: false }
})
defineEmits(['cerrar', 'reintentar'])

const MAX_VISIBLES = 10
const visibles = computed(() => props.conversaciones.slice(0, MAX_VISIBLES))

// El backend hoy guarda el mismo título para todas las libres ("Conversacion libre"); si llega
// vacío usamos uno genérico. La fecha y la hora bajo el título son lo que las distingue.
const tituloDe = (c) => c.titulo || (c.tipo === 'guiada' ? 'Conversación guiada' : 'Chat libre')
const metaDe = (c) => [fechaRelativa(c.updated_at), horaCorta(c.updated_at)].filter(Boolean).join(' · ')

// La vista llama a esto al abrir el cajón para llevar el foco adentro (accesibilidad con teclado)
const nueva = ref(null)
defineExpose({ enfocar: () => nueva.value?.$el?.focus() })
</script>

<template>
  <aside id="panel-conversaciones" class="panel" :class="{ abierto }" aria-label="Conversaciones">
    <header class="panel__cabecera">
      <h2>Conversaciones</h2>
      <button type="button" class="panel__cerrar" aria-label="Cerrar el panel de conversaciones" @click="$emit('cerrar')">
        <X :size="18" />
      </button>
    </header>

    <!-- "Nueva conversación" siempre abre una LIBRE: va a /estudiante/ia sin ?guiaId -->
    <RouterLink ref="nueva" :to="{ name: 'ChatIA' }" class="btn btn-primario" @click="$emit('cerrar')">
      <Plus :size="18" /> Nueva conversación
    </RouterLink>

    <p v-if="cargando" class="panel__estado">Cargando conversaciones…</p>

    <div v-else-if="error" class="panel__estado panel__estado--error">
      <p>No se pudieron cargar.</p>
      <button type="button" class="btn btn-secundario btn-sm" @click="$emit('reintentar')">Reintentar</button>
    </div>

    <p v-else-if="!visibles.length" class="panel__estado">Aún no tienes conversaciones.</p>

    <ul v-else class="panel__lista">
      <li v-for="c in visibles" :key="c.id">
        <RouterLink :to="{ name: 'ChatIAConversacion', params: { conversacionId: c.id } }" class="item"
          :class="{ activa: c.id === activaId }" :aria-current="c.id === activaId ? 'page' : undefined"
          @click="$emit('cerrar')">
          <component :is="c.tipo === 'guiada' ? BookOpen : MessageSquare" :size="16" class="item__icono" />
          <span class="item__texto">
            <span class="item__titulo" :title="tituloDe(c)">{{ tituloDe(c) }}</span>
            <span class="item__meta">
              <span class="etiqueta" :class="c.tipo">{{ c.tipo === 'guiada' ? 'Guiada' : 'Libre' }}</span>
              {{ metaDe(c) }}
            </span>
          </span>
        </RouterLink>
      </li>
    </ul>

    <!-- TODO Tarea 5: cuando exista /estudiante/historial, cambiar este <span> por un RouterLink.
         Hoy no se enlaza porque el router manda toda ruta desconocida a /login. -->
    <span class="panel__historial" aria-disabled="true" title="Disponible próximamente">
      <History :size="15" /> Ver todo el historial <small>Próximamente</small>
    </span>
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

/* Solo la lista hace scroll: el botón de arriba y el enlace de abajo se quedan fijos */
.panel__lista {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  gap: .25rem;
  overflow-y: auto;
  list-style: none;
}

.item {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  padding: .55rem .6rem;
  border: 1px solid transparent;
  border-radius: 10px;
  color: var(--color-text);
  text-decoration: none;
  transition: background-color .2s, border-color .2s;
}

.item:hover { background: color-mix(in srgb, var(--color-primary) 10%, transparent); }

/* La conversación abierta va resaltada */
.item.activa {
  border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
}

.item__icono {
  flex-shrink: 0;
  margin-top: .15rem;
  color: var(--color-primary-light);
}

.item__texto {
  display: flex;
  flex-direction: column;
  gap: .2rem;
  min-width: 0;
}

.item__titulo {
  overflow: hidden;
  font-size: .85rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .4rem;
  color: var(--color-text-secondary);
  font-size: .72rem;
}

.etiqueta {
  padding: .05rem .45rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-info) 18%, transparent);
  color: var(--color-info);
  font-size: .66rem;
  font-weight: 600;
}

.etiqueta.guiada {
  background: color-mix(in srgb, var(--color-success) 18%, transparent);
  color: var(--color-success);
}

.panel__historial {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding-top: .7rem;
  border-top: 1px solid var(--color-border);
  color: var(--color-text-secondary);
  font-size: .8rem;
  opacity: .7;
  cursor: not-allowed;
}

.panel__historial small {
  margin-left: auto;
  color: var(--color-warning);
  font-size: .68rem;
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
