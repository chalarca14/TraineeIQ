import api from './api'

/**
 * Devuelve los avisos del instructor y cuántos no ha leído (el número de la campanita).
 * Respuesta: { no_leidas, notificaciones: { data, current_page, last_page, ... } }
 */
export async function getNotificaciones(pagina = 1) {
    const { data } = await api.get('/notificaciones', { params: { page: pagina } })
    return data
}

/** Marca un aviso como leído y lo devuelve actualizado. */
export async function marcarLeida(id) {
    const { data } = await api.patch(`/notificaciones/${id}/leida`)
    return data.notificacion
}

/** Marca como leídos todos los avisos pendientes y devuelve cuántos eran. */
export async function marcarTodasLeidas() {
    const { data } = await api.patch('/notificaciones/leidas')
    return data.marcadas
}
