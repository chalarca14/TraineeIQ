<script setup>
import { computed } from 'vue'
import { Send } from 'lucide-vue-next'

// v-model: el texto vive en la vista, que es quien lo vacía en cuanto se envía.
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
  transition: border-color .2s, box-shadow .2s;
}

/* El anillo morado aparece cuando se escribe, como en los demás campos de la app */
.entrada:focus-within {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 25%, transparent);
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
  border-radius: 12px;
  background: var(--gradiente-primario);
  box-shadow: var(--brillo-primario);
  color: var(--color-text);
  cursor: pointer;
  transition: transform .15s;
}

button:hover:not(:disabled) { transform: translateY(-1px); }
button:disabled { opacity: .45; cursor: not-allowed; }
</style>
