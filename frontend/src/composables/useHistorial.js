import { ref, computed } from 'vue'
import { cargarHistorialInicial, cargarMasConversaciones } from '@/services/historialService'
import { mensajeDeError } from '@/utils/errores'

// Estado y carga del Historial. Vive en un composable para que Historial.vue se ocupe solo de
// filtros y pantalla (y para respetar el máximo de 300 líneas por archivo).
export function useHistorial() {
  const conversaciones = ref([])
  const recomendaciones = ref([])
  const cargando = ref(true)
  const error = ref('')
  const aviso = ref('')          // una fuente falló pero la otra sí cargó
  const hayMas = ref(false)
  const cargandoMas = ref(false)
  const errorMas = ref('')
  let paginaActual = 1

  // Todas las entradas mezcladas, la más reciente primero
  const entradas = computed(() =>
    [...conversaciones.value, ...recomendaciones.value]
      .sort((a, b) => Date.parse(b.fecha) - Date.parse(a.fecha))
  )

  async function cargar() {
    cargando.value = true
    error.value = ''
    aviso.value = ''
    paginaActual = 1
    const r = await cargarHistorialInicial()
    conversaciones.value = r.conversaciones
    recomendaciones.value = r.recomendaciones
    hayMas.value = r.hayMas
    // Error a pantalla completa solo si NO se pudo cargar nada; si falló una sola fuente, aviso suave
    if (r.errores.length === 2) error.value = mensajeDeError(r.errores[0])
    else if (r.fallos.length) aviso.value = `No se pudieron cargar ${r.fallos.join(' ni ')}. Lo demás sí se muestra.`
    cargando.value = false
  }

  async function cargarMas() {
    cargandoMas.value = true
    errorMas.value = ''
    try {
      const r = await cargarMasConversaciones(paginaActual + 1)
      paginaActual += 1
      conversaciones.value = [...conversaciones.value, ...r.conversaciones]
      hayMas.value = r.hayMas
    } catch (err) {
      // El error de "cargar más" no tapa la lista que ya está en pantalla
      errorMas.value = mensajeDeError(err)
    } finally {
      cargandoMas.value = false
    }
  }

  return { entradas, cargando, error, aviso, hayMas, cargandoMas, errorMas, cargar, cargarMas }
}
