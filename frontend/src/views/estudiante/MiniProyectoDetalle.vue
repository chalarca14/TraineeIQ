<template>
    <main class="detalle">

        <EstadoVista :cargando="cargando" :error="error" :vacio="!proyecto" texto-cargando="Cargando el mini proyecto..."
            texto-vacio="No encontramos este mini proyecto." @reintentar="cargar">
            <!-- Cabecera del proyecto, las pestañas y el bloque de la descripción -->
            <template #cargando>
                <EsqueletoBloque ancho="200px" alto="2.4rem" radio="999px" />
                <EsqueletoTarjeta :lineas="3" alto="180px" />
                <EsqueletoBloque ancho="260px" alto="2.6rem" radio="12px" />
                <EsqueletoTarjeta :lineas="5" :con-icono="false" alto="240px" />
            </template>
            <template #vacio>
                <RouterLink class="btn btn-secundario" to="/estudiante/mini-proyectos">Volver a mis mini proyectos</RouterLink>
            </template>

            <BotonVolver to="/estudiante/mini-proyectos">Volver a Mini Proyectos</BotonVolver>

            <HeaderProyecto :proyecto="proyecto" />

            <Pestanas v-model="pestana" :pestanas="PESTANAS" />

            <DescripcionProyecto v-if="pestana === 'descripcion'" :proyecto="proyecto" :entrega="ctl.entrega.value"
                :intentos-restantes="ctl.intentosRestantes.value" :guardando="ctl.guardando.value"
                :evaluando="ctl.evaluando.value" :error-guardar="ctl.errorGuardar.value"
                :error-evaluar="ctl.errorEvaluar.value" @guardar="ctl.guardar" @evaluar="alEvaluar" />

            <EvaluacionProyecto v-else :entrega="ctl.entrega.value" :cargando="ctl.cargando.value"
                :error="ctl.error.value" :evaluando="ctl.evaluando.value" :revisando="ctl.revisando.value"
                :error-evaluar="ctl.errorEvaluar.value" :intentos-restantes="ctl.intentosRestantes.value"
                :es-personal="esPersonal" @recargar="ctl.cargar" @ir-descripcion="pestana = 'descripcion'"
                @revisar="ctl.revisar" @evaluar="alEvaluar" />
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { FileText, Star } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import EsqueletoBloque from '@/components/ui/EsqueletoBloque.vue'
import BotonVolver from '@/components/ui/BotonVolver.vue'
import Pestanas from '@/components/ui/Pestanas.vue'
import HeaderProyecto from '@/components/miniproyectos/HeaderProyecto.vue'
import DescripcionProyecto from '@/components/miniproyectos/DescripcionProyecto.vue'
import EvaluacionProyecto from '@/components/miniproyectos/EvaluacionProyecto.vue'
import { getMiniProyecto } from '@/services/miniProyectoService'
import { useEntrega } from '@/composables/useEntrega'
import { useModoStore } from '@/stores/modo'
import { mensajeDeError } from '@/utils/errores'

const PESTANAS = [
    { clave: 'descripcion', etiqueta: 'Descripción', icono: FileText },
    { clave: 'evaluacion', etiqueta: 'Evaluación', icono: Star }
]

const route = useRoute()
const modoStore = useModoStore()
const proyecto = ref(null)
const cargando = ref(true)
const error = ref('')
const pestana = ref('descripcion')

// El XP solo existe en Modo Personal
const esPersonal = computed(() => modoStore.esModoPersonal)

// Entrega del proyecto: guardar el repositorio, evaluar y revisar (ver useEntrega.js)
const ctl = useEntrega(() => route.params.id)

async function cargar() {
    cargando.value = true
    error.value = ''
    proyecto.value = null
    try {
        proyecto.value = await getMiniProyecto(route.params.id)
    } catch (err) {
        const status = err.response?.status
        if (status === 404) {
            // Sin proyecto: EstadoVista muestra "No encontramos este mini proyecto"
        } else if (status === 403) {
            // El backend responde 403 mientras la semana del proyecto no esté liberada para este usuario
            error.value = 'Este mini proyecto aún no está disponible: su semana todavía no se ha liberado para ti.'
        } else {
            error.value = mensajeDeError(err)
        }
    } finally {
        cargando.value = false
    }
    if (!proyecto.value) return

    // Con el proyecto visible se carga su entrega; si ya hay evaluación o está en curso, se abre en Evaluación
    await ctl.cargar()
    if (['evaluado', 'evaluando'].includes(ctl.entrega.value?.estado)) pestana.value = 'evaluacion'
}

// Al pedir la evaluación se pasa a la pestaña Evaluación para ver el estado de carga (la petición
// espera el resultado) y, al terminar, el resultado o el error con su message
async function alEvaluar() {
    pestana.value = 'evaluacion'
    await ctl.evaluar()
}

// Si se navega a otro proyecto este componente se reutiliza: hay que volver a cargarlo todo
watch(() => route.params.id, () => {
    pestana.value = 'descripcion'
    cargar()
})

onMounted(cargar)
</script>

<style scoped>
.detalle {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    max-width: 960px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 2.5rem);
}
</style>
