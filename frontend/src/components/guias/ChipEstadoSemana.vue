<template>
    <span class="chip" :class="estado.clase">
        <span v-if="estado.girando" class="giro" aria-hidden="true"></span>
        {{ estado.texto }}
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { estadoSemana } from '@/utils/estadoSemana'

// Etiqueta de estado de una semana (Lista, Pendiente, En preparación, Error, Próximamente)
const props = defineProps({
    semana: { type: Object, required: true },
    generandoAqui: { type: Boolean, default: false }
})

const estado = computed(() => estadoSemana(props.semana, props.generandoAqui))
</script>

<style scoped>
.chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    flex-shrink: 0;
    padding: .15rem .65rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
}

.chip.listo { background: color-mix(in srgb, var(--color-success) 16%, transparent); color: var(--color-success); }
.chip.generando { background: color-mix(in srgb, var(--color-info) 16%, transparent); color: var(--color-info); }
.chip.preparacion { background: color-mix(in srgb, var(--color-warning) 16%, transparent); color: var(--color-warning); }
.chip.error { background: color-mix(in srgb, var(--color-danger) 16%, transparent); color: var(--color-danger); }
.chip.pendiente,
.chip.bloqueada { background: var(--color-card); color: var(--color-text-secondary); }

.giro {
    display: inline-block;
    width: 12px;
    height: 12px;
    flex-shrink: 0;
    border: 2px solid var(--color-card);
    border-top-color: currentColor;
    border-radius: 50%;
    animation: girar 1s linear infinite;
}

@keyframes girar { to { transform: rotate(360deg); } }

/* Quien prefiere menos movimiento ve el indicador quieto */
@media (prefers-reduced-motion: reduce) {
    .giro { animation: none; }
}
</style>
