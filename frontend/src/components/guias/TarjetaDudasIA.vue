<template>
    <aside class="dudas">
        <span class="icono" aria-hidden="true"><Sparkles :size="22" /></span>
        <div class="texto">
            <h2>¿Tienes dudas sobre esta guía?</h2>
            <p>Pregunta a la IA y recibe explicaciones, ejemplos y recursos adicionales.</p>
        </div>
        <!-- Lleva al chat en modo guiado: la IA solo responde sobre esta guía -->
        <RouterLink :to="{ path: '/estudiante/ia', query: { guiaId } }" class="btn btn-primario">
            Abrir chat con la IA <ArrowRight :size="16" aria-hidden="true" />
        </RouterLink>
    </aside>
</template>

<script setup>
import { Sparkles, ArrowRight } from 'lucide-vue-next'

defineProps({ guiaId: { type: Number, required: true } })
</script>

<style scoped>
.dudas {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    align-items: start;
    gap: .8rem;
    padding: 1rem;
    border: 1px solid color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
    border-radius: 16px;
    background: linear-gradient(140deg, color-mix(in srgb, var(--color-primary) 22%, var(--color-surface)), var(--color-surface));
}

.icono {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-primary) 25%, var(--color-card));
    color: var(--color-primary-light);
}

h2 {
    font-size: .95rem;
    overflow-wrap: anywhere;
}

p {
    margin-top: .25rem;
    color: var(--color-text-secondary);
    font-size: .82rem;
}

.btn {
    grid-column: 1 / -1;
    justify-content: center;
}
</style>
