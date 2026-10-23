// Traduce un error de axios a un mensaje para el usuario, según el código HTTP
export function mensajeDeError(err) {
    const status = err.response?.status
    if (status === 403) return 'No tienes acceso a esta guía.'
    if (status === 422) return err.response.data?.message ?? 'No se pudo procesar este tema.'
    if (status === 503) return err.response.data?.message ?? 'La IA está saturada. Intenta en un momento.'
    if (err.code === 'ECONNABORTED') return 'La IA tardó demasiado. Intenta de nuevo.'
    return 'Algo salió mal. Intenta de nuevo.'
}