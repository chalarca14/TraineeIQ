// Errores del cambio de contraseña. Se decide por el código HTTP (no por el texto del backend,
// que llega en inglés). 422 = contraseña actual incorrecta o nueva inválida; lo demás es genérico.
// (Un 401 no llega aquí: el interceptor de api.js lo trata como sesión vencida y manda al login.)
export function mensajeDeErrorPassword(err) {
    if (err.response?.status === 422) return 'La contraseña actual no es correcta. Revísala e intenta de nuevo.'
    return mensajeDeError(err)
}

// Traduce un error de axios a un mensaje para el usuario, según el código HTTP
export function mensajeDeError(err) {
    const status = err.response?.status
    if (status === 403) return 'No tienes acceso a esta guía.'
    if (status === 422) return err.response.data?.message ?? 'No se pudo procesar este tema.'
    if (status === 503) return err.response.data?.message ?? 'La IA está saturada. Intenta en un momento.'
    // 429: demasiadas peticiones (por ejemplo, otra entrega mientras la anterior aún se evalúa)
    if (status === 429) return 'Ya hay una evaluación en curso o hiciste demasiados envíos seguidos. Espera un momento y vuelve a intentarlo.'
    if (status >= 500) return 'El servidor tuvo un problema. Intenta de nuevo.'
    if (err.code === 'ECONNABORTED') return 'La IA tardó demasiado. Intenta de nuevo.'
    // Sin respuesta del servidor: backend apagado o sin internet
    if (!err.response) return 'No se pudo conectar con el servidor. Revisa tu conexión.'
    return 'Algo salió mal. Intenta de nuevo.'
}
