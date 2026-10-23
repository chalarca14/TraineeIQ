import api from './api'

const USAR_DATOS_PRUEBA = true
const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

export async function generarRecomendaciones(temaId) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(1500)
        // Una llega con "contenido" como TEXTO con un JSON adentro y otra como objeto,
        // porque el backend puede mandar cualquiera de las dos formas
        return [
            { id: 1, tema_id: temaId, nivel: 'importante', contenido: JSON.stringify({ titulo: 'Practica con ejemplos pequeños', descripcion: 'Escribe tres ejemplos por cada concepto antes de avanzar.' }) },
            { id: 2, tema_id: temaId, nivel: 'recomendado', contenido: { titulo: 'Lee la documentación oficial', descripcion: 'Complementa la guía con la fuente original.' } },
            { id: 3, tema_id: temaId, nivel: 'avanzado', contenido: { titulo: 'Explícalo con tus palabras', descripcion: 'Grábate o escríbelo como si enseñaras el tema.' } }
        ]
    }
    const { data } = await api.post('/recomendaciones', { tema_id: temaId }, { timeout: 120000 })
    return data.recomendaciones
}

// "contenido" puede ser texto con un JSON o ya un objeto: aquí lo dejamos siempre como objeto
export function leerContenido(recomendacion) {
    try {
        const c = typeof recomendacion.contenido === 'string' ? JSON.parse(recomendacion.contenido) : recomendacion.contenido
        return { titulo: c.titulo ?? 'Recomendación', descripcion: c.descripcion ?? '' }
    } catch {
        // Si el texto no era un JSON válido, mostramos tal cual en vez de romper la pantalla
        return { titulo: 'Recomendación', descripcion: String(recomendacion.contenido) }
    }
}