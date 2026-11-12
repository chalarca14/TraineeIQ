<template>
    <article class="siguiente-paso">
        <Medalla :icono="Rocket" :size="48" />

        <div class="texto">
            <h2>Continúa donde quedaste</h2>

            <!-- Un solo mensaje a la vez, según lo que haya -->
            <template v-if="siguiente">
                <p>Tu siguiente mini proyecto es <strong>{{ siguiente.proyecto.titulo }}</strong>.</p>
                <p class="contexto">Semana {{ siguiente.semana.numero_semana }} · {{ siguiente.guia.nombre }}</p>
            </template>
            <p v-else-if="hayProyectos">
                ¡Estás al día! Ya evaluaste todos los mini proyectos de las semanas abiertas.
            </p>
            <p v-else>Los mini proyectos de tus semanas abiertas aún se están preparando.</p>
        </div>

        <RouterLink :to="accion.ruta" class="btn btn-primario btn-accion">
            {{ accion.texto }}
            <ArrowRight :size="16" aria-hidden="true" />
        </RouterLink>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { Rocket, ArrowRight } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import { textoBoton } from '@/utils/estadoEntrega'

const props = defineProps({
    // { proyecto, semana, guia }: primer proyecto sin evaluar de una semana abierta, o null
    siguiente: { type: Object, default: null },
    // Hay proyectos en semanas abiertas (aunque todos estén evaluados)
    hayProyectos: { type: Boolean, default: false }
})

// El botón dice lo mismo que la tarjeta del proyecto en la lista (Comenzar, Continuar, Ver estado),
// para que el aprendiz reconozca la acción. Sin proyecto pendiente, lleva a la lista completa.
const accion = computed(() =>
    props.siguiente
        ? { texto: textoBoton(props.siguiente.proyecto), ruta: `/estudiante/mini-proyectos/${props.siguiente.proyecto.id}` }
        : { texto: 'Ver mini proyectos', ruta: '/estudiante/mini-proyectos' }
)
</script>

<style scoped>
.siguiente-paso {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100%;
    padding: 1.4rem;
    border: 1px solid var(--color-card);
    border-radius: 16px;
    border-color: color-mix(in srgb, var(--color-primary) 45%, var(--color-card));
    box-shadow: 0 0 32px color-mix(in srgb, var(--color-primary) 12%, transparent);
    background: linear-gradient(160deg, color-mix(in srgb, var(--color-primary) 28%, var(--color-surface)), var(--color-surface) 65%);
}

h2 {
    margin-bottom: .4rem;
    font-size: 1.05rem;
}

p {
    font-size: .9rem;
    line-height: 1.5;
    color: var(--color-text-secondary);
    overflow-wrap: anywhere;
}

p strong {
    color: var(--color-text);
}

.contexto {
    margin-top: .3rem;
    font-size: .8rem;
}

/* margin-top:auto empuja el botón al fondo si la tarjeta se estira */
.btn-accion {
    margin-top: auto;
    align-self: flex-start;
}
</style>
