import api from './api'

// Servicio de autenticación — traduce las acciones de la app
// a las peticiones exactas que espera el AuthController de Laravel

/**
 * Inicia sesión contra el backend.
 * Recibe el correo y la contraseña que escribió el usuario en el formulario,
 * y los envía en el formato que espera el backend (campo "email", no "correo").
 */
export async function login(correo, password, rol) {
    // Mapeamos correo -> email aquí, en un solo lugar,
    // así el resto de la app puede seguir usando "correo" sin problema
    const { data } = await api.post('/login', {
        email: correo,
        password: password,
        rol: rol
    })

    // El backend devuelve { success, message, token, user }
    // Solo nos interesa devolver eso hacia quien llamó la función
    return data
}

/**
 * Avisa al backend que cierre la sesión: POST /logout borra el token en el servidor (Sanctum).
 * Sin esto el token seguiría siendo válido aunque el navegador lo olvide.
 * El timeout es corto a propósito: si el backend no responde, el usuario no debe quedarse
 * esperando para poder salir (el composable useCerrarSesion limpia lo local igual).
 */
export async function cerrarSesionServidor() {
    await api.post('/logout', null, { timeout: 5000 })
}

/**
 * Registra un nuevo usuario en el backend.
 * Igual que login(), traduce los nombres de campo al formato que espera Laravel.
 */
export async function registro(nombre, correo, password, rol) {
    const { data } = await api.post('/register', {
        name: nombre,
        email: correo,
        password: password,
        rol: rol
    })

    return data
}