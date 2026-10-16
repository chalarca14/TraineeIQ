// Importamos axios para hacer peticiones HTTP al backend
import axios from 'axios'

// Creamos una instancia de axios con la configuracion base
// La URL base viene del archivo .env para no escribirla directamente
const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})



// Interceptor de peticion — se ejecuta antes de cada llamada al backend
// Su trabajo es agregar el token de autenticacion automaticamente
api.interceptors.request.use((config) => {
    // Buscamos el token guardado en localStorage
    const token = localStorage.getItem('token')

    // Si existe el token, lo agregamos al header de autorizacion
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }

    return config
})

// Interceptor de respuesta — se ejecuta cuando el backend responde
// Su trabajo es manejar errores globales como token vencido
// services/api.js — solo cambia el interceptor de respuesta

api.interceptors.response.use(
    (response) => response,

    (error) => {
        // Si la petición que falló NO era el propio login,
        // significa que el token expiró en medio de la sesión.
        // Ahí sí tiene sentido limpiar todo y mandar al login.
        const esPeticionDeLogin = error.config?.url?.includes('/login')

        if (error.response?.status === 401 && !esPeticionDeLogin) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        }

        // Si SÍ era el login (credenciales incorrectas), no hacemos nada aquí:
        // dejamos que LoginForm.vue reciba el error y lo muestre en pantalla
        return Promise.reject(error)
    }
)

// Exportamos la instancia para usarla en todos los servicios
export default api