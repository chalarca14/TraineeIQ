<script setup>
import EsqueletoBloque from './EsqueletoBloque.vue'

// Esqueleto de una tarjeta (estadística, guía, grupo): ícono opcional y unas líneas de texto.
// Los anchos varían a propósito: líneas todas iguales se ven artificiales.
defineProps({
  lineas: { type: Number, default: 2 },
  conIcono: { type: Boolean, default: true },
  // Alto mínimo, para que coincida con la tarjeta real y no haya salto al cargar
  alto: { type: String, default: '' }
})

const ANCHOS = ['55%', '35%', '80%', '65%', '45%']
</script>

<template>
  <div class="tarjeta" :style="alto ? { minHeight: alto } : null">
    <EsqueletoBloque v-if="conIcono" ancho="48px" alto="48px" radio="14px" />
    <div class="lineas">
      <EsqueletoBloque v-for="n in lineas" :key="n" :ancho="ANCHOS[(n - 1) % ANCHOS.length]"
        :alto="n === 2 ? '1.4rem' : '.8rem'" />
    </div>
  </div>
</template>

<style scoped>
.tarjeta {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.1rem;
  border: 1px solid var(--color-card);
  border-radius: 16px;
  background: var(--color-surface);
}

.lineas {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--espacio-sm);
  min-width: 0;
}
</style>
