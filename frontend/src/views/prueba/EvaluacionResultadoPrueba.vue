<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <div class="resultado">
        <p class="puntaje"><strong>{{ evaluacion.puntaje }}</strong> / 100</p>
        <p class="nota">Es una retroalimentación orientativa: la IA lee tu código, pero no lo ejecuta.</p>
        <p class="resumen">{{ evaluacion.resumen }}</p>

        <p v-if="evaluacion.recortado" class="aviso">
            Tu repositorio es grande, así que solo se revisó una parte de los archivos.
        </p>

        <h3>Criterios</h3>
        <article v-for="c in evaluacion.criterios" :key="c.criterio" class="item">
            <div class="fila">
                <strong>{{ c.criterio }}</strong>
                <span>{{ c.cumplimiento }}% · peso {{ c.peso }}</span>
            </div>
            <p>{{ c.comentario }}</p>
        </article>

        <h3>Requisitos</h3>
        <article v-for="r in evaluacion.requisitos" :key="r.requisito" class="item">
            <div class="fila">
                <strong>{{ r.requisito }}</strong>
                <span :class="r.cumple ? 'cumple' : 'no-cumple'">{{ r.cumple ? 'Cumple' : 'No cumple' }}</span>
            </div>
            <p>{{ r.comentario }}</p>
        </article>

        <h3>Buenas prácticas</h3>
        <article v-for="b in evaluacion.buenas_practicas" :key="b.practica" class="item">
            <div class="fila">
                <strong>{{ b.practica }}</strong>
                <span :class="b.cumple ? 'cumple' : 'no-cumple'">{{ b.cumple ? 'Cumple' : 'No cumple' }}</span>
            </div>
            <p>{{ b.comentario }}</p>
        </article>

        <template v-if="evaluacion.sugerencias.length">
            <h3>Sugerencias</h3>
            <ul>
                <li v-for="sugerencia in evaluacion.sugerencias" :key="sugerencia">{{ sugerencia }}</li>
            </ul>
        </template>
    </div>
</template>

<script setup>
defineProps({
    evaluacion: { type: Object, required: true }
})
</script>

<style scoped>
.resultado {
    display: grid;
    gap: .6rem;
}

.puntaje strong {
    font-size: 2rem;
    color: var(--color-primary-light);
}

h3 {
    margin-top: .5rem;
    font-size: .95rem;
}

.nota,
.resumen,
.item p,
.fila span,
li {
    color: var(--color-text-secondary);
}

.nota {
    font-size: .85rem;
}

.aviso {
    color: var(--color-warning);
}

.item {
    padding: .75rem 1rem;
    border-radius: 12px;
    background: var(--color-surface);
}

.fila {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

.fila span {
    white-space: nowrap;
}

.fila .cumple {
    color: var(--color-success);
}

.fila .no-cumple {
    color: var(--color-danger);
}

ul {
    padding-left: 1.2rem;
}
</style>
