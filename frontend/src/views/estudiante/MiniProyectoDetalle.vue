<template>
    <main class="detalle">

        <EstadoVista :cargando="cargando" :error="error" :vacio="!proyecto" texto-cargando="Cargando el mini proyecto..."
            texto-vacio="No encontramos este mini proyecto." @reintentar="cargar">
            <template #vacio>
                <RouterLink class="btn btn-secundario" to="/estudiante/mini-proyectos">Volver a mis mini proyectos</RouterLink>
            </template>

            <BotonVolver to="/estudiante/mini-proyectos">Volver a Mini Proyectos</BotonVolver>

            <HeaderProyecto :proyecto="proyecto" />

            <!-- Proyecto bloqueado: no se muestra la entrega, así no se puede enviar nada -->
            <section v-if="proyecto.bloqueado" class="bloqueado" role="status">
                <span class="candado"><Lock :size="28" /></span>
                <h2>Este proyecto está bloqueado</h2>
                <p>Completa el nivel {{ NOMBRES_NIVEL[proyecto.bloqueadoPor] ?? 'anterior' }} de este tema para desbloquearlo.</p>
                <RouterLink class="btn btn-secundario" to="/estudiante/mini-proyectos">Volver a mis mini proyectos</RouterLink>
            </section>

            <template v-else>
                <Pestanas v-model="pestana" :pestanas="PESTANAS" />

                <DescripcionProyecto v-if="pestana === 'descripcion'" :proyecto="proyecto" :puede-entregar="puedeEntregar"
                    :en-evaluacion="entregaCtl.enCurso.value || entregaCtl.enviando.value"
                    :error-entrega="entregaCtl.errorEnvio.value" @entregar="alEntregar" />

                <EvaluacionProyecto v-else :entrega="entregaCtl.entrega.value" :cargando="entregaCtl.cargando.value"
                    :error="entregaCtl.error.value" :sondeo-agotado="entregaCtl.sondeoAgotado.value"
                    :enviando="entregaCtl.enviando.value" :siguiente-id="siguienteId" @recargar="entregaCtl.cargar"
                    @ir-descripcion="pestana = 'descripcion'" @revisar="entregaCtl.reanudarSondeo"
                    @reintentar-envio="reintentarEnvio" />
            </template>
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Lock, FileText, Star } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import BotonVolver from '@/components/ui/BotonVolver.vue'
import Pestanas from '@/components/ui/Pestanas.vue'
import HeaderProyecto from '@/components/miniproyectos/HeaderProyecto.vue'
import DescripcionProyecto from '@/components/miniproyectos/DescripcionProyecto.vue'
import EvaluacionProyecto from '@/components/miniproyectos/EvaluacionProyecto.vue'
import { obtenerMiniProyecto } from '@/services/miniProyectoService'
import { useEntrega } from '@/composables/useEntrega'
import { estadoDelProyecto, siguienteProyecto } from '@/utils/progresoEjemplo'
import { mensajeDeError } from '@/utils/errores'

const PESTANAS = [
    { clave: 'descripcion', etiqueta: 'Descripción', icono: FileText },
    { clave: 'evaluacion', etiqueta: 'Evaluación', icono: Star }
]
const NOMBRES_NIVEL = { basico: 'básico', intermedio: 'intermedio', avanzado: 'avanzado' }

const route = useRoute()
const proyecto = ref(null)
const cargando = ref(true)
const error = ref('')
const pestana = ref('descripcion')
const todos = ref([])

// Entrega del proyecto: carga, envío y sondeo del estado mientras la IA evalúa (ver useEntrega.js).
// El contexto solo lo usa el servicio de prueba para devolver una entrega aprobada a los
// proyectos que la lista (ejemplo) marca como completados.
const entregaCtl = useEntrega(
    () => route.params.id,
    () => ({ dificultad: proyecto.value?.dificultad, completadoEjemplo: proyecto.value?.estado === 'completado' })
)

// Un proyecto aprobado (por la lista de ejemplo o por la entrega) ya no se entrega otra vez
const aprobado = computed(() =>
    proyecto.value?.estado === 'completado' || entregaCtl.entrega.value?.estado === 'aprobado')
const puedeEntregar = computed(() => !aprobado.value)

// Botón "Continuar": el siguiente nivel del mismo tema. Cuando el backend mande
// `siguiente_proyecto_id` en la entrega, se usa ese en lugar de este cálculo.
const siguienteId = computed(() =>
    entregaCtl.entrega.value?.siguiente_proyecto_id
    ?? (proyecto.value ? siguienteProyecto(proyecto.value, todos.value)?.id ?? null : null))

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        const resultado = await obtenerMiniProyecto(route.params.id)
        todos.value = resultado.todos
        // Mezclamos el estado de ejemplo (bloqueado, bloqueadoPor...) igual que en la lista
        proyecto.value = resultado.proyecto
            ? { ...resultado.proyecto, ...estadoDelProyecto(resultado.proyecto, resultado.todos) }
            : null
        // Un proyecto bloqueado no tiene entrega que consultar
        if (proyecto.value && !proyecto.value.bloqueado) {
            // Si ya está completado, el detalle abre directamente en la evaluación aprobada
            pestana.value = proyecto.value.estado === 'completado' ? 'evaluacion' : 'descripcion'
            entregaCtl.cargar()
        }
    } catch (err) {
        error.value = mensajeDeError(err)
    } finally {
        cargando.value = false
    }
}

async function alEntregar(url) {
    const aceptada = await entregaCtl.enviar(url)
    // Si el backend la aceptó, se pasa a ver la evaluación en curso; si no, el error queda junto al campo
    if (aceptada) pestana.value = 'evaluacion'
}

// "Reintentar" tras un error de evaluación: vuelve a enviar el mismo repositorio
function reintentarEnvio() {
    const url = entregaCtl.entrega.value?.repo_url
    if (url) alEntregar(url)
}

// Si se navega a otro proyecto (por ejemplo con "Continuar") este componente se reutiliza:
// hay que volver a cargarlo todo desde cero
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


.bloqueado {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .6rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 16px;
    color: var(--color-text-secondary);
    text-align: center;
}

.candado {
    display: grid;
    place-items: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--color-warning) 16%, transparent);
    color: var(--color-warning);
}

.bloqueado h2 {
    color: var(--color-text);
    font-size: 1.1rem;
}
</style>
