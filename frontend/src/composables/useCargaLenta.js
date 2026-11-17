import { ref, watch, onUnmounted, toValue } from 'vue'

/**
 * Tiempos de una carga, para decidir qué mostrar mientras llegan los datos.
 * - visible: true si la carga pasa de `retraso` ms. Así las respuestas rápidas no hacen "flash"
 *   de esqueleto (aparecer y desaparecer en un instante se ve peor que no mostrar nada).
 * - lenta: true si pasa de `lenta` ms. En Render el servidor se duerme a los 15 min y la primera
 *   llamada tarda 30 a 50 s: hay que avisar para que no parezca que la app se colgó.
 * cargando: ref, computed o función que diga si la carga sigue activa.
 */
export function useCargaLenta(cargando, { retraso = 250, lenta = 8000 } = {}) {
    const visible = ref(false)
    const esLenta = ref(false)
    let tVisible = null
    let tLenta = null

    function limpiar() {
        clearTimeout(tVisible)
        clearTimeout(tLenta)
    }

    watch(() => toValue(cargando), (activo) => {
        limpiar()
        if (!activo) {
            visible.value = false
            esLenta.value = false
            return
        }
        tVisible = setTimeout(() => { visible.value = true }, retraso)
        tLenta = setTimeout(() => { esLenta.value = true }, lenta)
    }, { immediate: true })

    // Si la vista se cierra a mitad de la carga, los temporizadores no deben quedar vivos
    onUnmounted(limpiar)

    return { visible, lenta: esLenta }
}
