// Acepta https://github.com/usuario/repositorio, con o sin ".git" y con o sin "/" al final.
// Reglas de GitHub: el usuario usa letras, números y guiones (máx. 39, sin empezar por guion);
// el repositorio admite letras, números, punto, guion y guion bajo.
const REPO_GITHUB = /^https:\/\/github\.com\/[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})\/[A-Za-z0-9._-]+?(?:\.git)?\/?$/

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
