<template>
  <div class="anillo" :class="{ aprobado }" role="img" :aria-label="descripcion">
    <svg viewBox="0 0 120 120" aria-hidden="true">
      <circle class="fondo" cx="60" cy="60" :r="RADIO" />
      <circle class="valor" cx="60" cy="60" :r="RADIO" :stroke-dasharray="`${arco} ${CIRCUNFERENCIA}`" />
    </svg>
    <div class="centro">
      <strong>{{ valido ? puntajeAjustado : '—' }}<small v-if="valido">/100</small></strong>
      <!-- La calificación ("Excelente") la decide el backend; si no llega, solo se muestra el puntaje -->
      <span v-if="calificacion">{{ calificacion }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Anillo con el puntaje (0 a 100). Solo MUESTRA lo que el backend calculó.
const props = defineProps({
  puntaje: { type: Number, default: null },
  calificacion: { type: String, default: '' },
  aprobado: { type: Boolean, default: false }
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
  --c: var(--color-warning);
  position: relative;
  width: 132px;
  height: 132px;
  flex-shrink: 0;
}

.anillo.aprobado { --c: var(--color-success); }

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
  filter: drop-shadow(0 0 6px color-mix(in srgb, var(--c) 60%, transparent));
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
.centro span { margin-top: .25rem; color: var(--c); font-size: .8rem; font-weight: 600; }

@media (prefers-reduced-motion: reduce) {
  .valor { transition: none; }
}
</style>
