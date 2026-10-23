<template>
    <article class="stat-card">
        <span class="stat-icon">
            <component :is="icono" :size="22" />
        </span>

        <div class="stat-body">
            <span class="stat-label">{{ etiqueta }}</span>
            <strong class="stat-value">{{ valor }}</strong>

            <!-- Si la tarjeta trae un progreso, muestra la barra; si no, el texto de detalle -->
            <div v-if="progreso !== undefined" class="stat-bar">
                <div class="stat-bar-fill" :style="{ width: progreso + '%' }"></div>
            </div>
            <small v-else class="stat-detail">{{ detalle }}</small>
        </div>
    </article>
</template>

<script setup>
// Los íconos de lucide son componentes, por eso el tipo es Object o Function
defineProps({
    icono: { type: [Object, Function], required: true },
    etiqueta: { type: String, required: true },
    valor: { type: [String, Number], required: true },
    detalle: String,
    progreso: Number
})
</script>

<style scoped>
.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
    transition: .2s;
}

.stat-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-3px);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border-radius: 12px;
    background: rgba(124, 58, 237, .18);
    color: var(--color-primary);
}

.stat-body {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    min-width: 0;
    flex: 1;
}

.stat-label {
    font-size: .8rem;
    color: var(--color-text-secondary);
}

.stat-value {
    font-size: 1.6rem;
    line-height: 1.1;
    color: var(--color-text);
}

.stat-detail {
    font-size: .75rem;
    color: var(--color-text-secondary);
}

.stat-bar {
    height: 6px;
    margin-top: .3rem;
    border-radius: 999px;
    background: var(--color-background);
    overflow: hidden;
}

.stat-bar-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    transition: width .4s ease;
}
</style>