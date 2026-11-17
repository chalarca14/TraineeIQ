<template>
    <div ref="raiz" class="campana" @keydown.esc="abierto = false">
        <button class="boton" type="button" :aria-label="etiquetaBoton" :aria-expanded="abierto" @click="alternar">
            <Bell :size="20" aria-hidden="true" />
            <span v-if="noLeidas" class="contador" aria-hidden="true">{{ noLeidas > 9 ? '9+' : noLeidas }}</span>
        </button>

        <PanelNotificaciones v-if="abierto" :avisos="avisos" :no-leidas="noLeidas" :cargando="cargando"
            :marcando="marcando" :error="error" :puede-reintentar="!noDisponible"
            @abrir="abrir" @marcar-todas="marcarTodas" @reintentar="cargar" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { Bell } from 'lucide-vue-next'
import PanelNotificaciones from './PanelNotificaciones.vue'
import { getNotificaciones, marcarLeida, marcarTodasLeidas } from '@/services/notificacionService'
import { useAuthStore } from '@/stores/auth'
import { mensajeDeError } from '@/utils/errores'

// No hay tiempo real (el backend no usa colas ni websockets): se consulta cada minuto, como pide el PLAN.
const INTERVALO_MS = 60000

const router = useRouter()
const authStore = useAuthStore()
const raiz = ref(null)
const abierto = ref(false)
const avisos = ref([])
const noLeidas = ref(0)
const cargando = ref(false)
const marcando = ref(false)
const error = ref('')
// 403 = el backend aún no le abre las notificaciones a este rol: no tiene sentido seguir preguntando
const noDisponible = ref(false)
let temporizador = null

const etiquetaBoton = computed(() =>
    noLeidas.value ? `Notificaciones, ${noLeidas.value} sin leer` : 'Notificaciones'
)

async function cargar() {
    if (noDisponible.value) return
    cargando.value = true
    try {
        const respuesta = await getNotificaciones()
        avisos.value = respuesta.notificaciones?.data ?? []
        noLeidas.value = respuesta.no_leidas ?? 0
        error.value = ''
    } catch (e) {
        if (e.response?.status === 403) {
            noDisponible.value = true
            detener()
            error.value = 'Las notificaciones estarán disponibles pronto.'
        } else {
            error.value = mensajeDeError(e)
        }
    } finally {
        cargando.value = false
    }
}

function alternar() {
    abierto.value = !abierto.value
    // Al abrir se pide la lista otra vez para no mostrar algo de hace casi un minuto
    if (abierto.value) cargar()
}

async function abrir(aviso) {
    if (!aviso.leida_en) {
        try {
            const actualizado = await marcarLeida(aviso.id)
            aviso.leida_en = actualizado?.leida_en ?? new Date().toISOString()
            noLeidas.value = Math.max(0, noLeidas.value - 1)
        } catch {
            // Si falla, igual se lleva a su destino: el aviso seguirá sin leer y se verá en la próxima consulta
        }
    }
    abierto.value = false
    const destino = destinoDe(aviso)
    if (destino) router.push(destino)
}

async function marcarTodas() {
    marcando.value = true
    try {
        await marcarTodasLeidas()
        const ahora = new Date().toISOString()
        avisos.value.forEach((a) => { a.leida_en ??= ahora })
        noLeidas.value = 0
    } catch (e) {
        error.value = mensajeDeError(e)
    } finally {
        marcando.value = false
    }
}

// A dónde lleva cada aviso, según quién lo recibe:
// - Instructor ("evaluación de una entrega"): la pestaña Semanas y proyectos del grupo con ese proyecto
//   abierto (?proyecto=ID), para ver la entrega que la IA acaba de evaluar.
// - Estudiante ("guía nueva", "semana liberada"): la guía del grupo; la semana que se abre la decide
//   la vista de la guía (la última liberada), igual que al entrar desde Mis grupos.
function destinoDe(aviso) {
    const { grupo_id: grupoId, guia_id: guiaId, mini_proyecto_id: proyectoId } = aviso.datos ?? {}
    if (authStore.rolUsuario === 'instructor') {
        if (!grupoId) return null
        const query = proyectoId ? { tab: 'semanas', proyecto: proyectoId } : { tab: 'semanas' }
        return { name: 'InstructorGrupoDetalle', params: { id: grupoId }, query }
    }
    if (grupoId && guiaId) return `/estudiante/grupos/${grupoId}/guias/${guiaId}`
    if (grupoId) return `/estudiante/grupos/${grupoId}`
    return null
}

function detener() {
    clearInterval(temporizador)
    temporizador = null
}

// Cierra el panel al hacer clic fuera de la campana
function clicFuera(evento) {
    if (raiz.value && !raiz.value.contains(evento.target)) abierto.value = false
}

onMounted(() => {
    cargar()
    // document.hidden: si la pestaña está en segundo plano no se consulta, para no gastar peticiones
    temporizador = setInterval(() => { if (!document.hidden) cargar() }, INTERVALO_MS)
    document.addEventListener('click', clicFuera)
})

// Al cerrar sesión la NavBar desaparece (el login no la tiene) y con ella este componente:
// aquí se detiene la consulta de cada minuto, así no sigue preguntando sin token.
onBeforeUnmount(() => {
    detener()
    document.removeEventListener('click', clicFuera)
})
</script>

<style scoped>
.campana {
    position: relative;
}

.boton {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid var(--color-card);
    border-radius: 12px;
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: .2s;
}

.boton:hover,
.boton[aria-expanded='true'] {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

/* Número de no leídas sobre la esquina de la campana */
.contador {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 19px;
    height: 19px;
    padding: 0 .3rem;
    border-radius: 999px;
    background: var(--color-danger);
    color: var(--color-text);
    font-size: .7rem;
    font-weight: 700;
    line-height: 19px;
    text-align: center;
}
</style>
