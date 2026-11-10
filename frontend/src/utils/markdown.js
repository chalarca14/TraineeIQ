// Convierte el Markdown de la IA en HTML SEGURO para pintar con v-html.
//
// POR QUÉ sanitizar: el texto de la IA no es de confianza (puede repetir HTML malicioso
// que venga en una guía PDF o en lo que escriba el usuario). Si se pinta tal cual con
// v-html, el navegador ejecutaría ese código (XSS) y podría robar el token de localStorage.
// `marked` NO protege: deja pasar el HTML crudo. Por eso el orden SIEMPRE es:
//   Markdown -> HTML (marked) -> limpiar (DOMPurify) -> v-html
import { marked } from 'marked'
import DOMPurify from 'dompurify'

// Lista corta de etiquetas permitidas: solo lo que el Markdown necesita.
// Todo lo demás (script, img, iframe, style, form...) se elimina.
const ETIQUETAS = [
  'p', 'br', 'hr', 'strong', 'em', 'del', 'code', 'pre', 'blockquote',
  'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a',
  'table', 'thead', 'tbody', 'tr', 'th', 'td'
]
// Los únicos atributos que dejamos pasar son los de los enlaces
const ATRIBUTOS = ['href', 'target', 'rel']

// Los enlaces de la IA se abren en otra pestaña. noopener/noreferrer evita que la
// página abierta pueda controlar esta (window.opener). Se hace DESPUÉS de limpiar.
DOMPurify.addHook('afterSanitizeAttributes', (nodo) => {
  if (nodo.tagName === 'A') {
    nodo.setAttribute('target', '_blank')
    nodo.setAttribute('rel', 'noopener noreferrer')
  }
})

export function renderizarMarkdown(texto) {
  // async: false para que marked devuelva un string y no una promesa
  const html = marked.parse(texto ?? '', { gfm: true, breaks: true, async: false })
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ETIQUETAS,
    ALLOWED_ATTR: ATRIBUTOS,
    ALLOW_DATA_ATTR: false
  })
}
