<template>
  <ul class="criterios">
    <li v-for="(c, i) in criterios" :key="i">
      <span class="nombre">{{ c.criterio }}</span>
      <span class="valoracion" :style="{ '--c': colorDe(c.valoracion) }">{{ c.valoracion }}</span>
    </li>
  </ul>
</template>

<script setup>
// Análisis del código por criterios: [{ criterio, valoracion }]. Los textos los escribe la IA y
// se pintan con {{ }} (texto plano), nunca con v-html, así no se interpretan como HTML.
defineProps({ criterios: { type: Array, required: true } })

const COLORES = {
  excelente: 'var(--color-success)',
  bueno: 'var(--color-info)',
  regular: 'var(--color-warning)',
  mejorable: 'var(--color-danger)'
}

// Si llega una valoración que no conocemos, se muestra igual con un color neutro
const colorDe = (valoracion) => COLORES[String(valoracion ?? '').toLowerCase()] ?? 'var(--color-text-secondary)'
</script>

<style scoped>
.criterios {
  display: flex;
  flex-direction: column;
  gap: .6rem;
  list-style: none;
}

li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .8rem;
  font-size: .88rem;
}

.nombre { min-width: 0; overflow-wrap: anywhere; }

.valoracion {
  flex-shrink: 0;
  padding: .1rem .6rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--c) 16%, transparent);
  color: var(--c);
  font-size: .75rem;
  font-weight: 700;
}
</style>
