// Meses abreviados escritos a mano. Se evita toLocaleDateString porque el punto de la
// abreviatura ("oct." u "oct") cambia según el navegador y el idioma, y queremos siempre "3 oct 2026".
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

/** "Hoy", "Ayer" o, para fechas más viejas, "3 oct 2026". Devuelve '' si la fecha no es válida. */
export function fechaRelativa(valor) {
    const fecha = new Date(valor)
    if (Number.isNaN(fecha.getTime())) return ''
    // Se compara solo el DÍA (sin la hora) para que 23:50 de ayer cuente como "Ayer"
    const inicioDelDia = (d) => new Date(d.getFullYear(), d.getMonth(), d.getDate())
    const dias = Math.round((inicioDelDia(new Date()) - inicioDelDia(fecha)) / 86400000)
    if (dias === 0) return 'Hoy'
    if (dias === 1) return 'Ayer'
    return formatearFecha(valor)
}

/** Hora en formato "10:24". Devuelve '' si la fecha no es válida. */
export function horaCorta(valor) {
    const fecha = new Date(valor)
    if (Number.isNaN(fecha.getTime())) return ''
    return fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })
}

/** Convierte una fecha ISO (o Date) en "3 oct 2026". Devuelve '' si la fecha no es válida. */
export function formatearFecha(valor) {
    const fecha = new Date(valor)
    if (Number.isNaN(fecha.getTime())) return ''
    return `${fecha.getDate()} ${MESES[fecha.getMonth()]} ${fecha.getFullYear()}`
}
