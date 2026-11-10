import { ref, computed } from 'vue'
import { cargarPaginaConversaciones } from '@/services/historialService'
import { mensajeDeError } from '@/utils/errores'

// Estado y carga del Historial (solo conversaciones con la IA). Vive en un composable para que
// Historial.vue se ocupe solo de filtros y pantalla (y para respetar el máximo de 300 líneas por archivo).
export function useHistorial() {
  const lista = ref([])
  const cargando = ref(true)
  const error = ref('')
  const hayMas = ref(false)         // el backend trae otra página (next_page_url)
  const cargandoMas = ref(false)
  const errorMas = ref('')
  let paginaActual = 1

  // La más reciente primero (el backend ya las manda así, pero al juntar páginas se reordena por seguridad)
  const entradas = computed(() =>
    [...lista.value].sort((a, b) => Date.parse(b.fecha) - Date.parse(a.fecha))
  )

  async function cargar() {
    cargando.value = true
    error.value = ''
    paginaActual = 1
    try {
      const r = await cargarPaginaConversaciones(1)
      lista.value = r.entradas
      hayMas.value = r.hayMas
    } catch (err) {
      error.value = mensajeDeError(err)
    } finally {
      cargando.value = false
    }
  }

  async function cargarMas() {
    if (cargandoMas.value) return
    cargandoMas.value = true
    errorMas.value = ''
    try {
      const r = await cargarPaginaConversaciones(paginaActual + 1)
      paginaActual += 1
      lista.value = [...lista.value, ...r.entradas]
      hayMas.value = r.hayMas
    } catch (err) {
      // El error de "cargar más" no tapa la lista que ya está en pantalla
      errorMas.value = mensajeDeError(err)
    } finally {
      cargandoMas.value = false
    }
  }

  return { entradas, cargando, error, hayMas, cargandoMas, errorMas, cargar, cargarMas }
}
