<template>
    <aside class="resumen">
        <section class="bloque">
            <h2>Tu avance en mini proyectos</h2>
            <div class="anillo-fila">
                <div class="anillo" role="img" :aria-label="`${resumen.evaluados} de ${resumen.total} evaluados`">
                    <svg viewBox="0 0 100 100">
                        <circle class="fondo" cx="50" cy="50" :r="RADIO" />
                        <circle class="valor" cx="50" cy="50" :r="RADIO"
                            :stroke-dasharray="`${arco} ${CIRCUNFERENCIA}`" />
                    </svg>
                    <div class="centro">
                        <strong>{{ resumen.evaluados }}/{{ resumen.total }}</strong>
                        <small>evaluados</small>
                    </div>
                </div>
                <ul class="leyenda">
                    <li class="evaluados">Evaluados <b>{{ resumen.evaluados }}</b></li>
                    <li class="evaluando">Evaluando <b>{{ resumen.evaluando }}</b></li>
                    <li class="por-entregar">Por entregar <b>{{ resumen.porEntregar }}</b></li>
                </ul>
            </div>
        </section>

        <!-- Modo Personal: XP tal como llega del backend. Modo Grupo: solo evaluados sobre el total, sin XP -->
        <section v-if="semanas.length" class="bloque">
            <h2>{{ esPersonal ? 'Experiencia por semana' : 'Por semana' }}</h2>
            <ul class="semanas">
                <li v-for="s in semanas" :key="`${s.guiaId}-${s.numero_semana}`">
                    <div class="semana-texto">
                        <span :title="s.guiaNombre">
                            Semana {{ s.numero_semana }}
                            <small v-if="variasGuias">· {{ s.guiaNombre }}</small>
                        </span>
                        <b v-if="esPersonal">{{ s.xp_obtenido }} / {{ s.xp_maximo }} XP</b>
                        <b v-else>{{ s.evaluados }} / {{ s.total }}</b>
                    </div>
                    <div class="barra"><span :style="{ width: porcentaje(s) + '%' }"></span></div>
                    <small v-if="esPersonal && s.xp_requerido != null" class="requerido">
                        Para abrir la siguiente: {{ s.xp_requerido }} XP
                    </small>
                </li>
            </ul>
        </section>
    </aside>
</template>

<script setup>
import { computed } from 'vue'

// resumen y semanas se CALCULAN en la vista a partir de la misma lista real de las tarjetas,
// nunca se guardan aparte: por eso los números no se contradicen. Del XP no se calcula nada:
// xp_obtenido, xp_maximo y xp_requerido se muestran tal como llegan. La barra solo dibuja
// esa proporción; no decide nada.
const props = defineProps({
    resumen: { type: Object, required: true },
    semanas: { type: Array, default: () => [] },
    esPersonal: { type: Boolean, default: false },
    variasGuias: { type: Boolean, default: false }
})

const RADIO = 42
const CIRCUNFERENCIA = 2 * Math.PI * RADIO

// Largo del arco = fracción de evaluados × circunferencia (0 si no hay proyectos)
const arco = computed(() =>
    props.resumen.total ? (props.resumen.evaluados / props.resumen.total) * CIRCUNFERENCIA : 0
)

function porcentaje(s) {
    const [parte, total] = props.esPersonal ? [s.xp_obtenido, s.xp_maximo] : [s.evaluados, s.total]
    return total ? Math.min(100, Math.round((parte / total) * 100)) : 0
}
</script>

<style scoped>
.resumen {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.bloque {
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
}

h2 {
    margin-bottom: .8rem;
    font-size: .9rem;
}

.anillo-fila {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.anillo {
    position: relative;
    width: 110px;
    height: 110px;
    flex-shrink: 0;
}

svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

circle {
    fill: none;
    stroke-width: 9;
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
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.centro small {
    color: var(--color-text-secondary);
    font-size: .65rem;
}

.leyenda,
.semanas {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    list-style: none;
}

.leyenda li {
    display: flex;
    gap: .5rem;
    color: var(--color-text-secondary);
    font-size: .8rem;
}

.leyenda li::before {
    content: '';
    width: 8px;
    height: 8px;
    align-self: center;
    border-radius: 50%;
    background: var(--color-text-secondary);
}

.leyenda b {
    margin-left: auto;
    color: var(--color-text);
}

.leyenda .evaluados::before { background: var(--color-primary); }
.leyenda .evaluando::before { background: var(--color-info); }

/* Con muchas semanas (hasta 52) el panel no debe alargar toda la página */
.semanas {
    max-height: 280px;
    padding-right: .3rem;
    overflow-y: auto;
}

.semana-texto {
    display: flex;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .3rem;
    color: var(--color-text-secondary);
    font-size: .8rem;
}

.semana-texto b {
    flex-shrink: 0;
    color: var(--color-text);
}

.requerido {
    display: block;
    margin-top: .25rem;
    color: var(--color-text-secondary);
    font-size: .7rem;
}

.barra {
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
</style>
