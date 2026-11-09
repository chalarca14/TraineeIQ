import api from './api'

/** Devuelve un grupo con su semana actual. */
export async function getGrupo(id) {
    const { data } = await api.get(`/grupos/${id}`)
    return data.data
}

/** Devuelve una página de entregas del grupo con su paginación (solo su instructor). */
export async function getEntregasGrupo(id, pagina = 1) {
    const { data } = await api.get(`/grupos/${id}/entregas`, { params: { page: pagina } })
    return data
}

/** Libera las semanas del grupo hasta la indicada (solo su instructor). */
export async function liberarSemana(id, semanaActual) {
    const { data } = await api.patch(`/grupos/${id}/semana-actual`, { semana_actual: semanaActual })
    return data.semana_actual
}

/** Lista los grupos del usuario: los suyos si es instructor, en los que está si es estudiante. */
export async function listarGrupos() {
    const { data } = await api.get('/grupos')
    return data.data
}

/** Crea un grupo (solo instructor). El backend genera el código de acceso. */
export async function crearGrupo({ nombre, descripcion = null }) {
    const { data } = await api.post('/grupos', { nombre, descripcion })
    return data.data
}

/** Une al estudiante a un grupo con su código de acceso. */
export async function unirseAGrupo(codigoAcceso) {
    const { data } = await api.post('/grupos/unirse', { codigo_acceso: codigoAcceso })
    return data.data
}

/** Borra un grupo con todas sus guías y entregas (solo su instructor). */
export async function eliminarGrupo(id) {
    await api.delete(`/grupos/${id}`)
}
