<script setup>
import { computed } from 'vue'
import { Send } from 'lucide-vue-next'

// v-model: el texto vive en la vista (así, si el envío falla, el texto sigue ahí para reintentar).
const texto = defineModel({ type: String, default: '' })
const props = defineProps({ deshabilitado: Boolean })
const emit = defineEmits(['enviar'])

// Mismo límite que el backend (max:2000)
const MAX = 2000

const puedeEnviar = computed(() => !props.deshabilitado && texto.value.trim().length > 0)

function enviar() {
  // Validación antes de enviar: no mandamos mensajes vacíos
  if (puedeEnviar.value) emit('enviar')
}
</script>

<template>
  <form class="entrada" @submit.prevent="enviar">
    <input
      v-model="texto"
      type="text"
      placeholder="Escribe tu mensaje aquí…"
      :maxlength="MAX"
      :disabled="deshabilitado"
      aria-label="Mensaje para la IA"
    />
    <button type="submit" :disabled="!puedeEnviar" aria-label="Enviar mensaje">
      <Send :size="18" />
    </button>
  </form>
</template>

<style scoped>
.entrada {
  display: flex;
  gap: .6rem;
  padding: .6rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 14px;
}

input {
  flex: 1;
  min-width: 0;
  padding: .6rem .8rem;
  border: none;
  background: transparent;
  color: var(--color-text);
  font-size: .95rem;
}

input:focus { outline: none; }
input::placeholder { color: var(--color-text-secondary); }

button {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border: none;
  border-radius: 10px;
  background: var(--color-primary);
  color: #fff;
  cursor: pointer;
}

button:hover:not(:disabled) { background: var(--color-primary-hover); }
button:disabled { opacity: .45; cursor: not-allowed; }
</style>
