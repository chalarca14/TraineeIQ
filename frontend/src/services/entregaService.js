// Servicio de entregas de mini proyectos: el aprendiz sube el enlace de su repositorio de GitHub
// y el backend (con la IA) lo evalúa. NO hay editor ni ejecución de código en la web.
//
// ⚠️ LOS ENDPOINTS REALES AÚN NO EXISTEN. Contrato propuesto (hay que acordarlo con el equipo):
//   POST /mini-proyectos/{id}/entregas   { repo_url }  → inicia la evaluación (estado "evaluando")
//   GET  /mini-proyectos/{id}/entrega                  → última entrega, o 404 si no hay ninguna
// Una entrega trae: estado, intento, repo_url, fecha_entrega, puntaje, calificacion, resumen,
// requisitos [{ texto, cumplido }], analisis [{ criterio, valoracion }], recomendaciones [texto],
// xp_ganado, codigo_error y mensaje_error.
// Aprobación, XP (una sola vez por usuario y proyecto) y desbloqueo de niveles los decide SOLO el
// backend; el frontend únicamente muestra el resultado.
import api from './api'
import { xpEstimado } from '@/utils/progresoEjemplo'

// Mientras el backend no tenga los endpoints, true. Cuando existan, false (y se borra el bloque de ejemplo).
const USAR_DATOS_PRUEBA = true

// ---------------------------------------------------------------------------
// Llamadas reales
// ---------------------------------------------------------------------------

async function obtenerEntregaReal(proyectoId) {
    try {
        const { data } = await api.get(`/mini-proyectos/${proyectoId}/entrega`)
        // PENDIENTE de acordar: si la entrega viene directa o envuelta en { entrega }
        return data.entrega ?? data
    } catch (err) {
        // 404 = todavía no entregó nada: no es un error, es el estado "sin entrega"
        if (err.response?.status === 404) return null
        throw err
    }
}

async function enviarEntregaReal(proyectoId, repoUrl) {
    const { data } = await api.post(`/mini-proyectos/${proyectoId}/entregas`, { repo_url: repoUrl })
    return data.entrega ?? data
}

// ---------------------------------------------------------------------------
// DATOS DE EJEMPLO (solo mientras USAR_DATOS_PRUEBA = true). Deterministas: nada aleatorio.
//
// Cómo recorrer los 5 estados con el servicio de prueba:
//   · un enlace que contenga "privado", "vacio", "grande" o "falla" termina en error
//     (repo_privado, repo_vacio, repo_muy_grande, ia_no_pudo)
//   · el 1.er envío normal (sin esas palabras) termina en "necesita mejoras"
//   · el 2.º envío normal termina en "aprobado" (los envíos con error no cuentan para esto)
//   · "evaluando" dura 6 s, así que se ve el sondeo (2 consultas de 3 s)
//   · un proyecto que la lista marca como completado ya trae una entrega aprobada
//   · enviar mientras hay una evaluación en curso responde 429, como haría el backend
// El estado vive en sessionStorage para que sobreviva a recargar la página durante la prueba.
// ---------------------------------------------------------------------------

const CLAVE = 'traineeiq.entregas.ejemplo'
const DURACION_EVALUACION_MS = 6000
const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

function leerAlmacen() {
    try {
        return JSON.parse(sessionStorage.getItem(CLAVE)) ?? {}
    } catch {
        return {} // sessionStorage bloqueado o JSON dañado: se trabaja en blanco
    }
}

function guardarAlmacen(almacen) {
    try {
        sessionStorage.setItem(CLAVE, JSON.stringify(almacen))
    } catch {
        // sin almacenamiento la prueba sigue funcionando, solo que no sobrevive a recargar
    }
}

const REQUISITOS_EJEMPLO = [
    'Cumple con la funcionalidad principal del proyecto',
    'Solicita y procesa los datos de entrada',
    'Valida los casos inválidos',
    'Muestra mensajes claros al usuario',
    'El código está organizado en funciones o clases',
    'Incluye un README con las instrucciones de uso'
]

const ERRORES = {
    privado: { codigo_error: 'repo_privado', mensaje_error: 'El repositorio es privado.' },
    vacio: { codigo_error: 'repo_vacio', mensaje_error: 'El repositorio no tiene archivos.' },
    grande: { codigo_error: 'repo_muy_grande', mensaje_error: 'El repositorio supera el tamaño permitido.' },
    falla: { codigo_error: 'ia_no_pudo', mensaje_error: 'La IA no pudo analizar el repositorio.' }
}

function errorSegunUrl(url) {
    return Object.entries(ERRORES).find(([palabra]) => url.toLowerCase().includes(palabra))?.[1] ?? null
}

// Resultado final de una entrega, según las reglas de arriba
function resultadoEjemplo(registro, contexto) {
    const base = {
        estado: '', intento: registro.intento, repo_url: registro.repo_url, fecha_entrega: registro.fecha_entrega
    }
    const error = errorSegunUrl(registro.repo_url)
    if (error) return { ...base, estado: 'error', ...error }

    // El resultado depende de cuántos envíos NORMALES van (los que terminan en error no cuentan):
    // así el 1.º envío válido siempre da "necesita mejoras" y el 2.º "aprobado". El número de
    // intento que se muestra sí cuenta todos los envíos. (`?? intento`: registros guardados antes.)
    if ((registro.normales ?? registro.intento) < 2) {
        return {
            ...base,
            estado: 'necesita_mejoras',
            puntaje: 58,
            calificacion: 'Regular',
            resumen: 'Tu proyecto avanza bien, pero todavía no cumple todos los requisitos obligatorios.',
            requisitos: REQUISITOS_EJEMPLO.map((texto, i) => ({ texto, cumplido: i < 4 })),
            analisis: [
                { criterio: 'Organización del código', valoracion: 'Regular' },
                { criterio: 'Buenas prácticas', valoracion: 'Bueno' },
                { criterio: 'Manejo de errores', valoracion: 'Mejorable' },
                { criterio: 'Claridad y legibilidad', valoracion: 'Bueno' },
                { criterio: 'Cumplimiento de requisitos', valoracion: 'Regular' }
            ],
            recomendaciones: [
                'Separa la lógica en funciones pequeñas con un solo propósito.',
                'Valida los datos de entrada antes de procesarlos.',
                'Agrega un README que explique cómo ejecutar el proyecto.'
            ],
            xp_ganado: 0
        }
    }
    return aprobadoEjemplo(base, contexto)
}

function aprobadoEjemplo(base, contexto, puntaje = 92) {
    return {
        ...base,
        estado: 'aprobado',
        puntaje,
        calificacion: 'Excelente',
        resumen: 'Tu proyecto cumple con todos los requisitos y tu código sigue buenas prácticas.',
        requisitos: REQUISITOS_EJEMPLO.map((texto) => ({ texto, cumplido: true })),
        analisis: [
            { criterio: 'Organización del código', valoracion: 'Excelente' },
            { criterio: 'Buenas prácticas', valoracion: 'Excelente' },
            { criterio: 'Manejo de errores', valoracion: 'Bueno' },
            { criterio: 'Claridad y legibilidad', valoracion: 'Excelente' },
            { criterio: 'Cumplimiento de requisitos', valoracion: 'Excelente' }
        ],
        recomendaciones: [
            'Tu código está muy bien estructurado.',
            'Puedes agregar más comentarios para mejorar la legibilidad.',
            'Considera manejar otros tipos de errores de entrada.'
        ],
        // Los XP los decide el backend (una sola vez por usuario y proyecto); aquí es solo ejemplo
        xp_ganado: xpEstimado(contexto?.dificultad)
    }
}

async function obtenerEntregaEjemplo(proyectoId, contexto) {
    await esperar(400)
    const registro = leerAlmacen()[proyectoId]

    if (!registro) {
        if (!contexto?.completadoEjemplo) return null // sin entrega
        // La lista (ejemplo) marca este proyecto como completado: el backend real tendría su entrega aprobada.
        // Fecha y puntaje salen del id, nunca de la fecha actual ni del azar.
        const base = {
            intento: 1, repo_url: `https://github.com/aprendiz/proyecto-${proyectoId}`,
            fecha_entrega: new Date(2026, 9, 1 + (Number(proyectoId) % 25), 10, 24).toISOString()
        }
        return aprobadoEjemplo(base, contexto, 90 + (Number(proyectoId) % 9))
    }

    // Todavía evaluando: se responde "evaluando" hasta que pase el tiempo
    if (Date.now() < registro.evaluado_en) {
        return { estado: 'evaluando', intento: registro.intento, repo_url: registro.repo_url, fecha_entrega: registro.fecha_entrega }
    }
    return resultadoEjemplo(registro, contexto)
}

async function enviarEntregaEjemplo(proyectoId, repoUrl) {
    await esperar(500)
    const almacen = leerAlmacen()
    const previo = almacen[proyectoId]

    // El backend no acepta otra entrega mientras se evalúa la anterior: 429 (demasiadas peticiones)
    if (previo && Date.now() < previo.evaluado_en) {
        const error = new Error('Ya hay una evaluación en curso')
        error.response = { status: 429 }
        throw error
    }

    const ahora = Date.now()
    const registro = {
        intento: (previo?.intento ?? 0) + 1,
        normales: (previo?.normales ?? 0) + (errorSegunUrl(repoUrl) ? 0 : 1),
        repo_url: repoUrl,
        fecha_entrega: new Date(ahora).toISOString(),
        evaluado_en: ahora + DURACION_EVALUACION_MS
    }
    almacen[proyectoId] = registro
    guardarAlmacen(almacen)
    return { estado: 'evaluando', intento: registro.intento, repo_url: repoUrl, fecha_entrega: registro.fecha_entrega }
}

// ---------------------------------------------------------------------------
// API pública. `contexto` ({ dificultad, completadoEjemplo }) solo lo usa el servicio de prueba;
// el backend real lo ignora porque ya conoce el proyecto y su historial.
// ---------------------------------------------------------------------------

/** Última entrega del proyecto, o null si todavía no ha entregado nada. */
export function obtenerEntrega(proyectoId, contexto) {
    return USAR_DATOS_PRUEBA ? obtenerEntregaEjemplo(proyectoId, contexto) : obtenerEntregaReal(proyectoId)
}

/** Registra la entrega e inicia la evaluación. Devuelve la entrega en estado "evaluando". */
export function enviarEntrega(proyectoId, repoUrl) {
    return USAR_DATOS_PRUEBA ? enviarEntregaEjemplo(proyectoId, repoUrl) : enviarEntregaReal(proyectoId, repoUrl)
}
