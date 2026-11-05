// DATOS DE EJEMPLO, CENTRALIZADOS EN UN SOLO ARCHIVO.
// El backend aún no guarda progreso, XP ni semanas. Tenerlos aquí permite que, cuando
// existan los endpoints, se cambie la fuente en un solo lugar sin buscar en cada vista.

// EJEMPLO: reemplazar cuando exista el endpoint
const TEMAS_HECHOS_EJEMPLO = 1

/**
 * Cuántos temas se consideran terminados.
 * Nunca llega al total: así siempre hay un tema "actual", incluso en guías de 1 tema.
 */
export function temasHechos(totalTemas) {
    return Math.min(TEMAS_HECHOS_EJEMPLO, Math.max(totalTemas - 1, 0))
}

// EJEMPLO: reemplazar cuando exista el endpoint
// Resumen que muestran las tarjetas de estadísticas del dashboard.
export const RESUMEN_EJEMPLO = {
    semanaActual: 5,
    semanasTotales: 12,
    xp: 850,
    xpEstaSemana: 120,
    progreso: 68 // Porcentaje de progreso en la guía
}
