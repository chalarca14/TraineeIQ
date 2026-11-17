<script setup>
import EsqueletoBloque from './EsqueletoBloque.vue'

// Esqueleto de una tabla (entregas del instructor): encabezado y filas. Las columnas se reparten
// el ancho con minmax(0, 1fr), así en 360 px se encogen en vez de desbordar
defineProps({
  filas: { type: Number, default: 5 },
  columnas: { type: Number, default: 4 }
})
</script>

<template>
  <div class="tabla" :style="{ '--columnas': columnas }">
    <div class="fila fila--cabecera">
      <EsqueletoBloque v-for="c in columnas" :key="c" ancho="60%" alto=".75rem" />
    </div>
    <div v-for="f in filas" :key="f" class="fila">
      <EsqueletoBloque v-for="c in columnas" :key="c" :ancho="c === 1 ? '85%' : '50%'" alto=".85rem" />
    </div>
  </div>
</template>

<style scoped>
.tabla {
  border: 1px solid var(--color-card);
  border-radius: 14px;
  background: var(--color-surface);
  overflow: hidden;
}

.fila {
  display: grid;
  grid-template-columns: repeat(var(--columnas), minmax(0, 1fr));
  gap: var(--espacio-md);
  align-items: center;
  padding: var(--espacio-md);
  border-top: 1px solid var(--color-card);
}

.fila--cabecera {
  border-top: none;
  background: color-mix(in srgb, var(--color-card) 40%, transparent);
}
</style>
