<script setup>
import { ref, onUnmounted } from 'vue'
import { Copy, Check } from 'lucide-vue-next'

// Código de acceso del grupo con su botón "Copiar". Lo usan la tarjeta de "Mis grupos" y la cabecera
// del detalle del grupo: un solo lugar para la lógica del portapapeles y sus avisos.
const props = defineProps({
  codigo: { type: String, required: true },
  // Sin la etiqueta "Código de acceso" encima (cuando el contexto ya lo dice)
  sinEtiqueta: { type: Boolean, default: false }
})

// '' | 'copiado' | 'error'. Se anuncia con aria-live para quien usa lector de pantalla
const copia = ref('')
let temporizador = null

async function copiar() {
  clearTimeout(temporizador)
  try {
    await navigator.clipboard.writeText(props.codigo)
    copia.value = 'copiado'
  } catch {
    // Sin permiso del portapapeles: el código queda a la vista y se puede seleccionar a mano
    copia.value = 'error'
  }
  temporizador = setTimeout(() => { copia.value = '' }, 2500)
}

onUnmounted(() => clearTimeout(temporizador))
</script>

<template>
  <div class="codigo">
    <span v-if="!sinEtiqueta" class="codigo__etiqueta">Código de acceso</span>
    <div class="codigo__fila">
      <code class="codigo__valor">{{ codigo }}</code>
      <button type="button" class="btn btn-secundario btn-sm" :aria-label="`Copiar el código ${codigo}`"
        @click="copiar">
        <Check v-if="copia === 'copiado'" :size="16" aria-hidden="true" />
        <Copy v-else :size="16" aria-hidden="true" />
        {{ copia === 'copiado' ? 'Copiado' : 'Copiar' }}
      </button>
    </div>
    <p class="codigo__estado" :class="{ 'codigo__estado--error': copia === 'error' }" aria-live="polite">
      <template v-if="copia === 'copiado'">Código copiado. Compártelo con tus aprendices.</template>
      <template v-else-if="copia === 'error'">No se pudo copiar: selecciona el código y cópialo a mano.</template>
    </p>
  </div>
</template>

<style scoped>
.codigo__etiqueta {
  color: var(--color-text-secondary);
  font-size: .8rem;
}

.codigo__fila {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
  margin-top: .25rem;
}

/* user-select: all: un clic selecciona el código completo, por si el botón Copiar no funciona */
.codigo__valor {
  padding: .45rem .8rem;
  border: 1px solid var(--color-card);
  border-radius: 10px;
  background: var(--color-background);
  font-size: 1rem;
  letter-spacing: .08em;
  user-select: all;
}

.codigo__estado {
  min-height: 1rem;
  margin-top: .25rem;
  color: var(--color-success);
  font-size: .78rem;
}

.codigo__estado--error {
  color: var(--color-warning);
}
</style>
