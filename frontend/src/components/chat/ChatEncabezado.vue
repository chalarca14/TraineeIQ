<script setup>
import { Bot, Sparkles, BookOpen, MessageCircle } from 'lucide-vue-next'

// Si viene guiaNombre, el chat es guiado; si no, es libre.
// La etiqueta de contexto le dice al usuario qué reglas sigue la IA (RF-14 / RF-15).
defineProps({
  guiaNombre: { type: String, default: '' },
  guiado: { type: Boolean, default: false }
})
</script>

<template>
  <header class="encabezado">
    <span class="encabezado__icono"><Bot :size="28" /></span>

    <div class="encabezado__texto">
      <h1>Asistente IA</h1>
      <p>Tu compañero de aprendizaje. Pregúntame lo que necesites.</p>
      <span class="encabezado__contexto">
        <component :is="guiado ? BookOpen : MessageCircle" :size="14" />
        {{ guiado ? `Guía: ${guiaNombre || 'cargando…'}` : 'Chat libre' }}
      </span>
    </div>

    <span class="encabezado__gemini"><Sparkles :size="14" /> Gemini</span>
  </header>
</template>

<style scoped>
.encabezado {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.2rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.encabezado__icono {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  flex-shrink: 0;
  border-radius: 14px;
  background: var(--color-primary);
  color: #fff;
}

.encabezado__texto { min-width: 0; flex: 1; }
.encabezado__texto h1 { margin: 0; font-size: clamp(1.2rem, 3vw, 1.5rem); }
.encabezado__texto p { margin: .15rem 0 .5rem; color: var(--color-text-secondary); font-size: .9rem; }

.encabezado__contexto {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  max-width: 100%;
  padding: .2rem .65rem;
  border-radius: 999px;
  background: rgba(124, 58, 237, .15);
  color: var(--color-primary-light);
  font-size: .78rem;
  font-weight: 600;
  /* un nombre de guía largo no debe romper el diseño en 360 px */
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.encabezado__gemini {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  align-self: flex-start;
  padding: .3rem .7rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-text-secondary);
  font-size: .78rem;
}

@media (max-width: 600px) {
  .encabezado__icono { display: none; }
  .encabezado__gemini { display: none; }
}
</style>
