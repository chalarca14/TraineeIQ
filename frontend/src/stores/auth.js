import { defineStore } from 'pinia'

// Store de autenticacion — maneja el estado del usuario en toda la app
export const useAuthStore = defineStore('auth', {
    // State — los datos que guarda este store
    state: () => ({
        // Token de autenticacion que devuelve el backend
        token: localStorage.getItem('token') || null,

        // Datos del usuario autenticado
        usuario: JSON.parse(localStorage.getItem('usuario')) || null,
    }),

    // Getters — datos derivados del state (como computed en componentes)
    getters: {
        // Verifica si hay un usuario autenticado
        estaAutenticado: (state) => !!state.token,

        // Devuelve el rol del usuario para proteger rutas
        rolUsuario: (state) => state.usuario?.rol || null,
    },

    // Actions — metodos que modifican el state
    actions: {
        // Guarda el token y usuario cuando el login es exitoso
        iniciarSesion(token, usuario) {
            this.token = token
            this.usuario = usuario

            // Guardamos en localStorage para que persista al recargar la pagina
            localStorage.setItem('token', token)
            localStorage.setItem('usuario', JSON.stringify(usuario))
        },

        // Reemplaza los datos del usuario (por ejemplo, tras cambiar el nombre en el Perfil). Como la
        // NavBar y el Perfil leen de este store, el nombre y las iniciales cambian en todos lados a la vez.
        actualizarUsuario(cambios) {
            this.usuario = { ...this.usuario, ...cambios }
            localStorage.setItem('usuario', JSON.stringify(this.usuario))
        },

        // Limpia todos los datos cuando el usuario cierra sesion
        cerrarSesion() {
            this.token = null
            this.usuario = null

            // Eliminamos del localStorage
            localStorage.removeItem('token')
            localStorage.removeItem('usuario')
        },
    },
})