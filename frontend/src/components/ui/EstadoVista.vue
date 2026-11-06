<script setup>
import Medalla from '@/components/ui/Medalla.vue'

// Los tres estados que toda vista con datos reales debe manejar (CLAUDE.md): cargando, error y
// vacío. Antes cada vista repetía su propio HTML y CSS; ahora se declaran aquí una sola vez.
// Si no hay ninguno de los tres, se muestra el contenido normal (slot por defecto).
// El slot "vacio" sirve para añadir un botón o enlace debajo del mensaje de vacío.
defineProps({
  cargando: { type: Boolean, default: false },
  error: { type: String, default: '' },        // texto del error; '' = sin error
  vacio: { type: Boolean, default: false },
  textoCargando: { type: String, default: 'Cargando...' },
  textoVacio: { type: String, default: 'No hay nada para mostrar.' },
  descripcionVacio: { type: String, default: '' },
  icono: { type: [Object, Function], default: null }
})
defineEmits(['reintentar'])
</script>

<template>
  <p v-if="cargando" class="estado">{{ textoCargando }}</p>

  <div v-else-if="error" class="caja caja--error" role="alert">
    <p>{{ error }}</p>
    <button type="button" class="btn btn-secundario btn-sm" @click="$emit('reintentar')">Reintentar</button>
  </div>

  <div v-else-if="vacio" class="caja">
    <Medalla v-if="icono" :icono="icono" :size="56" />
    <p class="caja__titulo">{{ textoVacio }}</p>
    <span v-if="descripcionVacio">{{ descripcionVacio }}</span>
    <slot name="vacio" />
  </div>

  <slot v-else />
</template>

<style scoped>
.estado {
  color: var(--color-text-secondary);
}

.caja {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .5rem;
  padding: 2.5rem 1rem;
  border: 1px dashed var(--color-card);
  border-radius: 16px;
  color: var(--color-text-secondary);
  text-align: center;
}

.caja__titulo {
  color: var(--color-text);
  font-weight: 600;
}

.caja--error,
.caja--error p {
  color: var(--color-danger);
}
</style>
