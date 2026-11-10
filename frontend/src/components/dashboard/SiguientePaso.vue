<template>
    <article class="siguiente-paso">
        <Medalla :icono="Rocket" :size="48" />

        <div class="texto">
            <h2>Continúa donde quedaste</h2>

            <!-- Un solo mensaje a la vez, según el estado -->
            <p v-if="cargando">Preparando tu siguiente paso...</p>
            <p v-else-if="error">No pudimos preparar tu siguiente paso.</p>
            <p v-else-if="!guia">Aún no tienes guías. Sube tu primera guía en PDF y la dividimos en temas.</p>
            <p v-else>
                Tu siguiente tema en <strong>{{ guia.nombre }}</strong> es
                <strong>{{ temaActual?.nombre_tema }}</strong>.
            </p>
        </div>

        <RouterLink v-if="accion" :to="accion.ruta" class="btn btn-primario btn-accion">
            {{ accion.texto }}
            <ArrowRight :size="16" />
        </RouterLink>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { Rocket, ArrowRight } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import { temasHechos } from '@/utils/progresoEjemplo'

const props = defineProps({
    guia: { type: Object, default: null },
    cargando: { type: Boolean, default: false },
    error: { type: Boolean, default: false }
})

// El tema actual es el primero que NO está hecho
const temaActual = computed(() => {
    const temas = props.guia?.temas ?? []
    return temas[temasHechos(temas.length)] ?? null
})

// Decide qué botón mostrar; null = sin botón (cargando o error)
const accion = computed(() => {
    if (props.cargando || props.error) return null
    if (!props.guia) return { texto: 'Subir mi primera guía', ruta: '/estudiante/guias' }
    return { texto: 'Estudiar este tema', ruta: `/estudiante/guias/${props.guia.id}` }
})
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
}

p strong {
    color: var(--color-text);
}

/* margin-top:auto empuja el botón al fondo si la tarjeta se estira */
.btn-accion {
    margin-top: auto;
}
</style>