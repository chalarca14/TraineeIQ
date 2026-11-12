import api from './api'

// Datos reales (CLAUDE.md: USAR_DATOS_PRUEBA = false). En true, la lista y la subida usan los datos
// de abajo, sin backend.
const USAR_DATOS_PRUEBA = false

// Datos de prueba con la misma forma que la respuesta real del backend
let guiasPrueba = [
    {
        id: 1,
        nombre: 'JavaScript desde cero',
        origen: 'personal',
        temas: [
            { id: 11, nombre_tema: 'Variables y tipos de datos', descripcion: 'Cómo guardar y tipar la información.', orden: 1 },
            { id: 12, nombre_tema: 'Funciones', descripcion: 'Declarar, llamar y reutilizar código.', orden: 2 },
            { id: 13, nombre_tema: 'DOM y eventos', descripcion: 'Leer y modificar la página desde JS.', orden: 3 },
            { id: 14, nombre_tema: 'Fetch API', descripcion: 'Consumir datos de un servidor.', orden: 4 }
        ]
    },
    {
        id: 2,
        nombre: 'Introducción a Vue 3',
        origen: 'personal',
        temas: [
            { id: 21, nombre_tema: 'Reactividad', descripcion: 'ref, reactive y computed.', orden: 1 },
            { id: 22, nombre_tema: 'Componentes y props', descripcion: 'Dividir la interfaz en piezas.', orden: 2 },
            { id: 23, nombre_tema: 'Vue Router', descripcion: 'Navegar entre vistas.', orden: 3 }
        ]
    }
]

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

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
    if (USAR_DATOS_PRUEBA) {
        await esperar(400)
        return [...guiasPrueba]
    }

    // El backend devuelve también las de grupo; aquí solo mostramos las personales
    return (await pedirTodasLasGuias()).filter((guia) => guia.origen === 'personal')
}

/** Sube un PDF; el backend lo lee y lo divide en temas. */
export async function subirGuia({ nombre, archivo }) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(1200) // simula el tiempo de leer el PDF y dividirlo con la IA
        const nueva = {
            id: Date.now(),
            nombre,
            origen: 'personal',
            temas: [
                { id: Date.now() + 1, nombre_tema: 'Introducción', descripcion: 'Contexto general de la guía.', orden: 1 },
                { id: Date.now() + 2, nombre_tema: 'Conceptos clave', descripcion: 'Las ideas principales.', orden: 2 },
                { id: Date.now() + 3, nombre_tema: 'Práctica guiada', descripcion: 'Ejercicios para aplicar lo visto.', orden: 3 }
            ]
        }
        guiasPrueba = [nueva, ...guiasPrueba]
        return nueva
    }

    const formData = new FormData()
    formData.append('nombre', nombre)
    formData.append('archivo', archivo)
    // Sin 'origen' (lo decide el backend) y sin Content-Type (el navegador lo genera solo).
    // El timeout es de 120 s porque el backend lee el PDF y llama a la IA
    const { data } = await api.post('/guias', formData, { timeout: 120000 })
    return data.guia
}

/** Devuelve una guía con todos sus temas (para la pantalla de detalle). */
export async function obtenerGuia(id) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(300)
        const guia = guiasPrueba.find((g) => g.id === Number(id))
        if (!guia) throw new Error('Guía no encontrada')
        return guia
    }

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