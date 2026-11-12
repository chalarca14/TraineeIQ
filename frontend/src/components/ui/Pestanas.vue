<template>
  <div class="pestanas" :class="{ compacto }" role="tablist">
    <button v-for="p in pestanas" :key="p.clave" type="button" role="tab" :class="{ activa: modelo === p.clave }"
      :aria-selected="modelo === p.clave" @click="modelo = p.clave">
      <component :is="p.icono" v-if="p.icono" :size="compacto ? 15 : 18" />
      {{ p.etiqueta }}
    </button>
  </div>
</template>

<script setup>
// Pestañas compartidas. Solo cambian el valor activo (v-model): quién muestra qué contenido
// lo decide la vista. `pestanas` es una lista de { clave, etiqueta, icono? }.
// Dos tamaños: normal (con borde, para secciones como Descripción/Evaluación) y compacto
// (píldoras, para filtros como Todos/En progreso).
const modelo = defineModel({ type: String, required: true })
defineProps({
  pestanas: { type: Array, required: true },
  compacto: { type: Boolean, default: false }
})
</script>

<style scoped>
.pestanas {
  display: flex;
  gap: .6rem;
}

button {
  display: flex;
  align-items: center;
  gap: .55rem;
  padding: .7rem 1.4rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-surface);
  color: var(--color-text-secondary);
  font-weight: 600;
  cursor: pointer;
  transition: color .2s, border-color .2s;
}

button:hover {
  border-color: var(--color-primary);
  color: var(--color-text);
}

/* La activa lleva degradado y resplandor */
button.activa {
  border-color: transparent;
  background: var(--gradiente-primario);
  box-shadow: var(--brillo-primario);
  color: var(--color-text);
}

/* Compacto: píldoras sin borde, para filtros */
.compacto {
  flex-wrap: wrap;
  gap: .5rem;
}

.compacto button {
  padding: .45rem 1rem;
  border: none;
  border-radius: 999px;
  background: transparent;
  box-shadow: none;
}

.compacto button.activa {
  background: var(--color-primary);
}

@media (max-width: 520px) {
  /* "1 1 auto" y no "1": con "1" todas medían lo mismo y "Mi progreso" o "Información personal"
     no cabían en su parte y se partían en dos líneas. Con "auto" cada una parte del ancho de su
     texto y reparte el espacio que sobre. */
  .pestanas:not(.compacto) button {
    flex: 1 1 auto;
    justify-content: center;
    gap: .4rem;
    padding: .7rem .6rem;
    white-space: nowrap;
  }

  /* 40 px de alto mínimo: con 29 px los filtros quedaban difíciles de tocar con el dedo */
  .compacto button {
    min-height: 40px;
    justify-content: center;
  }
}
</style>
