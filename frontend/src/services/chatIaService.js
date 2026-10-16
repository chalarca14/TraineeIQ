// Servicio del chat con IA: aquí viven TODAS las llamadas al backend del chat.
// La vista nunca usa axios directo, solo llama a estas funciones.
import api from './api'

// Convierte cualquier error de axios en un mensaje amigable.
// Laravel responde 422 con { errors: { campo: [...] } } y los demás con { message }.
function crearError(error) {
  const datos = error.response?.data
  const primerError = datos?.errors ? Object.values(datos.errors).flat()[0] : null
  return new Error(primerError || datos?.message || 'No se pudo conectar con el servidor.')
}

// Crea una conversación. En modo libre NO se envía guia_id (el backend lo prohíbe).
export async function crearConversacion(tipo, guiaId = null, titulo = null) {
  try {
    // Definimos un título autogenerado si no se envía alguno explícito
    const tituloFinal = titulo || (tipo === 'guiada' ? `Conversación Guiada (${guiaId})` : 'Conversación Libre')

    const cuerpo = { 
      tipo,
      titulo: tituloFinal // <--- Campo requerido por el backend para evitar el error 422
    }
    
    if (tipo === 'guiada') cuerpo.guia_id = guiaId
    
    const { data } = await api.post('/conversaciones', cuerpo)
    return data
  } catch (error) {
    throw crearError(error)
  }
}

// Envía un mensaje y devuelve { mensaje_usuario, respuesta_ia }.
export async function enviarMensaje(conversacionId, contenido) {
  try {
    const { data } = await api.post(`/conversaciones/${conversacionId}/mensajes`, { contenido })
    return data
  } catch (error) {
    throw crearError(error)
  }
}