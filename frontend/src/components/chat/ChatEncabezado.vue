<script setup>
import { ref } from 'vue'
import { Bot, Sparkles, BookOpen, MessageCircle, History } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'

// Si viene guiaNombre, el chat es guiado; si no, es libre.
// La etiqueta de contexto le dice al usuario qué reglas sigue la IA (RF-14 / RF-15).
// Es solo informativa: no es un selector, el modo lo decide cómo se entró al chat.
defineProps({
  guiaNombre: { type: String, default: '' },
  guiado: { type: Boolean, default: false },
  panelAbierto: { type: Boolean, default: false }
})
defineEmits(['abrir-panel'])

// La vista necesita devolver el foco a este botón cuando se cierra el cajón con Escape o
// con un clic fuera: quien usa teclado no debe quedarse "perdido" en la página.
const boton = ref(null)
defineExpose({ enfocarBoton: () => boton.value?.focus() })
</script>

<template>
  <!-- Banner compacto: el chat necesita la altura, así que no lleva ilustración -->
  <EncabezadoPagina compacto titulo="Chat con IA" :icono="Bot"
    descripcion="Pregunta, aprende y resuelve tus dudas con nuestra inteligencia artificial.">
    <template #etiquetas>
      <span class="chip contexto" :title="guiado ? guiaNombre : ''">
        <component :is="guiado ? BookOpen : MessageCircle" :size="14" />
        {{ guiado ? `Guía: ${guiaNombre || 'cargando…'}` : 'Chat libre' }}
      </span>
      <span class="chip gemini"><Sparkles :size="14" /> Gemini</span>
    </template>

    <!-- Solo se ve en pantallas angostas, donde el panel de conversaciones es un cajón -->
    <template #acciones>
      <button ref="boton" type="button" class="btn btn-secundario btn-sm historial"
        aria-controls="panel-conversaciones" :aria-expanded="panelAbierto" @click="$emit('abrir-panel')">
        <History :size="16" /> Historial
      </button>
    </template>
  </EncabezadoPagina>
</template>

<style scoped>
.chip {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  max-width: 100%;
  padding: .2rem .65rem;
  border-radius: 999px;
  font-size: .75rem;
  font-weight: 600;
  /* un nombre de guía largo no debe romper el diseño en 360 px */
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.contexto {
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
  color: var(--color-primary-light);
}

.gemini {
  border: 1px solid var(--color-border);
  color: var(--color-text-secondary);
}

.historial { display: none; }

@media (max-width: 900px) {
  .historial { display: inline-flex; }
}

@media (max-width: 600px) {
  .gemini { display: none; }
  /* en 360 px solo el icono, para que el banner no crezca */
  .historial { padding: .5rem .6rem; font-size: 0; gap: 0; }
}
</style>
