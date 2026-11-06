<template>
  <RouterLink :to="to" class="volver">
    <span class="flecha"><ArrowLeft :size="16" /></span>
    <slot>Volver</slot>
  </RouterLink>
</template>

<script setup>
import { ArrowLeft } from 'lucide-vue-next'

// Botón "← Volver a ..." de las pantallas de detalle. Es un enlace (RouterLink) y no un
// router.back() para que siempre lleve a la lista correcta, aunque se llegue por un enlace directo.
defineProps({ to: { type: [String, Object], required: true } })
</script>

<style scoped>
.volver {
  display: inline-flex;
  align-items: center;
  align-self: flex-start;
  gap: .6rem;
  padding: .3rem .9rem .3rem .3rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-surface);
  color: var(--color-text);
  font-size: .88rem;
  text-decoration: none;
  transition: border-color .2s;
}

.volver:hover {
  border-color: var(--color-primary);
}

.flecha {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-primary) 25%, transparent);
  color: var(--color-primary-light);
}
</style>
