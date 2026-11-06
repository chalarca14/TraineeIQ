// DATOS DE EJEMPLO, CENTRALIZADOS EN UN SOLO ARCHIVO.
// El backend aún no guarda progreso, XP ni semanas. Tenerlos aquí permite que, cuando
// existan los endpoints, se cambie la fuente en un solo lugar sin buscar en cada vista.

// EJEMPLO: reemplazar cuando exista el endpoint
const TEMAS_HECHOS_EJEMPLO = 1

/**
 * Cuántos temas se consideran terminados.
 * Nunca llega al total: así siempre hay un tema "actual", incluso en guías de 1 tema.
 */
export function temasHechos(totalTemas) {
    return Math.min(TEMAS_HECHOS_EJEMPLO, Math.max(totalTemas - 1, 0))
}

// ---------------------------------------------------------------------------
// MINI PROYECTOS — estado de ejemplo (Tarea 2)
// ---------------------------------------------------------------------------
const ORDEN_NIVEL = { basico: 0, intermedio: 1, avanzado: 2 }

// Los proyectos se encadenan por guía (y por tema, si se generaron para un tema puntual):
// básico → intermedio → avanzado. Cada uno se desbloquea al aprobar el anterior.
const claveDeCadena = (p) => `${p.guia_id}-${p.tema_id ?? 'guia'}`

// Orden estable dentro de una cadena: por nivel y, a igual nivel, por id
const porNivelEId = (a, b) =>
    (ORDEN_NIVEL[a.dificultad] ?? 0) - (ORDEN_NIVEL[b.dificultad] ?? 0) || a.id - b.id

// Nivel que hay que APROBAR para abrir cada nivel. El básico no tiene requisito: nunca se bloquea.
const NIVEL_PREVIO = { intermedio: 'basico', avanzado: 'intermedio' }

/**
 * Estado de UN mini proyecto: { estado, progreso, finalizado, bloqueado, bloqueadoPor }.
 *
 * EJEMPLO: reemplazar cuando existan las entregas evaluadas (Tarea 3). Es la ÚNICA función
 * que decide el estado, así cambiar la fuente real es tocar solo este lugar.
 * Es DETERMINISTA (depende solo del orden y los ids), nunca aleatorio, para que la pantalla
 * se vea igual en cada recarga. Recibe la lista completa porque el bloqueo depende de otros.
 *
 * Regla de bloqueo, POR TEMA y por NIVEL (no por posición): el básico siempre está disponible,
 * el intermedio se abre al aprobar el básico de su tema y el avanzado al aprobar el intermedio.
 * Por eso el mensaje del candado siempre nombra el nivel ANTERIOR.
 */
export function estadoDelProyecto(proyecto, todos) {
    // Agrupamos por cadena (guía + tema) y la ordenamos por nivel
    const cadenas = new Map()
    for (const p of todos) {
        const clave = claveDeCadena(p)
        if (!cadenas.has(clave)) cadenas.set(clave, [])
        cadenas.get(clave).push(p)
    }
    cadenas.forEach((lista) => lista.sort(porNivelEId))

    // Cadenas ordenadas por el id más bajo de cada una, para que el patrón sea estable
    const claves = [...cadenas.keys()].sort(
        (a, b) => Math.min(...cadenas.get(a).map((p) => p.id)) - Math.min(...cadenas.get(b).map((p) => p.id))
    )

    // Estado "base" de un proyecto según su posición en su cadena (solo para armar la demo).
    // Patrón 0: 1.º completado, 2.º en progreso · 1: 1.º en progreso · 2: todo por iniciar
    const estadoBase = (p) => {
        const cadena = cadenas.get(claveDeCadena(p))
        const posicion = cadena.findIndex((x) => x.id === p.id)
        const patron = claves.indexOf(claveDeCadena(p)) % 3
        if (patron === 0 && posicion === 0) return { estado: 'completado', progreso: 100 }
        if (patron === 0 && posicion === 1) return { estado: 'en_progreso', progreso: 40 }
        if (patron === 1 && posicion === 0) return { estado: 'en_progreso', progreso: 60 }
        return { estado: 'por_iniciar', progreso: 0 }
    }

    const cadena = cadenas.get(claveDeCadena(proyecto))
    let { estado, progreso } = estadoBase(proyecto)

    // Bloqueo por nivel: ¿hay un proyecto del nivel previo, en este tema, aprobado?
    const nivelPrevio = NIVEL_PREVIO[proyecto.dificultad]
    const previos = nivelPrevio ? cadena.filter((p) => p.dificultad === nivelPrevio) : []
    // Si el tema no tiene proyectos del nivel previo no hay nada que aprobar: no se bloquea
    const bloqueado = previos.length > 0 && !previos.some((p) => estadoBase(p).estado === 'completado')

    // Un proyecto bloqueado no puede estar empezado
    if (bloqueado) { estado = 'por_iniciar'; progreso = 0 }

    // Fecha fija derivada del id (nunca la fecha actual ni aleatoria)
    const finalizado = estado === 'completado'
        ? new Date(2026, 9, 1 + (proyecto.id % 25)).toISOString()
        : null

    return { estado, progreso, finalizado, bloqueado, bloqueadoPor: bloqueado ? nivelPrevio : null }
}

// EJEMPLO: reemplazar cuando el backend calcule el XP de cada mini proyecto.
// Es solo una estimación por nivel que se muestra en "Información del proyecto".
// Los XP reales los decide el backend al aprobar la entrega; el frontend nunca los otorga.
const XP_ESTIMADO_EJEMPLO = { basico: 100, intermedio: 150, avanzado: 250 }

export function xpEstimado(dificultad) {
    return XP_ESTIMADO_EJEMPLO[dificultad] ?? 100
}

// EJEMPLO: reemplazar cuando exista el endpoint
// Resumen que muestran las tarjetas de estadísticas del dashboard.
export const RESUMEN_EJEMPLO = {
    semanaActual: 5,
    semanasTotales: 12,
    xp: 850,
    xpEstaSemana: 120,
    progreso: 68 // Porcentaje de progreso en la guía
}
