// DATOS DE EJEMPLOS, CUANDO SE TENGA EL ENDPOINT SE REEMPLAZARÁN POR LOS DATOS REALES

const TEMAS_HECHOS_EJEMPLO = 1

/**
 * Cuántos temas se consideran terminados.
 * Nunca llega al total: así siempre hay un tema "actual", incluso en guías de 1 tema.
 */
export function temasHechos(totalTemas) {
    return Math.min(TEMAS_HECHOS_EJEMPLO, Math.max(totalTemas - 1, 0))
}