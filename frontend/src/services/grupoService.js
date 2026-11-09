import api from './api'

// Datos reales (CLAUDE.md: USAR_DATOS_PRUEBA = false). Solo se llama a la API desde aquí,
// con la instancia de api.js, para que el token y el manejo del 401 sean los mismos de siempre.
const USAR_DATOS_PRUEBA = false

const GRUPOS_PRUEBA = [
    { id: 1, nombre: 'Grupo Demo SENA', descripcion: 'Grupo de prueba para desarrollo', instructor: { id: 2, name: 'Carlos Méndez' } }
]

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

/** Grupos en los que está inscrito el aprendiz. El backend los manda en data.data. */
export async function listarMisGrupos() {
    if (USAR_DATOS_PRUEBA) {
        await esperar(300)
        return [...GRUPOS_PRUEBA]
    }

    const { data } = await api.get('/grupos')
    return data.data
}

/**
 * Inscribe al aprendiz en un grupo con el código de acceso. Devuelve el grupo.
 * Los errores se dejan subir tal cual: el modal decide el mensaje según el código HTTP
 * (422 = código inexistente, 400 = ya eres miembro).
 */
export async function unirseAGrupo(codigo) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(400)
        return GRUPOS_PRUEBA[0]
    }

    const { data } = await api.post('/grupos/unirse', { codigo_acceso: codigo })
    return data.data
}
