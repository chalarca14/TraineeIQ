// Cómo se ve un mini proyecto según el estado de SU entrega.
// El backend manda entrega = null (aún no guarda el repositorio) o un objeto con
// estado: pendiente | evaluando | evaluado | error. Aquí solo se TRADUCE ese estado a la
// pestaña, la etiqueta y el botón; no se decide nada (ni aprobación ni XP: eso es del backend).

/**
 * Pestaña a la que pertenece el proyecto.
 * "pendiente" (repositorio guardado, sin evaluar) y "error" van en "Por entregar": el aprendiz
 * todavía tiene algo que hacer, y el error se distingue con su etiqueta roja (ver hayError).
 */
export function pestanaDe(proyecto) {
    const estado = proyecto.entrega?.estado
    if (estado === 'evaluando') return 'evaluando'
    if (estado === 'evaluado') return 'evaluado'
    return 'por_entregar'
}

export const hayError = (proyecto) => proyecto.entrega?.estado === 'error'

/** Texto del botón de la tarjeta. */
export function textoBoton(proyecto) {
    switch (proyecto.entrega?.estado) {
        case undefined: return 'Comenzar'
        case 'evaluando': return 'Ver estado'
        case 'evaluado': return 'Ver evaluación'
        default: return 'Continuar' // pendiente o error
    }
}

/** Etiqueta corta del estado, o null si no hay nada que decir (aún sin entrega). */
export function etiquetaDeEstado(proyecto) {
    switch (proyecto.entrega?.estado) {
        case 'pendiente': return 'Repositorio guardado'
        case 'evaluando': return 'Evaluando'
        default: return null
    }
}
