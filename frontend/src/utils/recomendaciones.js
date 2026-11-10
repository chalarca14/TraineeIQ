// "contenido" de una recomendación puede llegar como texto con un JSON o ya como objeto
// ({ titulo, descripcion, tema, tema_semana, por_que }). Aquí se deja siempre como objeto, para que
// las pantallas no se preocupen.
// tema, tema_semana y por_que son nuevos: las semanas generadas antes del cambio no los traen. Por eso
// se devuelven como cadena vacía y la pantalla los muestra solo si tienen texto (v-if).
export function leerContenido(recomendacion) {
    const texto = (valor) => (typeof valor === 'string' ? valor.trim() : '')

    try {
        const c = typeof recomendacion.contenido === 'string' ? JSON.parse(recomendacion.contenido) : recomendacion.contenido
        return {
            titulo: c.titulo ?? 'Recomendación',
            descripcion: c.descripcion ?? '',
            tema: texto(c.tema),
            tema_semana: texto(c.tema_semana),
            por_que: texto(c.por_que)
        }
    } catch {
        // Si el texto no era un JSON válido, mostramos tal cual en vez de romper la pantalla
        return { titulo: 'Recomendación', descripcion: String(recomendacion.contenido), tema: '', tema_semana: '', por_que: '' }
    }
}
