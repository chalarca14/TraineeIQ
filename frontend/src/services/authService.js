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

/**
 * "¿Olvidaste tu contraseña?": pide al backend que envíe el enlace al correo.
 * Responde lo mismo exista o no el correo (así nadie averigua qué correos tienen cuenta).
 * Devuelve el `message` del backend para mostrarlo tal cual.
 */
export async function pedirEnlaceRecuperacion(correo) {
    const { data } = await api.post('/password/olvide', { email: correo })
    return data.message
}

/**
 * Crea la contraseña nueva con el token y el correo que vienen en el enlace del correo.
 * 422 = enlace inválido o vencido (message) o contraseña que no cumple las reglas (errors.password).
 */
export async function restablecerPassword({ token, correo, password }) {
    const { data } = await api.post('/password/restablecer', {
        token,
        email: correo,
        password,
        password_confirmation: password
    })
    return data.message
}

/**
 * Entra con la credencial que entregó el botón de Google.
 * Si la cuenta ya existe devuelve { token, user } como login().
 * Si es nueva devuelve { requiere_registro, registro_token, nombre, email }.
 * Timeout de 60 s: Render gratis puede estar dormido y tardar en responder.
 */
export async function loginConGoogle(credencial, rol) {
    const { data } = await api.post('/auth/google', { credential: credencial, rol }, { timeout: 60000 })

    return data
}

/**
 * Termina el registro de una cuenta nueva de Google con el nombre, el rol y la contraseña elegidos.
 * Devuelve { token, user } como registro(). El correo no se envía: sale del registro_token.
 */
export async function completarRegistroGoogle({ registroToken, nombre, rol, password, passwordConfirmacion }) {
    const { data } = await api.post('/auth/google/completar', {
        registro_token: registroToken,
        name: nombre,
        rol,
        password,
        password_confirmation: passwordConfirmacion
    }, { timeout: 60000 })

    return data
}