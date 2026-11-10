<template>
    <div class="guia">
        <!-- Texto plano de la guía (transcrito del PDF). Se pinta con {{ }} y white-space: pre-line,
             nunca con v-html: así conserva los saltos de línea sin interpretar nada como HTML -->
        <p v-if="texto" class="texto">{{ texto }}</p>
        <p v-else class="vacio">Esta semana no trae texto de la guía.</p>
    </div>
</template>

<script setup>
// Pestaña "Guía": el contenido_texto de la semana elegida
defineProps({ texto: { type: String, default: '' } })
</script>

<style scoped>
.guia {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.texto {
    white-space: pre-line;
    overflow-wrap: anywhere;
    line-height: 1.65;
    font-size: .93rem;
}

.vacio {
    color: var(--color-text-secondary);
}
</style>
