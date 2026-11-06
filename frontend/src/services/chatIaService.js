// Servicio del chat con IA: aquí viven TODAS las llamadas al backend del chat.
// La vista nunca usa axios directo, solo llama a estas funciones.
// Los errores de axios se dejan pasar tal cual: la vista los traduce por código HTTP
// con utils/errores.js, así el mensaje depende del status y no de texto del backend.
import api from './api'

// Gemini puede tardar; sin esto axios esperaría "para siempre" y el usuario vería la pantalla colgada.
const TIMEOUT_IA = 120000

// Crea una conversación. En modo libre NO se envía guia_id (el backend lo prohíbe con 422).
// El título se arma aquí porque el backend lo exige.
export async function crearConversacion(tipo, guiaId = null) {
  const cuerpo = {
    tipo,
    titulo: tipo === 'guiada' ? `Conversación guiada (guía ${guiaId})` : 'Conversación libre'
  }
  if (tipo === 'guiada') cuerpo.guia_id = guiaId

  // El backend responde la conversación directamente (status 201), sin envoltorio.
  const { data } = await api.post('/conversaciones', cuerpo)
  return data
}

// Envía un mensaje y devuelve { mensaje_usuario, respuesta_ia }.
// Aquí sí va el timeout largo: es la llamada que espera a Gemini.
export async function enviarMensaje(conversacionId, contenido) {
  const { data } = await api.post(
    `/conversaciones/${conversacionId}/mensajes`,
    { contenido },
    { timeout: TIMEOUT_IA }
  )
  return data
}
