<template>
    <article class="proyecto" :class="{ bloqueado: proyecto.bloqueado }">
        <div class="cabecera">
            <EtiquetaNivel :valor="proyecto.dificultad" />
            <span v-if="etiqueta" class="tag" :title="etiqueta">{{ etiqueta }}</span>
        </div>

        <div class="titulo-fila">
            <span class="icono" :style="{ '--c': visual.color }" aria-hidden="true">
                <component :is="visual.icono" :size="20" />
            </span>
            <h3>{{ proyecto.titulo }}</h3>
        </div>
        <p class="descripcion">{{ proyecto.descripcion }}</p>

        <!-- La barra solo tiene sentido si el proyecto ya se empezó o terminó -->
        <div v-if="!proyecto.bloqueado" class="progreso" :aria-label="`Progreso ${proyecto.progreso}%`">
            <div class="barra"><span :style="{ width: proyecto.progreso + '%' }"></span></div>
            <small>{{ proyecto.progreso }}%</small>
        </div>

        <p v-if="proyecto.estado === 'completado'" class="finalizado">
            <Check :size="14" /> Finalizado: {{ fechaFinalizado }}
        </p>

        <p v-if="proyecto.bloqueado" class="candado">
            <Lock :size="14" /> Completa el nivel {{ NOMBRES_NIVEL[proyecto.bloqueadoPor] ?? 'anterior' }}
        </p>

        <!-- No es un RouterLink: la ruta de detalle aún no existe (Tarea 3). Solo avisamos a la vista. -->
        <button type="button" class="accion" :disabled="proyecto.bloqueado" @click="$emit('abrir', proyecto)">
            <template v-if="proyecto.bloqueado">Bloqueado</template>
            <template v-else>{{ ETIQUETA_BOTON[proyecto.estado] }} <ArrowRight :size="15" /></template>
        </button>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { Check, Lock, ArrowRight } from 'lucide-vue-next'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import { iconoDelProyecto } from '@/utils/iconosProyecto'
import { formatearFecha } from '@/utils/fechas'

const props = defineProps({
    // Proyecto ya enriquecido con estado, progreso, bloqueado, etc.
    proyecto: { type: Object, required: true }
})
defineEmits(['abrir'])

const ETIQUETA_BOTON = { por_iniciar: 'Comenzar', en_progreso: 'Continuar', completado: 'Ver proyecto' }
const NOMBRES_NIVEL = { basico: 'básico', intermedio: 'intermedio', avanzado: 'avanzado' }

const visual = computed(() => iconoDelProyecto(props.proyecto))

const fechaFinalizado = computed(() => formatearFecha(props.proyecto.finalizado))

// Texto de la etiqueta de arriba (ej. "Semana 1: Introducción..."). Se recorta con "…" por falta
// de espacio, así que el texto completo va también en el atributo title.
const etiqueta = computed(() => props.proyecto.tema?.nombre_tema ?? props.proyecto.guiaNombre ?? '')
</script>

<style scoped>
.proyecto {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
}

.cabecera {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}

.tag {
    overflow: hidden;
    max-width: 55%;
    color: var(--color-text-secondary);
    font-size: .72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.titulo-fila {
    display: flex;
    align-items: center;
    gap: .6rem;
}

/* Cuadro redondeado: fondo = el color de la tecnología al 16 %, icono = el color puro */
.icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 10px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 16%, transparent);
}

h3 {
    font-size: 1rem;
}

.descripcion {
    flex: 1;
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.progreso {
    display: flex;
    align-items: center;
    gap: .6rem;
}

.barra {
    flex: 1;
    height: 6px;
    overflow: hidden;
    border-radius: 999px;
    background: var(--color-card);
}

.barra span {
    display: block;
    height: 100%;
    background: var(--color-primary);
}

.progreso small,
.finalizado,
.candado {
    color: var(--color-text-secondary);
    font-size: .75rem;
}

.finalizado,
.candado {
    display: flex;
    align-items: center;
    gap: .3rem;
}

.finalizado {
    color: var(--color-success);
}

.accion {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
    align-self: flex-end;
    padding: .5rem 1rem;
    border: none;
    border-radius: 10px;
    background: var(--color-primary);
    color: var(--color-text);
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
}

.accion:hover:not(:disabled) {
    background: var(--color-primary-hover);
}

.accion:disabled {
    background: var(--color-card);
    color: var(--color-text-secondary);
    cursor: not-allowed;
}

.bloqueado h3 {
    color: var(--color-text-secondary);
}
</style>
