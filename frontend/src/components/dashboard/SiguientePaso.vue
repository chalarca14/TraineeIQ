<template>
    <article class="siguiente-paso">
        <span class="icono">
            <Rocket :size="22" />
        </span>

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

        <RouterLink v-if="accion" :to="accion.ruta" class="btn-accion">
            {{ accion.texto }}
            <ArrowRight :size="16" />
        </RouterLink>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { Rocket, ArrowRight } from 'lucide-vue-next'
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
    border-radius: 14px;
    background: linear-gradient(160deg, rgba(124, 58, 237, .22), var(--color-surface) 65%);
}

.icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(124, 58, 237, .25);
    color: var(--color-primary-light);
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

.btn-accion {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    /* margin-top:auto empuja el botón al fondo si la tarjeta se estira */
    margin-top: auto;
    padding: .65rem 1rem;
    border-radius: 10px;
    background: var(--color-primary);
    color: var(--color-text);
    font-size: .88rem;
    text-decoration: none;
    transition: .2s;
}

.btn-accion:hover {
    background: var(--color-primary-hover);
}
</style>