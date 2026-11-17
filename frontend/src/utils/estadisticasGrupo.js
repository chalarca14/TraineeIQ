// Estadísticas de un grupo (Tarea I-7), calculadas en el frontend con las entregas: el backend no tiene
// un endpoint de estadísticas. Funciones puras (sin Vue ni llamadas): reciben datos y devuelven números,
// así se pueden probar con datos inventados.
//
// Reglas (PLAN del instructor, I-7):
// - Promedios y distribución usan SOLO las entregas con estado "evaluado" (el puntaje es null si no).
// - El puntaje es el de la última evaluación y siempre es ORIENTATIVO: no hay "aprobado" ni rangos de color.
// - "X de Y estudiantes": Y es el total del grupo (GET /grupos/{id}); solo llegan entregas de quien guardó
//   un repositorio, así que quien no aparece es que aún no entregó.

const evaluadas = (entregas) => entregas.filter((e) => e.estado === 'evaluado' && typeof e.puntaje === 'number')

const porcentaje = (parte, total) => (total > 0 ? Math.round((parte / total) * 100) : 0)

/** Estudiantes distintos que guardaron al menos una entrega. */
export function estudiantesQueEntregaron(entregas) {
    return new Set(entregas.map((e) => e.estudiante?.id).filter((id) => id != null)).size
}

/** Promedio del puntaje de las entregas evaluadas, redondeado a un decimal; null si no hay ninguna. */
export function promedioPuntaje(entregas) {
    const lista = evaluadas(entregas)
    if (!lista.length) return null
    return Math.round((lista.reduce((suma, e) => suma + e.puntaje, 0) / lista.length) * 10) / 10
}

/**
 * Por cada semana liberada (1..semanaActual): cuántos estudiantes entregaron algún proyecto de esa semana.
 * Devuelve [{ semana, estudiantes, porcentaje }].
 */
export function entregasPorSemana(entregas, semanaActual, totalEstudiantes) {
    return Array.from({ length: Math.max(0, semanaActual) }, (_, i) => {
        const semana = i + 1
        const quienes = estudiantesQueEntregaron(entregas.filter((e) => e.mini_proyecto?.semana?.numero_semana === semana))
        return { semana, estudiantes: quienes, porcentaje: porcentaje(quienes, totalEstudiantes) }
    })
}

// Rangos de la distribución, de mayor a menor como en el boceto. Son solo agrupaciones para contar,
// no niveles de aprobación: se dibujan todos del mismo color.
export const RANGOS_PUNTAJE = [
    { etiqueta: '90-100', desde: 90, hasta: 100 },
    { etiqueta: '80-89', desde: 80, hasta: 89 },
    { etiqueta: '70-79', desde: 70, hasta: 79 },
    { etiqueta: '60-69', desde: 60, hasta: 69 },
    { etiqueta: '<60', desde: 0, hasta: 59 }
]

/** Cuántas entregas evaluadas caen en cada rango de puntaje. [{ etiqueta, cantidad }] */
export function distribucionPuntajes(entregas) {
    const lista = evaluadas(entregas)
    return RANGOS_PUNTAJE.map(({ etiqueta, desde, hasta }) => ({
        etiqueta,
        // Math.floor: un 89.5 cuenta en 80-89, no "entre" dos rangos
        cantidad: lista.filter((e) => Math.floor(e.puntaje) >= desde && Math.floor(e.puntaje) <= hasta).length
    }))
}

/**
 * En qué punto están las entregas esperadas: cada estudiante debería entregar cada proyecto liberado.
 * "Por entregar" = estudiantes × proyectos liberados − entregas guardadas (nunca negativo).
 */
export function estadoDeEntregas(entregas, totalEstudiantes, proyectosLiberados) {
    const contar = (...estados) => entregas.filter((e) => estados.includes(e.estado)).length
    const esperadas = totalEstudiantes * proyectosLiberados
    return {
        evaluadas: contar('evaluado'),
        enCurso: contar('pendiente', 'evaluando'),
        conError: contar('error'),
        porEntregar: Math.max(0, esperadas - entregas.length),
        esperadas
    }
}

/** Todo junto, listo para la pantalla. */
export function calcularEstadisticas({ entregas, totalEstudiantes, semanaActual, proyectosLiberados }) {
    const entregaron = estudiantesQueEntregaron(entregas)
    return {
        totalEstudiantes,
        entregaron,
        tasaEntrega: porcentaje(entregaron, totalEstudiantes),
        promedio: promedioPuntaje(entregas),
        evaluadas: evaluadas(entregas).length,
        proyectosLiberados,
        entregasTotales: entregas.length,
        porSemana: entregasPorSemana(entregas, semanaActual, totalEstudiantes),
        distribucion: distribucionPuntajes(entregas),
        estado: estadoDeEntregas(entregas, totalEstudiantes, proyectosLiberados)
    }
}
