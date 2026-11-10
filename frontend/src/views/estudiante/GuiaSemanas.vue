<template>
    <main class="guia">

        <EstadoVista :cargando="cargando" :error="error" texto-cargando="Cargando la guía..." @reintentar="cargar">
            <!-- Migaja: en Modo Grupo "Mis grupos › grupo › Guías › guía"; en Modo Personal "Mis guías › guía" -->
            <nav class="migaja" aria-label="Migaja de pan">
                <template v-if="esGrupo">
                    <RouterLink to="/estudiante/grupos">Mis grupos</RouterLink>
                    <ChevronRight :size="14" aria-hidden="true" />
                    <span v-if="grupo">{{ grupo.nombre }}</span>
                    <ChevronRight v-if="grupo" :size="14" aria-hidden="true" />
                    <span>Guías</span>
                </template>
                <RouterLink v-else to="/estudiante/guias">Mis guías</RouterLink>
                <ChevronRight :size="14" aria-hidden="true" />
                <span aria-current="page">{{ guia.nombre }}</span>
            </nav>

            <EncabezadoPagina :titulo="guia.nombre" :icono="BookOpen" :descripcion="descripcion">
                <template #etiquetas>
                    <span class="tag tag--acento">{{ esGrupo ? 'Guía del grupo' : 'Guía personal' }}</span>
                    <span v-if="esGrupo && grupo?.instructor?.name" class="tag">Instructor: {{ grupo.instructor.name }}</span>
                    <span v-if="esGrupo && grupo?.nombre" class="tag">Grupo: {{ grupo.nombre }}</span>
                    <span v-if="store.semanas.length" class="tag">
                        {{ store.semanas.length }} {{ store.semanas.length === 1 ? 'semana' : 'semanas' }}
                    </span>
                    <span v-if="semanaActual" class="tag">Semana actual: {{ semanaActual }}</span>
                </template>
                <template #acciones>
                    <!-- Lleva al chat en modo guiado: la IA solo responde sobre esta guía -->
                    <RouterLink :to="{ path: '/estudiante/ia', query: { guiaId: guia.id } }" class="btn btn-primario">
                        <Bot :size="18" /> Preguntar a la IA
                    </RouterLink>
                </template>
            </EncabezadoPagina>

            <!-- Avisos de la generación (por ejemplo 503 "IA ocupada"): la semana queda pendiente y se puede reintentar -->
            <div v-if="store.aviso" class="aviso" role="alert">
                <span>{{ store.aviso }}</span>
                <button type="button" class="btn btn-secundario btn-sm" :disabled="store.procesandoCola"
                    @click="store.procesarCola()">
                    <RefreshCw :size="15" /> Volver a intentar
                </button>
            </div>

            <p v-if="cargandoSemanas" class="estado" role="status">Cargando las semanas...</p>

            <div v-else-if="!store.semanas.length" class="caja" :class="{ 'caja--error': store.error }">
                <p>{{ store.error || 'Esta guía aún no tiene semanas.' }}</p>
                <button v-if="store.error" type="button" class="btn btn-secundario btn-sm" @click="abrirSemanas">Reintentar</button>
            </div>

            <!-- Dos columnas en escritorio (contenido | lista + dudas). En 360 px es una sola columna y el
                 orden cambia con áreas de la cuadrícula: selector arriba, contenido y tarjeta al final -->
            <div v-else class="cuerpo">
                <ListaSemanas class="area-lista" :semanas="store.semanas" :activa="store.semanaActiva"
                    :generando-aqui="store.generandoAqui" @seleccionar="elegirSemana" />

                <div class="area-panel">
                    <PanelSemana v-if="store.semanaSeleccionada" v-model:pestana="pestana" :semana="store.semanaSeleccionada"
                        :anterior="semanaAnterior" :contenido="store.contenido" :cargando-contenido="store.cargandoContenido"
                        :error-contenido="store.error" :generando-aqui="store.generandoAqui === store.semanaActiva"
                        :revisando="store.revisando" :es-dueno="esDueno" :es-personal="esPersonal"
                        @reintentar="store.reintentar(store.semanaActiva)" @revisar="store.revisar()"
                        @recargar="store.cargarContenido()" />
                </div>

                <TarjetaDudasIA class="area-dudas" :guia-id="guia.id" />
            </div>
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { Bot, BookOpen, ChevronRight, RefreshCw } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import ListaSemanas from '@/components/guias/ListaSemanas.vue'
import PanelSemana from '@/components/guias/PanelSemana.vue'
import TarjetaDudasIA from '@/components/guias/TarjetaDudasIA.vue'
import { obtenerGuia } from '@/services/guiaService'
import { listarMisGrupos } from '@/services/grupoService'
import { useContenidoStore } from '@/stores/contenido'
import { useAuthStore } from '@/stores/auth'
import { mensajeDeError } from '@/utils/errores'

// UNA sola vista para las dos rutas: /estudiante/guias/:id (Modo Personal) y
// /estudiante/grupos/:grupoId/guias/:guiaId (Modo Grupo). Qué reglas aplican lo decide el
// `origen` de la guía que manda el backend, no la ruta.
const route = useRoute()
const store = useContenidoStore()
const authStore = useAuthStore()

const guia = ref(null)
const grupo = ref(null)
const cargando = ref(true)
const cargandoSemanas = ref(false)
const error = ref('')
// Pestaña activa del panel. Vive aquí y no en PanelSemana para no perderse al cambiar de semana
const pestana = ref('guia')

const guiaId = computed(() => Number(route.params.guiaId ?? route.params.id))
const esGrupo = computed(() => guia.value?.origen === 'grupo')
const esPersonal = computed(() => guia.value?.origen === 'personal')
// Dueño = quien puede "Reintentar" (forzar). En el aprendiz solo ocurre en su guía personal;
// en una guía de grupo el dueño es el instructor.
const esDueno = computed(() => esPersonal.value && guia.value?.usuario_id === authStore.usuario?.id)

// La semana más avanzada que el backend dejó abierta. Solo se LEE `disponible`: el frontend no decide
// qué semana se abre. Si ninguna está abierta, la primera (se mostrará como "Próximamente").
const semanaPorDefecto = computed(() => {
    const abiertas = store.semanas.filter((s) => s.disponible).map((s) => s.numero_semana)
    return abiertas.length ? Math.max(...abiertas) : (store.semanas[0]?.numero_semana ?? null)
})

// En Modo Grupo el dato del grupo manda; en Modo Personal es la mayor semana abierta
const semanaActual = computed(() => {
    if (esGrupo.value && grupo.value?.semana_actual != null) return grupo.value.semana_actual
    const abiertas = store.semanas.filter((s) => s.disponible)
    return abiertas.length ? semanaPorDefecto.value : null
})

const semanaAnterior = computed(() => {
    const i = store.semanas.findIndex((s) => s.numero_semana === store.semanaActiva)
    return i > 0 ? store.semanas[i - 1] : null
})

const descripcion = computed(() =>
    'La IA prepara el contenido de cada semana: temas, recomendaciones y mini proyectos.'
)

// Una semana a la vez: el store guarda una sola `semanaActiva`. Elegir la que ya está abierta no repite la petición
function elegirSemana(numero) {
    if (numero !== store.semanaActiva) store.seleccionarSemana(numero)
}

// En Modo Grupo no se piden semanas sin liberar (soloLiberadas): el backend las generaría igual
async function abrirSemanas() {
    cargandoSemanas.value = true
    try {
        await store.abrirGuia(guiaId.value, { soloLiberadas: esGrupo.value })
        // El store abre la primera disponible; aquí se pasa a la más avanzada (la "semana actual")
        const objetivo = semanaPorDefecto.value
        if (objetivo != null && objetivo !== store.semanaActiva) {
            await store.seleccionarSemana(objetivo)
        }
    } finally {
        cargandoSemanas.value = false
    }
}

async function cargar() {
    cargando.value = true
    error.value = ''
    grupo.value = null
    try {
        guia.value = await obtenerGuia(guiaId.value)
    } catch (err) {
        // 404 = la guía no existe (mensajeDeError ya cubre el 403 "No tienes acceso a esta guía")
        error.value = err.response?.status === 404 ? 'No encontramos esta guía.' : mensajeDeError(err)
        return
    } finally {
        cargando.value = false
    }

    // El nombre del grupo y el instructor solo adornan la cabecera: si falla, la guía sigue funcionando
    if (esGrupo.value && route.params.grupoId) {
        try {
            const grupos = await listarMisGrupos()
            grupo.value = grupos.find((g) => g.id === Number(route.params.grupoId)) ?? null
        } catch {
            grupo.value = null
        }
    }

    abrirSemanas()
}

// Si se navega a otra guía este componente se reutiliza: se vuelve a cargar todo
watch(guiaId, () => {
    pestana.value = 'guia'
    cargar()
})
onMounted(cargar)

// Al salir se detiene la cola de generación: no se sigue gastando Gemini en una pantalla que ya no se ve
onBeforeUnmount(() => store.cerrarGuia())
</script>

<style scoped>
.guia {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    max-width: 1180px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

.migaja {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.migaja a {
    color: var(--color-primary-light);
}

.migaja [aria-current='page'] {
    color: var(--color-text);
    overflow-wrap: anywhere;
}

.tag {
    padding: .2rem .7rem;
    border-radius: 999px;
    background: var(--color-card);
    color: var(--color-text-secondary);
    font-size: .75rem;
    overflow-wrap: anywhere;
}

.tag--acento {
    background: color-mix(in srgb, var(--color-primary) 30%, var(--color-card));
    color: var(--color-text);
}

/* Escritorio: el panel ocupa las dos filas de la izquierda; lista y tarjeta de dudas se apilan a la derecha */
.cuerpo {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    grid-template-rows: auto 1fr;
    grid-template-areas:
        'panel lista'
        'panel dudas';
    align-items: start;
    gap: 1rem;
}

.area-panel { grid-area: panel; min-width: 0; }
.area-lista { grid-area: lista; }
.area-dudas { grid-area: dudas; }

@media (max-width: 860px) {
    .cuerpo {
        grid-template-columns: minmax(0, 1fr);
        grid-template-rows: none;
        grid-template-areas:
            'lista'
            'panel'
            'dudas';
    }
}

.estado {
    color: var(--color-text-secondary);
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

.caja {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    padding: 2rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 16px;
    color: var(--color-text-secondary);
    text-align: center;
}

.caja--error,
.caja--error p {
    color: var(--color-danger);
}
</style>
