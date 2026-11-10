<template>
    <article class="stat-card" :style="{ '--c': COLORES[color] ?? COLORES.primary }">
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
    progreso: Number,
    // Color de la medalla: así cada estadística se distingue de un vistazo
    color: { type: String, default: 'primary' }
})

const COLORES = {
    primary: 'var(--color-primary-light)',
    info: 'var(--color-info)',
    success: 'var(--color-success)',
    warning: 'var(--color-warning)'
}
</script>

<style scoped>
.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.1rem;
    border: 1px solid var(--color-card);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
    transition: .2s;
}

.stat-card:hover {
    border-color: color-mix(in srgb, var(--c) 60%, transparent);
    box-shadow: 0 8px 24px color-mix(in srgb, var(--c) 18%, transparent);
    transform: translateY(-3px);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border-radius: 14px;
    background: linear-gradient(145deg, color-mix(in srgb, var(--c) 30%, var(--color-card)), color-mix(in srgb, var(--c) 10%, var(--color-surface)));
    box-shadow: 0 6px 16px color-mix(in srgb, var(--c) 28%, transparent);
    color: var(--c);
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
    background: var(--gradiente-primario);
    transition: width .4s ease;
}
</style>