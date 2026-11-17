<script setup>
import { computed } from 'vue'
import { CircleCheck, Loader, CircleAlert, Circle } from 'lucide-vue-next'

// En qué punto están las entregas esperadas del grupo (estudiantes × proyectos liberados): una barra
// apilada de partes de un total. Son colores de ESTADO: siempre van con ícono y etiqueta en la leyenda,
// nunca el color solo; entre segmentos hay 2 px de separación para distinguirlos.
const props = defineProps({
  // { evaluadas, enCurso, conError, porEntregar, esperadas } de utils/estadisticasGrupo.js
  estado: { type: Object, required: true }
})

const partes = computed(() => [
  { clave: 'evaluadas', texto: 'Evaluadas', valor: props.estado.evaluadas, icono: CircleCheck, color: 'var(--color-success)' },
  { clave: 'enCurso', texto: 'En curso', ayuda: 'Repositorio guardado o evaluándose', valor: props.estado.enCurso, icono: Loader, color: 'var(--color-info)' },
  { clave: 'conError', texto: 'Con error', valor: props.estado.conError, icono: CircleAlert, color: 'var(--color-danger)' },
  { clave: 'porEntregar', texto: 'Por entregar', valor: props.estado.porEntregar, icono: Circle, color: 'var(--color-card)' }
])

const total = computed(() => partes.value.reduce((suma, p) => suma + p.valor, 0))
</script>

<template>
  <div class="estado-entregas">
    <!-- Si no hay nada esperado (sin estudiantes o sin proyectos liberados) no se dibuja una barra vacía -->
    <p v-if="!total" class="vacio">Aún no hay entregas esperadas: hace falta tener estudiantes y semanas liberadas.</p>

    <template v-else>
      <div class="barra" aria-hidden="true">
        <span v-for="p in partes.filter((x) => x.valor)" :key="p.clave" class="segmento"
          :style="{ flexGrow: p.valor, background: p.color }" :title="`${p.texto}: ${p.valor}`"></span>
      </div>

      <!-- La leyenda es también el dato para el lector de pantalla: ícono + etiqueta + número -->
      <ul class="leyenda">
        <li v-for="p in partes" :key="p.clave" :title="p.ayuda">
          <component :is="p.icono" :size="16" :style="{ color: p.clave === 'porEntregar' ? 'var(--color-text-secondary)' : p.color }"
            aria-hidden="true" />
          <span>{{ p.texto }}</span>
          <strong>{{ p.valor }}</strong>
        </li>
      </ul>
      <p class="nota">De {{ estado.esperadas }} entregas esperadas (cada estudiante, cada mini proyecto liberado).</p>
    </template>
  </div>
</template>

<style scoped>
.estado-entregas {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
}

.barra {
  display: flex;
  gap: 2px;
  height: 14px;
  overflow: hidden;
  border-radius: 4px;
}

.segmento {
  min-width: 4px;
}

.leyenda {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espacio-sm) var(--espacio-lg);
  margin: 0;
  padding: 0;
  list-style: none;
}

.leyenda li {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  color: var(--color-text-secondary);
  font-size: .88rem;
}

.leyenda strong {
  color: var(--color-text);
}

.nota,
.vacio {
  color: var(--color-text-secondary);
  font-size: .82rem;
}
</style>
