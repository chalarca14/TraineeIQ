<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <main class="prueba">
        <RouterLink to="/prueba/guias" class="volver">Volver a las guías</RouterLink>
        <h1>Entregas del grupo</h1>

        <p v-if="cargando" class="estado">Cargando entregas...</p>
        <p v-else-if="error" class="estado error">{{ error }}</p>
        <p v-else-if="entregas.length === 0" class="estado">Aún no hay entregas en este grupo.</p>

        <template v-else>
            <div class="tabla">
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Mini proyecto</th>
                            <th>Repositorio</th>
                            <th>Estado</th>
                            <th>Puntaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entrega in entregas" :key="entrega.id">
                            <td>{{ entrega.estudiante.name }}</td>
                            <td>
                                {{ entrega.mini_proyecto.titulo }}
                                <small>
                                    {{ entrega.mini_proyecto.guia.nombre }},
                                    semana {{ entrega.mini_proyecto.semana.numero_semana }}
                                </small>
                            </td>
                            <td>
                                <a :href="entrega.repo_url" target="_blank" rel="noopener noreferrer">Abrir</a>
                            </td>
                            <td>{{ ESTADOS[entrega.estado] }}</td>
                            <td>{{ entrega.puntaje ?? 'Sin evaluar' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav v-if="ultimaPagina > 1" class="paginas">
                <button type="button" class="btn" :disabled="pagina === 1" @click="cargar(pagina - 1)">Anterior</button>
                <span class="estado">Página {{ pagina }} de {{ ultimaPagina }}</span>
                <button type="button" class="btn" :disabled="pagina === ultimaPagina" @click="cargar(pagina + 1)">
                    Siguiente
                </button>
            </nav>
        </template>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getEntregasGrupo } from '@/services/grupoService'

const ESTADOS = { pendiente: 'Pendiente', evaluando: 'Evaluando', evaluado: 'Evaluado', error: 'Con error' }

const route = useRoute()

const entregas = ref([])
const pagina = ref(1)
const ultimaPagina = ref(1)
const cargando = ref(true)
const error = ref('')

// Trae una página de entregas del grupo
async function cargar(numero) {
    cargando.value = true
    error.value = ''

    try {
        const datos = await getEntregasGrupo(route.params.id, numero)
        entregas.value = datos.data
        pagina.value = datos.current_page
        ultimaPagina.value = datos.last_page
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos cargar las entregas.'
    } finally {
        cargando.value = false
    }
}

onMounted(() => cargar(1))
</script>

<style scoped>
.prueba {
    display: grid;
    gap: 1rem;
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

h1 {
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.volver,
.estado,
small {
    color: var(--color-text-secondary);
}

.estado.error {
    color: var(--color-danger);
}

.tabla {
    overflow-x: auto;
    border-radius: 14px;
    background: var(--color-card);
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: .75rem 1rem;
    text-align: left;
}

small {
    display: block;
}

a {
    color: var(--color-primary-light);
}

.paginas {
    display: flex;
    align-items: center;
    justify-content: center;
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
