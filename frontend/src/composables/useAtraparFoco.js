import { onMounted, onUnmounted } from 'vue'

// Elementos a los que se puede llegar con Tab
const ENFOCABLES = [
    'a[href]', 'button:not([disabled])', 'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])', 'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])'
].join(',')

/**
 * Atrapa el foco dentro de una ventana modal: Tab y Shift+Tab dan la vuelta dentro de ella.
 * Por qué: con aria-modal el lector de pantalla solo "ve" la ventana; si el foco se escapara al
 * formulario de atrás, quien usa teclado quedaría escribiendo en algo que no ve.
 * contenedor: ref al elemento de la ventana.
 */
export function useAtraparFoco(contenedor) {
    function alTeclear(evento) {
        const caja = contenedor.value
        if (evento.key !== 'Tab' || !caja) return

        const elementos = [...caja.querySelectorAll(ENFOCABLES)]
        if (!elementos.length) return
        const primero = elementos[0]
        const ultimo = elementos[elementos.length - 1]
        const actual = document.activeElement

        // Si el foco quedó fuera (o en el borde), se lleva al otro extremo de la ventana
        if (!caja.contains(actual)) {
            evento.preventDefault()
            primero.focus()
        } else if (evento.shiftKey && actual === primero) {
            evento.preventDefault()
            ultimo.focus()
        } else if (!evento.shiftKey && actual === ultimo) {
            evento.preventDefault()
            primero.focus()
        }
    }

    onMounted(() => document.addEventListener('keydown', alTeclear))
    onUnmounted(() => document.removeEventListener('keydown', alTeclear))
}
