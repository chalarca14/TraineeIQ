<template>
  <section class="en-curso" role="status" aria-live="polite">
    <!-- Esta pantalla tiene la petición abierta: el backend evalúa y responde al terminar (no hay polling) -->
    <template v-if="esperando">
      <span class="giro" aria-hidden="true"></span>
      <h2>La IA está revisando tu repositorio</h2>
      <p>Puede tardar hasta 3 minutos. No cierres esta pestaña: el resultado aparece aquí cuando termine.</p>
    </template>

    <!-- El backend dice "evaluando" pero esta pantalla no espera: se evaluó desde otra pestaña o se interrumpió -->
    <template v-else>
      <span class="giro giro--quieto" aria-hidden="true"><Clock :size="26" /></span>
      <h2>Tu entrega figura como "evaluando"</h2>
      <p>Pudo terminar o haberse interrumpido. Consulta el estado una vez.</p>
      <button type="button" class="btn btn-secundario" :disabled="revisando" @click="$emit('revisar')">
        <RefreshCw :size="16" /> {{ revisando ? 'Revisando…' : 'Revisar de nuevo' }}
      </button>
    </template>
  </section>
</template>

<script setup>
import { Clock, RefreshCw } from 'lucide-vue-next'

defineProps({
  // true = esta pantalla tiene la petición de evaluar abierta
  esperando: { type: Boolean, default: false },
  revisando: { type: Boolean, default: false }
})
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
