<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <main class="prueba">
        <RouterLink to="/prueba/guias" class="volver">Volver a las guías</RouterLink>

        <p v-if="cargando" class="estado">Cargando guía...</p>
        <p v-else-if="errorGuia" class="estado error">{{ errorGuia }}</p>

        <template v-else>
            <h1>{{ guia.nombre }}</h1>

            <form v-if="grupo" class="liberar" @submit.prevent="liberar">
                <label for="semana-actual">Semana liberada para el grupo</label>
                <input id="semana-actual" v-model.number="semanaLiberada" type="number" min="1" max="52" />
                <button type="submit" class="btn" :disabled="liberando">
                    {{ liberando ? 'Guardando...' : 'Liberar' }}
                </button>
                <span v-if="mensajeLiberar" class="mensaje">{{ mensajeLiberar }}</span>
            </form>

            <RouterLink v-if="grupo" :to="`/prueba/grupos/${grupo.id}/entregas`" class="volver">
                Ver entregas del grupo
            </RouterLink>

            <p v-if="store.aviso" class="estado aviso">{{ store.aviso }}</p>
            <p v-if="store.error" class="estado error">{{ store.error }}</p>
            <p v-if="store.procesandoCola" class="estado">Preparando semanas...</p>

            <nav class="pestanas">
                <button
                    v-for="s in store.semanas"
                    :key="s.id"
                    type="button"
                    class="pestana"
                    :class="{ activa: s.numero_semana === store.semanaActiva, bloqueada: !s.disponible }"
                    @click="store.seleccionarSemana(s.numero_semana)"
                >
                    Semana {{ s.numero_semana }}
                    <small>{{ etiquetaEstado(s) }}</small>
                    <small v-if="s.xp_maximo">XP {{ s.xp_obtenido }}/{{ s.xp_maximo }} (meta {{ s.xp_requerido }})</small>
                </button>
            </nav>

            <SemanaContenidoPrueba
                v-if="store.semanaSeleccionada"
                :semana="store.semanaSeleccionada"
                :contenido="store.contenido"
                :cargando="store.cargandoContenido"
                :es-dueno="esDueno"
                @reintentar="store.reintentar(store.semanaActiva)"
            />
        </template>
    </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { obtenerGuia } from '@/services/guiaService'
import { getGrupo, liberarSemana } from '@/services/grupoService'
import { useAuthStore } from '@/stores/auth'
import { useContenidoStore } from '@/stores/contenido'
import SemanaContenidoPrueba from './SemanaContenidoPrueba.vue'

const CONSULTA_CADA_MS = 10000

const route = useRoute()
const auth = useAuthStore()
const store = useContenidoStore()

const guia = ref(null)
const grupo = ref(null)
const cargando = ref(true)
const errorGuia = ref('')
const semanaLiberada = ref(1)
const liberando = ref(false)
const mensajeLiberar = ref('')
let temporizador = null

const esInstructor = computed(() => auth.rolUsuario === 'instructor')

// Si la API dejó ver la guía, un instructor es su dueño; un estudiante solo en las personales
const esDueno = computed(() => esInstructor.value || guia.value?.origen === 'personal')

// Texto corto que acompaña a cada pestaña
function etiquetaEstado(semana) {
    // En Modo Personal la semana se abre con experiencia, no la libera un instructor
    if (!semana.disponible) return guia.value?.origen === 'personal' ? 'Bloqueada' : 'Próximamente'
    const etiquetas = { pendiente: 'Preparando', generando: 'Preparando', listo: 'Lista', error: 'Con error' }
    return etiquetas[semana.estado_generacion]
}

// Guarda hasta qué semana pueden leer los estudiantes del grupo
async function liberar() {
    liberando.value = true
    mensajeLiberar.value = ''

    try {
        semanaLiberada.value = await liberarSemana(grupo.value.id, semanaLiberada.value)
        mensajeLiberar.value = 'Semana liberada.'
        await store.refrescarSemanas()
    } catch (e) {
        mensajeLiberar.value = e.response?.data?.message ?? 'No pudimos liberar la semana.'
    } finally {
        liberando.value = false
    }
}

onMounted(async () => {
    // Otra persona puede estar generando una semana: se consulta hasta que termine
    temporizador = setInterval(() => {
        if (store.hayGenerando) {
            store.procesarCola()
        }
    }, CONSULTA_CADA_MS)

    try {
        guia.value = await obtenerGuia(route.params.id)

        if (guia.value.grupo_id && esInstructor.value) {
            grupo.value = await getGrupo(guia.value.grupo_id)
            semanaLiberada.value = grupo.value.semana_actual
        }
    } catch (e) {
        errorGuia.value = e.response?.data?.message ?? 'No pudimos cargar esta guía.'
        return
    } finally {
        cargando.value = false
    }

    await store.abrirGuia(guia.value.id)
})

onUnmounted(() => {
    clearInterval(temporizador)
    store.cerrarGuia()
})
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
.mensaje {
    color: var(--color-text-secondary);
}

.estado.aviso {
    color: var(--color-warning);
}

.estado.error {
    color: var(--color-danger);
}

.liberar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .75rem;
}

.liberar input {
    width: 5rem;
    padding: .5rem;
    border: 1px solid var(--color-card);
    border-radius: 10px;
    background: var(--color-surface);
    color: var(--color-text);
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

.pestanas {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.pestana {
    display: grid;
    gap: .1rem;
    padding: .5rem .9rem;
    border: 1px solid transparent;
    border-radius: 10px;
    background: var(--color-card);
    color: var(--color-text);
    cursor: pointer;
}

.pestana small {
    color: var(--color-text-secondary);
}

.pestana.activa {
    border-color: var(--color-primary);
}

.pestana.bloqueada {
    opacity: .6;
}
</style>
