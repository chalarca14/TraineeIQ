import api from './api'

// Servicio de autenticación — traduce las acciones de la app
// a las peticiones exactas que espera el AuthController de Laravel

/**
 * Inicia sesión contra el backend.
 * Recibe el correo y la contraseña que escribió el usuario en el formulario,
 * y los envía en el formato que espera el backend (campo "email", no "correo").
 */
export async function login(correo, password) {
    // Mapeamos correo -> email aquí, en un solo lugar,
    // así el resto de la app puede seguir usando "correo" sin problema
    const { data } = await api.post('/login', {
        email: correo,
        password: password
    })

    // El backend devuelve { success, message, token, user }
    // Solo nos interesa devolver eso hacia quien llamó la función
    return data
}