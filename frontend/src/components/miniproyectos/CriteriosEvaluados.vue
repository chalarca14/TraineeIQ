<template>
    <ul class="criterios">
        <li v-for="(c, i) in criterios" :key="i">
            <div class="linea">
                <span class="nombre">{{ c.criterio }}</span>
                <span class="peso">peso {{ c.peso }} %</span>
            </div>
            <!-- La barra dibuja el cumplimiento (0 a 100) que calculó la IA, en color neutro -->
            <div class="barra" role="img" :aria-label="`Cumplimiento ${c.cumplimiento} de 100`">
                <span :style="{ width: ancho(c.cumplimiento) + '%' }"></span>
            </div>
            <div class="linea">
                <small class="comentario">{{ c.comentario }}</small>
                <b>{{ c.cumplimiento }} %</b>
            </div>
        </li>
    </ul>
</template>

<script setup>
// Criterios evaluados: [{ criterio, peso, cumplimiento, comentario }]. Los textos los escribe la IA
// y se pintan con {{ }} (texto plano), nunca con v-html, así no se interpretan como HTML.
defineProps({ criterios: { type: Array, required: true } })

// Se limita a 0–100 por si llegara un valor fuera de rango
const ancho = (n) => Math.min(100, Math.max(0, Number(n) || 0))
</script>

<style scoped>
.criterios {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    list-style: none;
}

.linea {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .8rem;
}

.nombre {
    min-width: 0;
    font-size: .9rem;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.peso,
.comentario {
    color: var(--color-text-secondary);
    font-size: .75rem;
}

.comentario {
    min-width: 0;
    overflow-wrap: anywhere;
}

b {
    flex-shrink: 0;
    font-size: .8rem;
}

.barra {
    height: 6px;
    margin: .35rem 0;
    overflow: hidden;
    border-radius: 999px;
    background: var(--color-card);
}

.barra span {
    display: block;
    height: 100%;
    background: var(--color-primary);
}
</style>
