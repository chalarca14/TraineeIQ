<template>
    <aside class="avance" aria-labelledby="titulo-avance">
        <h2 id="titulo-avance">Tu avance en este grupo</h2>

        <p v-if="cargando" class="estado">Cargando tu avance...</p>
        <p v-else-if="error" class="estado estado--error">No pudimos calcular tu avance.</p>

        <template v-else>
            <div class="anillo-fila">
                <div class="anillo" role="img" :aria-label="`${porcentaje}% de tus mini proyectos evaluados`">
                    <svg viewBox="0 0 100 100">
                        <circle class="fondo" cx="50" cy="50" :r="RADIO" />
                        <circle class="valor" cx="50" cy="50" :r="RADIO" :stroke-dasharray="`${arco} ${CIRCUNFERENCIA}`" />
                    </svg>
                    <strong class="centro">{{ porcentaje }}%</strong>
                </div>
                <p class="leyenda">De los mini proyectos de las semanas liberadas, ya evaluaste {{ resumen.evaluados }}.</p>
            </div>

            <!-- Sin XP ni niveles: en Modo Grupo no existen (CLAUDE.md) -->
            <ul class="filas">
                <li><span><Code :size="16" aria-hidden="true" /> Mini proyectos evaluados</span>
                    <b>{{ resumen.evaluados }} / {{ resumen.total }}</b></li>
                <li><span><Award :size="16" aria-hidden="true" /> Puntaje promedio</span>
                    <b>{{ promedio != null ? `${promedio}/100` : '—' }}</b></li>
                <li v-if="semanaActual != null && ultimaSemana">
                    <span><CalendarDays :size="16" aria-hidden="true" /> Semanas liberadas</span>
                    <b>{{ semanaActual }} / {{ ultimaSemana }}</b></li>
            </ul>
            <p class="nota">El puntaje es orientativo: lo calcula la IA para ayudarte a mejorar.</p>
        </template>
    </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Code, Award, CalendarDays } from 'lucide-vue-next'
import { contarPorEstado, puntajePromedio } from '@/utils/estadoEntrega'

// Recibe los MISMOS bloques que las pestañas: así "Tu avance" y "Mi progreso" nunca se contradicen.
// semanaActual y ultimaSemana llegan de GET /grupos/{id} y se muestran tal cual
const props = defineProps({
    bloques: { type: Array, required: true },
    cargando: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
    semanaActual: { type: Number, default: null },
    ultimaSemana: { type: Number, default: null }
})

const RADIO = 42
const CIRCUNFERENCIA = 2 * Math.PI * RADIO

const proyectos = computed(() => props.bloques.flatMap((b) => b.semanas.flatMap((s) => s.proyectos)))
const resumen = computed(() => contarPorEstado(proyectos.value))
const promedio = computed(() => puntajePromedio(proyectos.value))

// Solo dibuja la proporción de evaluados; no decide nada (0 si aún no hay proyectos)
const porcentaje = computed(() =>
    resumen.value.total ? Math.round((resumen.value.evaluados / resumen.value.total) * 100) : 0
)
const arco = computed(() => (porcentaje.value / 100) * CIRCUNFERENCIA)
</script>

<style scoped>
.avance {
    display: flex;
    flex-direction: column;
    gap: .9rem;
    padding: 1.1rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
}

h2 {
    font-size: 1rem;
}

.estado {
    color: var(--color-text-secondary);
    font-size: .88rem;
}

.estado--error {
    color: var(--color-danger);
}

.anillo-fila {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.anillo {
    position: relative;
    width: 84px;
    height: 84px;
    flex-shrink: 0;
}

/* Solo el svg del anillo: un selector "svg" suelto también agrandaría los iconos de las filas */
.anillo svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.anillo circle {
    fill: none;
    stroke-width: 10;
}

.fondo {
    stroke: var(--color-card);
}

.valor {
    stroke: var(--color-primary);
    stroke-linecap: round;
}

.centro {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    font-size: 1.1rem;
}

.leyenda,
.nota {
    color: var(--color-text-secondary);
    font-size: .8rem;
}

.filas {
    display: flex;
    flex-direction: column;
    list-style: none;
}

.filas li {
    display: flex;
    justify-content: space-between;
    gap: .6rem;
    padding: .6rem 0;
    border-top: 1px solid var(--color-border);
    font-size: .85rem;
}

.filas span {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: var(--color-text-secondary);
}

.filas b {
    flex-shrink: 0;
}
</style>
