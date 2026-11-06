import { ref, watch, nextTick, onBeforeUnmount } from 'vue'
import { listarConversaciones } from '@/services/chatIaService'

// Lógica del panel lateral de conversaciones: la lista y el cajón que lo abre en pantallas
// angostas. Vive aparte de la vista para que ChatIA.vue se concentre en la conversación
// (y para no pasar de las 300 líneas por archivo).
export function usePanelConversaciones() {
  const conversaciones = ref([])
  const cargandoLista = ref(true)
  const errorLista = ref(false)
  const panelAbierto = ref(false)

  // Referencias a componentes hijos: la vista las enlaza con ref="panel" y ref="encabezado"
  const panel = ref(null)
  const encabezado = ref(null)

  async function cargarLista() {
    try {
      conversaciones.value = await listarConversaciones()
      errorLista.value = false
    } catch {
      errorLista.value = true
    } finally {
      cargandoLista.value = false
    }
  }

  function reintentarLista() {
    cargandoLista.value = true
    cargarLista()
  }

  // Cajón móvil: se cierra con el botón, con Escape y con clic fuera. Al cerrar por teclado o
  // por clic fuera, el foco vuelve al botón que lo abrió (quien usa teclado no queda "perdido").
  function abrirPanel() {
    panelAbierto.value = true
  }

  function cerrarPanel() {
    panelAbierto.value = false
    nextTick(() => encabezado.value?.enfocarBoton())
  }

  function alTeclear(e) {
    if (e.key === 'Escape') cerrarPanel()
  }

  // El oyente de Escape solo existe mientras el cajón está abierto
  watch(panelAbierto, (abierto) => {
    if (abierto) {
      window.addEventListener('keydown', alTeclear)
      nextTick(() => panel.value?.enfocar())
    } else {
      window.removeEventListener('keydown', alTeclear)
    }
  })
  onBeforeUnmount(() => window.removeEventListener('keydown', alTeclear))

  return {
    conversaciones, cargandoLista, errorLista, panelAbierto, panel, encabezado,
    cargarLista, reintentarLista, abrirPanel, cerrarPanel
  }
}
