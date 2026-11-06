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

                <DescripcionProyecto v-if="pestana === 'descripcion'" :proyecto="proyecto" @entregar="alEntregar" />

                <!-- TODO fase 3b: aquí van los 5 estados de la evaluación con IA -->
                <p v-else class="pendiente">La evaluación con IA se conecta en la siguiente fase.</p>
            </template>
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Lock, FileText, Star } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import BotonVolver from '@/components/ui/BotonVolver.vue'
import Pestanas from '@/components/ui/Pestanas.vue'
import HeaderProyecto from '@/components/miniproyectos/HeaderProyecto.vue'
import DescripcionProyecto from '@/components/miniproyectos/DescripcionProyecto.vue'
import { obtenerMiniProyecto } from '@/services/miniProyectoService'
import { estadoDelProyecto } from '@/utils/progresoEjemplo'
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

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        const resultado = await obtenerMiniProyecto(route.params.id)
        // Mezclamos el estado de ejemplo (bloqueado, bloqueadoPor...) igual que en la lista
        proyecto.value = resultado.proyecto
            ? { ...resultado.proyecto, ...estadoDelProyecto(resultado.proyecto, resultado.todos) }
            : null
    } catch (err) {
        error.value = mensajeDeError(err)
    } finally {
        cargando.value = false
    }
}

function alEntregar() {
    // TODO fase 3b: llamar a entregaService.enviarEntrega y pasar a la pestaña Evaluación.
    // Por ahora el formulario solo valida la URL; todavía no hay servicio de entregas.
}

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

.pendiente {
    color: var(--color-text-secondary);
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
