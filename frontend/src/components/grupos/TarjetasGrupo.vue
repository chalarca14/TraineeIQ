<template>
    <section class="tarjetas" aria-label="Datos del grupo">
        <article class="tarjeta">
            <h2><UserRound :size="16" aria-hidden="true" /> Instructor</h2>
            <!-- Solo el nombre: el backend no manda correo ni cargo del instructor -->
            <p class="valor">{{ instructor || 'Sin asignar' }}</p>
        </article>

        <article class="tarjeta">
            <h2><BookOpen :size="16" aria-hidden="true" /> Guías asignadas</h2>
            <p class="valor">{{ textoGuias }}</p>
            <!-- No navega: avisa a la vista para que active la pestaña Guías -->
            <button type="button" class="enlace" :disabled="cantidadGuias == null" @click="$emit('ver-guias')">
                Ver todas las guías <ArrowRight :size="15" aria-hidden="true" />
            </button>
        </article>

        <article class="tarjeta">
            <h2><Users :size="16" aria-hidden="true" /> Miembros del grupo</h2>
            <!-- Solo la cantidad: no hay lista de miembros (el aprendiz no ve datos de sus compañeros) -->
            <p class="valor">{{ miembros }} {{ miembros === 1 ? 'estudiante' : 'estudiantes' }}</p>
            <small>Tú eres parte de este grupo</small>
        </article>
    </section>
</template>

<script setup>
import { computed } from 'vue'
import { UserRound, BookOpen, Users, ArrowRight } from 'lucide-vue-next'

// cantidadGuias = null mientras las guías cargan o si fallaron: así no se muestra un "0 guías" falso
const props = defineProps({
    instructor: { type: String, default: '' },
    cantidadGuias: { type: Number, default: null },
    miembros: { type: Number, default: 0 }
})
defineEmits(['ver-guias'])

const textoGuias = computed(() => {
    const n = props.cantidadGuias
    if (n == null) return '—'
    return `${n} ${n === 1 ? 'guía' : 'guías'}`
})
</script>

<style scoped>
/* Siempre son 3 tarjetas: 3 columnas fijas. Con auto-fit y mínimo de 200 px solo cabían 2 en la
   columna del grupo y "Miembros" quedaba sola en otra fila con un hueco al lado. */
.tarjetas {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

/* En celular, una debajo de la otra */
@media (max-width: 600px) {
    .tarjetas {
        grid-template-columns: minmax(0, 1fr);
    }
}

.tarjeta {
    display: flex;
    flex-direction: column;
    gap: .45rem;
    padding: 1rem 1.1rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
}

h2 {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    color: var(--color-text-secondary);
    font-size: .85rem;
    font-weight: 600;
}

.valor {
    font-size: 1.1rem;
    font-weight: 600;
    overflow-wrap: anywhere;
}

small {
    color: var(--color-text-secondary);
    font-size: .78rem;
}

.enlace {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: .3rem;
    padding: 0;
    border: none;
    background: none;
    color: var(--color-primary-light);
    font-size: .85rem;
    cursor: pointer;
}

.enlace:hover:not(:disabled) {
    text-decoration: underline;
}

.enlace:disabled {
    opacity: .5;
    cursor: default;
}
</style>
