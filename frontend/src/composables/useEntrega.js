import { ref, computed, onBeforeUnmount } from 'vue'
import { obtenerEntrega, enviarEntrega } from '@/services/entregaService'
import { mensajeDeError } from '@/utils/errores'

// La evaluación con IA tarda, así que el frontend NO deja una petición abierta esperando:
// cada pocos segundos pregunta "¿ya terminó?" hasta que el estado cambie, con un máximo de
// intentos para no consultar para siempre si algo se atascó en el servidor.
const INTERVALO_MS = 3000
const MAX_INTENTOS = 20 // 20 × 3 s = 1 minuto

const ESTADOS_EN_CURSO = ['pendiente', 'evaluando']

/**
 * Estado y acciones de la entrega de UN mini proyecto.
 * @param {() => number|string} idProyecto  función que devuelve el id (puede cambiar con la ruta)
 * @param {() => object} contexto           datos que solo usa el servicio de prueba
 */
export function useEntrega(idProyecto, contexto = () => ({})) {
  const entrega = ref(null)          // null = todavía no entregó nada
  // Empieza en true: así la pantalla muestra "cargando" y no parpadea "aún no has entregado"
  // en el instante antes de que arranque la primera consulta
  const cargando = ref(true)
  const error = ref('')              // error al CARGAR la entrega
  const enviando = ref(false)
  const errorEnvio = ref('')         // error al ENVIAR (URL rechazada, 429, red...)
  const sondeoAgotado = ref(false)   // se acabaron los intentos y sigue evaluando

  const enCurso = computed(() => ESTADOS_EN_CURSO.includes(entrega.value?.estado))

  let temporizador = null
  let intentos = 0
  // Sube cada vez que se carga otra cosa: una respuesta que llega tarde de un envío o sondeo
  // anterior compara su número y, si ya no coincide, se descarta.
  let generacion = 0

  function detener() {
    clearTimeout(temporizador)
    temporizador = null
  }

  function programarSondeo() {
    detener()
    // setTimeout encadenado (y no setInterval): la siguiente consulta se agenda solo cuando
    // termina la anterior, así nunca hay dos peticiones solapadas si el servidor se demora.
    if (enCurso.value) temporizador = setTimeout(sondear, INTERVALO_MS)
  }

  async function sondear() {
    const gen = generacion
    intentos++
    try {
      const nueva = await obtenerEntrega(idProyecto(), contexto())
      if (gen !== generacion) return
      entrega.value = nueva
    } catch {
      // Un fallo aislado del sondeo (red) no debe tumbar la pantalla: cuenta como un intento más
      if (gen !== generacion) return
    }
    if (!enCurso.value) return // terminó: el estado ya cambió y no se agenda nada más
    if (intentos >= MAX_INTENTOS) {
      sondeoAgotado.value = true
      return
    }
    programarSondeo()
  }

  function iniciarSondeo() {
    intentos = 0
    sondeoAgotado.value = false
    programarSondeo()
  }

  /** Carga la última entrega. Si todavía está evaluándose, empieza a sondear. */
  async function cargar() {
    const gen = ++generacion
    detener()
    cargando.value = true
    error.value = ''
    try {
      const resultado = await obtenerEntrega(idProyecto(), contexto())
      if (gen !== generacion) return
      entrega.value = resultado
      iniciarSondeo()
    } catch (e) {
      if (gen === generacion) error.value = mensajeDeError(e)
    } finally {
      if (gen === generacion) cargando.value = false
    }
  }

  /** Envía el enlace del repositorio. Devuelve true si el backend lo aceptó. */
  async function enviar(repoUrl) {
    if (enviando.value || enCurso.value) return false // el botón ya está deshabilitado; esto es el seguro
    errorEnvio.value = ''
    enviando.value = true
    const gen = generacion
    try {
      const nueva = await enviarEntrega(idProyecto(), repoUrl)
      if (gen !== generacion) return false
      entrega.value = nueva
      iniciarSondeo()
      return true
    } catch (e) {
      // 429 y demás códigos se traducen a un mensaje en utils/errores.js
      if (gen === generacion) errorEnvio.value = mensajeDeError(e)
      return false
    } finally {
      if (gen === generacion) enviando.value = false
    }
  }

  /** Reanuda las consultas cuando se agotaron los intentos ("Revisar de nuevo"). */
  function reanudarSondeo() {
    iniciarSondeo()
    sondear()
  }

  // Al salir de la pantalla se cancela el temporizador y se descarta cualquier respuesta pendiente
  onBeforeUnmount(() => {
    generacion++
    detener()
  })

  return { entrega, cargando, error, enviando, errorEnvio, sondeoAgotado, enCurso, cargar, enviar, reanudarSondeo }
}
