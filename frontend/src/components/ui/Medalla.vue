<template>
    <span class="medalla" :style="{ '--c': COLORES[color] ?? COLORES.primary, width: size + 'px', height: size + 'px' }"
        aria-hidden="true">
        <component :is="icono" :size="Math.round(size * 0.55)" />
    </span>
</template>

<script setup>
// Círculo de color con un icono dentro, usado en los títulos de sección y en las tarjetas.
// Es decorativo (aria-hidden): el texto que lo acompaña ya dice de qué trata la sección.
defineProps({
    icono: { type: [Object, Function], required: true },
    color: { type: String, default: 'primary' },
    size: { type: Number, default: 32 }
})

const COLORES = {
    primary: 'var(--color-primary-light)',
    info: 'var(--color-info)',
    success: 'var(--color-success)',
    warning: 'var(--color-warning)',
    danger: 'var(--color-danger)'
}
</script>

<style scoped>
.medalla {
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: color-mix(in srgb, var(--c) 22%, transparent);
    color: var(--c);
}
</style>
