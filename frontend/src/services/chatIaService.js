// Servicio del chat con IA: aquí viven TODAS las llamadas al backend del chat.
// La vista nunca usa axios directo, solo llama a estas funciones.
// Los errores de axios se dejan pasar tal cual: la vista los traduce por código HTTP
// con utils/errores.js, así el mensaje depende del status y no de texto del backend.
import api from './api'

// Gemini puede tardar; sin esto axios esperaría "para siempre" y el usuario vería la pantalla colgada.
const TIMEOUT_IA = 120000

const MAX_TITULO = 50

// Título de una conversación a partir de su primer mensaje (como en ChatGPT): una sola línea,
// recortada para que quepa en el panel lateral.
export function tituloDesdeMensaje(texto) {
  const limpio = (texto ?? '').trim().replace(/\s+/g, ' ')
  return limpio.length > MAX_TITULO ? `${limpio.slice(0, MAX_TITULO).trimEnd()}…` : limpio
}

// Crea una conversación. En modo libre NO se envía guia_id (el backend lo prohíbe con 422).
// PENDIENTE DEL BACKEND: hoy ChatIaController::store NO valida ni guarda `titulo`; fija siempre
// "Conversacion libre" o "Conversacion sobre <guía>". Se envía igualmente (Laravel ignora los
// campos que no valida) para que el panel muestre el primer mensaje en cuanto el backend lo acepte.
export async function crearConversacion(tipo, guiaId = null, primerMensaje = '') {
  const cuerpo = { tipo }
  if (tipo === 'guiada') cuerpo.guia_id = guiaId
  if (primerMensaje) cuerpo.titulo = tituloDesdeMensaje(primerMensaje)

  // El backend responde la conversación directamente (status 201), sin envoltorio.
  const { data } = await api.post('/conversaciones', cuerpo)
  return data
}

// Conversaciones del aprendiz, la más reciente primero. El backend pagina de 15 en 15 y aquí
// solo se necesita la primera página: el panel muestra las últimas 10.
export async function listarConversaciones() {
  const { data } = await api.get('/conversaciones')
  return data.data
}

// Una página de conversaciones para el Historial, que sí necesita llegar a las más viejas.
// El backend usa paginate(15) de Laravel: junto con `data` manda `next_page_url`, que es null en la
// última página. Se devuelve `hayMas` ya calculado para que la vista no conozca el formato del paginador.
// Si por algún motivo no llegara `next_page_url`, se cae a comparar current_page con last_page.
export async function listarConversacionesPagina(pagina = 1) {
  const { data } = await api.get('/conversaciones', { params: { page: pagina } })
  const hayMas = data.next_page_url !== undefined
    ? Boolean(data.next_page_url)
    : (data.current_page ?? 1) < (data.last_page ?? 1)
  return { items: data.data ?? [], hayMas }
}

// Una conversación con su historial completo (`mensajes`), para reabrirla.
export async function obtenerConversacion(id) {
  const { data } = await api.get(`/conversaciones/${id}`)
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
