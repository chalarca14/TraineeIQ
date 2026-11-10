import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { cerrarSesionServidor } from '@/services/authService'

// Cierre de sesión completo, compartido por la NavBar y el Perfil para que ambos hagan LO MISMO.
// Orden importante:
//   1. Primero se avisa al servidor, porque la petición necesita el token que aún está guardado.
//   2. Después se limpia el token local SIEMPRE (finally), aunque la petición falle: con el
//      backend apagado, sin internet o con el token ya vencido (401) el usuario igual debe poder
//      salir. Lo peor que puede pasar es que el token quede vivo en el servidor hasta que expire.
export function useCerrarSesion() {
  const router = useRouter()
  const authStore = useAuthStore()

  async function cerrarSesion() {
    try {
      await cerrarSesionServidor()
    } catch {
      // Se ignora a propósito: no hay nada útil que mostrarle al usuario, lo local se limpia abajo.
    } finally {
      authStore.cerrarSesion()
      router.push('/login')
    }
  }

  return { cerrarSesion }
}
