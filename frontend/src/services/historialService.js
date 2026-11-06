// Servicio del Historial: junta dos fuentes distintas del backend (conversaciones con la IA y
// recomendaciones) en UNA sola lista de "entradas" con la misma forma. Así la vista y los filtros
// no necesitan saber de dónde vino cada cosa.
//
// Forma de una entrada:
//   { clave, categoria: 'ia' | 'guia', titulo, descripcion, etiqueta, fecha, ruta }
import { listarConversacionesPagina } from './chatIaService'
import { obtenerHistorialRecomendaciones, leerContenido } from './recomendacionServices'

const PREFIJO_GUIADA = 'Conversacion sobre '

// El backend guarda el título guiado como "Conversacion sobre <guía>" (sin tilde). Para la
// etiqueta "Sobre: guía" se quita ese prefijo; si el título no lo trae, se usa tal cual.
function nombreDeGuia(titulo) {
  const t = (titulo ?? '').trim()
  return t.startsWith(PREFIJO_GUIADA) ? t.slice(PREFIJO_GUIADA.length) : t
}

export function entradaDeConversacion(c) {
  const guiada = c.tipo === 'guiada'
  const guia = guiada ? nombreDeGuia(c.titulo) : ''
  return {
    clave: `conv-${c.id}`,
    categoria: 'ia',
    titulo: 'Conversación con la IA',
    descripcion: guiada
      ? `Chat guiado sobre la guía ${guia || 'seleccionada'}.`
      : 'Chat libre para resolver tus dudas.',
    etiqueta: guiada ? `Sobre: ${guia || 'guía'}` : 'Chat libre',
    // updated_at cambia con cada mensaje nuevo (el backend hace touch), así que refleja la última actividad
    fecha: c.updated_at ?? c.created_at,
    // Cada conversación se reabre en el chat para continuarla (Tarea 1)
    ruta: { name: 'ChatIAConversacion', params: { conversacionId: c.id } }
  }
}

// Cada vez que el aprendiz pide recomendaciones el backend guarda varias filas del mismo tema.
// Mostrarlas una por una llenaría el historial, así que se agrupan cuando coinciden la guía,
// el tema Y la hora de creación (así dos generaciones del mismo tema en horas distintas
// siguen siendo dos entradas). `created_at` viene en ISO ("2026-11-06T15:04:05Z"):
// sus primeros 13 caracteres llegan hasta la hora ("2026-11-06T15").
export function agruparRecomendaciones(filas) {
  const grupos = new Map()
  for (const r of filas ?? []) {
    const clave = `rec-${r.guia_id}-${r.tema_id}-${String(r.created_at).slice(0, 13)}`
    if (!grupos.has(clave)) grupos.set(clave, [])
    grupos.get(clave).push(r)
  }

  return [...grupos].map(([clave, grupo]) => {
    const primera = grupo[0]
    const tema = primera.tema?.nombre_tema ?? 'un tema de tu guía'
    return {
      clave,
      categoria: 'guia',
      titulo: `Recomendaciones: ${tema}`,
      descripcion: `La IA te dio ${grupo.length} ${grupo.length === 1 ? 'recomendación' : 'recomendaciones'}, por ejemplo: «${leerContenido(primera).titulo}».`,
      etiqueta: 'Recomendaciones',
      fecha: primera.created_at,
      ruta: { name: 'GuiaDetalle', params: { id: primera.guia_id } }
    }
  })
}

// Primera carga: las dos fuentes en paralelo. Se usa allSettled (no Promise.all) para que si una
// falla la otra igual se muestre; la vista avisa de lo que faltó en vez de quedarse en blanco.
export async function cargarHistorialInicial() {
  const [conv, rec] = await Promise.allSettled([
    listarConversacionesPagina(1),
    obtenerHistorialRecomendaciones()
  ])

  return {
    conversaciones: conv.status === 'fulfilled' ? conv.value.items.map(entradaDeConversacion) : [],
    recomendaciones: rec.status === 'fulfilled' ? agruparRecomendaciones(rec.value) : [],
    hayMas: conv.status === 'fulfilled' ? conv.value.hayMas : false,
    // Texto de las fuentes que fallaron, para el aviso parcial
    fallos: [
      conv.status === 'rejected' ? 'tus conversaciones' : null,
      rec.status === 'rejected' ? 'tus recomendaciones' : null
    ].filter(Boolean),
    // Si fallaron las dos se necesita el error real para traducirlo por código HTTP
    errores: [conv, rec].filter((r) => r.status === 'rejected').map((r) => r.reason)
  }
}

// "Cargar más": solo las conversaciones paginan (las recomendaciones llegan completas)
export async function cargarMasConversaciones(pagina) {
  const { items, hayMas } = await listarConversacionesPagina(pagina)
  return { conversaciones: items.map(entradaDeConversacion), hayMas }
}
