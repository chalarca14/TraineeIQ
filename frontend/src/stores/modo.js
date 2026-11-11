import { defineStore } from 'pinia'
import { actualizarModo } from '@/services/usuarioService'

export const useModoStore = defineStore('modo', {
    state: () => ({
        // Lee el valor guardado en localStorage al arrancar, o 'grupo' por defecto
        modoActivo: localStorage.getItem('modoActivo') || 'grupo'
    }),

    getters: {
        esModoPersonal: (state) => state.modoActivo === 'personal'
    },

    actions: {
        async cambiarModo(nuevoModo) {
            // La interfaz cambia al instante, sin esperar al servidor
            this.modoActivo = nuevoModo
            localStorage.setItem('modoActivo', nuevoModo)

            try {
                await actualizarModo(nuevoModo)
            } catch (error) {
                // El modo es solo una preferencia de pantalla. Si no se pudo guardar en el
                // servidor, seguimos con el cambio local en vez de romper la interfaz
                console.warn('No se pudo guardar el modo en el servidor')
            }
        }
    }
})