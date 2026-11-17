<template>
    <!-- Pasar a la semana anterior o siguiente sin volver a la lista. Si no hay, el lado queda vacío -->
    <nav class="navegacion" aria-label="Cambiar de semana">
        <button v-if="anterior" type="button" class="paso" @click="$emit('ir', anterior.numero_semana)">
            <ArrowLeft :size="18" aria-hidden="true" />
            <span class="texto">
                <small>Semana anterior</small>
                <strong>{{ anterior.numero_semana }}. {{ anterior.titulo || 'Sin título' }}</strong>
            </span>
        </button>
        <span v-else></span>

        <button v-if="siguiente" type="button" class="paso paso--siguiente" @click="$emit('ir', siguiente.numero_semana)">
            <span class="texto">
                <small>Semana siguiente</small>
                <strong>{{ siguiente.numero_semana }}. {{ siguiente.titulo || 'Sin título' }}</strong>
            </span>
            <ArrowRight :size="18" aria-hidden="true" />
        </button>
    </nav>
</template>

<script setup>
import { ArrowLeft, ArrowRight } from 'lucide-vue-next'

// Solo avisa a qué semana ir; quien la abre (y pide su contenido) es la vista, con el store
defineProps({
    anterior: { type: Object, default: null },
    siguiente: { type: Object, default: null }
})
defineEmits(['ir'])
</script>

<style scoped>
.navegacion {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--espacio-sm);
    padding-top: var(--espacio-md);
    border-top: 1px solid var(--color-border);
}

.paso {
    display: flex;
    align-items: center;
    gap: var(--espacio-sm);
    min-width: 0;
    min-height: 44px;
    padding: var(--espacio-sm) var(--espacio-md);
    border: 1px solid var(--color-card);
    border-radius: 12px;
    background: var(--color-background);
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}

.paso:hover {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 10%, var(--color-background));
}

.paso svg {
    flex-shrink: 0;
    color: var(--color-primary-light);
}

.paso--siguiente {
    grid-column: 2;
    justify-content: flex-end;
    text-align: right;
}

.texto {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.texto small {
    color: var(--color-text-secondary);
    font-size: .75rem;
}

.texto strong {
    overflow: hidden;
    font-size: .88rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Celular: un botón debajo del otro, para que el título de la semana se lea completo */
@media (max-width: 520px) {
    .navegacion {
        grid-template-columns: minmax(0, 1fr);
    }

    .paso--siguiente {
        grid-column: 1;
    }

    .texto strong {
        white-space: normal;
    }
}
</style>
