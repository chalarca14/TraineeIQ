// "contenido" de una recomendación puede llegar como texto con un JSON o ya como objeto
// ({ titulo, descripcion }). Aquí se deja siempre como objeto, para que las pantallas no se preocupen.
export function leerContenido(recomendacion) {
    try {
        const c = typeof recomendacion.contenido === 'string' ? JSON.parse(recomendacion.contenido) : recomendacion.contenido
        return { titulo: c.titulo ?? 'Recomendación', descripcion: c.descripcion ?? '' }
    } catch {
        // Si el texto no era un JSON válido, mostramos tal cual en vez de romper la pantalla
        return { titulo: 'Recomendación', descripcion: String(recomendacion.contenido) }
    }
}
