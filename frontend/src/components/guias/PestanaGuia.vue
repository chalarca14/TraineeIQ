<template>
    <div class="guia">
        <!-- Texto plano de la guía (transcrito del PDF). Se pinta con {{ }} y white-space: pre-line,
             nunca con v-html: así conserva los saltos de línea sin interpretar nada como HTML -->
        <p v-if="texto" class="texto">{{ texto }}</p>
        <p v-else class="vacio">Esta semana no trae texto de la guía.</p>

        <!-- El id fijo deja que la vista le devuelva el foco al volver de "Guía completa" -->
        <button id="btn-ver-guia-completa" type="button" class="btn btn-secundario btn-sm ver" @click="$emit('ver-completa')">
            <FileText :size="15" aria-hidden="true" /> Ver guía completa
        </button>
    </div>
</template>

<script setup>
import { FileText } from 'lucide-vue-next'

// Pestaña "Guía": el contenido_texto de la semana elegida y el acceso a la guía completa
defineProps({ texto: { type: String, default: '' } })
defineEmits(['ver-completa'])
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

.ver {
    align-self: flex-start;
}

.vacio {
    color: var(--color-text-secondary);
}
</style>
