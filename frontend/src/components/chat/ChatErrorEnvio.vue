<script setup>
import { RotateCcw } from 'lucide-vue-next'

// Error al enviar un mensaje. El texto sigue en la caja de escribir, así que "Reintentar"
// solo le avisa a la vista que vuelva a enviarlo.
defineProps({ mensaje: { type: String, required: true } })
defineEmits(['reintentar'])
</script>

<template>
  <div class="error" role="alert">
    <span>{{ mensaje }}</span>
    <button type="button" class="btn btn-secundario btn-sm" @click="$emit('reintentar')">
      <RotateCcw :size="14" /> Reintentar
    </button>
  </div>
</template>

<style scoped>
.error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .8rem;
  padding: .6rem .9rem;
  border: 1px solid var(--color-danger);
  border-radius: 12px;
  background: color-mix(in srgb, var(--color-danger) 10%, transparent);
  color: var(--color-danger);
  font-size: .88rem;
}
</style>
