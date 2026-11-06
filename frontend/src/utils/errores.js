// Traduce un error de axios a un mensaje para el usuario, según el código HTTP
export function mensajeDeError(err) {
    const status = err.response?.status
    if (status === 403) return 'No tienes acceso a esta guía.'
    if (status === 422) return err.response.data?.message ?? 'No se pudo procesar este tema.'
    if (status === 503) return err.response.data?.message ?? 'La IA está saturada. Intenta en un momento.'
    if (status >= 500) return 'El servidor tuvo un problema. Intenta de nuevo.'
    if (err.code === 'ECONNABORTED') return 'La IA tardó demasiado. Intenta de nuevo.'
    // Sin respuesta del servidor: backend apagado o sin internet
    if (!err.response) return 'No se pudo conectar con el servidor. Revisa tu conexión.'
    return 'Algo salió mal. Intenta de nuevo.'
}
