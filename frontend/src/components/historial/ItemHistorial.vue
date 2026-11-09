<script setup>
import { computed } from 'vue'
import { MessageSquare, BookOpen, ArrowRight, Clock } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import { fechaRelativa, horaCorta } from '@/utils/fechas'

// Una fila del historial. Toda la fila es un RouterLink (no un <div> con @click): así funciona
// con teclado (Tab + Enter), se puede abrir en otra pestaña y los lectores de pantalla la
// anuncian como enlace. La vista no decide a dónde ir: la ruta ya viene dentro de la entrada.
const props = defineProps({
  entrada: { type: Object, required: true }
})

// Icono y color según el tipo de chat: libre (burbuja) o guiado (libro)
const VISUAL = {
  libre: { icono: MessageSquare, color: 'primary' },
  guiada: { icono: BookOpen, color: 'info' }
}
const visual = computed(() => VISUAL[props.entrada.categoria] ?? VISUAL.libre)

const cuando = computed(() =>
  [fechaRelativa(props.entrada.fecha), horaCorta(props.entrada.fecha)].filter(Boolean).join(' · ')
)
</script>

<template>
  <RouterLink :to="entrada.ruta" class="item">
    <Medalla :icono="visual.icono" :color="visual.color" :size="48" />

    <div class="item__texto">
      <h3>{{ entrada.titulo }}</h3>
      <p class="item__descripcion">{{ entrada.descripcion }}</p>
      <small v-if="cuando" class="item__fecha"><Clock :size="13" /> {{ cuando }}</small>
    </div>

    <span class="item__etiqueta" :title="entrada.etiqueta">{{ entrada.etiqueta }}</span>
    <ArrowRight :size="18" class="item__flecha" aria-hidden="true" />
  </RouterLink>
</template>

<style scoped>
.item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: .9rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 14px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
  color: var(--color-text);
  text-decoration: none;
  transition: border-color .2s, box-shadow .2s;
}

.item:hover,
.item:focus-visible {
  border-color: color-mix(in srgb, var(--color-primary) 60%, transparent);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 18%, transparent);
}

/* min-width: 0 permite que el texto largo se corte en vez de empujar la flecha fuera de la tarjeta */
.item__texto {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: .25rem;
  min-width: 0;
}

h3 {
  font-size: 1rem;
  overflow-wrap: anywhere;
}

.item__descripcion {
  color: var(--color-text-secondary);
  font-size: .85rem;
}

.item__fecha {
  display: flex;
  align-items: center;
  gap: .3rem;
  color: var(--color-text-secondary);
  font-size: .75rem;
}

.item__etiqueta {
  max-width: 180px;
  flex-shrink: 0;
  overflow: hidden;
  padding: .25rem .8rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-primary) 20%, transparent);
  color: var(--color-primary-light);
  font-size: .75rem;
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item__flecha {
  flex-shrink: 0;
  color: var(--color-text-secondary);
}

/* En 360 px la etiqueta pasa debajo del texto: en fila no cabría con el icono y la flecha */
@media (max-width: 520px) {
  .item {
    flex-wrap: wrap;
  }

  .item__texto {
    flex-basis: calc(100% - 48px - 1rem);
  }

  .item__etiqueta {
    order: 3;
    margin-left: calc(48px + 1rem);
  }

  .item__flecha {
    display: none;
  }
}
</style>
