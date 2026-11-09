<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <main class="prueba">
        <p v-if="cargando" class="estado">Cargando mini proyecto...</p>
        <p v-else-if="error" class="estado error">{{ error }}</p>

        <template v-else>
            <RouterLink :to="`/prueba/guias/${proyecto.guia_id}`" class="volver">Volver a la guía</RouterLink>

            <header class="encabezado">
                <h1>{{ proyecto.titulo }}</h1>
                <EtiquetaNivel :valor="proyecto.dificultad" />
            </header>
            <p class="secundario">Semana {{ proyecto.semana.numero_semana }}: {{ proyecto.semana.titulo }}</p>
            <p class="secundario">{{ proyecto.descripcion }}</p>

            <section v-if="proyecto.contenido" class="bloque">
                <h2>Instrucciones</h2>
                <p class="instrucciones">{{ proyecto.contenido }}</p>
            </section>

            <section class="bloque">
                <h2>Requisitos funcionales</h2>
                <ul>
                    <li v-for="requisito in proyecto.requisitos_funcionales" :key="requisito">{{ requisito }}</li>
                </ul>
            </section>

            <section class="bloque">
                <h2>Buenas prácticas</h2>
                <ul>
                    <li v-for="practica in proyecto.buenas_practicas" :key="practica">{{ practica }}</li>
                </ul>
            </section>

            <section v-if="esEstudiante" class="bloque">
                <h2>Tu entrega</h2>
                <RepoFormPrueba :mini-proyecto-id="proyecto.id" :repo-url="repoUrl" @guardado="entrega = $event" />
                <BotonEditorPrueba :repo-url="repoUrl" />
            </section>

            <section v-if="esEstudiante" class="bloque">
                <h2>Evaluación de tu código</h2>
                <div class="evaluar">
                    <button type="button" class="btn" :disabled="!puedeEvaluar" @click="evaluar">
                        {{ evaluando ? 'Evaluando...' : 'Evaluar mi código' }}
                    </button>
                    <span class="secundario">Te quedan {{ intentosRestantes }} evaluaciones hoy.</span>
                </div>
                <p v-if="evaluando" class="secundario">Estamos leyendo tu repositorio. Puede tardar un momento.</p>
                <p v-if="errorEvaluar" class="estado error">{{ errorEvaluar }}</p>
                <p v-else-if="entrega?.estado === 'error'" class="estado error">
                    No se pudo evaluar, intenta de nuevo.
                </p>
                <EvaluacionResultadoPrueba v-if="entrega?.evaluacion" :evaluacion="entrega.evaluacion" />
            </section>
        </template>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import { getEntrega, getMiniProyecto, pedirEvaluacion } from '@/services/miniProyectoService'
import { useAuthStore } from '@/stores/auth'
import BotonEditorPrueba from './BotonEditorPrueba.vue'
import EvaluacionResultadoPrueba from './EvaluacionResultadoPrueba.vue'
import RepoFormPrueba from './RepoFormPrueba.vue'

const route = useRoute()
const auth = useAuthStore()

const proyecto = ref(null)
const entrega = ref(null)
const cargando = ref(true)
const error = ref('')
const intentosRestantes = ref(0)
const evaluando = ref(false)
const errorEvaluar = ref('')

const esEstudiante = computed(() => auth.rolUsuario === 'estudiante')
const repoUrl = computed(() => entrega.value?.repo_url ?? '')
const puedeEvaluar = computed(() => Boolean(repoUrl.value) && intentosRestantes.value > 0 && !evaluando.value)

// Actualiza la pantalla con lo que responde el backend
function aplicarEntrega(datos) {
    entrega.value = datos.entrega
    intentosRestantes.value = datos.intentos_restantes
}

// Espera el resultado. Si falla, recarga la entrega porque el estado pudo cambiar
async function evaluar() {
    evaluando.value = true
    errorEvaluar.value = ''

    try {
        aplicarEntrega(await pedirEvaluacion(proyecto.value.id))
    } catch (e) {
        errorEvaluar.value = e.response?.data?.message ?? 'No pudimos evaluar tu código.'

        try {
            aplicarEntrega(await getEntrega(proyecto.value.id))
        } catch {
            // El mensaje de error ya está en pantalla
        }
    } finally {
        evaluando.value = false
    }
}

onMounted(async () => {
    try {
        proyecto.value = await getMiniProyecto(route.params.id)

        // El instructor no entrega repositorios
        if (esEstudiante.value) {
            aplicarEntrega(await getEntrega(route.params.id))
        }
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos cargar este mini proyecto.'
    } finally {
        cargando.value = false
    }
})
</script>

<style scoped>
.prueba {
    display: grid;
    gap: 1rem;
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 900px;
    margin: 0 auto;
}

.encabezado {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
}

h1 {
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

h2 {
    font-size: 1rem;
}

.volver,
.estado,
.secundario,
.instrucciones,
li {
    color: var(--color-text-secondary);
}

.estado.error {
    color: var(--color-danger);
}

.bloque {
    display: grid;
    gap: .6rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    background: var(--color-card);
}

.instrucciones {
    white-space: pre-line;
}

ul {
    padding-left: 1.2rem;
}

.evaluar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
}

.btn {
    padding: .5rem 1rem;
    border: none;
    border-radius: 10px;
    background: var(--color-primary);
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}
</style>
