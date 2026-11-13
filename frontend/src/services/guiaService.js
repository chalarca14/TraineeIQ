import api from './api'

// Siempre datos reales del backend, con la instancia de api.js.

// Tope de seguridad: si el backend respondiera mal (last_page enorme o sin fin), no se piden páginas
// para siempre. 20 páginas de 15 son 300 guías, más de las que tendrá un aprendiz.
const MAX_PAGINAS = 20

/**
 * GET /guias viene paginado de 15 en 15 (Laravel: data, current_page, last_page). Antes solo se leía
 * la primera página: con más de 15 guías las demás no salían, y como las personales se filtraban
 * DENTRO de esa página, podían desaparecer si había muchas de grupo. Aquí se piden todas, una
 * página tras otra (en orden, para no lanzar muchas peticiones a la vez al servidor).
 */
async function pedirTodasLasGuias(filtros = {}) {
    const guias = []
    let pagina = 1
    let ultima = 1
    do {
        const { data } = await api.get('/guias', { params: { ...filtros, page: pagina } })
        guias.push(...data.data)
        ultima = data.last_page ?? 1
        pagina++
    } while (pagina <= ultima && pagina <= MAX_PAGINAS)
    return guias
}

/** Devuelve las guías personales del estudiante con sus temas. */
export async function listarMisGuias() {
    // El backend devuelve también las de grupo; aquí solo mostramos las personales
    return (await pedirTodasLasGuias()).filter((guia) => guia.origen === 'personal')
}

/**
 * Arma el FormData de una guía y la envía. grupoId y semanasTotales solo van si vienen: el estudiante
 * NUNCA manda grupo_id (el backend responde 422) y el instructor SIEMPRE (si no, 422).
 * Sin 'origen' (lo decide el backend) y sin Content-Type (el navegador lo genera con el límite del
 * FormData). Timeout de 120 s porque el backend lee el PDF y llama a la IA.
 */
async function enviarGuia({ nombre, archivo, grupoId = null, semanasTotales = null }) {
    const formData = new FormData()
    formData.append('nombre', nombre)
    formData.append('archivo', archivo)
    if (grupoId != null) formData.append('grupo_id', grupoId)
    if (semanasTotales != null) formData.append('semanas_totales', semanasTotales)
    const { data } = await api.post('/guias', formData, { timeout: 120000 })
    return data
}

/**
 * Instructor: asigna una guía a su grupo. Devuelve { guia, yaExistia }: con yaExistia (200) el mismo
 * PDF ya estaba en el grupo y el backend NO lo duplica; con 201 las semanas llegan "pendiente".
 */
export async function asignarGuia({ nombre, archivo, grupoId, semanasTotales = null }) {
    const data = await enviarGuia({ nombre, archivo, grupoId, semanasTotales })
    return { guia: data.guia, yaExistia: Boolean(data.ya_existia) }
}

/** Estudiante (Modo Personal): sube un PDF; el backend lo lee y lo divide en temas. */
export async function subirGuia({ nombre, archivo }) {
    const data = await enviarGuia({ nombre, archivo })
    return data.guia
}

/** Devuelve una guía con todos sus temas (para la pantalla de detalle). */
export async function obtenerGuia(id) {
    const { data } = await api.get(`/guias/${id}`)
    return data.guia
}

/** Devuelve las guías que puede ver el usuario, personales y de grupo. */
export async function listarGuias() {
    return pedirTodasLasGuias()
}

/** Guías de un grupo (GET /guias?grupo_id=). La usa el instructor en el detalle del grupo. */
export async function listarGuiasDeGrupo(grupoId) {
    return pedirTodasLasGuias({ grupo_id: grupoId })
}

/** Devuelve las semanas de una guía con su estado y si están disponibles. */
export async function getSemanas(guiaId) {
    const { data } = await api.get(`/guias/${guiaId}/semanas`)
    return data.semanas
}

/** Devuelve las recomendaciones y los mini proyectos de una semana. */
export async function getSemana(guiaId, numero) {
    const { data } = await api.get(`/guias/${guiaId}/semanas/${numero}`)
    return data.semana
}

/**
 * Pide generar una semana. Con forzar la regenera (solo el dueño de la guía).
 * El timeout supera los 180 s que el backend le da a esta petición.
 */
export async function generarSemana(guiaId, numero, forzar = false) {
    const { data } = await api.post(`/guias/${guiaId}/semanas/${numero}/generar`, { forzar }, { timeout: 200000 })
    return data.semana
}

/**
 * Devuelve el texto de TODAS las semanas de la guía, en orden, para leerla como un documento continuo.
 * Cada semana trae numero_semana, titulo, disponible y contenido_texto. Las que no están liberadas
 * llegan con su título pero con contenido_texto en null: el backend no revela su texto.
 * Se pide una sola vez y solo cuando el aprendiz abre "Ver guía completa", porque el texto es pesado.
 */
export async function getTextoGuia(guiaId) {
    const { data } = await api.get(`/guias/${guiaId}/texto`)
    return data.semanas
}