import api from './api'

/** Devuelve un grupo con su semana actual. */
export async function getGrupo(id) {
    const { data } = await api.get(`/grupos/${id}`)
    return data.data
}

/** Libera las semanas del grupo hasta la indicada (solo su instructor). */
export async function liberarSemana(id, semanaActual) {
    const { data } = await api.patch(`/grupos/${id}/semana-actual`, { semana_actual: semanaActual })
    return data.semana_actual
}
