<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Check } from 'lucide-vue-next'

// Espera larga de la IA (subir una guía, generar una semana): robot animado en un círculo, "Cargando…"
// y una lista de pasos para que el usuario vea que algo está pasando y no crea que la app se colgó.
// OJO: el backend NO informa en qué paso va, solo responde al final. Por eso los pasos avanzan por
// tiempo y el último se queda fijo (nunca se marca como terminado) hasta que llega la respuesta.
// Lo que sí es dato real (por ejemplo "Semana 2 de 6") llega en `detalle`.
const props = defineProps({
  titulo: { type: String, default: 'Cargando…' },
  // Texto real que manda quien usa el componente; vacío = no se muestra
  detalle: { type: String, default: '' },
  pasos: { type: Array, default: () => [] },
  // Segundos que dura cada paso antes de pasar al siguiente
  intervalo: { type: Number, default: 9 },
  // sm 96 px (modales y formularios) · md 140 px (panel de la semana)
  tamano: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) }
})

const VIDEO = '/animaciones/pantalla-carga.mp4'
const POSTER = '/animaciones/pantalla-carga-poster.webp'

// Con movimiento reducido, o si el video falla, queda la imagen quieta: la espera se sigue entendiendo
const reducido = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
const usarPoster = ref(reducido)

const indice = ref(0)
let temporizador = null

// El componente existe solo mientras se espera: el tiempo cuenta desde que aparece
onMounted(() => {
  if (props.pasos.length < 2) return
  temporizador = setInterval(() => {
    if (indice.value < props.pasos.length - 1) indice.value++
    else clearInterval(temporizador)
  }, props.intervalo * 1000)
})

onUnmounted(() => clearInterval(temporizador))

const pasoActual = computed(() => props.pasos[indice.value] ?? '')
</script>

<template>
  <div class="carga-ia" :class="`carga-ia--${tamano}`">
    <div class="carga-ia__circulo" aria-hidden="true">
      <img v-if="usarPoster" :src="POSTER" alt="" />
      <video v-else :src="VIDEO" :poster="POSTER" autoplay muted loop playsinline @error="usarPoster = true" />
    </div>

    <div class="carga-ia__textos">
      <!-- role="status": el lector de pantalla anuncia el paso nuevo cada vez que cambia -->
      <p class="carga-ia__titulo" role="status">
        {{ titulo }}<span v-if="pasoActual" class="solo-lector"> {{ pasoActual }}</span>
      </p>
      <p v-if="detalle" class="carga-ia__detalle">{{ detalle }}</p>

      <!-- aria-hidden: el paso actual ya lo anuncia el status; leer toda la lista sería ruido -->
      <ol v-if="pasos.length" class="carga-ia__pasos" aria-hidden="true">
        <li v-for="(paso, i) in pasos" :key="paso"
          :class="{ hecho: i < indice, actual: i === indice }">
          <Check v-if="i < indice" :size="14" />
          <span v-else class="punto" />
          {{ paso }}
        </li>
      </ol>
    </div>
  </div>
</template>

<style scoped>
.carga-ia {
  display: flex;
  align-items: center;
  gap: var(--espacio-lg);
  padding: var(--espacio-md);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 12%, var(--color-surface)), var(--color-surface));
}

/* Círculo: el video es rectangular con fondo casi negro. Recorte redondo + borde difuminado para que
   se funda con el tema oscuro y no se vea ningún "cuadro" */
.carga-ia__circulo {
  flex-shrink: 0;
  aspect-ratio: 1 / 1;
  overflow: hidden;
  border-radius: 50%;
  -webkit-mask-image: radial-gradient(circle, #000 64%, transparent 71%);
  mask-image: radial-gradient(circle, #000 64%, transparent 71%);
}

.carga-ia--sm .carga-ia__circulo { width: 96px; }
.carga-ia--md .carga-ia__circulo { width: 140px; }

.carga-ia__circulo img,
.carga-ia__circulo video {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.carga-ia__textos {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-xs);
  min-width: 0;
}

.carga-ia__titulo {
  margin: 0;
  color: var(--color-text);
  font-size: 1.05rem;
  font-weight: 700;
}

.carga-ia__detalle {
  margin: 0;
  color: var(--color-primary-light);
  font-size: .88rem;
  font-weight: 600;
}

.carga-ia__pasos {
  display: flex;
  flex-direction: column;
  gap: .3rem;
  margin: var(--espacio-xs) 0 0;
  padding: 0;
  list-style: none;
  font-size: .85rem;
}

.carga-ia__pasos li {
  display: flex;
  align-items: center;
  gap: .45rem;
  color: var(--color-text-secondary);
  opacity: .55;
  overflow-wrap: anywhere;
  transition: opacity .3s, color .3s;
}

.carga-ia__pasos li.hecho {
  opacity: .85;
}

.carga-ia__pasos li.hecho svg {
  flex-shrink: 0;
  color: var(--color-success);
}

.carga-ia__pasos li.actual {
  color: var(--color-text);
  font-weight: 600;
  opacity: 1;
}

.punto {
  flex-shrink: 0;
  width: 8px;
  height: 8px;
  margin-inline: 3px;
  border-radius: 50%;
  background: currentColor;
}

/* El paso actual "late" para que se note que sigue trabajando */
.actual .punto {
  background: var(--color-primary-light);
  animation: latir 1.2s ease-in-out infinite;
}

@keyframes latir {
  50% { transform: scale(1.5); opacity: .6; }
}

.solo-lector {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {
  .actual .punto { animation: none; }
}

/* 360 px: el círculo arriba y el texto debajo, centrados; en fila, los pasos quedaban muy estrechos */
@media (max-width: 480px) {
  .carga-ia {
    flex-direction: column;
    text-align: center;
    gap: var(--espacio-md);
  }

  .carga-ia__pasos li {
    justify-content: center;
  }
}
</style>
