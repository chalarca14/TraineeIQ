import api from './api'

// Solo se llama a la API desde aquí, con la instancia de api.js, para que el token y el manejo
// del 401 sean los mismos de siempre. Siempre datos reales del backend.

/** Grupos en los que está inscrito el aprendiz. El backend los manda en data.data. */
export async function listarMisGrupos() {
    const { data } = await api.get('/grupos')
    return data.data
}

/**
 * Inscribe al aprendiz en un grupo con el código de acceso. Devuelve el grupo.
 * Los errores se dejan subir tal cual: el modal decide el mensaje según el código HTTP
 * (422 = código inexistente, 400 = ya eres miembro).
 */
export async function unirseAGrupo(codigo) {
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

/** Borra un grupo con todas sus guías y entregas (solo su instructor). */
export async function eliminarGrupo(id) {
    await api.delete(`/grupos/${id}`)
}

// Tope de seguridad: 20 páginas de 30 son 600 entregas, más de las que tendrá un grupo del SENA
const MAX_PAGINAS_ENTREGAS = 20

/**
 * TODAS las entregas del grupo (solo su instructor): recorre las páginas en orden, una tras otra.
 * La pestaña "Semanas y proyectos" las necesita todas para contar quién entregó en cada semana y
 * en cada proyecto; así cada proyecto se abre al instante, sin otra petición.
 */
export async function getTodasEntregasGrupo(id) {
    const entregas = []
    let pagina = 1
    let ultima = 1
    do {
        const data = await getEntregasGrupo(id, pagina)
        entregas.push(...(data.data ?? []))
        ultima = data.last_page ?? 1
        pagina++
    } while (pagina <= ultima && pagina <= MAX_PAGINAS_ENTREGAS)
    return entregas
}
