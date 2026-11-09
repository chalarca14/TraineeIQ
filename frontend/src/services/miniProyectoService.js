import api from './api'
import { listarTodasMisGuias } from './guiaService'

const USAR_DATOS_PRUEBA = false
const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

export async function generarMiniProyectos({ guiaId, temaId }) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(1500)
        return [
            { id: 1, guia_id: guiaId, tema_id: temaId, titulo: 'Calculadora básica', descripcion: 'Suma, resta, multiplica y divide.', dificultad: 'basico', contenido: '1. Crea los botones.\n2. Guarda los números en variables.\n3. Muestra el resultado en pantalla.' },
            { id: 2, guia_id: guiaId, tema_id: temaId, titulo: 'Lista de tareas', descripcion: 'Agrega y elimina tareas.', dificultad: 'intermedio', contenido: '1. Crea el formulario.\n2. Guarda las tareas en un arreglo.\n3. Pinta la lista y permite borrar.' },
            { id: 3, guia_id: guiaId, tema_id: temaId, titulo: 'Consumo de una API pública', descripcion: 'Muestra datos reales en una tarjeta.', dificultad: 'avanzado', contenido: '1. Elige una API.\n2. Haz la petición con fetch.\n3. Maneja la carga y los errores.' }
        ]
    }
    // tema_id es opcional: sin él se generan sobre toda la guía
    const { data } = await api.post('/mini-proyectos/generar', { guia_id: guiaId, tema_id: temaId }, { timeout: 120000 })
    return data.mini_proyectos
}

/** Mini proyectos ya generados de UNA guía (el backend verifica que tengas acceso a ella). */
export async function listarMiniProyectosDeGuia(guiaId) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(300)
        return []
    }
    const { data } = await api.get(`/mini-proyectos/guia/${guiaId}`)
    return data.mini_proyectos
}

/**
 * Junta los mini proyectos de todas las guías del aprendiz.
 *
 * Por qué por guía y no GET /mini-proyectos: ese endpoint devuelve los de TODOS los usuarios
 * sin filtrar. En cambio /mini-proyectos/guia/{id} valida el acceso a la guía.
 *
 * Por qué Promise.allSettled: con Promise.all, si UNA petición falla se rechaza todo y se pierde
 * el resultado de las demás. allSettled espera a todas y devuelve el estado de cada una
 * ('fulfilled' o 'rejected'), así mostramos lo que sí cargó y avisamos de lo que falló.
 *
 * Devuelve { proyectos, guiasConError }. Si falla la lista de guías, lanza el error
 * (no hay nada que mostrar).
 */
export async function listarMisMiniProyectos() {
    const guias = await listarTodasMisGuias()

    const resultados = await Promise.allSettled(
        guias.map((guia) => listarMiniProyectosDeGuia(guia.id))
    )

    const proyectos = []
    let guiasConError = 0

    resultados.forEach((resultado, i) => {
        if (resultado.status === 'rejected') {
            guiasConError++
            return
        }
        // Añadimos el nombre de la guía para mostrarlo como etiqueta en la tarjeta
        resultado.value.forEach((p) => proyectos.push({ ...p, guiaNombre: guias[i].nombre }))
    })

    // Si TODAS fallaron (y había guías), es un error real y no una lista vacía
    if (guias.length > 0 && guiasConError === guias.length) {
        throw resultados[0].reason
    }

    return { proyectos, guiasConError }
}

/**
 * Un mini proyecto del aprendiz, junto con la lista completa de los suyos.
 *
 * Se busca dentro de LOS PROYECTOS DEL APRENDIZ en vez de usar GET /mini-proyectos/{id}, porque
 * ese endpoint no valida acceso (cualquiera podría leer el proyecto de otro por su id).
 * Además la lista completa hace falta para saber si el proyecto está bloqueado, ya que eso
 * depende de si se aprobó el nivel anterior del mismo tema.
 * Devuelve { proyecto: null } si el id no es de este aprendiz.
 */
export async function obtenerMiniProyecto(id) {
    const { proyectos } = await listarMisMiniProyectos()
    const proyecto = proyectos.find((p) => p.id === Number(id)) ?? null
    return { proyecto, todos: proyectos }
}

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