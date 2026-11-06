import api from './api'

// Guarda en el backend el modo preferido del usuario ('grupo' o 'personal')
export async function actualizarModo(modo) {
    const { data } = await api.patch('/usuario/modo', { modo_preferido: modo })
    return data
}

// ---------------------------------------------------------------------------
// Cambio de contraseña
// ---------------------------------------------------------------------------
// ⚠️ EL ENDPOINT REAL AÚN NO EXISTE. Contrato propuesto (hay que acordarlo con el equipo):
//   PATCH /usuario/password  { password_actual, password, password_confirmation }
//   200 → contraseña cambiada
//   422 → la contraseña actual es incorrecta, o la nueva no cumple las reglas (mín. 8 caracteres)
// IMPORTANTE: una contraseña actual incorrecta debe ser 422 y NO 401. El interceptor de api.js
// trata cualquier 401 como "sesión vencida": borra el token y manda al login, así que un simple
// error de tipeo cerraría la sesión del aprendiz.

// Mientras el backend no tenga el endpoint, true. Cuando exista, false (y se borra el bloque de ejemplo).
const USAR_DATOS_PRUEBA = true

// La pantalla lo usa para mostrar la nota "de ejemplo"; al pasar a false la nota desaparece sola.
export const CAMBIO_PASSWORD_ES_EJEMPLO = USAR_DATOS_PRUEBA

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

// Contraseña actual que el ejemplo rechaza con 422, para poder ver el estado de error.
const ACTUAL_RECHAZADA_EN_EJEMPLO = 'incorrecta'

async function cambiarPasswordReal(actual, nueva) {
    // Las contraseñas solo viajan en el cuerpo de la petición: no se guardan ni se muestran en consola.
    await api.patch('/usuario/password', {
        password_actual: actual,
        password: nueva,
        password_confirmation: nueva
    })
}

async function cambiarPasswordEjemplo(actual) {
    await esperar(800) // simula la espera de la red
    if (actual === ACTUAL_RECHAZADA_EN_EJEMPLO) {
        // Mismo aspecto que un error de axios, para que la pantalla lo trate igual que uno real
        throw Object.assign(new Error('Contraseña actual incorrecta (ejemplo)'), { response: { status: 422 } })
    }
}

/** Cambia la contraseña del usuario. Lanza el error de axios si falla (la vista lo traduce por código HTTP). */
export function cambiarPassword(actual, nueva) {
    return USAR_DATOS_PRUEBA ? cambiarPasswordEjemplo(actual) : cambiarPasswordReal(actual, nueva)
}
