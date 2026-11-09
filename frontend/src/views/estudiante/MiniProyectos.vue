<template>
    <main class="mini-proyectos">

        <EncabezadoPagina titulo="Mini Proyectos" :icono="Code"
            descripcion="Aplica lo que aprendiste con proyectos prácticos, entrega tu repositorio y recibe una evaluación orientativa." />

        <EstadoVista :cargando="cargando" :error="error" :vacio="bloques.length === 0" :icono="Code"
            texto-cargando="Cargando tus mini proyectos..." :texto-vacio="textoVacio"
            :descripcion-vacio="descripcionVacio" @reintentar="cargar">
            <div class="contenido">
                <section class="principal">
                    <FiltrosProyectos v-model:pestana="pestana" v-model:busqueda="busqueda" />

                    <p v-if="sinResultados" class="vacio-pestana">
                        {{ busqueda ? 'Ningún proyecto coincide con tu búsqueda.' : VACIO_POR_PESTANA[pestana] }}
                    </p>

                    <section v-for="bloque in vista" :key="bloque.guia.id" class="guia">
                        <h2 :title="bloque.guia.nombre">{{ bloque.guia.nombre }}</h2>

                        <!-- Una guía que falla no tumba las demás: aviso propio con su reintento -->
                        <div v-if="bloque.fallo" class="aviso" role="alert">
                            <span>No se pudieron cargar los proyectos de esta guía.</span>
                            <button type="button" class="btn btn-secundario btn-sm"
                                :disabled="reintentando.includes(bloque.guia.id)" @click="reintentar(bloque.guia.id)">
                                {{ reintentando.includes(bloque.guia.id) ? 'Reintentando...' : 'Reintentar' }}
                            </button>
                        </div>

                        <div v-for="item in bloque.semanas" :key="item.semana.numero_semana" class="semana">
                            <SemanaBloqueada v-if="!item.semana.disponible" :semana="item.semana"
                                :anterior="item.anterior" :es-personal="esPersonal" />

                            <template v-else>
                                <h3>Semana {{ item.semana.numero_semana }}<template v-if="item.semana.titulo">: {{ item.semana.titulo }}</template></h3>
                                <p v-if="item.proyectos.length === 0" class="vacio-semana">
                                    {{ item.semana.estado_generacion === 'listo'
                                        ? 'Esta semana no tiene mini proyectos.'
                                        : 'El contenido de esta semana aún se está preparando.' }}
                                </p>
                                <div v-else class="cuadricula">
                                    <ProyectoCard v-for="p in item.proyectos" :key="p.id" :proyecto="p"
                                        :es-personal="esPersonal" @abrir="alAbrir" />
                                </div>
                            </template>
                        </div>
                    </section>
                </section>

                <ResumenProgreso :resumen="resumen" :semanas="semanasResumen" :es-personal="esPersonal"
                    :varias-guias="bloques.length > 1" />
            </div>
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Code } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import FiltrosProyectos from '@/components/miniproyectos/FiltrosProyectos.vue'
import ProyectoCard from '@/components/miniproyectos/ProyectoCard.vue'
import SemanaBloqueada from '@/components/miniproyectos/SemanaBloqueada.vue'
import ResumenProgreso from '@/components/miniproyectos/ResumenProgreso.vue'
import { useMiniProyectosSemanas } from '@/composables/useMiniProyectosSemanas'
import { useModoStore } from '@/stores/modo'
import { pestanaDe } from '@/utils/estadoEntrega'

const VACIO_POR_PESTANA = {
    todos: 'No hay proyectos para mostrar.',
    por_entregar: 'No tienes proyectos pendientes por entregar.',
    evaluando: 'No tienes proyectos en evaluación ahora.',
    evaluado: 'Aún no tienes proyectos evaluados.'
}

const router = useRouter()
const modoStore = useModoStore()
const { bloques, proyectos, cargando, error, cargar, reintentarGuia } = useMiniProyectosSemanas()

const pestana = ref('todos')
const busqueda = ref('')
const reintentando = ref([]) // ids de guías que se están reintentando

const esPersonal = computed(() => modoStore.esModoPersonal)

const textoVacio = computed(() =>
    esPersonal.value ? 'Aún no tienes guías en Modo Personal.' : 'Tus grupos aún no tienen guías.'
)
const descripcionVacio = computed(() =>
    esPersonal.value
        ? 'Sube una guía en "Mis guías": la IA crea los mini proyectos de cada semana.'
        : 'Cuando tu instructor suba una guía y libere semanas, sus proyectos aparecerán aquí.'
)

// La pestaña y la búsqueda solo FILTRAN la lista real; no cambian los datos
const coincide = (p) => {
    const texto = busqueda.value.trim().toLowerCase()
    return (pestana.value === 'todos' || pestanaDe(p) === pestana.value) &&
        (!texto || p.titulo.toLowerCase().includes(texto))
}

// Lo que se pinta: por guía, sus semanas con los proyectos que pasan el filtro.
// Las semanas con candado y las "en preparación" solo se ven en "Todos" y sin búsqueda,
// porque no tienen proyectos que filtrar.
const vista = computed(() => {
    const filtrando = pestana.value !== 'todos' || busqueda.value.trim() !== ''
    return bloques.value
        .map((bloque) => ({
            ...bloque,
            semanas: bloque.semanas
                .map((semana, i) => ({
                    semana,
                    anterior: i > 0 ? bloque.semanas[i - 1] : null,
                    proyectos: semana.proyectos.filter(coincide)
                }))
                .filter((it) => (it.semana.disponible && it.proyectos.length > 0) || !filtrando)
        }))
        // Una guía sin nada que mostrar no deja su título solo; la que falló sí se queda para avisar
        .filter((b) => b.fallo || b.semanas.length > 0)
})

const sinResultados = computed(() => vista.value.every((b) => !b.fallo && b.semanas.length === 0))

// Resumen de la derecha: se calcula de la lista completa (sin filtros de pestaña ni búsqueda)
const resumen = computed(() => {
    const cuenta = (clave) => proyectos.value.filter((p) => pestanaDe(p) === clave).length
    return {
        total: proyectos.value.length,
        evaluados: cuenta('evaluado'),
        evaluando: cuenta('evaluando'),
        porEntregar: cuenta('por_entregar')
    }
})

// Una fila por semana abierta con proyectos. Los datos de XP se pasan tal cual llegaron
const semanasResumen = computed(() =>
    bloques.value.flatMap((b) =>
        b.semanas
            .filter((s) => s.disponible && s.proyectos.length > 0)
            .map((s) => ({
                guiaId: b.guia.id,
                guiaNombre: b.guia.nombre,
                numero_semana: s.numero_semana,
                total: s.proyectos.length,
                evaluados: s.proyectos.filter((p) => pestanaDe(p) === 'evaluado').length,
                xp_obtenido: s.xp_obtenido,
                xp_maximo: s.xp_maximo,
                xp_requerido: s.xp_requerido
            }))
    )
)

async function reintentar(guiaId) {
    reintentando.value.push(guiaId)
    try {
        await reintentarGuia(guiaId)
    } finally {
        reintentando.value = reintentando.value.filter((id) => id !== guiaId)
    }
}

// La tarjeta solo avisa "abrir este proyecto"; la vista decide a dónde ir
function alAbrir(proyecto) {
    router.push({ name: 'MiniProyectoDetalle', params: { id: proyecto.id } })
}

onMounted(cargar)
</script>

<style scoped>
.mini-proyectos {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 2.5rem);
}

.vacio-pestana,
.vacio-semana {
    color: var(--color-text-secondary);
}

.contenido {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 1.2rem;
    align-items: start;
}

.guia {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.guia h2 {
    overflow: hidden;
    font-size: 1.1rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.semana {
    display: flex;
    flex-direction: column;
    gap: .6rem;
}

.semana h3 {
    color: var(--color-text-secondary);
    font-size: .95rem;
    overflow-wrap: anywhere;
}

.cuadricula {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
}

.aviso {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    padding: .6rem .8rem;
    border-radius: 10px;
    background: color-mix(in srgb, var(--color-warning) 14%, transparent);
    color: var(--color-warning);
    font-size: .85rem;
}

/* En pantallas angostas el resumen pasa debajo de la lista */
@media (max-width: 900px) {
    .contenido {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
