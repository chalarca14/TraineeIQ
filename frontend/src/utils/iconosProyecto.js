import {
    Calculator, MessageSquare, ListChecks, Image, Cloud, Database, ChartColumn,
    Coffee, Braces, Globe, Receipt, Box
} from 'lucide-vue-next'

// Los colores son variables de variables.css (nunca colores sueltos). Como solo hay unas
// pocas, algunas tecnologías comparten color; el icono es lo que las distingue.
//
// ORDEN IMPORTA: gana la primera coincidencia. Los tipos de proyecto (calculadora, chat...)
// van antes que los lenguajes, para que "Calculadora en Python" muestre la calculadora.
// Las expresiones se prueban sobre texto en minúsculas y SIN tildes.
const REGLAS = [
    { patron: /calculadora/, icono: Calculator, color: 'var(--color-info)' },
    { patron: /\b(chat|bot|ia)\b/, icono: MessageSquare, color: 'var(--color-primary)' },
    { patron: /to-?do|tareas|lista de/, icono: ListChecks, color: 'var(--color-success)' },
    { patron: /galeria|imagen|imagenes/, icono: Image, color: 'var(--color-success)' },
    { patron: /nube|cloud|despliegue/, icono: Cloud, color: 'var(--color-info)' },
    { patron: /factur|recibo|boleta|ticket/, icono: Receipt, color: 'var(--color-success)' },
    { patron: /sql|base de datos/, icono: Database, color: 'var(--color-info)' },
    // \b evita que "java" coincida dentro de "javascript"
    { patron: /\bjava\b/, icono: Coffee, color: 'var(--color-danger)' },
    { patron: /python/, icono: Braces, color: 'var(--color-info)' },
    { patron: /javascript|\bjs\b/, icono: Braces, color: 'var(--color-warning)' },
    { patron: /html|css|\bweb\b|sitio/, icono: Globe, color: 'var(--color-danger)' }
]

// Respaldo cuando nada coincide
// El de barras es el genérico: ya no hay una regla para "datos", que coincidía con casi cualquier
// descripción ("tipos de datos") y le ponía barras a proyectos que no tenían nada que ver.
const GENERICO = { icono: ChartColumn, color: 'var(--color-primary)' }

// Pasa a minúsculas y quita tildes: "Galería" → "galeria"
const normalizar = (texto) =>
    (texto ?? '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')

/**
 * Elige { icono, color } para un mini proyecto según su texto.
 * Busca por prioridad: primero el título (lo más específico), luego el tema y la guía, y por
 * último la descripción. Así una guía llamada "Java" no le pone el icono de Java a un
 * proyecto cuyo título ya dice "Chat con IA".
 */
export function iconoDelProyecto(proyecto) {
    const fuentes = [
        proyecto.titulo,
        `${proyecto.tema?.nombre_tema ?? ''} ${proyecto.guiaNombre ?? ''}`,
        proyecto.descripcion
    ]

    for (const fuente of fuentes) {
        const texto = normalizar(fuente)
        const regla = REGLAS.find((r) => r.patron.test(texto))
        if (regla) return { icono: regla.icono, color: regla.color }
    }
    return GENERICO
}

// Tecnologías que se reconocen por palabras clave en el texto del proyecto.
// El backend todavía NO manda etiquetas de tecnología (PENDIENTE: que el generador devuelva
// `etiquetas`). Mientras tanto se detectan del título, el tema, la guía y la descripción;
// cuando el backend las envíe, usar esas y dejar esta detección solo como respaldo.
const TECNOLOGIAS = [
    { etiqueta: 'Python', abrev: 'Py', patron: /python/, color: 'var(--color-info)' },
    { etiqueta: 'JavaScript', abrev: 'JS', patron: /javascript|\bjs\b|\bnode(\.?js)?\b/, color: 'var(--color-warning)' },
    { etiqueta: 'Java', abrev: 'Jv', patron: /\bjava\b/, color: 'var(--color-danger)' },
    { etiqueta: 'C#', abrev: 'C#', patron: /c#|\.net\b|dotnet|csharp/, color: 'var(--color-primary-light)' },
    { etiqueta: 'HTML', abrev: 'H5', patron: /html/, color: 'var(--color-danger)' },
    { etiqueta: 'CSS', abrev: 'C3', patron: /\bcss\b/, color: 'var(--color-info)' },
    { etiqueta: 'SQL', abrev: 'SQL', patron: /sql/, color: 'var(--color-info)' },
    { etiqueta: 'Vue', abrev: 'V', patron: /\bvue\b/, color: 'var(--color-success)' },
    { etiqueta: 'PHP', abrev: 'php', patron: /\bphp\b/, color: 'var(--color-primary)' }
]

/** Hasta 3 tecnologías reconocidas en el proyecto: [{ etiqueta, abrev, color }]. */
export function tecnologiasDelProyecto(proyecto) {
    const texto = normalizar([
        proyecto.titulo, proyecto.tema?.nombre_tema, proyecto.guiaNombre, proyecto.descripcion
    ].join(' '))
    return TECNOLOGIAS.filter((t) => t.patron.test(texto)).slice(0, 3)
}

// Icono de caja por nivel: verde, azul y naranja (boceto 501)
const NIVELES = {
    basico: 'var(--color-success)',
    intermedio: 'var(--color-info)',
    avanzado: 'var(--color-warning)'
}

export function iconoDeNivel(dificultad) {
    return { icono: Box, color: NIVELES[dificultad] ?? 'var(--color-text-secondary)' }
}
