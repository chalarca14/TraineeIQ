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
api.interceptors.response.use(
    // Si la respuesta es exitosa, la dejamos pasar normal
    (response) => response,

    // Si hay un error, lo analizamos
    (error) => {
        // Si el backend responde 401, el token vencio o no es valido
        // Limpiamos el token y redirigimos al login
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        }

        // Retornamos el error para que cada llamada lo maneje tambien
        return Promise.reject(error)
    }
)

// Exportamos la instancia para usarla en todos los servicios
export default api