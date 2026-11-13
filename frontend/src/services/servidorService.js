// ¿Está despierto el servidor? En Render el backend se duerme tras 15 min sin uso y la primera
// llamada tarda 30 a 50 s. La pantalla de entrada pregunta aquí para saber cuándo dejar pasar.
import api from './api'

// No se usa /up de Laravel: está fuera de /api y el CORS del backend solo cubre api/*, así que el
// navegador bloquearía la respuesta aunque el servidor esté vivo. Se pregunta a una ruta de /api que
// no existe: el servidor despierto responde 404 (con CORS) y eso basta, lo que importa es que conteste.
// Un 404 no dispara el interceptor de api.js (solo actúa con 401).
// PENDIENTE DEL BACKEND (opcional): una ruta GET /api/estado que responda 200 dejaría esto más claro.
const RUTA_PING = '/ping'

// Espera a que el servidor conteste. true = contestó (con cualquier código HTTP);
// false = no contestó (sin conexión o más de 90 s). Con false la app entra igual: cada pantalla
// tiene su propio manejo de errores y su aviso de "servidor despertando".
export async function esperarServidor() {
  try {
    await api.get(RUTA_PING, { timeout: 90000 })
    return true
  } catch (error) {
    return Boolean(error.response)
  }
}
