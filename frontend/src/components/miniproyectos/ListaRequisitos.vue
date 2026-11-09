<template>
    <ul class="requisitos">
        <li v-for="(r, i) in items" :key="i" :class="{ cumplido: r.cumplido === true, pendiente: r.cumplido === false }">
            <span class="marca" aria-hidden="true">
                <Check v-if="r.cumplido !== false" :size="13" :stroke-width="3" />
                <X v-else :size="13" :stroke-width="3" />
            </span>
            <span class="texto">
                <TextoConCodigo :texto="r.texto" />
                <!-- Comentario de la IA sobre este punto (solo en la evaluación), en texto plano -->
                <small v-if="r.comentario" class="comentario">{{ r.comentario }}</small>
            </span>
            <!-- Solo en la evaluación: el resultado lo decide el backend, aquí únicamente se muestra -->
            <small v-if="r.cumplido !== undefined" class="estado">{{ r.cumplido ? 'Cumplido' : 'No cumplido' }}</small>
        </li>
    </ul>
</template>

<script setup>
import { computed } from 'vue'
import { Check, X } from 'lucide-vue-next'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'

// Acepta frases sueltas (requisitos de la descripción) u objetos { texto, cumplido, comentario }
// (resultado de la evaluación). Las casillas NO son interactivas: quien verifica es la IA.
const props = defineProps({ requisitos: { type: Array, required: true } })

const items = computed(() =>
    props.requisitos.map((r) => (typeof r === 'string' ? { texto: r } : r))
)
</script>

<style scoped>
.requisitos {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    list-style: none;
}

li {
    display: flex;
    align-items: center;
    gap: .7rem;
    font-size: .9rem;
}

.marca {
    display: grid;
    place-items: center;
    width: 22px;
    height: 22px;
    flex-shrink: 0;
    border: 1.5px solid var(--color-text-secondary);
    border-radius: 6px;
    color: transparent;
}

/* Sin resultado todavía (descripción): casilla vacía. Con resultado: verde o rojo */
li.cumplido .marca {
    border-color: var(--color-success);
    border-radius: 50%;
    background: var(--color-success);
    color: var(--color-background);
}

li.pendiente .marca {
    border-color: var(--color-danger);
    border-radius: 50%;
    background: var(--color-danger);
    color: var(--color-background);
}

.texto {
    flex: 1;
    min-width: 0;
}

.estado {
    flex-shrink: 0;
    color: var(--color-success);
    font-weight: 600;
}

li.pendiente .estado {
    color: var(--color-danger);
}

.comentario {
    display: block;
    margin-top: .15rem;
    color: var(--color-text-secondary);
    font-size: .78rem;
}
</style>
