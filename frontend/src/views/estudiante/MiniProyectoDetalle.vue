<template>
    <main class="detalle">

        <p v-if="cargando" class="estado">Cargando el mini proyecto...</p>

        <div v-else-if="error" class="estado error">
            <p>{{ error }}</p>
            <button type="button" class="boton" @click="cargar">Reintentar</button>
        </div>

        <div v-else-if="!proyecto" class="estado vacio">
            <p>No encontramos este mini proyecto.</p>
            <RouterLink class="boton" to="/estudiante/mini-proyectos">Volver a mis mini proyectos</RouterLink>
        </div>

        <template v-else>
            <RouterLink class="volver" to="/estudiante/mini-proyectos">
                <span class="flecha"><ArrowLeft :size="16" /></span> Volver a Mini Proyectos
            </RouterLink>

            <HeaderProyecto :proyecto="proyecto" />

            <!-- Proyecto bloqueado: no se muestra la entrega, así no se puede enviar nada -->
            <section v-if="proyecto.bloqueado" class="bloqueado" role="status">
                <span class="candado"><Lock :size="28" /></span>
                <h2>Este proyecto está bloqueado</h2>
                <p>Completa el nivel {{ NOMBRES_NIVEL[proyecto.bloqueadoPor] ?? 'anterior' }} de este tema para desbloquearlo.</p>
                <RouterLink class="boton" to="/estudiante/mini-proyectos">Volver a mis mini proyectos</RouterLink>
            </section>

            <template v-else>
                <div class="pestanas" role="tablist">
                    <button v-for="p in PESTANAS" :key="p.clave" type="button" role="tab"
                        :class="{ activa: pestana === p.clave }" :aria-selected="pestana === p.clave"
                        @click="pestana = p.clave">
                        <component :is="p.icono" :size="18" /> {{ p.etiqueta }}
                    </button>
                </div>

                <DescripcionProyecto v-if="pestana === 'descripcion'" :proyecto="proyecto" @entregar="alEntregar" />

                <!-- TODO fase 3b: aquí van los 5 estados de la evaluación con IA -->
                <p v-else class="estado">La evaluación con IA se conecta en la siguiente fase.</p>
            </template>
        </template>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, Lock, FileText, Star } from 'lucide-vue-next'
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

.volver {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: .6rem;
    padding: .3rem .9rem .3rem .3rem;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background: var(--color-surface);
    color: var(--color-text);
    font-size: .88rem;
    text-decoration: none;
    transition: border-color .2s;
}

.volver:hover {
    border-color: var(--color-primary);
}

.flecha {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--color-primary) 25%, transparent);
    color: var(--color-primary-light);
}

/* Pestañas grandes con icono; la activa lleva degradado y resplandor */
.pestanas {
    display: flex;
    gap: .6rem;
}

.pestanas button {
    display: flex;
    align-items: center;
    gap: .55rem;
    padding: .7rem 1.4rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
    color: var(--color-text-secondary);
    font-weight: 600;
    cursor: pointer;
    transition: color .2s, border-color .2s;
}

.pestanas button:hover {
    border-color: var(--color-primary);
    color: var(--color-text);
}

.pestanas button.activa {
    border-color: transparent;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    box-shadow: 0 6px 20px color-mix(in srgb, var(--color-primary) 40%, transparent);
    color: var(--color-text);
}

.estado {
    color: var(--color-text-secondary);
}

.estado.error,
.estado.vacio,
.bloqueado {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .6rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 16px;
    text-align: center;
}

.estado.error {
    color: var(--color-danger);
}

.bloqueado {
    color: var(--color-text-secondary);
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

.boton {
    padding: .6rem 1.2rem;
    border: 1px solid var(--color-primary);
    border-radius: 12px;
    background: transparent;
    color: var(--color-primary-light);
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
}

.boton:hover {
    background: color-mix(in srgb, var(--color-primary) 15%, transparent);
}

@media (max-width: 520px) {
    .pestanas button {
        flex: 1;
        justify-content: center;
        padding: .7rem .5rem;
    }
}
</style>
