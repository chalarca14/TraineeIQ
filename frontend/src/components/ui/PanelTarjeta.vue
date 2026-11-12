<script setup>
// Bloque con título e ícono ("Información del grupo", "Guías asignadas"…). Así todos los paneles del
// instructor tienen el mismo borde, fondo y encabezado sin repetir el CSS en cada componente.
// Slot "accion": un enlace o botón a la derecha del título (por ejemplo "Ver todas").
defineProps({
  titulo: { type: String, required: true },
  icono: { type: [Object, Function], default: null }
})
</script>

<template>
  <section class="panel">
    <header class="panel__cabecera">
      <h2>
        <component :is="icono" v-if="icono" :size="19" aria-hidden="true" />
        {{ titulo }}
      </h2>
      <slot name="accion" />
    </header>
    <slot />
  </section>
</template>

<style scoped>
.panel {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
  min-width: 0;
  padding: 1.2rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

.panel__cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--espacio-sm);
}

h2 {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: 1.05rem;
}

h2 svg {
  color: var(--color-primary-light);
}
</style>
