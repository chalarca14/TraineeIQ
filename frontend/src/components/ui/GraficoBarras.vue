<script setup>
import { computed, useId } from 'vue'

// Gráfica de barras verticales de UNA serie, hecha con CSS (sin librerías). Reglas de la guía de
// visualización: un solo color (es una sola serie: el título dice qué es, sin leyenda), el número
// escrito sobre cada barra con color de texto (no depende del color), extremos redondeados apoyados
// en la base, y una tabla equivalente para el lector de pantalla.
const props = defineProps({
  // [{ etiqueta, valor, texto?, ayuda? }] texto: lo que se escribe sobre la barra (por defecto el valor)
  datos: { type: Array, required: true },
  // Valor que llena toda la altura (por ejemplo 100 para porcentajes). Por defecto, el mayor de los datos
  maximo: { type: Number, default: null },
  // Para la tabla del lector de pantalla
  descripcion: { type: String, required: true },
  columnaEtiqueta: { type: String, default: 'Categoría' },
  columnaValor: { type: String, default: 'Valor' }
})

const idDescripcion = useId()
const tope = computed(() => props.maximo ?? Math.max(1, ...props.datos.map((d) => d.valor)))
const alto = (valor) => `${Math.min(100, (valor / tope.value) * 100)}%`
</script>

<template>
  <figure class="grafico">
    <!-- La parte visual se oculta al lector de pantalla: él lee la tabla de abajo -->
    <div class="area" aria-hidden="true">
      <div v-for="d in datos" :key="d.etiqueta" class="columna" :title="d.ayuda ?? `${d.etiqueta}: ${d.texto ?? d.valor}`">
        <div class="pista">
          <span class="valor">{{ d.texto ?? d.valor }}</span>
          <div class="barra" :class="{ 'barra--cero': !d.valor }" :style="{ height: alto(d.valor) }"></div>
        </div>
        <span class="etiqueta">{{ d.etiqueta }}</span>
      </div>
    </div>

    <!-- La tabla va dentro de un div oculto: una <table> no se encoge a 1 px y se salía del contenedor -->
    <div class="solo-lector">
      <table>
        <caption :id="idDescripcion">{{ descripcion }}</caption>
        <thead><tr><th scope="col">{{ columnaEtiqueta }}</th><th scope="col">{{ columnaValor }}</th></tr></thead>
        <tbody>
          <tr v-for="d in datos" :key="d.etiqueta"><th scope="row">{{ d.etiqueta }}</th><td>{{ d.ayuda ?? d.texto ?? d.valor }}</td></tr>
        </tbody>
      </table>
    </div>
  </figure>
</template>

<style scoped>
.grafico {
  margin: 0;
}

.area {
  display: flex;
  align-items: stretch;
  gap: var(--espacio-sm);
  height: 190px;
  padding-top: var(--espacio-sm);
}

.columna {
  display: flex;
  flex: 1 1 0;
  flex-direction: column;
  gap: var(--espacio-xs);
  min-width: 0;
  max-width: 72px;
  margin: 0 auto;
}

/* La pista ocupa la altura disponible; la barra crece desde su base (la línea de abajo) */
.pista {
  position: relative;
  display: flex;
  flex: 1;
  flex-direction: column;
  justify-content: flex-end;
  align-items: center;
  border-bottom: 1px solid var(--color-card);
}

.valor {
  margin-bottom: .3rem;
  color: var(--color-text);
  font-size: .82rem;
  font-weight: 700;
  white-space: nowrap;
}

/* Barra delgada con extremo redondeado de 4 px y apoyada en la base */
.barra {
  width: min(100%, 36px);
  border-radius: 4px 4px 0 0;
  background: var(--color-primary-light);
  transition: height .4s ease;
}

/* Un valor 0 no desaparece del todo: se ve una línea fina en la base */
.barra--cero {
  height: 2px !important;
  background: var(--color-card);
}

.etiqueta {
  overflow: hidden;
  color: var(--color-text-secondary);
  font-size: .75rem;
  text-align: center;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.solo-lector {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

@media (prefers-reduced-motion: reduce) {
  .barra {
    transition: none;
  }
}
</style>
