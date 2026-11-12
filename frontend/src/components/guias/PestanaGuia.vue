<template>
    <div class="guia">
        <!-- Texto de la guía (transcrito del PDF), organizado en títulos, párrafos y código por TextoGuia.
             Se pinta como texto, nunca con v-html: nada del PDF se interpreta como HTML -->
        <div v-if="texto" class="lectura">
            <TextoGuia :texto="texto" />
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
import TextoGuia from '@/components/guias/TextoGuia.vue'

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

@media (max-width: 520px) {
    .lectura {
        padding: var(--espacio-md);
    }
}

.ver {
    align-self: flex-start;
}

.vacio {
    color: var(--color-text-secondary);
}
</style>
