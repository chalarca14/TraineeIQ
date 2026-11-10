<script setup>
import { computed } from 'vue'
import { MessageSquare, Check, Lightbulb } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'

// Estado vacío del chat: mientras no hay mensajes, estas pistas sí sirven para saber qué
// preguntar (antes vivían en un panel fijo que quitaba espacio al chat).
const props = defineProps({
  guiado: { type: Boolean, default: false },
  guiaNombre: { type: String, default: '' }
})

const LIBRE = [
  'Resolver dudas sobre programación',
  'Explorar conceptos y tecnologías',
  'Pedir ejemplos de código',
  'Recibir consejos de estudio',
  '¡Y mucho más!'
]
const GUIADO = [
  'Resolver dudas sobre el contenido de la guía',
  'Aclarar los conceptos de cada tema',
  'Pedir ejemplos relacionados con la guía',
  'Recibir consejos de estudio',
  '¡Y mucho más!'
]

const items = computed(() => (props.guiado ? GUIADO : LIBRE))
</script>

<template>
  <div class="vacio">
    <Medalla :icono="MessageSquare" :size="56" />
    <h2>¿Qué puedes hacer aquí?</h2>
    <p v-if="guiado" class="intro">Pregúntame sobre el contenido de <strong>{{ guiaNombre || 'esta guía' }}</strong>.</p>
    <p v-else class="intro">Escribe tu primera pregunta para empezar la conversación.</p>

    <ul>
      <li v-for="item in items" :key="item">
        <span class="marca" aria-hidden="true"><Check :size="12" :stroke-width="3" /></span>
        {{ item }}
      </li>
    </ul>

    <p class="consejo">
      <Lightbulb :size="18" />
      <span><strong>Consejo:</strong> sé específico en tus preguntas para obtener mejores respuestas.</span>
    </p>
  </div>
</template>

<style scoped>
.vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .6rem;
  margin: auto;
  max-width: 440px;
  text-align: center;
}

h2 { font-size: 1.15rem; }

.intro {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.intro strong { color: var(--color-text); }

ul {
  display: flex;
  flex-direction: column;
  gap: .5rem;
  margin: .4rem 0;
  list-style: none;
  text-align: left;
}

li {
  display: flex;
  align-items: center;
  gap: .6rem;
  font-size: .9rem;
}

.marca {
  display: grid;
  place-items: center;
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--gradiente-primario);
  color: var(--color-text);
}

.consejo {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  margin-top: .4rem;
  padding: .7rem .9rem;
  border: 1px solid color-mix(in srgb, var(--color-warning) 35%, transparent);
  border-radius: 12px;
  background: color-mix(in srgb, var(--color-warning) 10%, transparent);
  font-size: .82rem;
  text-align: left;
}

.consejo svg { flex-shrink: 0; color: var(--color-warning); }
.consejo strong { color: var(--color-warning); }
</style>
