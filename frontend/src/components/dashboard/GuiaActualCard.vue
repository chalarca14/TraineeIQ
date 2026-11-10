<template>
    <article class="guia-actual">
        <h2>
            <Medalla :icono="BookOpen" /> Mi guía actual
        </h2>

        <!-- Si la guía no cargó, se avisa aquí sin tumbar el resto del dashboard -->
        <p v-if="!bloque" class="mensaje error">No pudimos cargar tu guía. Intenta de nuevo más tarde.</p>

        <template v-else>
            <div class="resumen">
                <div>
                    <h3>{{ bloque.guia.nombre }}</h3>
                    <div class="etiquetas">
                        <span class="chip">{{ esPersonal ? 'Guía personal' : 'Guía del grupo' }}</span>
                        <span v-if="!esPersonal && instructor" class="dato">Instructor: {{ instructor }}</span>
                    </div>
                </div>
                <RouterLink :to="rutaGuia" class="btn btn-primario btn-sm">
                    Ver guía
                    <ArrowRight :size="16" aria-hidden="true" />
                </RouterLink>
            </div>

            <!-- Avance real de ESTA guía: evaluados sobre los proyectos de sus semanas abiertas -->
            <div v-if="total" class="progreso">
                <div class="barra" role="img" :aria-label="`${evaluados} de ${total} proyectos evaluados`">
                    <div class="relleno" :style="{ width: porcentaje + '%' }"></div>
                </div>
                <span>{{ evaluados }} de {{ total }} evaluados</span>
            </div>

            <h4>Semanas</h4>
            <!-- Estado tal como lo manda el backend (Lista, Próximamente…), con el mismo chip de la guía -->
            <ul class="semanas">
                <li v-for="semana in bloque.semanas" :key="semana.numero_semana"
                    :class="{ bloqueada: !semana.disponible }">
                    <span class="numero">{{ semana.numero_semana }}</span>
                    <span class="datos">
                        <!-- El título se corta si es largo: el title lo deja leer completo -->
                        <span class="titulo" :title="semana.titulo">{{ semana.titulo }}</span>
                        <ChipEstadoSemana :semana="semana" />
                    </span>
                </li>
            </ul>
        </template>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { BookOpen, ArrowRight } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import ChipEstadoSemana from '@/components/guias/ChipEstadoSemana.vue'
import { pestanaDe } from '@/utils/estadoEntrega'

const props = defineProps({
    // { guia, semanas: [{ ...semana, proyectos }], fallo } de useMiniProyectosSemanas
    bloque: { type: Object, default: null },
    esPersonal: { type: Boolean, default: false },
    // Modo Grupo: el grupo elegido, para el nombre del instructor
    grupo: { type: Object, default: null }
})

const instructor = computed(() => props.grupo?.instructor?.name ?? '')

// Cada modo abre la guía en su propia ruta (Tarea 9): la de grupo lleva la migaja del grupo
const rutaGuia = computed(() => {
    const guia = props.bloque.guia
    return props.esPersonal ? `/estudiante/guias/${guia.id}` : `/estudiante/grupos/${guia.grupo_id}/guias/${guia.id}`
})

const proyectos = computed(() => props.bloque?.semanas.flatMap((s) => s.proyectos) ?? [])
const total = computed(() => proyectos.value.length)
const evaluados = computed(() => proyectos.value.filter((p) => pestanaDe(p) === 'evaluado').length)
const porcentaje = computed(() => (total.value ? Math.round((evaluados.value / total.value) * 100) : 0))
</script>

<style scoped>
.guia-actual {
    padding: 1.4rem;
    border: 1px solid var(--color-card);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

h2 {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.resumen {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.2rem;
}

.resumen h3 {
    margin-bottom: .5rem;
    overflow-wrap: anywhere;
}

.etiquetas {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
}

.chip {
    padding: .15rem .6rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 20%, transparent);
    color: var(--color-primary-light);
    font-size: .72rem;
}

.dato {
    color: var(--color-text-secondary);
    font-size: .8rem;
}

/* Barra + texto en una sola fila (el texto no se encoge) */
.progreso {
    display: flex;
    align-items: center;
    gap: .8rem;
    margin-bottom: 1.4rem;
}

.progreso span {
    flex-shrink: 0;
    font-size: .8rem;
    color: var(--color-text-secondary);
}

.barra {
    flex: 1;
    height: 8px;
    border-radius: 999px;
    background: var(--color-background);
    overflow: hidden;
}

.relleno {
    height: 100%;
    border-radius: 999px;
    background: var(--gradiente-primario);
    transition: width .4s ease;
}

h4 {
    margin-bottom: .7rem;
    font-size: .9rem;
    color: var(--color-text-secondary);
}

/* Fila horizontal con scroll: con muchas semanas (hasta 52) no alarga la tarjeta ni rompe en celular */
.semanas {
    display: flex;
    gap: .7rem;
    padding: 0 0 .5rem;
    list-style: none;
    overflow-x: auto;
}

.semanas li {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex: 0 0 auto;
    max-width: 220px;
    padding: .6rem .8rem;
    border: 1px solid var(--color-card);
    border-radius: 12px;
    background: var(--color-surface);
}

.semanas li.bloqueada {
    opacity: .75;
}

.numero {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--color-card);
    font-size: .8rem;
    font-weight: 700;
}

.datos {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .3rem;
    min-width: 0;
}

.titulo {
    max-width: 100%;
    overflow: hidden;
    font-size: .8rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mensaje {
    color: var(--color-text-secondary);
    font-size: .9rem;
}

.mensaje.error {
    color: var(--color-danger);
}
</style>
