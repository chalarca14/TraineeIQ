<template>
  <section class="error" role="alert">
    <span class="icono" aria-hidden="true"><TriangleAlert :size="28" /></span>
    <h2>No pudimos evaluar tu entrega</h2>
    <p class="mensaje">{{ mensaje }}</p>
    <!-- Detalle técnico del backend, en texto plano (nunca v-html) -->
    <p v-if="detalle" class="detalle">{{ detalle }}</p>

    <div class="acciones">
      <button type="button" class="btn btn-primario" :disabled="enviando" @click="$emit('reintentar')">
        <RefreshCw :size="16" /> {{ enviando ? 'Enviando…' : 'Reintentar' }}
      </button>
      <button type="button" class="btn btn-secundario" @click="$emit('cambiar-enlace')">Cambiar el enlace</button>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { TriangleAlert, RefreshCw } from 'lucide-vue-next'

const props = defineProps({
  entrega: { type: Object, required: true },
  enviando: { type: Boolean, default: false }
})
defineEmits(['reintentar', 'cambiar-enlace'])

// Mensajes claros por código de error. El backend decide el código; aquí solo se traduce a
// una frase que le diga al aprendiz QUÉ hacer.
const MENSAJES = {
  repo_no_encontrado: 'No encontramos el repositorio. Revisa que el enlace esté bien escrito.',
  repo_privado: 'El repositorio es privado. Hazlo público en GitHub e inténtalo de nuevo.',
  repo_vacio: 'El repositorio está vacío. Sube el código de tu proyecto y vuelve a entregar.',
  repo_muy_grande: 'El repositorio es demasiado grande para analizarlo. Quita los archivos que no hagan falta.',
  ia_no_pudo: 'La IA no pudo analizar tu proyecto esta vez. Inténtalo de nuevo en un momento.'
}

const mensaje = computed(() => MENSAJES[props.entrega.codigo_error] ?? 'Ocurrió un problema al evaluar tu repositorio.')
// Solo se muestra el detalle del backend cuando aporta algo distinto al mensaje de arriba
const detalle = computed(() => (MENSAJES[props.entrega.codigo_error] ? '' : props.entrega.mensaje_error ?? ''))
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
.mensaje { max-width: 48ch; color: var(--color-text); }
.detalle { color: var(--color-text-secondary); font-size: .85rem; }

.acciones {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: .6rem;
  margin-top: .4rem;
}
</style>
