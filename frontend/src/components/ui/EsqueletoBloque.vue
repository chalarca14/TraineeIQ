<script setup>
// La pieza base de todos los esqueletos: un rectángulo (o línea) gris con un brillo que pasa.
// Las demás piezas (tarjeta, lista, tabla) se arman con varios de estos.
defineProps({
  ancho: { type: String, default: '100%' },
  alto: { type: String, default: '.9rem' },
  // Para íconos o avatares redondos
  redondo: { type: Boolean, default: false },
  radio: { type: String, default: '8px' }
})
</script>

<template>
  <span class="bloque" :style="{ width: ancho, height: alto, borderRadius: redondo ? '50%' : radio }"></span>
</template>

<style scoped>
.bloque {
  position: relative;
  display: block;
  flex-shrink: 0;
  max-width: 100%;
  overflow: hidden;
  background: var(--color-card);
}

/* El brillo: un degradado que cruza el bloque de izquierda a derecha. Solo CSS, sin librerías */
.bloque::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent,
      color-mix(in srgb, var(--color-text) 7%, transparent), transparent);
  animation: brillo 1.4s ease-in-out infinite;
}

@keyframes brillo {
  to {
    transform: translateX(100%);
  }
}

/* Quien pide menos movimiento ve el bloque fijo, sin brillo */
@media (prefers-reduced-motion: reduce) {
  .bloque::after {
    display: none;
  }
}
</style>
