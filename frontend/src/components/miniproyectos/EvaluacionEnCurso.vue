<template>
  <section class="en-curso" role="status" aria-live="polite">
    <span v-if="!sondeoAgotado" class="giro" aria-hidden="true"></span>
    <span v-else class="giro giro--quieto" aria-hidden="true"><Clock :size="26" /></span>

    <template v-if="!sondeoAgotado">
      <h2>La IA está revisando tu repositorio</h2>
      <p>Esto puede tardar un momento. Puedes quedarte aquí: la pantalla se actualiza sola cuando termine.</p>
    </template>

    <!-- Se acabaron los intentos de consulta (≈1 minuto) y sigue evaluando -->
    <template v-else>
      <h2>La evaluación está tardando más de lo normal</h2>
      <p>Tu entrega sigue en revisión. Puedes volver a consultar el estado.</p>
      <button type="button" class="btn btn-secundario" @click="$emit('revisar')">
        <RefreshCw :size="16" /> Revisar de nuevo
      </button>
    </template>
  </section>
</template>

<script setup>
import { Clock, RefreshCw } from 'lucide-vue-next'

defineProps({ sondeoAgotado: { type: Boolean, default: false } })
defineEmits(['revisar'])
</script>

<style scoped>
.en-curso {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .7rem;
  padding: 2.5rem 1rem;
  border: 1px dashed color-mix(in srgb, var(--color-primary) 45%, transparent);
  border-radius: 16px;
  background: color-mix(in srgb, var(--color-primary) 6%, transparent);
  color: var(--color-text-secondary);
  text-align: center;
}

h2 { color: var(--color-text); font-size: 1.1rem; }

.giro {
  display: grid;
  place-items: center;
  width: 56px;
  height: 56px;
  border: 4px solid var(--color-card);
  border-top-color: var(--color-primary-light);
  border-radius: 50%;
  animation: girar 1s linear infinite;
}

.giro--quieto {
  border-color: var(--color-card);
  color: var(--color-warning);
  animation: none;
}

@keyframes girar { to { transform: rotate(360deg); } }

/* Quien prefiere menos movimiento ve el indicador quieto */
@media (prefers-reduced-motion: reduce) {
  .giro { animation: none; }
}
</style>
