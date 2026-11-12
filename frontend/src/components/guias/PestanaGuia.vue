<template>
    <div class="guia">
        <!-- Texto plano de la guía (transcrito del PDF). Se pinta con {{ }} y white-space: pre-line,
             nunca con v-html: así conserva los saltos de línea sin interpretar nada como HTML -->
        <div v-if="texto" class="lectura">
            <p class="texto">{{ texto }}</p>
        </div>
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
    gap: var(--espacio-md);
}

/* Área de lectura: fondo suave y un borde de acento a la izquierda, para que el texto de la guía
   se distinga del resto del panel */
.lectura {
    padding: var(--espacio-lg);
    border: 1px solid var(--color-card);
    border-left: 3px solid var(--color-primary);
    border-radius: 14px;
    background: var(--color-background);
}

/* ~70 caracteres por línea y más interlineado: se lee con menos esfuerzo que una línea de todo el ancho */
.texto {
    max-width: 70ch;
    white-space: pre-line;
    overflow-wrap: anywhere;
    line-height: 1.75;
    font-size: 1rem;
}

@media (max-width: 520px) {
    .lectura {
        padding: var(--espacio-md);
    }

    .texto {
        font-size: .95rem;
    }
}

.ver {
    align-self: flex-start;
}

.vacio {
    color: var(--color-text-secondary);
}
</style>
