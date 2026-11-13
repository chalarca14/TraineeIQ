<template>
    <main class="grupo-detalle">
        <nav class="migaja" aria-label="Migaja de pan">
            <RouterLink :to="{ name: 'InstructorMisGrupos' }">Mis grupos</RouterLink>
            <ChevronRight :size="14" aria-hidden="true" />
            <span aria-current="page">{{ grupo?.nombre ?? 'Detalle del grupo' }}</span>
        </nav>

        <!-- Sin los datos del grupo no hay nada que mostrar: cargando y error cubren toda la vista -->
        <EstadoVista :cargando="cargando" :error="error" texto-cargando="Cargando el grupo…" @reintentar="cargar">
            <template #cargando>
                <EsqueletoTarjeta :lineas="4" alto="170px" />
                <div class="esqueleto-columnas">
                    <EsqueletoTarjeta v-for="n in 4" :key="n" :lineas="4" :con-icono="false" alto="180px" />
                </div>
            </template>

            <CabeceraGrupoInstructor :grupo="grupo" />

            <section class="contenido" aria-label="Contenido del grupo">
                <Pestanas v-model="pestana" :pestanas="PESTANAS" cuadricula />

                <ResumenGrupo v-if="pestana === 'resumen'" :grupo="grupo" @ir-a="pestana = $event" />
                <GuiasGrupoInstructor v-else-if="pestana === 'guias'" :grupo-id="grupo.id" />
                <SemanasGrupo v-else-if="pestana === 'semanas'" :grupo="grupo"
                    @semana-liberada="grupo.semana_actual = $event" @ir-a="pestana = $event" />

                <EstadisticasGrupo v-else :grupo="grupo" @ir-a="pestana = $event" />
            </section>
        </EstadoVista>
    </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ChevronRight, LayoutDashboard, BookOpen, CalendarRange, BarChart3 } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import Pestanas from '@/components/ui/Pestanas.vue'
import CabeceraGrupoInstructor from '@/components/instructor/CabeceraGrupoInstructor.vue'
import ResumenGrupo from '@/components/instructor/ResumenGrupo.vue'
import GuiasGrupoInstructor from '@/components/instructor/GuiasGrupoInstructor.vue'
import SemanasGrupo from '@/components/instructor/SemanasGrupo.vue'
import EstadisticasGrupo from '@/components/instructor/EstadisticasGrupo.vue'
import { getGrupo } from '@/services/grupoService'
import { mensajeDeErrorGrupo } from '@/utils/errores'

const route = useRoute()
const router = useRouter()

// El id sale de la ruta una sola vez: para ir a otro grupo se pasa por "Mis grupos", así que la
// vista siempre se crea de nuevo con el id correcto
const grupoId = Number(route.params.id)

const PESTANAS = [
    { clave: 'resumen', etiqueta: 'Resumen', icono: LayoutDashboard },
    { clave: 'guias', etiqueta: 'Guías', icono: BookOpen },
    { clave: 'semanas', etiqueta: 'Semanas y proyectos', icono: CalendarRange },
    { clave: 'estadisticas', etiqueta: 'Estadísticas', icono: BarChart3 }
]

// La pestaña vive en la URL (?tab=guias): se puede enlazar, recargar y volver atrás con el navegador.
// replace (no push): cambiar de pestaña no llena el historial de pasos para "atrás"
const pestana = computed({
    get: () => (PESTANAS.some((p) => p.clave === route.query.tab) ? route.query.tab : 'resumen'),
    // Solo queda ?tab: el ?proyecto= de un aviso o una entrega solo tiene sentido en "Semanas y proyectos"
    set: (clave) => router.replace({ query: { tab: clave } })
})

const grupo = ref(null)
const cargando = ref(true)
const error = ref('')

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        grupo.value = await getGrupo(grupoId)
    } catch (err) {
        // 404: un id que no existe (o un grupo ya borrado). 403: el grupo es de otro instructor
        error.value = err.response?.status === 404 ? 'Este grupo no existe o ya fue eliminado.' : mensajeDeErrorGrupo(err)
    } finally {
        cargando.value = false
    }
}

onMounted(cargar)
</script>

<style scoped>
.grupo-detalle {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

.migaja {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    color: var(--color-text-secondary);
    font-size: .88rem;
}

.migaja a {
    color: var(--color-primary-light);
    text-decoration: none;
}

.migaja a:hover {
    text-decoration: underline;
}

.migaja span {
    overflow-wrap: anywhere;
}

.contenido {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-md);
}

.esqueleto-columnas {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--espacio-md);
}

@media (max-width: 900px) {
    .esqueleto-columnas {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
