import api from './api'

// DATOS DE PRUEBA de la campana: el backend todavía no deja que el estudiante lea avisos (responde 403)
// ni crea avisos de "guía nueva" o "semana liberada". Mientras Santi lo agrega, en desarrollo se usan
// los avisos de abajo. import.meta.env.DEV es true solo con `npm run dev`: el build que se sube a
// Vercel SIEMPRE usa la API real, aunque esto se suba sin cambiar.
// Cuando el backend esté listo: cambiar a `false`.
const USAR_DATOS_PRUEBA = import.meta.env.DEV

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))
const haceHoras = (horas) => new Date(Date.now() - horas * 3600000).toISOString()

// Misma forma que manda el backend. grupo_id y guia_id son los del grupo de prueba de la base local,
// así al tocar el aviso se llega a una guía que sí existe.
const datosGuia = { grupo_id: 1, grupo: 'Grupo Demo SENA', guia_id: 1, guia: 'Guia de Introduccion' }
const avisosPrueba = [
    {
        id: 3, tipo: 'semana_liberada', leida_en: null, created_at: haceHoras(2), updated_at: haceHoras(2),
        mensaje: 'Tu instructor liberó la semana 3 de «Guia de Introduccion».',
        datos: { ...datosGuia, numero_semana: 3 }
    },
    {
        id: 2, tipo: 'guia_nueva', leida_en: null, created_at: haceHoras(26), updated_at: haceHoras(26),
        mensaje: 'Tu instructor subió la guía «Guia de Introduccion» al grupo Grupo Demo SENA.',
        datos: datosGuia
    },
    {
        id: 1, tipo: 'semana_liberada', leida_en: haceHoras(40), created_at: haceHoras(50), updated_at: haceHoras(50),
        mensaje: 'Tu instructor liberó la semana 2 de «Guia de Introduccion».',
        datos: { ...datosGuia, numero_semana: 2 }
    }
]

/**
 * Devuelve los avisos del usuario y cuántos no ha leído (el número de la campanita).
 * Respuesta: { no_leidas, notificaciones: { data, current_page, last_page, ... } }
 */
export async function getNotificaciones(pagina = 1) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(300)
        return {
            no_leidas: avisosPrueba.filter((a) => !a.leida_en).length,
            // Copias: así la campana no cambia los datos de prueba sin pasar por marcarLeida
            notificaciones: { data: avisosPrueba.map((a) => ({ ...a })), current_page: 1, last_page: 1 }
        }
    }

    const { data } = await api.get('/notificaciones', { params: { page: pagina } })
    return data
}

/** Marca un aviso como leído y lo devuelve actualizado. */
export async function marcarLeida(id) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(150)
        const aviso = avisosPrueba.find((a) => a.id === id)
        if (aviso && !aviso.leida_en) aviso.leida_en = new Date().toISOString()
        return { ...aviso }
    }

    const { data } = await api.patch(`/notificaciones/${id}/leida`)
    return data.notificacion
}

/** Marca como leídos todos los avisos pendientes y devuelve cuántos eran. */
export async function marcarTodasLeidas() {
    if (USAR_DATOS_PRUEBA) {
        await esperar(200)
        const pendientes = avisosPrueba.filter((a) => !a.leida_en)
        pendientes.forEach((a) => { a.leida_en = new Date().toISOString() })
        return pendientes.length
    }

    const { data } = await api.patch('/notificaciones/leidas')
    return data.marcadas
}
