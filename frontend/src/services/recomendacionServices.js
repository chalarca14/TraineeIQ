import api from './api'

// OJO: POST /recomendaciones ya no existe: las recomendaciones llegan dentro de cada semana de la guía.
// Solo queda el historial, que usa historialService hasta que la Tarea 5 lo deje con solo chats.
// Este endpoint no pagina: devuelve la lista completa (cada una trae `tema.nombre_tema`).
export async function obtenerHistorialRecomendaciones() {
    const { data } = await api.get('/recomendaciones/historial')
    return data.historial
}
