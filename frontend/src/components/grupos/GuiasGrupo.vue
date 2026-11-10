<template>
    <ul class="guias">
        <li v-for="(bloque, i) in bloques" :key="bloque.guia.id">
            <!-- Cada fila abre la guía por semanas (Tarea 9) en su ruta de Modo Grupo -->
            <RouterLink class="fila"
                :to="{ name: 'GrupoGuiaSemanas', params: { grupoId, guiaId: bloque.guia.id } }">
                <span class="numero" aria-hidden="true">{{ i + 1 }}</span>
                <span class="icono" aria-hidden="true"><BookOpen :size="20" /></span>
                <span class="texto">
                    <strong>{{ bloque.guia.nombre }}</strong>
                    <small>{{ detalle(bloque) }}</small>
                </span>
                <ArrowRight class="flecha" :size="18" aria-hidden="true" />
            </RouterLink>
        </li>
    </ul>
</template>

<script setup>
import { BookOpen, ArrowRight } from 'lucide-vue-next'

// bloques = guías del grupo con sus semanas (de useMiniProyectosSemanas). No se pide nada aparte
defineProps({
    bloques: { type: Array, required: true },
    grupoId: { type: Number, required: true }
})

// "8 semanas · 3 liberadas": se cuenta `disponible` tal como llega; el frontend no decide qué se libera.
// Si las semanas de esa guía no cargaron, la fila sigue sirviendo para abrirla
function detalle(bloque) {
    if (bloque.fallo) return 'No se pudieron cargar sus semanas'
    const total = bloque.semanas.length
    const liberadas = bloque.semanas.filter((s) => s.disponible).length
    return `${total} ${total === 1 ? 'semana' : 'semanas'} · ${liberadas} ${liberadas === 1 ? 'liberada' : 'liberadas'}`
}
</script>

<style scoped>
.guias {
    display: flex;
    flex-direction: column;
    gap: .7rem;
    list-style: none;
}

.fila {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: .9rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
    color: var(--color-text);
    transition: border-color .2s;
}

.fila:hover,
.fila:focus-visible {
    border-color: var(--color-primary);
}

.numero {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--color-card);
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.icono {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--color-primary) 25%, var(--color-card));
    color: var(--color-primary-light);
}

.texto {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: .15rem;
    min-width: 0;
}

strong {
    overflow-wrap: anywhere;
}

small {
    color: var(--color-text-secondary);
    font-size: .8rem;
}

.flecha {
    flex-shrink: 0;
    color: var(--color-text-secondary);
}

/* En 360 px el número sobra: el orden ya se ve en la lista */
@media (max-width: 420px) {
    .numero {
        display: none;
    }
}
</style>
