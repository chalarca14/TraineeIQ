<template>
    <main class="mini-proyectos">

        <EncabezadoPagina titulo="Mini Proyectos" :icono="Code"
            descripcion="Aplica lo que aprendiste con proyectos prácticos y lleva tu conocimiento al siguiente nivel." />

        <p v-if="cargando" class="estado">Cargando tus mini proyectos...</p>

        <div v-else-if="error" class="estado error">
            <p>{{ error }}</p>
            <button type="button" class="btn btn-secundario btn-sm" @click="cargar">Reintentar</button>
        </div>

        <div v-else-if="proyectos.length === 0" class="vacio">
            <Code :size="32" />
            <p>Aún no tienes mini proyectos.</p>
            <span>Abre una de tus guías y pulsa "Comenzar proyecto" para que la IA los cree.</span>
        </div>

        <div v-else class="contenido">
            <section class="principal">
                <p v-if="guiasConError" class="aviso">
                    No se pudieron cargar los proyectos de {{ guiasConError }}
                    {{ guiasConError === 1 ? 'guía' : 'guías' }}. Lo demás sí se muestra.
                </p>

                <FiltrosProyectos v-model:pestana="pestana" v-model:busqueda="busqueda" v-model:orden="orden" />

                <div v-if="encabezado" class="encabezado">
                    <h2>{{ encabezado.titulo }}</h2>
                    <p>{{ encabezado.texto }}</p>
                </div>

                <p v-if="visibles.length === 0" class="vacio-pestana">
                    {{ busqueda ? 'Ningún proyecto coincide con tu búsqueda.' : VACIO_POR_PESTANA[pestana] }}
                </p>

                <div v-else class="cuadricula">
                    <ProyectoCard v-for="p in visibles" :key="p.id" :proyecto="p" @abrir="alAbrir" />
                </div>
            </section>

            <ResumenProgreso :resumen="resumen" />
        </div>

    </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Code } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import FiltrosProyectos from '@/components/miniproyectos/FiltrosProyectos.vue'
import ProyectoCard from '@/components/miniproyectos/ProyectoCard.vue'
import ResumenProgreso from '@/components/miniproyectos/ResumenProgreso.vue'
import { listarMisMiniProyectos } from '@/services/miniProyectoService'
import { estadoDelProyecto } from '@/utils/progresoEjemplo'
import { mensajeDeError } from '@/utils/errores'

const VACIO_POR_PESTANA = {
    todos: 'No hay proyectos para mostrar.',
    en_progreso: 'Aún no tienes proyectos en progreso.',
    completado: 'Aún no tienes proyectos completados.',
    por_iniciar: 'No tienes proyectos pendientes por iniciar.'
}

const router = useRouter()
const crudos = ref([])
const guiasConError = ref(0)
const cargando = ref(true)
const error = ref('')
const pestana = ref('todos')
const busqueda = ref('')
const orden = ref('tema') // por defecto: agrupado por tema, de básico a avanzado

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        const resultado = await listarMisMiniProyectos()
        crudos.value = resultado.proyectos
        guiasConError.value = resultado.guiasConError
    } catch (err) {
        error.value = mensajeDeError(err)
    } finally {
        cargando.value = false
    }
}

// UNA sola lista con el estado ya calculado: tarjetas y resumen salen de aquí,
// por eso los números nunca se contradicen entre pestañas.
const proyectos = computed(() =>
    crudos.value.map((p) => ({ ...p, ...estadoDelProyecto(p, crudos.value) }))
)

// "Más recientes": por fecha de creación si existe; si no (o si empatan), por id descendente
function compararRecientes(a, b) {
    const fa = Date.parse(a.created_at)
    const fb = Date.parse(b.created_at)
    if (!Number.isNaN(fa) && !Number.isNaN(fb) && fa !== fb) return fb - fa
    return b.id - a.id
}

// Orden por tema: primero por guía, luego por tema y dentro de cada tema de básico a avanzado.
// El backend no manda el campo "orden" del tema en este endpoint; usamos tema_id porque los
// temas se crean en el orden de la guía. Los proyectos de toda la guía (sin tema) van primero.
const NIVEL = { basico: 0, intermedio: 1, avanzado: 2 }
function compararPorTema(a, b) {
    return a.guia_id - b.guia_id
        || (a.tema_id ?? 0) - (b.tema_id ?? 0)
        || (NIVEL[a.dificultad] ?? 0) - (NIVEL[b.dificultad] ?? 0)
        || a.id - b.id
}

// La pestaña es solo un FILTRO de la misma lista
const visibles = computed(() => {
    const texto = busqueda.value.trim().toLowerCase()
    const lista = proyectos.value.filter((p) =>
        (pestana.value === 'todos' || p.estado === pestana.value) &&
        (!texto || p.titulo.toLowerCase().includes(texto))
    )
    if (orden.value === 'tema') return lista.sort(compararPorTema)
    lista.sort(compararRecientes)
    return orden.value === 'antiguos' ? lista.reverse() : lista
})

// Resumen de la derecha: se calcula de la lista completa (sin filtros de pestaña ni búsqueda)
const resumen = computed(() => {
    const cuenta = (estado) => proyectos.value.filter((p) => p.estado === estado).length
    const porNivel = {}
    for (const nivel of ['basico', 'intermedio', 'avanzado']) {
        const delNivel = proyectos.value.filter((p) => p.dificultad === nivel)
        porNivel[nivel] = { total: delNivel.length, hechos: delNivel.filter((p) => p.estado === 'completado').length }
    }
    return {
        total: proyectos.value.length,
        completados: cuenta('completado'),
        enProgreso: cuenta('en_progreso'),
        porIniciar: cuenta('por_iniciar'),
        porNivel
    }
})

const encabezado = computed(() => {
    const textos = {
        en_progreso: {
            titulo: `${resumen.value.enProgreso} ${resumen.value.enProgreso === 1 ? 'proyecto' : 'proyectos'} en progreso`,
            texto: 'Retoma donde te dejaste'
        },
        completado: { titulo: 'Tus proyectos finalizados', texto: 'Puedes revisar tus proyectos completados.' },
        por_iniciar: {
            titulo: 'Proyectos disponibles para comenzar',
            texto: 'Los niveles intermedio y avanzado se desbloquean al cumplir los requisitos previos.'
        }
    }
    return textos[pestana.value] ?? null
})

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

.encabezado p,
.estado,
.vacio,
.vacio-pestana {
    color: var(--color-text-secondary);
}

.contenido {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 1.2rem;
    align-items: start;
}

.encabezado {
    margin-bottom: 1rem;
}

.encabezado h2 {
    font-size: 1.05rem;
}

.cuadricula {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
}

.vacio,
.estado.error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 14px;
    text-align: center;
}

.vacio p {
    color: var(--color-text);
    font-weight: 600;
}

.estado.error {
    color: var(--color-danger);
}

.aviso {
    margin-bottom: 1rem;
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
