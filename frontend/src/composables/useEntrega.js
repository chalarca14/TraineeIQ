import { ref, computed, onBeforeUnmount } from 'vue'
import { getEntrega, guardarRepo, pedirEvaluacion } from '@/services/miniProyectoService'
import { mensajeDeError, mensajeDeErrorEntrega } from '@/utils/errores'

/**
 * Estado y acciones de la entrega de UN mini proyecto, con el flujo del backend:
 *   1. guardar(url)  → PUT del repositorio (409 si ya se está evaluando)
 *   2. evaluar()     → POST de evaluar. La petición ESPERA el resultado (hasta ~200 s): no hay
 *                      colas ni segundo plano en el backend, así que NO hay polling. Mientras
 *                      espera, `evaluando` es true y la pantalla muestra el estado de carga.
 *   3. revisar()     → una sola consulta manual ("Revisar de nuevo") por si el aprendiz salió
 *                      y volvió mientras la entrega figuraba como "evaluando".
 * Aquí no se decide nada (ni aprobación ni XP): se guarda lo que responde el backend.
 *
 * @param {() => number|string} idProyecto  función que devuelve el id (puede cambiar con la ruta)
 */
export function useEntrega(idProyecto) {
    const entrega = ref(null)               // null = todavía no guardó su repositorio
    const intentosRestantes = ref(null)     // evaluaciones que le quedan hoy (lo calcula el backend)
    // Empieza en true: así la pantalla dice "cargando" y no parpadea "aún no has entregado"
    const cargando = ref(true)
    const error = ref('')                   // error al CARGAR la entrega

    const guardando = ref(false)
    const errorGuardar = ref('')
    const evaluando = ref(false)            // hay una petición de evaluar abierta desde ESTA pantalla
    const errorEvaluar = ref('')
    const revisando = ref(false)

    // El backend dice "evaluando" pero esta pantalla no está esperando: la evaluó otra pestaña o
    // se interrumpió. Se ofrece "Revisar de nuevo" en lugar de dejar un giro eterno.
    const enCursoSinEspera = computed(() => entrega.value?.estado === 'evaluando' && !evaluando.value)

    // Sube al salir de la pantalla: una respuesta que llega tarde compara su número y se descarta
    let generacion = 0

    /** Carga la entrega. En modo silencioso no muestra "cargando" ni reemplaza errores previos. */
    async function cargar({ silencioso = false } = {}) {
        const gen = silencioso ? generacion : ++generacion
        if (!silencioso) {
            cargando.value = true
            error.value = ''
        }
        try {
            const resultado = await getEntrega(idProyecto())
            if (gen !== generacion) return
            entrega.value = resultado.entrega
            intentosRestantes.value = resultado.intentos_restantes
        } catch (e) {
            if (gen === generacion && !silencioso) error.value = mensajeDeError(e)
        } finally {
            if (gen === generacion && !silencioso) cargando.value = false
        }
    }

    /** Guarda el repositorio. Devuelve true si el backend lo aceptó. */
    async function guardar(repoUrl) {
        if (guardando.value || evaluando.value) return false // el botón ya está deshabilitado; esto es el seguro
        errorGuardar.value = ''
        guardando.value = true
        const gen = generacion
        try {
            const nueva = await guardarRepo(idProyecto(), repoUrl)
            if (gen !== generacion) return false
            entrega.value = nueva
            return true
        } catch (e) {
            if (gen === generacion) errorGuardar.value = mensajeDeErrorEntrega(e)
            return false
        } finally {
            if (gen === generacion) guardando.value = false
        }
    }

    /** Pide evaluar el repositorio guardado y espera el resultado. Devuelve true si salió bien. */
    async function evaluar() {
        if (evaluando.value || guardando.value) return false
        errorEvaluar.value = ''
        evaluando.value = true
        const gen = generacion
        let bien = false
        try {
            const resultado = await pedirEvaluacion(idProyecto())
            if (gen !== generacion) return false
            entrega.value = resultado.entrega
            intentosRestantes.value = resultado.intentos_restantes
            bien = true
        } catch (e) {
            if (gen === generacion) errorEvaluar.value = mensajeDeErrorEntrega(e)
        } finally {
            if (gen === generacion) evaluando.value = false
        }
        // Tras un fallo el backend pudo dejar la entrega en "error" o seguir "evaluando":
        // se consulta una vez para que la pantalla muestre el estado real (y los intentos que quedan)
        if (!bien && gen === generacion) await cargar({ silencioso: true })
        return bien
    }

    /** "Revisar de nuevo": UNA consulta, sin repetirse sola. */
    async function revisar() {
        if (revisando.value) return
        revisando.value = true
        try {
            await cargar({ silencioso: true })
        } finally {
            revisando.value = false
        }
    }

    onBeforeUnmount(() => { generacion++ })

    return {
        entrega, intentosRestantes, cargando, error,
        guardando, errorGuardar, evaluando, errorEvaluar, revisando, enCursoSinEspera,
        cargar, guardar, evaluar, revisar
    }
}
