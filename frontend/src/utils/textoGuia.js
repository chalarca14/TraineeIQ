// Organiza el texto plano de una guía (transcrito del PDF por el backend) en bloques para leerlo mejor:
// títulos, subtítulos, párrafos, listas, código y actividades.
//
// Por qué hace falta: el PDF llega como texto con SUS saltos de línea. Las frases quedan partidas
// ("Se usan operadores" / "de comparación…"), los títulos se ven como un párrafo más y el código pierde
// la sangría. Aquí solo se reconoce la forma de cada línea con reglas simples (no es IA). Lo que no
// encaja en ninguna regla se muestra como párrafo: nunca se pierde texto.
//
// Devuelve datos, no HTML: el componente los pinta con {{ }} (nunca v-html), así nada se interpreta.

const RE_SEMANA = /^semana\s+\d+\b/i
const RE_SUBTITULO = /^(tema|unidad|lecci[oó]n|cap[ií]tulo|m[oó]dulo|parte)\s+\d+(\.\d+)*\s*[:.\-–]/i
const RE_ACTIVIDAD = /^(actividad|ejercicio|taller|reto|evidencia|pr[aá]ctica)\b[^:]{0,40}:/i
const RE_LISTA = /^([-•*▪●◦]|\d{1,2}[.)])\s+/
// Palabras con las que suele empezar una línea de código (Java, JavaScript, Python, C#…)
// "Etiqueta: valor" al inicio de la línea ("Duración de la guía: 4 semanas", "Nota: …")
const RE_ETIQUETA = /^([A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚÑáéíóúñü ()/]{1,40}):\s+(.+)$/
const RE_INICIO_CODIGO = /^(if|else|for|foreach|while|switch|case|do|return|try|catch|finally|def|class|public|private|protected|static|import|from|const|let|var|function|print|println|System\.|console\.|Console\.|int|long|String|string|double|boolean|bool|float|char|void|new)\b/
const RE_SIMBOLOS = /[=(){};[\]<>]/

const palabras = (t) => t.split(/\s+/).filter(Boolean).length

// Línea corta escrita toda en mayúsculas ("GUÍA DE APRENDIZAJE"): en un PDF casi siempre es un título
const esMayusculas = (t) => t.length <= 80 && /[A-ZÁÉÍÓÚÑ]{3}/.test(t) && t === t.toUpperCase()

/** "Etiqueta: valor" con una etiqueta corta (hasta 4 palabras, como "Duración de la guía"); si no, null.
 *  Con más palabras suele ser una frase con dos puntos ("Java es de tipado estático: …"), no un campo. */
function etiquetaDe(t) {
    const m = t.match(RE_ETIQUETA)
    return m && palabras(m[1]) <= 4 ? { etiqueta: m[1], texto: m[2] } : null
}

/** ¿La línea parece código? Se exige algún símbolo de código para no confundir prosa con código. */
export function esCodigo(linea) {
    const t = linea.trim()
    if (!t) return false
    // Llaves sueltas o "} else {"
    if (/^[{}]+;?$/.test(t) || /^}\s*(else|catch|finally|while)\b/.test(t)) return true
    // Anotaciones de Java o decoradores de Python: "@Override", "@app.route('/')"
    if (/^@[A-Za-z][\w.]*(\(.*\))?$/.test(t)) return true
    if (!RE_SIMBOLOS.test(t)) return false
    // Termina en ; o { y es corta (o lleva = ( ) ): "int nota = 4;", "if (x) {"
    if (/[;{]$/.test(t) && (palabras(t) <= 8 || /[=()]/.test(t))) return true
    // Empieza con una palabra de código y lleva paréntesis, = o llaves: "for (int n : notas) {", "def f():"
    if (RE_INICIO_CODIGO.test(t) && /[=(){}]/.test(t)) return true
    return false
}

/** Adivina el lenguaje solo para la etiqueta del bloque; si no se sabe, "Código". */
function lenguajeDe(lineas) {
    const texto = lineas.join('\n')
    if (/System\.out|public\s+(static\s+)?(class|void)|\bString\[\]|\bint\[\]/.test(texto)) return 'Java'
    if (/Console\.Write|using\s+System/.test(texto)) return 'C#'
    if (/console\.log|=>|\b(const|let)\s+\w+\s*=|function\s+\w*\(/.test(texto)) return 'JavaScript'
    if (/^\s*def\s+\w+\(|\bprint\(|elif\b/m.test(texto)) return 'Python'
    if (/<\/?[a-z][\w-]*[^>]*>/i.test(texto)) return 'HTML'
    return 'Código'
}

/** Rehace la sangría contando llaves: el PDF la pierde. Sin llaves (Python) se deja como llegó. */
function sangrar(lineas) {
    if (!lineas.some((l) => /[{}]/.test(l))) return lineas.map((l) => l.replace(/\s+$/, ''))
    let nivel = 0
    return lineas.map((linea) => {
        const t = linea.trim()
        if (t.startsWith('}')) nivel = Math.max(0, nivel - 1)
        const resultado = '    '.repeat(nivel) + t
        const abre = (t.match(/{/g) ?? []).length
        const cierra = (t.match(/}/g) ?? []).length - (t.startsWith('}') ? 1 : 0)
        nivel = Math.max(0, nivel + abre - cierra)
        return resultado
    })
}

/** Une una línea al texto anterior: sin espacio si la palabra venía cortada con guion ("progra-" + "mación"). */
function unir(anterior, linea) {
    if (/[a-záéíóúñ]-$/i.test(anterior)) return anterior.slice(0, -1) + linea
    return `${anterior} ${linea}`
}

// Si la línea anterior termina en punto (o : ? !), la siguiente empieza un párrafo nuevo
const terminaFrase = (t) => /[.:!?]$/.test(t)

/**
 * Convierte el texto en una lista de bloques:
 * { tipo: 'semana' | 'titulo' | 'subtitulo', texto }  ('semana' no se pinta: la pantalla ya la muestra)
 * { tipo: 'parrafo', texto, etiqueta? }
 * { tipo: 'lista', items: [texto] }
 * { tipo: 'codigo', lenguaje, lineas: [texto] }
 * { tipo: 'actividad', titulo, texto }
 */
export function organizarTexto(texto) {
    const bloques = []
    let actual = null // bloque abierto al que se pueden sumar líneas

    const cerrar = () => {
        if (actual?.tipo === 'codigo') {
            actual.lenguaje = lenguajeDe(actual.lineas)
            actual.lineas = sangrar(actual.lineas)
        }
        actual = null
    }
    const abrir = (bloque) => {
        cerrar()
        bloques.push(bloque)
        actual = bloque
    }

    for (const cruda of (texto ?? '').replace(/\r\n?/g, '\n').split('\n')) {
        const t = cruda.trim()

        if (!t) { cerrar(); continue }
        // Viñeta sin texto: un punto de lista que el PDF cortó entre dos semanas. No hay nada que mostrar
        if (/^[-•*▪●◦]$/.test(t)) continue

        // "SEMANA 2 - …": la pantalla ya muestra la semana en su cabecera, así que la PRIMERA se marca para
        // no repetirla. Si el texto trae otra más adelante (el PDF se cortó en otro punto), sí se muestra
        if (RE_SEMANA.test(t) && t.length <= 120) {
            const yaHubo = bloques.some((b) => b.tipo === 'semana')
            abrir({ tipo: yaHubo ? 'titulo' : 'semana', texto: t })
            cerrar()
            continue
        }
        if (RE_SUBTITULO.test(t) && t.length <= 120) { abrir({ tipo: 'subtitulo', texto: t }); cerrar(); continue }

        if (esCodigo(t)) {
            if (actual?.tipo !== 'codigo') abrir({ tipo: 'codigo', lineas: [] })
            actual.lineas.push(t)
            continue
        }

        if (RE_ACTIVIDAD.test(t)) {
            const i = t.indexOf(':')
            abrir({ tipo: 'actividad', titulo: t.slice(0, i).trim(), texto: t.slice(i + 1).trim() })
            continue
        }

        if (esMayusculas(t)) { abrir({ tipo: 'titulo', texto: t }); cerrar(); continue }

        // "Duración de la guía: 4 semanas": empieza su propio párrafo, con la etiqueta resaltada
        const conEtiqueta = etiquetaDe(t)
        if (conEtiqueta) { abrir({ tipo: 'parrafo', ...conEtiqueta }); continue }

        if (RE_LISTA.test(t)) {
            if (actual?.tipo !== 'lista') abrir({ tipo: 'lista', items: [] })
            actual.items.push(t.replace(RE_LISTA, ''))
            continue
        }

        // Prosa: continúa el bloque abierto si la línea anterior quedó cortada a mitad de frase
        if (actual?.tipo === 'lista') {
            const i = actual.items.length - 1
            if (!terminaFrase(actual.items[i])) { actual.items[i] = unir(actual.items[i], t); continue }
        } else if (actual?.tipo === 'parrafo' || actual?.tipo === 'actividad') {
            if (!actual.texto || !terminaFrase(actual.texto)) {
                actual.texto = actual.texto ? unir(actual.texto, t) : t
                continue
            }
            // La actividad sigue aunque haya punto: todo su enunciado va dentro de la caja
            if (actual.tipo === 'actividad') { actual.texto = `${actual.texto} ${t}`; continue }
        }
        abrir({ tipo: 'parrafo', texto: t })
    }
    cerrar()
    return bloques
}
