<script setup>
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import EsqueletoBloque from '@/components/ui/EsqueletoBloque.vue'

// Esqueleto de una conversación mientras carga: burbujas alternadas (aprendiz a la derecha, IA a la
// izquierda) con la forma de los mensajes reales. Va en su propio archivo porque ChatIA.vue ya está
// cerca del máximo de 300 líneas.
const BURBUJAS = [
  { lado: 'derecha', ancho: '45%', alto: '2.6rem' },
  { lado: 'izquierda', ancho: '70%', alto: '5.5rem' },
  { lado: 'derecha', ancho: '35%', alto: '2.6rem' }
]
</script>

<template>
  <CargaEsqueleto texto="Cargando la conversación…">
    <div v-for="(b, i) in BURBUJAS" :key="i" class="fila" :class="`fila--${b.lado}`">
      <EsqueletoBloque :ancho="b.ancho" :alto="b.alto" radio="16px" />
    </div>
  </CargaEsqueleto>
</template>

<style scoped>
.fila {
  display: flex;
}

.fila--derecha {
  justify-content: flex-end;
}
</style>
