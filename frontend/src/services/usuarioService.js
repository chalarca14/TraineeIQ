import api from './api'

// Guarda en el backend el modo preferido del usuario ('grupo' o 'personal')
export async function actualizarModo(modo) {
    const { data } = await api.patch('/usuario/modo', { modo_preferido: modo })
    return data
}

// ---------------------------------------------------------------------------
// Cambio de contraseña
// ---------------------------------------------------------------------------
// PATCH /usuario/password  { password_actual, password, password_confirmation }
//   200 → contraseña cambiada
//   422 → la actual es incorrecta (errors.password_actual) o la nueva no cumple las reglas
//         (mínimo 8, confirmada y distinta a la actual: errors.password)
//   429 → más de 5 intentos por minuto
// Una contraseña actual incorrecta es 422 y NO 401, porque el interceptor de api.js trata cualquier
// 401 como "sesión vencida" (borra el token y manda al login).

/**
 * Cambia la contraseña del usuario. Lanza el error de axios si falla (la vista lo traduce por código HTTP).
 * Las contraseñas solo viajan en el cuerpo de la petición: no se guardan ni se muestran en consola.
 */
export async function cambiarPassword(actual, nueva) {
    await api.patch('/usuario/password', {
        password_actual: actual,
        password: nueva,
        password_confirmation: nueva
    })
}
