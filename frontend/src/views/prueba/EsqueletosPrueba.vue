<script setup>
// PRUEBA: muestra los esqueletos sin depender del backend. Cada botón simula una carga de distinta
// duración: la rápida no debe hacer "flash" y la de 12 s debe mostrar el aviso del servidor.
import { ref } from 'vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import EsqueletoTabla from '@/components/ui/EsqueletoTabla.vue'

const cargando = ref(false)
let temporizador = null

function simular(ms) {
  clearTimeout(temporizador)
  cargando.value = true
  temporizador = setTimeout(() => { cargando.value = false }, ms)
}
</script>

<template>
  <main class="prueba">
    <h1>Prueba de esqueletos</h1>
    <div class="botones">
      <button type="button" class="btn btn-secundario btn-sm" @click="simular(150)">Carga rápida (150 ms)</button>
      <button type="button" class="btn btn-secundario btn-sm" @click="simular(3000)">Carga de 3 s</button>
      <button type="button" class="btn btn-secundario btn-sm" @click="simular(12000)">Carga lenta (12 s)</button>
    </div>

    <EstadoVista :cargando="cargando" texto-cargando="Cargando la prueba…">
      <template #cargando>
        <div class="tarjetas">
          <EsqueletoTarjeta v-for="n in 4" :key="n" />
        </div>
        <EsqueletoLista :filas="3" />
        <EsqueletoTabla :filas="4" :columnas="4" />
      </template>

      <p class="listo">Datos cargados. Pulsa un botón para simular otra carga.</p>
    </EstadoVista>
  </main>
</template>

<style scoped>
.prueba {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
  max-width: 1100px;
  margin: 0 auto;
  padding: clamp(1rem, 4vw, 2.5rem);
}

.botones {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espacio-sm);
}

.tarjetas {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: var(--espacio-md);
}

.listo {
  color: var(--color-text-secondary);
}
</style>
