<script setup>
import { ref, onMounted, onUnmounted, useId } from 'vue'
import { useAtraparFoco } from '@/composables/useAtraparFoco'

// Ventana modal común (crear grupo, eliminar grupo, y las que vengan: liberar semana…).
// Reúne lo que todas deben cumplir, para no repetirlo en cada una:
// - role="dialog", aria-modal y el título enlazado con aria-labelledby;
// - el foco entra al abrir, queda atrapado (Tab da la vuelta) y vuelve a donde estaba al cerrar;
// - Esc cierra, pero un clic en el fondo NO (un clic sin querer borraría lo escrito);
// - "bloqueado": mientras se envía no se puede cerrar, para no perder la respuesta del servidor.
const props = defineProps({
  titulo: { type: String, required: true },
  bloqueado: { type: Boolean, default: false },
  // Borde rojo para confirmaciones destructivas
  peligro: { type: Boolean, default: false }
})
const emit = defineEmits(['cerrar'])

const idTitulo = useId()
const ventana = ref(null)
let focoAnterior = null

useAtraparFoco(ventana)

function alTeclear(evento) {
  if (evento.key === 'Escape' && !props.bloqueado) emit('cerrar')
}

onMounted(() => {
  focoAnterior = document.activeElement
  // Primero el elemento marcado con data-foco-inicial; si no hay, el primero que acepte foco
  const destino = ventana.value?.querySelector('[data-foco-inicial]')
    ?? ventana.value?.querySelector('input, textarea, button:not([disabled])')
  destino?.focus()
  document.addEventListener('keydown', alTeclear)
})

onUnmounted(() => {
  document.removeEventListener('keydown', alTeclear)
  // Quien abrió la ventana recupera el foco (si su botón sigue en la página)
  if (focoAnterior?.isConnected) focoAnterior.focus()
})
</script>

<template>
  <!-- Teleport: la ventana se pinta en <body> para que ningún contenedor la recorte.
       mousedown.prevent en el fondo: el clic ahí no cierra ni le quita el foco a la ventana -->
  <Teleport to="body">
    <div class="fondo" @mousedown.self.prevent>
      <div ref="ventana" class="modal" :class="{ 'modal--peligro': peligro }" role="dialog" aria-modal="true"
        :aria-labelledby="idTitulo">
        <h2 :id="idTitulo">{{ titulo }}</h2>
        <slot />
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.fondo {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  padding: var(--espacio-md);
  overflow-y: auto;
  background: color-mix(in srgb, var(--color-background) 75%, transparent);
  backdrop-filter: blur(3px);
}

.modal {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  width: min(100%, 460px);
  padding: var(--espacio-lg);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: var(--color-surface);
  box-shadow: var(--brillo-primario-fuerte);
}

.modal--peligro {
  border-color: color-mix(in srgb, var(--color-danger) 60%, transparent);
  box-shadow: 0 8px 22px color-mix(in srgb, var(--color-danger) 30%, transparent);
}

h2 {
  font-size: 1.2rem;
  color: var(--color-text);
}
</style>
