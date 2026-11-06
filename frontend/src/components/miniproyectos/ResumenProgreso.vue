<template>
    <aside class="resumen">
        <section class="bloque">
            <h2>Tu progreso en mini proyectos</h2>
            <div class="anillo-fila">
                <div class="anillo" role="img" :aria-label="`${resumen.completados} de ${resumen.total} completados`">
                    <svg viewBox="0 0 100 100">
                        <circle class="fondo" cx="50" cy="50" :r="RADIO" />
                        <circle class="valor" cx="50" cy="50" :r="RADIO"
                            :stroke-dasharray="`${arco} ${CIRCUNFERENCIA}`" />
                    </svg>
                    <div class="centro">
                        <strong>{{ resumen.completados }}/{{ resumen.total }}</strong>
                        <small>completados</small>
                    </div>
                </div>
                <ul class="leyenda">
                    <li class="completados">Completados <b>{{ resumen.completados }}</b></li>
                    <li class="progreso">En progreso <b>{{ resumen.enProgreso }}</b></li>
                    <li class="iniciar">Por iniciar <b>{{ resumen.porIniciar }}</b></li>
                </ul>
            </div>
        </section>

        <section class="bloque">
            <h2>Proyectos por nivel</h2>
            <ul class="niveles">
                <li v-for="nivel in NIVELES" :key="nivel.clave" :style="{ '--c': nivel.color }">
                    <span class="nivel-icono" aria-hidden="true">
                        <component :is="nivel.icono" :size="18" />
                    </span>
                    <div class="nivel-datos">
                        <div class="nivel-texto">
                            <span>{{ nivel.etiqueta }}</span>
                            <b>{{ resumen.porNivel[nivel.clave].hechos }} / {{ resumen.porNivel[nivel.clave].total }}</b>
                        </div>
                        <div class="barra"><span :style="{ width: porcentaje(nivel.clave) + '%' }"></span></div>
                    </div>
                </li>
            </ul>
        </section>
    </aside>
</template>

<script setup>
import { computed } from 'vue'
import { iconoDeNivel } from '@/utils/iconosProyecto'

// resumen se CALCULA en la vista a partir de la misma lista de las tarjetas, nunca se guarda aparte
const props = defineProps({ resumen: { type: Object, required: true } })

const RADIO = 42
const CIRCUNFERENCIA = 2 * Math.PI * RADIO

// Icono y color de cada nivel salen de utils/iconosProyecto.js, así quedan iguales en toda la app
const NIVELES = [
    { clave: 'basico', etiqueta: 'Básico' },
    { clave: 'intermedio', etiqueta: 'Intermedio' },
    { clave: 'avanzado', etiqueta: 'Avanzado' }
].map((n) => ({ ...n, ...iconoDeNivel(n.clave) }))

// Largo del arco verde = fracción de completados × circunferencia (0 si no hay proyectos)
const arco = computed(() =>
    props.resumen.total ? (props.resumen.completados / props.resumen.total) * CIRCUNFERENCIA : 0
)

function porcentaje(clave) {
    const { hechos, total } = props.resumen.porNivel[clave]
    return total ? Math.round((hechos / total) * 100) : 0
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
    stroke: var(--color-success);
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
.niveles {
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

.leyenda .completados::before { background: var(--color-success); }
.leyenda .progreso::before { background: var(--color-primary); }

.niveles li {
    display: flex;
    align-items: center;
    gap: .6rem;
}

.nivel-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 8px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 16%, transparent);
}

.nivel-datos {
    flex: 1;
    min-width: 0;
}

.nivel-texto {
    display: flex;
    justify-content: space-between;
    margin-bottom: .3rem;
    color: var(--c);
    font-size: .8rem;
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
    background: var(--c);
}
</style>
