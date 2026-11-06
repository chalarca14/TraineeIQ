// Acepta https://github.com/usuario/repositorio, con o sin ".git" y con o sin "/" al final.
// Reglas de GitHub: el usuario usa letras, números y guiones (máx. 39, sin empezar por guion);
// el repositorio admite letras, números, punto, guion y guion bajo.
const REPO_GITHUB = /^https:\/\/github\.com\/[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})\/[A-Za-z0-9._-]+?(?:\.git)?\/?$/

// Mismo mínimo que exige el backend al registrarse (password: min:8)
export const MIN_PASSWORD = 8

/**
 * Valida el formulario de cambio de contraseña ANTES de enviarlo.
 * Devuelve un mensaje por campo ('' = campo correcto). Las contraseñas NO se recortan con trim():
 * un espacio al principio o al final puede ser parte legítima de la contraseña.
 */
export function validarCambioPassword({ actual, nueva, confirmacion }) {
    const errores = { actual: '', nueva: '', confirmacion: '' }
    if (!actual) errores.actual = 'Escribe tu contraseña actual.'

    if (!nueva) errores.nueva = 'Escribe la nueva contraseña.'
    else if (nueva.length < MIN_PASSWORD) errores.nueva = `Debe tener al menos ${MIN_PASSWORD} caracteres.`
    else if (nueva === actual) errores.nueva = 'La nueva contraseña debe ser distinta de la actual.'

    if (!confirmacion) errores.confirmacion = 'Repite la nueva contraseña.'
    else if (confirmacion !== nueva) errores.confirmacion = 'Las contraseñas no coinciden.'
    return errores
}

/**
 * Valida la URL de un repositorio de GitHub en el frontend, antes de enviar nada.
 * Devuelve el mensaje de error, o '' si la URL es válida.
 * Es solo una ayuda para el usuario: quien confirma que el repositorio exista y sea público
 * es el backend.
 */
export function validarRepoGitHub(valor) {
    const url = (valor ?? '').trim()
    if (!url) return 'Pega el enlace de tu repositorio.'
    if (!REPO_GITHUB.test(url) || /\/\.{1,2}(\.git)?\/?$/.test(url)) {
        return 'El enlace debe tener la forma https://github.com/usuario/repositorio'
    }
    return ''
}
