<script setup>
import { FileText, Search } from 'lucide-vue-next'

// Columna lateral de la pestaña Guías: buscador, orden y total. Solo cambia los valores (v-model);
// filtrar y ordenar lo hace la lista, que es la que tiene las guías.
const busqueda = defineModel('busqueda', { type: String, default: '' })
const orden = defineModel('orden', { type: String, default: 'recientes' })
// null mientras carga: se muestra "…" en lugar de un 0 que no es cierto
defineProps({ total: { type: Number, default: null } })
</script>

<template>
  <aside class="lateral">
    <label class="buscador">
      <Search :size="16" aria-hidden="true" />
      <input v-model="busqueda" type="search" placeholder="Buscar guía por nombre…" aria-label="Buscar guía por nombre" />
    </label>
    <label class="orden">
      <span>Ordenar por</span>
      <select v-model="orden">
        <option value="recientes">Más recientes</option>
        <option value="nombre">Nombre (A-Z)</option>
        <option value="semanas">Más semanas</option>
      </select>
    </label>
    <div class="total">
      <span class="total__icono" aria-hidden="true"><FileText :size="22" /></span>
      <div>
        <span>Total de guías</span>
        <strong>{{ total ?? '…' }}</strong>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.lateral {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
  padding: var(--espacio-md);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: var(--color-surface);
}

.buscador {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding: 0 var(--espacio-md);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-background);
  color: var(--color-text-secondary);
}

.buscador:focus-within {
  border-color: var(--color-primary);
}

.buscador input,
.orden select {
  flex: 1;
  min-width: 0;
  min-height: 44px;
  border: none;
  outline: none;
  background: transparent;
  color: var(--color-text);
  font: inherit;
}

.orden {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-xs);
  color: var(--color-text-secondary);
  font-size: .85rem;
}

.orden select {
  padding: 0 var(--espacio-sm);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-background);
}

.total {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding-top: var(--espacio-sm);
  border-top: 1px solid var(--color-border);
}

.total__icono {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--color-primary) 25%, var(--color-card));
  color: var(--color-primary-light);
}

/* Solo el texto: la caja del ícono también es un <span> y debe seguir centrando su ícono */
.total div span {
  display: block;
  color: var(--color-text-secondary);
  font-size: .82rem;
}

.total strong {
  font-size: 1.4rem;
}
</style>
