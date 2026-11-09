<template>
  <section class="error" role="alert">
    <span class="icono" aria-hidden="true"><TriangleAlert :size="28" /></span>
    <h2>No pudimos evaluar tu entrega</h2>
    <!-- message del backend en texto plano (nunca v-html). Si solo llega "error" al recargar la
         pantalla, el backend no guarda el motivo y se muestra un texto general. -->
    <p class="mensaje">{{ mensaje || 'La última evaluación no se completó. Puedes intentarlo de nuevo.' }}</p>

    <div class="acciones">
      <button type="button" class="btn btn-primario" :disabled="evaluando || intentosRestantes === 0"
        @click="$emit('reintentar')">
        <RefreshCw :size="16" /> {{ evaluando ? 'Evaluando…' : 'Reintentar' }}
      </button>
      <button type="button" class="btn btn-secundario" @click="$emit('cambiar-enlace')">Cambiar el enlace</button>
    </div>
    <p v-if="intentosRestantes === 0" class="nota">Ya usaste tus evaluaciones de hoy. Vuelve mañana.</p>
  </section>
</template>

<script setup>
import { TriangleAlert, RefreshCw } from 'lucide-vue-next'

defineProps({
  mensaje: { type: String, default: '' },
  intentosRestantes: { type: Number, default: null },
  evaluando: { type: Boolean, default: false }
})
defineEmits(['reintentar', 'cambiar-enlace'])
</script>

<style scoped>
.error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .6rem;
  padding: 2.5rem 1rem;
  border: 1px dashed color-mix(in srgb, var(--color-danger) 55%, transparent);
  border-radius: 16px;
  background: color-mix(in srgb, var(--color-danger) 6%, transparent);
  text-align: center;
}

.icono {
  display: grid;
  place-items: center;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-danger) 16%, transparent);
  color: var(--color-danger);
}

h2 { font-size: 1.1rem; }
.mensaje { max-width: 48ch; color: var(--color-text); overflow-wrap: anywhere; }
.nota { color: var(--color-text-secondary); font-size: .85rem; }

.acciones {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: .6rem;
  margin-top: .4rem;
}
</style>
