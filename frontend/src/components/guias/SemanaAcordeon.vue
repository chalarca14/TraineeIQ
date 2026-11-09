<template>
    <!-- Semana que el instructor (o el XP, en Modo Personal) todavía no abre: sin contenido, no se despliega -->
    <SemanaBloqueada v-if="!semana.disponible" :semana="semana" :anterior="anterior" :es-personal="esPersonal" />

    <article v-else class="semana" :class="{ abierta }">
        <!-- aria-expanded dice si el panel está abierto; aria-controls, cuál es -->
        <button type="button" class="cabecera" :aria-expanded="abierta" :aria-controls="idPanel" @click="$emit('alternar')">
            <span class="numero" aria-hidden="true">{{ semana.numero_semana }}</span>
            <strong class="titulo">Semana {{ semana.numero_semana }}<template v-if="semana.titulo">: {{ semana.titulo }}</template></strong>
            <span class="chip" :class="estado.clase">
                <span v-if="estado.girando" class="giro" aria-hidden="true"></span>
                {{ estado.texto }}
            </span>
            <ChevronDown :size="18" class="chevron" :class="{ girado: abierta }" aria-hidden="true" />
        </button>

        <div v-if="abierta" :id="idPanel" class="cuerpo">
            <p v-if="semana.resumen" class="resumen">{{ semana.resumen }}</p>

            <!-- Modo Personal: el XP de la semana tal como llega del backend (nunca se calcula aquí) -->
            <p v-if="esPersonal && semana.xp_maximo != null" class="xp">
                Experiencia: {{ semana.xp_obtenido }} / {{ semana.xp_maximo }} XP
                <template v-if="semana.xp_requerido != null"> · para abrir la siguiente: {{ semana.xp_requerido }} XP</template>
            </p>

            <!-- Listo: el contenido se pide al abrir la semana -->
            <template v-if="semana.estado_generacion === 'listo'">
                <ContenidoSemana v-if="contenido" :semana="contenido" />
                <p v-else-if="cargandoContenido" class="estado-texto" role="status">Cargando la semana…</p>
                <div v-else class="aviso" role="alert">
                    <span>{{ errorContenido || 'No pudimos cargar el contenido de esta semana.' }}</span>
                    <button type="button" class="btn btn-secundario btn-sm" @click="$emit('recargar')">Intentar de nuevo</button>
                </div>
            </template>

            <!-- Pendiente: está en la cola de esta pantalla -->
            <p v-else-if="semana.estado_generacion === 'pendiente'" class="estado-texto" role="status">
                La IA preparará esta semana en un momento.
            </p>

            <!-- Generando: o la genera esta pantalla (espera) o el servidor dice "generando" por otra
                 persona o porque quedó caída (se ofrece consultar UNA vez, sin polling) -->
            <template v-else-if="semana.estado_generacion === 'generando'">
                <p v-if="generandoAqui" class="estado-texto" role="status">
                    <span class="giro" aria-hidden="true"></span> La IA está preparando esta semana. Puede tardar un minuto.
                </p>
                <div v-else class="aviso" role="status">
                    <span>Esta semana se está preparando.</span>
                    <button type="button" class="btn btn-secundario btn-sm" :disabled="revisando" @click="$emit('revisar')">
                        <RefreshCw :size="15" /> {{ revisando ? 'Revisando…' : 'Revisar de nuevo' }}
                    </button>
                </div>
            </template>

            <!-- Error: el dueño ve el motivo y puede reintentar; en Modo Grupo, texto fijo (sin error_mensaje) -->
            <template v-else-if="semana.estado_generacion === 'error'">
                <div v-if="esDueno" class="aviso aviso--error" role="alert">
                    <span>{{ semana.error_mensaje || 'Esta semana no se pudo generar.' }}</span>
                    <button type="button" class="btn btn-primario btn-sm" @click="$emit('reintentar')">
                        <RefreshCw :size="15" /> Reintentar
                    </button>
                </div>
                <p v-else class="aviso aviso--error" role="alert">
                    Esta semana no se pudo generar. Avisa a tu instructor.
                </p>
            </template>
        </div>
    </article>
</template>

<script setup>
import { computed, useId } from 'vue'
import { ChevronDown, RefreshCw } from 'lucide-vue-next'
import SemanaBloqueada from '@/components/miniproyectos/SemanaBloqueada.vue'
import ContenidoSemana from '@/components/guias/ContenidoSemana.vue'

const props = defineProps({
    // Semana de GET /guias/{id}/semanas (estado, resumen, disponible, XP)
    semana: { type: Object, required: true },
    // Semana anterior (solo para el texto del candado en Modo Personal)
    anterior: { type: Object, default: null },
    abierta: { type: Boolean, default: false },
    // Contenido de la semana ya abierta (GET .../semanas/{n}) o null
    contenido: { type: Object, default: null },
    cargandoContenido: { type: Boolean, default: false },
    errorContenido: { type: String, default: '' },
    // Esta pantalla está generando AHORA esta semana
    generandoAqui: { type: Boolean, default: false },
    revisando: { type: Boolean, default: false },
    // Dueño de la guía: solo él ve el error_mensaje y puede reintentar (forzar)
    esDueno: { type: Boolean, default: false },
    esPersonal: { type: Boolean, default: false }
})
defineEmits(['alternar', 'reintentar', 'revisar', 'recargar'])

const idPanel = useId()

// Texto y color del chip según el estado que manda el backend
const estado = computed(() => {
    switch (props.semana.estado_generacion) {
        case 'listo': return { texto: 'Lista', clase: 'listo' }
        case 'generando':
            return props.generandoAqui
                ? { texto: 'Generando…', clase: 'generando', girando: true }
                : { texto: 'En preparación', clase: 'preparacion' }
        case 'error': return { texto: 'Error', clase: 'error' }
        default: return { texto: 'Pendiente', clase: 'pendiente' }
    }
})
</script>

<style scoped>
.semana {
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
    transition: border-color .2s, box-shadow .2s;
}

.semana:hover,
.semana.abierta {
    border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
}

.semana.abierta {
    box-shadow: 0 8px 28px color-mix(in srgb, var(--color-primary) 14%, transparent);
}

.cabecera {
    display: flex;
    align-items: center;
    gap: .8rem;
    width: 100%;
    padding: .9rem 1rem;
    border: none;
    background: none;
    color: var(--color-text);
    text-align: left;
    cursor: pointer;
}

.numero {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--gradiente-primario);
    font-size: .85rem;
    font-weight: 700;
}

.titulo {
    flex: 1;
    min-width: 0;
    font-size: 1rem;
    overflow-wrap: anywhere;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    flex-shrink: 0;
    padding: .15rem .65rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
}

.chip.listo { background: color-mix(in srgb, var(--color-success) 16%, transparent); color: var(--color-success); }
.chip.generando { background: color-mix(in srgb, var(--color-info) 16%, transparent); color: var(--color-info); }
.chip.preparacion { background: color-mix(in srgb, var(--color-warning) 16%, transparent); color: var(--color-warning); }
.chip.error { background: color-mix(in srgb, var(--color-danger) 16%, transparent); color: var(--color-danger); }
.chip.pendiente { background: var(--color-card); color: var(--color-text-secondary); }

.chevron {
    flex-shrink: 0;
    color: var(--color-text-secondary);
    transition: transform .2s;
}

.chevron.girado {
    transform: rotate(180deg);
}

.cuerpo {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 0 1rem 1.1rem;
}

.resumen {
    color: var(--color-text-secondary);
    font-size: .9rem;
    overflow-wrap: anywhere;
}

.xp {
    color: var(--color-text-secondary);
    font-size: .82rem;
}

.estado-texto {
    display: flex;
    align-items: center;
    gap: .6rem;
    color: var(--color-text-secondary);
    font-size: .9rem;
}

.aviso {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    padding: .7rem .9rem;
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-warning) 12%, transparent);
    color: var(--color-warning);
    font-size: .88rem;
    overflow-wrap: anywhere;
}

.aviso--error {
    background: color-mix(in srgb, var(--color-danger) 12%, transparent);
    color: var(--color-danger);
}

.giro {
    display: inline-block;
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    border: 2px solid var(--color-card);
    border-top-color: currentColor;
    border-radius: 50%;
    animation: girar 1s linear infinite;
}

@keyframes girar { to { transform: rotate(360deg); } }

/* Quien prefiere menos movimiento ve el indicador y el chevron quietos */
@media (prefers-reduced-motion: reduce) {
    .giro { animation: none; }
    .chevron { transition: none; }
}

@media (max-width: 520px) {
    .cabecera { flex-wrap: wrap; }
    .titulo { flex-basis: calc(100% - 100px); }
}
</style>
