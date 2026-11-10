<template>
  <div class="anillo" role="img" :aria-label="descripcion">
    <svg viewBox="0 0 120 120" aria-hidden="true">
      <circle class="fondo" cx="60" cy="60" :r="RADIO" />
      <circle class="valor" cx="60" cy="60" :r="RADIO" :stroke-dasharray="`${arco} ${CIRCUNFERENCIA}`" />
    </svg>
    <div class="centro">
      <strong>{{ valido ? puntajeAjustado : '—' }}<small v-if="valido">/100</small></strong>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Anillo con el puntaje (0 a 100). Solo MUESTRA lo que el backend calculó.
// Color neutro a propósito: la evaluación es orientativa y no hay "bueno" ni "malo" por rangos,
// así que el frontend no pinta verde, amarillo o rojo según el número.
const props = defineProps({
  puntaje: { type: Number, default: null }
})

const RADIO = 52
const CIRCUNFERENCIA = 2 * Math.PI * RADIO

const valido = computed(() => Number.isFinite(props.puntaje))
// Se limita a 0–100 por si llegara un valor fuera de rango: así el anillo nunca se dibuja roto
const puntajeAjustado = computed(() => Math.min(100, Math.max(0, Math.round(props.puntaje))))
const arco = computed(() => (valido.value ? (puntajeAjustado.value / 100) * CIRCUNFERENCIA : 0))
const descripcion = computed(() => (valido.value ? `Puntaje ${puntajeAjustado.value} de 100` : 'Sin puntaje'))
</script>

<style scoped>
.anillo {
  --c: var(--color-primary);
  position: relative;
  width: 132px;
  height: 132px;
  flex-shrink: 0;
}

svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

circle {
  fill: none;
  stroke-width: 9;
}

.fondo { stroke: var(--color-card); }

.valor {
  stroke: var(--c);
  stroke-linecap: round;
  transition: stroke-dasharray .6s ease;
}

.centro {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.centro strong { font-size: 1.9rem; line-height: 1; }
.centro small { color: var(--color-text-secondary); font-size: .9rem; font-weight: 400; }

@media (prefers-reduced-motion: reduce) {
  .valor { transition: none; }
}
</style>
