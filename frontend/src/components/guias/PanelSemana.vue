<template>
    <section ref="seccion" class="panel" :aria-labelledby="idTitulo">
        <CabeceraSemana :semana="semana" :total="total" :contenido="contenido" :generando-aqui="generandoAqui"
            :es-personal="esPersonal" :id-titulo="idTitulo" />

        <!-- Semana que el instructor (o el XP, en Modo Personal) todavía no abre: sin contenido ni pestañas,
             pero dentro del mismo panel, con su cabecera y la navegación (antes quedaba una barra suelta) -->
        <SemanaBloqueada v-if="!semana.disponible" :semana="semana" :anterior="anterior" :es-personal="esPersonal" />

        <!-- Listo: el contenido se pide al abrir la semana -->
        <template v-else-if="semana.estado_generacion === 'listo'">
            <template v-if="contenido">
                <div class="pestanas" role="tablist" aria-label="Contenido de la semana" @keydown="teclas">
                    <button v-for="p in PESTANAS" :id="idPestana(p.id)" :key="p.id" type="button" role="tab" class="pestana"
                        :class="{ activa: p.id === pestana }" :aria-selected="p.id === pestana"
                        :aria-controls="idPanel" :tabindex="p.id === pestana ? 0 : -1" @click="$emit('update:pestana', p.id)">
                        <component :is="p.icono" :size="16" aria-hidden="true" /> {{ p.texto }}
                    </button>
                </div>

                <div :id="idPanel" role="tabpanel" :aria-labelledby="idPestana(pestana)" class="contenido-pestana">
                    <PestanaGuia v-if="pestana === 'guia'" :texto="contenido.contenido_texto ?? ''"
                        @ver-completa="$emit('ver-completa')" />
                    <ContenidoSemana v-else :semana="contenido" :seccion="pestana" />
                </div>
            </template>
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

        <NavegacionSemanas :anterior="anterior" :siguiente="siguiente" @ir="irA" />
    </section>
</template>

<script setup>
import { ref, nextTick, useId } from 'vue'
import { FileText, ListChecks, Sparkles, Rocket, RefreshCw } from 'lucide-vue-next'
import SemanaBloqueada from '@/components/miniproyectos/SemanaBloqueada.vue'
import CabeceraSemana from '@/components/guias/CabeceraSemana.vue'
import NavegacionSemanas from '@/components/guias/NavegacionSemanas.vue'
import PestanaGuia from '@/components/guias/PestanaGuia.vue'
import ContenidoSemana from '@/components/guias/ContenidoSemana.vue'

// Columna izquierda: la semana elegida con sus pestañas. Los estados (pendiente, generando, error) y
// sus botones son los mismos que tenía el acordeón; solo cambió dónde se pintan.
const props = defineProps({
    // Semana de GET /guias/{id}/semanas (estado, resumen, disponible, XP)
    semana: { type: Object, required: true },
    // Semanas vecinas: el candado de Modo Personal usa la anterior; la navegación, las dos
    anterior: { type: Object, default: null },
    siguiente: { type: Object, default: null },
    // Cuántas semanas tiene la guía ("Semana 2 de 4")
    total: { type: Number, default: 0 },
    // Contenido de la semana ya abierta (GET .../semanas/{n}) o null
    contenido: { type: Object, default: null },
    cargandoContenido: { type: Boolean, default: false },
    errorContenido: { type: String, default: '' },
    // Esta pantalla está generando AHORA esta semana
    generandoAqui: { type: Boolean, default: false },
    revisando: { type: Boolean, default: false },
    // Dueño de la guía: solo él ve el error_mensaje y puede reintentar (forzar)
    esDueno: { type: Boolean, default: false },
    esPersonal: { type: Boolean, default: false },
    // Pestaña activa. La guarda la vista (no este componente) para que "Volver a la semana" regrese a la misma
    pestana: { type: String, default: 'guia' }
})
const emit = defineEmits(['reintentar', 'revisar', 'recargar', 'update:pestana', 'ver-completa', 'seleccionar'])

const PESTANAS = [
    { id: 'guia', texto: 'Guía', icono: FileText },
    { id: 'temas', texto: 'Temas', icono: ListChecks },
    { id: 'recomendaciones', texto: 'Recomendaciones', icono: Sparkles },
    { id: 'proyectos', texto: 'Mini proyectos', icono: Rocket }
]

const idTitulo = useId()
const idPanel = useId()
const idBase = useId()
const idPestana = (id) => `${idBase}-${id}`

// Al cambiar de semana desde abajo se sube al inicio del panel: si no, se vería el final de la nueva
const seccion = ref(null)
async function irA(numero) {
    emit('seleccionar', numero)
    await nextTick()
    const suave = !window.matchMedia('(prefers-reduced-motion: reduce)').matches
    seccion.value?.scrollIntoView({ block: 'start', behavior: suave ? 'smooth' : 'auto' })
}

// Flechas izquierda/derecha cambian de pestaña (patrón de pestañas accesibles)
function teclas(evento) {
    const actual = PESTANAS.findIndex((p) => p.id === props.pestana)
    const paso = { ArrowRight: 1, ArrowLeft: -1 }[evento.key]
    if (!paso) return

    evento.preventDefault()
    const siguiente = PESTANAS[(actual + paso + PESTANAS.length) % PESTANAS.length]
    emit('update:pestana', siguiente.id)
    nextTick(() => document.getElementById(idPestana(siguiente.id))?.focus())
}
</script>

<style scoped>
/* Más aire entre cabecera, pestañas y contenido: antes todo quedaba pegado */
.panel {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-lg);
    min-width: 0;
    padding: var(--espacio-lg);
    border: 1px solid var(--color-border);
    border-radius: 18px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 45%, var(--color-surface)), var(--color-surface) 160px);
    /* Al saltar de semana, el panel no queda escondido bajo la barra superior */
    scroll-margin-top: 90px;
}

/* En escritorio, una fila. En celular pasan a 2 × 2 (ver @media abajo): con scroll horizontal
   "Mini proyectos" quedaba escondida y el aprendiz no sabía que existía. */
.pestanas {
    display: flex;
    gap: .3rem;
    overflow-x: auto;
    scrollbar-width: thin;
    border-bottom: 1px solid var(--color-border);
}

.pestana {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    flex-shrink: 0;
    min-height: 44px;
    padding: .6rem 1rem;
    border: none;
    border-bottom: 2px solid transparent;
    background: none;
    color: var(--color-text-secondary);
    font: inherit;
    font-size: .88rem;
    white-space: nowrap;
    cursor: pointer;
}

.pestana:hover {
    color: var(--color-text);
}

.pestana.activa {
    border-bottom-color: var(--color-primary-light);
    color: var(--color-text);
    font-weight: 700;
}

.pestana.activa svg {
    color: var(--color-primary-light);
}

.contenido-pestana {
    min-width: 0;
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

@media (prefers-reduced-motion: reduce) {
    .giro { animation: none; }
}

@media (max-width: 520px) {
    .panel { padding: var(--espacio-md); gap: var(--espacio-md); }

    /* Cuadrícula de 2 columnas: las cuatro pestañas se ven sin deslizar.
       Cada pestaña lleva su propio subrayado, así se sigue viendo cuál está activa. */
    .pestanas {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        overflow-x: visible;
        border-bottom: none;
    }

    /* Cada celda mide ~140 px: con menos relleno y letra un poco menor "Recomendaciones" cabe
       junto a su ícono (si no, el ícono se encogía hasta desaparecer). */
    .pestana {
        justify-content: center;
        gap: .3rem;
        min-height: 40px;
        padding: .6rem .35rem;
        border-bottom-color: var(--color-border);
        font-size: .82rem;
    }

    .pestana svg {
        flex-shrink: 0;
    }
}
</style>
