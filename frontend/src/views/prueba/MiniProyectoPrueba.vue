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
        </template>
    </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import { getEntrega, getMiniProyecto } from '@/services/miniProyectoService'
import { useAuthStore } from '@/stores/auth'
import BotonEditorPrueba from './BotonEditorPrueba.vue'
import RepoFormPrueba from './RepoFormPrueba.vue'

const route = useRoute()
const auth = useAuthStore()

const proyecto = ref(null)
const entrega = ref(null)
const cargando = ref(true)
const error = ref('')

const esEstudiante = computed(() => auth.rolUsuario === 'estudiante')
const repoUrl = computed(() => entrega.value?.repo_url ?? '')

onMounted(async () => {
    try {
        proyecto.value = await getMiniProyecto(route.params.id)

        // El instructor no entrega repositorios
        if (esEstudiante.value) {
            entrega.value = await getEntrega(route.params.id)
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
</style>
