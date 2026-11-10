// Botón oficial "Continuar con Google" (Google Identity Services).
// Google entrega un ID token (credencial) que se envía al backend, y el backend lo verifica.
// El Client ID es público: viene de VITE_GOOGLE_CLIENT_ID (en Vercel y en frontend/.env).

const CLIENT_ID = import.meta.env.VITE_GOOGLE_CLIENT_ID

// Sin Client ID el botón no se muestra y la app sigue funcionando con correo y contraseña
export const googleDisponible = Boolean(CLIENT_ID)

// El script de Google se carga una sola vez, aunque haya botones en varias pantallas
let cargaDelScript = null

function cargarScriptDeGoogle() {
    if (cargaDelScript) return cargaDelScript

    cargaDelScript = new Promise((resolve, reject) => {
        if (window.google?.accounts?.id) return resolve()

        const script = document.createElement('script')
        script.src = 'https://accounts.google.com/gsi/client'
        script.async = true
        script.defer = true
        script.onload = () => resolve()
        script.onerror = () => {
            // Se permite reintentar la próxima vez (por ejemplo, si no había internet)
            cargaDelScript = null
            reject(new Error('No se pudo cargar el botón de Google.'))
        }
        document.head.appendChild(script)
    })

    return cargaDelScript
}

/**
 * Dibuja el botón de Google dentro de `elemento`.
 * Cuando el usuario elige su cuenta, llama a alRecibirCredencial(credencial).
 * texto: 'continue_with' (Continuar con Google) o 'signup_with' (Registrarse con Google).
 */
export async function renderizarBotonGoogle(elemento, alRecibirCredencial, texto = 'continue_with') {
    await cargarScriptDeGoogle()

    window.google.accounts.id.initialize({
        client_id: CLIENT_ID,
        callback: (respuesta) => alRecibirCredencial(respuesta.credential),
        ux_mode: 'popup'
    })

    // Google acepta un ancho fijo entre 200 y 400 px: se ajusta al contenedor
    const ancho = Math.min(400, Math.max(200, elemento.offsetWidth || 320))

    window.google.accounts.id.renderButton(elemento, {
        type: 'standard',
        theme: 'filled_black',
        size: 'large',
        shape: 'pill',
        text: texto,
        width: ancho,
        locale: 'es'
    })
}
