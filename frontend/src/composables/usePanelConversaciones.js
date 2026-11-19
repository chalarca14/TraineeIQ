import { ref, watch, nextTick, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { listarConversaciones } from '@/services/chatIaService'

// Lógica del panel lateral de conversaciones: la lista y el cajón que lo abre en pantallas
// angostas. Vive aparte de la vista para que ChatIA.vue se concentre en la conversación
// (y para no pasar de las 300 líneas por archivo).
// Recibe el id y el tipo de la conversación abierta (refs de la vista) para saber, al borrar,
// si hay que dejar el chat vacío.
export function usePanelConversaciones({ conversacionId, tipo } = {}) {
  const router = useRouter()
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

  // Se borró una conversación (la ventana ya llamó al backend). Si era la abierta, se vuelve al chat
  // vacío; replace y no push, para que "atrás" no lleve a una conversación que ya no existe.
  // El watcher de la ruta en ChatIA.vue reinicia la pantalla. Si era otra, la vista no cambia.
  function alEliminarConversacion(id) {
    if (id === conversacionId?.value) router.replace({ name: 'ChatIA' })
    cargarLista()
  }

  // Se borró el historial de un tipo de chat: si la conversación abierta era de ese tipo, ya no
  // existe y se vuelve al chat vacío. Un chat nuevo que aún no se envió (sin id) no se toca.
  function alBorrarHistorial(tipoBorrado) {
    if (conversacionId?.value && (!tipoBorrado || tipoBorrado === tipo?.value)) router.replace({ name: 'ChatIA' })
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
    cargarLista, reintentarLista, abrirPanel, cerrarPanel, alEliminarConversacion, alBorrarHistorial
  }
}
