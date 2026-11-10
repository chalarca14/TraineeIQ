<template>
    <main class="grupo-detalle">
        <nav class="migaja" aria-label="Migaja de pan">
            <RouterLink to="/estudiante/grupos">Mis grupos</RouterLink>
            <ChevronRight :size="14" aria-hidden="true" />
            <span aria-current="page">{{ grupo?.nombre ?? 'Detalle del grupo' }}</span>
        </nav>

        <!-- Sin los datos del grupo no hay cabecera que mostrar: cargando y error cubren toda la vista -->
        <EstadoVista :cargando="cargandoGrupo" :error="errorGrupo" texto-cargando="Cargando el grupo..."
            @reintentar="cargar">
            <div class="cuerpo">
                <div class="principal">
                    <CabeceraGrupo :grupo="grupo" />
                    <TarjetasGrupo :instructor="instructor" :cantidad-guias="cantidadGuias"
                        :miembros="grupo.estudiantes?.length ?? 0" @ver-guias="verGuias" />

                    <section ref="seccionPestanas" class="pestanas-grupo" aria-label="Contenido del grupo">
                        <Pestanas v-model="pestana" :pestanas="PESTANAS" />

                        <!-- Las tres pestañas salen de las mismas guías: un solo cargando / error / vacío para todas -->
                        <EstadoVista :cargando="mini.cargando.value" :error="errorGuias"
                            :vacio="mini.bloques.value.length === 0" :icono="BookOpen"
                            texto-cargando="Cargando las guías del grupo..." texto-vacio="Este grupo aún no tiene guías."
                            descripcion-vacio="Cuando tu instructor suba una guía y libere semanas, aquí verás sus guías y mini proyectos."
                            @reintentar="mini.cargar">
                            <GuiasGrupo v-if="pestana === 'guias'" :bloques="mini.bloques.value" :grupo-id="grupoId" />
                            <ProyectosGrupo v-else-if="pestana === 'proyectos'" :bloques="mini.bloques.value"
                                :reintentar="mini.reintentarGuia" />
                            <MiProgresoGrupo v-else :bloques="mini.bloques.value" />
                        </EstadoVista>
                    </section>
                </div>
            </div>
        </EstadoVista>
    </main>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronRight, BookOpen, Code, TrendingUp } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import Pestanas from '@/components/ui/Pestanas.vue'
import CabeceraGrupo from '@/components/grupos/CabeceraGrupo.vue'
import TarjetasGrupo from '@/components/grupos/TarjetasGrupo.vue'
import GuiasGrupo from '@/components/grupos/GuiasGrupo.vue'
import ProyectosGrupo from '@/components/grupos/ProyectosGrupo.vue'
import MiProgresoGrupo from '@/components/grupos/MiProgresoGrupo.vue'
import { getGrupo } from '@/services/grupoService'
import { useMiniProyectosSemanas } from '@/composables/useMiniProyectosSemanas'
import { mensajeDeError } from '@/utils/errores'

const route = useRoute()
// El id sale de la ruta una sola vez: desde la interfaz no se salta de un grupo a otro sin pasar
// por "Mis grupos", así que la vista siempre se crea de nuevo con el id correcto
const grupoId = Number(route.params.id)

// Guías, semanas y mini proyectos de ESTE grupo, con el mismo composable de la lista de mini proyectos
// y del dashboard: así las tres pantallas cuentan exactamente lo mismo
const mini = useMiniProyectosSemanas({ grupoId })

const grupo = ref(null)
const cargandoGrupo = ref(true)
const errorGrupo = ref('')
// En este orden (PLAN Tarea 8). No hay pestañas Temas, General ni Miembros
const PESTANAS = [
    { clave: 'guias', etiqueta: 'Guías', icono: BookOpen },
    { clave: 'proyectos', etiqueta: 'Proyectos', icono: Code },
    { clave: 'progreso', etiqueta: 'Mi progreso', icono: TrendingUp }
]
const pestana = ref('guias')
const seccionPestanas = ref(null)

// El composable habla de "tus mini proyectos"; aquí lo que falla es la lista de guías del grupo
const errorGuias = computed(() =>
    mini.error.value ? 'No se pudieron cargar las guías del grupo. Intenta de nuevo.' : ''
)

// "Ver todas las guías" activa la pestaña y la acerca a la vista: en 360 px queda lejos de la tarjeta
async function verGuias() {
    pestana.value = 'guias'
    await nextTick()
    seccionPestanas.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const instructor = computed(() => grupo.value?.instructor?.name ?? grupo.value?.profesor?.name ?? '')

// null mientras cargan o si fallaron: la tarjeta muestra "—" en vez de un 0 falso
const cantidadGuias = computed(() =>
    mini.cargando.value || mini.error.value ? null : mini.bloques.value.length
)

// Se decide por el código HTTP. 403 = no es miembro; 404 = el grupo no existe (o se borró)
function mensajeGrupo(err) {
    const status = err.response?.status
    if (status === 403) return 'No tienes acceso a este grupo. Únete con el código que te dio tu instructor.'
    if (status === 404) return 'No encontramos este grupo. Puede que se haya eliminado.'
    return mensajeDeError(err)
}

async function cargarGrupo() {
    cargandoGrupo.value = true
    errorGrupo.value = ''
    try {
        grupo.value = await getGrupo(grupoId)
    } catch (err) {
        errorGrupo.value = mensajeGrupo(err)
    } finally {
        cargandoGrupo.value = false
    }
}

// Grupo y guías se piden a la vez: son independientes y así la pantalla tarda lo de la más lenta
function cargar() {
    return Promise.all([cargarGrupo(), mini.cargar()])
}

onMounted(cargar)
</script>

<style scoped>
.grupo-detalle {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 2.5rem);
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

/* Escritorio: contenido | "Tu avance". En pantallas angostas, una sola columna */
.cuerpo {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 1.2rem;
    align-items: start;
}

.principal {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    min-width: 0;
}

.pestanas-grupo {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    scroll-margin-top: 1rem;
}

@media (max-width: 900px) {
    .cuerpo {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
