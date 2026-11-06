<template>
    <span>
        <template v-for="(parte, i) in partes" :key="i">
            <code v-if="parte.codigo">{{ parte.texto }}</code>
            <template v-else>{{ parte.texto }}</template>
        </template>
    </span>
</template>

<script setup>
import { computed } from 'vue'

// La IA escribe el código entre comillas inversas (`dotnet run`). Partimos el texto por ellas:
// los trozos en posición impar son código. No usamos v-html, así que el texto nunca se
// interpreta como HTML y no hay riesgo de inyección.
const props = defineProps({ texto: { type: String, default: '' } })

const partes = computed(() =>
    props.texto
        .split('`')
        .map((texto, i) => ({ texto, codigo: i % 2 === 1 }))
        .filter((parte) => parte.texto)
)
</script>

<style scoped>
code {
    padding: .1rem .4rem;
    border-radius: 6px;
    background: color-mix(in srgb, var(--color-primary) 18%, var(--color-background));
    color: var(--color-primary-light);
    font-family: Consolas, 'Courier New', monospace;
    font-size: .85em;
    overflow-wrap: anywhere;
}
</style>
