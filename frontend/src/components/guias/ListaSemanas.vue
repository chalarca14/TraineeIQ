<template>
    <section class="semanas" :aria-labelledby="idTitulo">
        <h2 :id="idTitulo">Recorrido</h2>

        <!-- 360 px: un selector nativo. Lo maneja el teclado del celular y no necesita scroll -->
        <div class="selector">
            <label class="sr-only" :for="idSelect">Elegir semana</label>
            <select :id="idSelect" :value="activa ?? ''" @change="$emit('seleccionar', Number($event.target.value))">
                <option v-for="s in semanas" :key="s.numero_semana" :value="s.numero_semana">{{ textoOpcion(s) }}</option>
            </select>
            <ChevronDown :size="18" class="flecha" aria-hidden="true" />
        </div>

        <!-- Escritorio: la lista. Es la MISMA selección que el selector (las dos leen `activa`) -->
        <ol class="lista">
            <li v-for="s in semanas" :key="s.numero_semana">
                <button type="button" class="fila" :class="{ activa: s.numero_semana === activa }"
                    :aria-current="s.numero_semana === activa ? 'true' : null" @click="$emit('seleccionar', s.numero_semana)">
                    <span class="numero" aria-hidden="true">{{ s.numero_semana }}</span>
                    <span class="datos">
                        <span class="titulo">{{ s.titulo || `Semana ${s.numero_semana}` }}</span>
                        <!-- Estado y visibilidad en UNA línea debajo del título: antes eran tres renglones -->
                        <span class="estado-fila">
                            <ChipEstadoSemana :semana="s" :generando-aqui="generandoAqui === s.numero_semana" />
                            <!-- Instructor: él ve todas las semanas; aquí sabe cuáles ven ya sus estudiantes -->
                            <span v-if="semanaLiberada != null" class="visibilidad"
                                :class="{ 'visibilidad--no': s.numero_semana > semanaLiberada }">
                                <Eye v-if="s.numero_semana <= semanaLiberada" :size="13" aria-hidden="true" />
                                <EyeOff v-else :size="13" aria-hidden="true" />
                                {{ textoVisibilidad(s) }}
                            </span>
                        </span>
                    </span>
                    <Lock v-if="!s.disponible" :size="16" class="candado" aria-label="Semana bloqueada" />
                </button>
            </li>
        </ol>
    </section>
</template>

<script setup>
import { useId } from 'vue'
import { ChevronDown, Lock, Eye, EyeOff } from 'lucide-vue-next'
import ChipEstadoSemana from '@/components/guias/ChipEstadoSemana.vue'
import { estadoSemana } from '@/utils/estadoSemana'

// Lista lateral de semanas (escritorio) y selector (360 px). Solo muestra y avisa cuál se eligió:
// quien abre la semana y pide su contenido es la vista, con el store.
const props = defineProps({
    semanas: { type: Array, required: true },
    // Número de la semana elegida
    activa: { type: Number, default: null },
    // Número de la semana que esta pantalla está generando ahora (para el chip "Generando…")
    generandoAqui: { type: Number, default: null },
    // Solo el instructor: semana_actual del grupo (hasta dónde la ven los estudiantes). null = no aplica
    semanaLiberada: { type: Number, default: null }
})
defineEmits(['seleccionar'])

const idTitulo = useId()
const idSelect = useId()

// Una <option> solo admite texto plano: el candado va como emoji, sin iconos
function textoOpcion(semana) {
    const { texto } = estadoSemana(semana, props.generandoAqui === semana.numero_semana)
    const candado = semana.disponible ? '' : '🔒 '
    const visibilidad = props.semanaLiberada != null ? ` · ${textoVisibilidad(semana)}` : ''
    return `${candado}${semana.numero_semana}. ${semana.titulo || 'Sin título'} · ${texto}${visibilidad}`
}

function textoVisibilidad(semana) {
    return semana.numero_semana <= props.semanaLiberada ? 'La ven tus estudiantes' : 'Aún no liberada'
}
</script>

<style scoped>
.semanas {
    display: flex;
    flex-direction: column;
    gap: .8rem;
    min-width: 0;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
}

h2 {
    font-size: 1rem;
}

.selector {
    display: none;
    position: relative;
}

.selector select {
    width: 100%;
    padding: .7rem 2.2rem .7rem .9rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-card);
    color: var(--color-text);
    font: inherit;
    text-overflow: ellipsis;
    appearance: none;
    cursor: pointer;
}

.flecha {
    position: absolute;
    top: 50%;
    right: .8rem;
    transform: translateY(-50%);
    color: var(--color-text-secondary);
    pointer-events: none;
}

.lista {
    display: flex;
    flex-direction: column;
    gap: 0;
    max-height: 60vh;
    margin: 0;
    padding: 0;
    overflow-y: auto;
    list-style: none;
}

/* Recorrido: una línea vertical une los números, como una ruta de una semana a la siguiente */
.lista li {
    position: relative;
}

.lista li:not(:last-child)::before {
    content: '';
    position: absolute;
    top: 2.6rem;
    bottom: -.4rem;
    left: calc(.7rem + 15px);
    width: 2px;
    background: color-mix(in srgb, var(--color-primary) 35%, var(--color-card));
}

.fila {
    position: relative;
    display: flex;
    align-items: center;
    gap: .8rem;
    width: 100%;
    margin-bottom: .4rem;
    padding: .7rem;
    border: 1px solid transparent;
    border-radius: 12px;
    background: none;
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
    transition: background .2s, border-color .2s;
}

.fila:hover {
    background: var(--color-card);
}

/* La semana elegida se resalta con el color de acento, no solo con el fondo */
.fila.activa {
    border-color: color-mix(in srgb, var(--color-primary) 60%, transparent);
    background: color-mix(in srgb, var(--color-primary) 14%, var(--color-surface));
}

.numero {
    position: relative;
    z-index: 1;
    display: grid;
    place-items: center;
    width: 30px;
    height: 30px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--color-card);
    font-size: .8rem;
    font-weight: 700;
}

.fila.activa .numero {
    background: var(--gradiente-primario);
}

.datos {
    display: flex;
    flex: 1;
    flex-direction: column;
    align-items: flex-start;
    gap: .3rem;
    min-width: 0;
}

.titulo {
    font-size: .92rem;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.estado-fila {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .3rem .6rem;
}

.candado {
    flex-shrink: 0;
    color: var(--color-text-secondary);
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
}

/* En celular la lista se cambia por el selector */
@media (max-width: 860px) {
    .selector { display: block; }
    .lista { display: none; }
    .semanas { padding: .8rem; }
}

.visibilidad {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    color: var(--color-success);
    font-size: .72rem;
}

.visibilidad--no {
    color: var(--color-text-secondary);
}
</style>
