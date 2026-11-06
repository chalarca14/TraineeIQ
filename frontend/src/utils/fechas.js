// Meses abreviados escritos a mano. Se evita toLocaleDateString porque el punto de la
// abreviatura ("oct." u "oct") cambia según el navegador y el idioma, y queremos siempre "3 oct 2026".
const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

/** Convierte una fecha ISO (o Date) en "3 oct 2026". Devuelve '' si la fecha no es válida. */
export function formatearFecha(valor) {
    const fecha = new Date(valor)
    if (Number.isNaN(fecha.getTime())) return ''
    return `${fecha.getDate()} ${MESES[fecha.getMonth()]} ${fecha.getFullYear()}`
}
