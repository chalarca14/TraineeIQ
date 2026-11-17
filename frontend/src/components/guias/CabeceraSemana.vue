<template>
    <header class="cabecera">
        <div class="arriba">
            <!-- "Semana 2 de 4" en pequeño y el título grande: así la semana tiene presencia y se sabe
                 en qué parte del recorrido se está -->
            <span class="antetitulo">Semana {{ semana.numero_semana }}<template v-if="total"> de {{ total }}</template></span>
            <ChipEstadoSemana :semana="semana" :generando-aqui="generandoAqui" />
        </div>

        <h2 :id="idTitulo">{{ semana.titulo || `Semana ${semana.numero_semana}` }}</h2>

        <p v-if="semana.resumen" class="resumen">{{ semana.resumen }}</p>

        <!-- Modo Personal: el XP de la semana tal como llega del backend (nunca se calcula aquí) -->
        <p v-if="esPersonal && semana.xp_maximo != null" class="xp">
            <Star :size="15" aria-hidden="true" />
            Experiencia: {{ semana.xp_obtenido }} / {{ semana.xp_maximo }} XP
            <template v-if="semana.xp_requerido != null"> · para abrir la siguiente: {{ semana.xp_requerido }} XP</template>
        </p>

        <!-- Lo que trae la semana, contado con el contenido que ya llegó (solo cuando está listo) -->
        <ul v-if="conteos.length" class="conteos" aria-label="Contenido de la semana">
            <li v-for="c in conteos" :key="c.texto">
                <component :is="c.icono" :size="15" aria-hidden="true" /> {{ c.texto }}
            </li>
        </ul>
    </header>
</template>

<script setup>
import { computed } from 'vue'
import { ListChecks, Sparkles, Rocket, Star } from 'lucide-vue-next'
import ChipEstadoSemana from '@/components/guias/ChipEstadoSemana.vue'

const props = defineProps({
    semana: { type: Object, required: true },
    // Cuántas semanas tiene la guía (para "Semana 2 de 4")
    total: { type: Number, default: 0 },
    // Contenido de la semana ya abierta (GET .../semanas/{n}) o null
    contenido: { type: Object, default: null },
    generandoAqui: { type: Boolean, default: false },
    esPersonal: { type: Boolean, default: false },
    // El panel usa este id en aria-labelledby: el título de la semana nombra a toda la sección
    idTitulo: { type: String, required: true }
})

// "3 temas", "1 recomendación"… Solo cuenta lo que llegó del backend; si algo viene vacío, no se muestra
const plural = (n, uno, varios) => `${n} ${n === 1 ? uno : varios}`
const conteos = computed(() => {
    const c = props.contenido
    if (!c) return []
    return [
        { n: c.temas?.length ?? 0, icono: ListChecks, texto: plural(c.temas?.length ?? 0, 'tema', 'temas') },
        { n: c.recomendaciones?.length ?? 0, icono: Sparkles, texto: plural(c.recomendaciones?.length ?? 0, 'recomendación', 'recomendaciones') },
        { n: c.mini_proyectos?.length ?? 0, icono: Rocket, texto: plural(c.mini_proyectos?.length ?? 0, 'mini proyecto', 'mini proyectos') }
    ].filter((x) => x.n > 0)
})
</script>

<style scoped>
.cabecera {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-sm);
}

.arriba {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--espacio-sm);
}

.antetitulo {
    color: var(--color-primary-light);
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}

h2 {
    min-width: 0;
    font-size: clamp(1.3rem, 2.5vw, 1.6rem);
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.resumen {
    max-width: 70ch;
    color: var(--color-text-secondary);
    font-size: .95rem;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.xp {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .35rem;
    color: var(--color-warning);
    font-size: .85rem;
}

.conteos {
    display: flex;
    flex-wrap: wrap;
    gap: var(--espacio-sm);
    margin: var(--espacio-xs) 0 0;
    padding: 0;
    list-style: none;
}

.conteos li {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .3rem .75rem;
    border: 1px solid var(--color-card);
    border-radius: 999px;
    background: var(--color-background);
    color: var(--color-text-secondary);
    font-size: .8rem;
}

.conteos svg {
    color: var(--color-primary-light);
}
</style>
