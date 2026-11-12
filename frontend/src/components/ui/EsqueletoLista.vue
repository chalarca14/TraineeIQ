<script setup>
import EsqueletoBloque from './EsqueletoBloque.vue'

// Esqueleto de una lista (guías, chats, mini proyectos, avisos): filas con ícono y líneas de texto
defineProps({
  filas: { type: Number, default: 4 },
  lineas: { type: Number, default: 2 },
  conIcono: { type: Boolean, default: true }
})

const ANCHOS = ['60%', '40%', '75%']
</script>

<template>
  <ul class="lista">
    <li v-for="f in filas" :key="f" class="fila">
      <EsqueletoBloque v-if="conIcono" ancho="40px" alto="40px" radio="10px" />
      <div class="lineas">
        <EsqueletoBloque v-for="n in lineas" :key="n" :ancho="ANCHOS[(n + f) % ANCHOS.length]"
          :alto="n === 1 ? '.95rem' : '.75rem'" />
      </div>
    </li>
  </ul>
</template>

<style scoped>
.lista {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

.fila {
  display: flex;
  align-items: center;
  gap: var(--espacio-md);
  padding: var(--espacio-md);
  border: 1px solid var(--color-card);
  border-radius: 14px;
  background: var(--color-surface);
}

.lineas {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--espacio-sm);
  min-width: 0;
}
</style>
