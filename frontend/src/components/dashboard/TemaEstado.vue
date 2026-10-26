<template>
    <div class="tema-estado" :class="estado">
        <!-- Hecho: check. Actual: número resaltado. Pendiente: círculo vacío con el número -->
        <span class="marca">
            <Check v-if="estado === 'hecho'" :size="14" />
            <template v-else>{{ numero }}</template>
        </span>
        <span class="nombre">{{ nombre }}</span>
    </div>
</template>

<script setup>
import { Check } from 'lucide-vue-next'

defineProps({
    numero: { type: Number, required: true },
    nombre: { type: String, required: true },
    // Solo tres valores posibles; validator avisa en consola si llega otro
    estado: {
        type: String,
        default: 'pendiente',
        validator: (valor) => ['hecho', 'actual', 'pendiente'].includes(valor)
    }
})
</script>

<style scoped>
.tema-estado {
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 170px;
    padding: .7rem .9rem;
    border: 1px solid var(--color-card);
    border-radius: 12px;
    background: var(--color-background);
    font-size: .82rem;
}

.marca {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    border: 1px solid var(--color-text-secondary);
    border-radius: 50%;
    font-size: .72rem;
    color: var(--color-text-secondary);
}

.nombre {
    color: var(--color-text-secondary);
    /* Si el nombre es largo, se corta en 2 líneas en vez de agrandar la tarjeta */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.hecho .marca {
    border-color: var(--color-success);
    background: rgba(34, 197, 94, .15);
    color: var(--color-success);
}

.actual {
    border-color: var(--color-primary);
}

.actual .marca {
    border-color: var(--color-primary);
    background: var(--color-primary);
    color: var(--color-text);
}

.actual .nombre {
    color: var(--color-text);
}
</style>