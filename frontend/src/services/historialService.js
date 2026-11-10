// Servicio del Historial: SOLO conversaciones con la IA. Antes también mezclaba las recomendaciones
// (GET /recomendaciones/historial), pero ese endpoint ya no existe: ahora las recomendaciones llegan
// dentro de cada semana de la guía. Cada conversación se convierte en una "entrada" con la forma
// que esperan la lista y los filtros, así la vista no conoce el formato del backend.
//
// Forma de una entrada:
//   { clave, categoria: 'libre' | 'guiada', titulo, descripcion, etiqueta, fecha, ruta }
import { listarConversacionesPagina } from './chatIaService'

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
    // La categoría es el tipo de chat: así las pestañas "Chats libres" y "Guiados" filtran por ella
    categoria: guiada ? 'guiada' : 'libre',
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

/**
 * Una página de conversaciones ya convertidas en entradas. `hayMas` sale de `next_page_url` del
 * paginador de Laravel (lo calcula chatIaService): el botón "Cargar más" solo aparece si hay otra página.
 */
export async function cargarPaginaConversaciones(pagina = 1) {
  const { items, hayMas } = await listarConversacionesPagina(pagina)
  return { entradas: items.map(entradaDeConversacion), hayMas }
}
