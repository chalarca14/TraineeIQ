<template>
    <span class="chip" :style="{ '--c': tecnologia.color }">
        <b class="insignia" aria-hidden="true">{{ tecnologia.abrev }}</b>
        {{ tecnologia.etiqueta }}
    </span>
</template>

<script setup>
// Etiqueta de tecnología: insignia de color con la abreviatura + el nombre.
// Se usa una insignia (y no el logo oficial) para no depender de imágenes de marca;
// si más adelante se agregan los logos SVG, solo cambia el contenido de .insignia.
defineProps({ tecnologia: { type: Object, required: true } })
</script>

<style scoped>
.chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .15rem .65rem .15rem .2rem;
    border: 1px solid color-mix(in srgb, var(--c) 35%, transparent);
    border-radius: 999px;
    background: color-mix(in srgb, var(--c) 10%, var(--color-surface));
    color: var(--color-text);
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}

.insignia {
    display: grid;
    place-items: center;
    min-width: 22px;
    height: 22px;
    padding: 0 .25rem;
    border-radius: 999px;
    background: var(--c);
    color: var(--color-background);
    font-size: .62rem;
}
</style>
