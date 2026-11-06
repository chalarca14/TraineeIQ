<script setup>
import { Search, Calendar } from 'lucide-vue-next'
import Pestanas from '@/components/ui/Pestanas.vue'

// Filtros del Historial: pestañas por tipo, rango de fechas y buscador. No filtra nada por sí
// mismo: con defineModel solo edita los valores que viven en la vista, que es quien tiene la lista.
const pestana = defineModel('pestana', { type: String, required: true })
const dias = defineModel('dias', { type: Number, default: 30 })
const busqueda = defineModel('busqueda', { type: String, default: '' })

const PESTANAS = [
  { clave: 'todos', etiqueta: 'Todos' },
  { clave: 'guia', etiqueta: 'Guías' },
  { clave: 'miniproyecto', etiqueta: 'Mini proyectos' },
  { clave: 'ia', etiqueta: 'IA' }
]

// 0 = sin límite. Es número (no texto) para pasarlo directo a dentroDeUltimosDias.
const RANGOS = [
  { dias: 7, etiqueta: 'Últimos 7 días' },
  { dias: 30, etiqueta: 'Últimos 30 días' },
  { dias: 90, etiqueta: 'Últimos 90 días' },
  { dias: 0, etiqueta: 'Todo el historial' }
]
</script>

<template>
  <div class="filtros">
    <Pestanas v-model="pestana" :pestanas="PESTANAS" compacto />

    <div class="herramientas">
      <label class="campo campo--fecha">
        <Calendar :size="16" />
        <select v-model.number="dias" aria-label="Rango de fechas">
          <option v-for="r in RANGOS" :key="r.dias" :value="r.dias">{{ r.etiqueta }}</option>
        </select>
      </label>

      <label class="campo campo--buscar">
        <Search :size="16" />
        <input v-model="busqueda" type="search" placeholder="Buscar en tu historial" aria-label="Buscar en tu historial" />
      </label>
    </div>
  </div>
</template>

<style scoped>
.filtros {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: .8rem;
}

.herramientas {
  display: flex;
  flex: 1;
  gap: .6rem;
  min-width: 0;
  justify-content: flex-end;
}

.campo {
  display: flex;
  align-items: center;
  gap: .5rem;
  min-width: 0;
  padding: 0 .8rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-surface);
  color: var(--color-text-secondary);
}

.campo--buscar {
  flex: 1;
  max-width: 280px;
}

select,
input {
  min-width: 0;
  border: none;
  outline: none;
  background: transparent;
  color: var(--color-text);
}

select {
  padding: .6rem 0;
}

input {
  flex: 1;
  padding: .6rem 0;
}

/* En celular cada herramienta ocupa su propia fila para que el buscador no se aplaste */
@media (max-width: 600px) {
  .herramientas {
    flex-direction: column;
    flex-basis: 100%;
  }

  .campo--buscar {
    max-width: none;
  }
}
</style>
