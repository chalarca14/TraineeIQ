import api from './api'

// Datos reales (CLAUDE.md: USAR_DATOS_PRUEBA = false). Solo se llama a la API desde aquí,
// con la instancia de api.js, para que el token y el manejo del 401 sean los mismos de siempre.
const USAR_DATOS_PRUEBA = false

const GRUPOS_PRUEBA = [
    { id: 1, nombre: 'Grupo Demo SENA', descripcion: 'Grupo de prueba para desarrollo', instructor: { id: 2, name: 'Carlos Méndez' } }
]

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

/** Grupos en los que está inscrito el aprendiz. El backend los manda en data.data. */
export async function listarMisGrupos() {
    if (USAR_DATOS_PRUEBA) {
        await esperar(300)
        return [...GRUPOS_PRUEBA]
    }

    const { data } = await api.get('/grupos')
    return data.data
}

/**
 * Inscribe al aprendiz en un grupo con el código de acceso. Devuelve el grupo.
 * Los errores se dejan subir tal cual: el modal decide el mensaje según el código HTTP
 * (422 = código inexistente, 400 = ya eres miembro).
 */
export async function unirseAGrupo(codigo) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(400)
        return GRUPOS_PRUEBA[0]
    }

    const { data } = await api.post('/grupos/unirse', { codigo_acceso: codigo })
    return data.data
}

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
