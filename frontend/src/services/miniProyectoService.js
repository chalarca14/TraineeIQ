import api from './api'

// Misma regla del backend: solo https://github.com/{dueño}/{repo}
const REPO_GITHUB = /^https:\/\/github\.com\/[A-Za-z0-9][A-Za-z0-9-]{0,38}\/(?!\.{1,2}$)[A-Za-z0-9._-]{1,100}(?<!\.git)$/

/** Indica si un link tiene la forma de un repositorio de GitHub. */
export function esRepoGithub(link) {
    return REPO_GITHUB.test(link)
}

/** Devuelve un mini proyecto con sus requisitos, buenas prácticas y criterios. */
export async function getMiniProyecto(id) {
    const { data } = await api.get(`/mini-proyectos/${id}`)
    return data.mini_proyecto
}

/** Devuelve { entrega, intentos_restantes }. entrega es null si aún no guardó su repositorio. */
export async function getEntrega(id) {
    const { data } = await api.get(`/mini-proyectos/${id}/entrega`)
    return data
}

/**
 * Pide evaluar el código y devuelve { entrega, intentos_restantes }.
 * El timeout supera los 180 s que el backend le da a esta petición.
 */
export async function pedirEvaluacion(id) {
    const { data } = await api.post(`/mini-proyectos/${id}/entrega/evaluar`, null, { timeout: 200000 })
    return data
}

/** Guarda o cambia el repositorio del estudiante. */
export async function guardarRepo(id, repoUrl) {
    const { data } = await api.put(`/mini-proyectos/${id}/entrega`, { repo_url: repoUrl })
    return data.entrega
}

/**
 * Mini proyectos de UNA guía, con la entrega propia de cada uno (o null) en una sola consulta.
 * GET /guias/{id}/mini-proyectos. Cada item trae: id, titulo, descripcion, dificultad, orden,
 * semana { numero_semana, titulo } y entrega { estado, puntaje, mejor_puntaje, intentos, ... }.
 * OJO: el backend solo manda los de semanas YA liberadas para este usuario; las no liberadas
 * se conocen con getSemanas() (disponible: false).
 */
export async function getMiniProyectosGuia(id) {
    const { data } = await api.get(`/guias/${id}/mini-proyectos`)
    return data.mini_proyectos
}
