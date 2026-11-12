// Errores del cambio de contraseña. Se decide por el código HTTP. El 422 puede ser de dos clases,
// y se distinguen por el CAMPO que falló (errors.password_actual vs errors.password), no por el
// texto: así nunca le decimos "la actual es incorrecta" cuando el problema es la nueva.
// (Un 401 no llega aquí: el interceptor de api.js lo trata como sesión vencida y manda al login.)
export function mensajeDeErrorPassword(err) {
    const status = err.response?.status
    if (status === 422) {
        const errores = err.response.data?.errors ?? {}
        if (errores.password_actual) return 'La contraseña actual no es correcta. Revísala e intenta de nuevo.'
        if (errores.password) return 'La nueva contraseña no es válida: mínimo 8 caracteres, distinta a la actual y igual en los dos campos.'
        return 'No se pudo cambiar la contraseña. Revisa los datos e intenta de nuevo.'
    }
    if (status === 429) return 'Hiciste muchos intentos seguidos. Espera un minuto y vuelve a probar.'
    return mensajeDeError(err)
}

// Errores de "Unirse a un grupo". Se decide por el código HTTP, no por el texto del backend (en inglés).
// 422 = el código no existe; 409 = ya eres miembro (antes era 400); 429 = más de 10 intentos por minuto.
// El backend normaliza el código (mayúsculas y sin espacios), así que aquí no hace falta tocarlo.
export function mensajeDeErrorUnirse(err) {
    const status = err.response?.status
    if (status === 422) return 'No encontramos un grupo con ese código. Revisa que esté bien escrito y pídele el código a tu instructor.'
    if (status === 409) return 'Ya eres miembro de este grupo. Lo encontrarás en tu lista.'
    if (status === 429) return 'Hiciste muchos intentos seguidos. Espera un momento y vuelve a probar.'
    return mensajeDeError(err)
}

// Errores de guardar el repositorio y de evaluar. En estos códigos el backend manda un `message`
// ya escrito en español y pensado para el aprendiz (422 sin repositorio o repositorio no público,
// 409 ya se está evaluando, 429 tope diario, 503 GitHub o IA ocupados), así que se muestra SIEMPRE
// ese texto. Si hay errores de validación por campo (errors.repo_url), se usa el primero.
// Es texto plano: la vista lo pinta con {{ }}, nunca con v-html.
const CODIGOS_CON_MENSAJE = [409, 422, 429, 500, 503]

export function mensajeDeErrorEntrega(err) {
    // La evaluación espera hasta 200 s. Si el navegador se cansa antes que el servidor, la
    // evaluación puede haber terminado igual: se invita a revisar, no a repetir.
    if (err.code === 'ECONNABORTED') {
        return 'La evaluación está tardando más de lo esperado. Espera un momento y pulsa "Revisar de nuevo".'
    }
    const status = err.response?.status
    if (CODIGOS_CON_MENSAJE.includes(status)) {
        const datos = err.response.data
        const porCampo = datos?.errors ? Object.values(datos.errors).flat()[0] : null
        const mensaje = porCampo ?? datos?.message
        if (typeof mensaje === 'string' && mensaje.trim()) return mensaje
    }
    return mensajeDeError(err)
}

// Errores de "Continuar con Google". En estos códigos el backend manda un `message` en español
// pensado para el usuario (401 cuenta no verificada, 403 pestaña de rol equivocada, 409 correo
// ya registrado, 422 tiempo de registro vencido, 503 Google no configurado), así que se muestra.
const CODIGOS_GOOGLE_CON_MENSAJE = [401, 403, 409, 422, 503]

export function mensajeDeErrorGoogle(err) {
    // Render gratis se duerme: la primera petición puede tardar casi un minuto
    if (err.code === 'ECONNABORTED') return 'El servidor está despertando. Espera unos segundos e intenta de nuevo.'
    const status = err.response?.status
    if (status === 429) return 'Hiciste muchos intentos seguidos. Espera un minuto y vuelve a probar.'
    const mensaje = err.response?.data?.message
    if (CODIGOS_GOOGLE_CON_MENSAJE.includes(status) && typeof mensaje === 'string' && mensaje.trim()) return mensaje
    if (!err.response) return 'No hay conexión con el servidor. Revisa tu internet e intenta de nuevo.'
    return 'No se pudo entrar con Google. Intenta de nuevo.'
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
