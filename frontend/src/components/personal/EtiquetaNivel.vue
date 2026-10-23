<template>
    <span class="etiqueta" :style="{ '--c': estilo.color }">{{ estilo.etiqueta }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ valor: { type: String, required: true } })

const ESTILOS = {
    basico: { etiqueta: 'Básico', color: '#22C55E' },
    recomendado: { etiqueta: 'Recomendado', color: '#3B82F6' },
    intermedio: { etiqueta: 'Intermedio', color: '#F59E0B' },
    importante: { etiqueta: 'Importante', color: '#F59E0B' },
    avanzado: { etiqueta: 'Avanzado', color: '#EF4444' }
}

// Si llega un valor desconocido, mostramos el texto tal cual en vez de romper
const estilo = computed(() => ESTILOS[props.valor] ?? { etiqueta: props.valor, color: '#94A3B8' })
</script>

<style scoped>
.etiqueta {
    padding: .15rem .6rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 600;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 16%, transparent);
    white-space: nowrap;
}
</style>