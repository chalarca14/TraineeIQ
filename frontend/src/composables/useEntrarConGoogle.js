import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { loginConGoogle } from '@/services/authService'
import { mensajeDeErrorGoogle } from '@/utils/errores'

/**
 * Lógica de "Continuar con Google" que comparten el login y el registro.
 * rol: ref con el rol elegido en la pantalla (pestaña del login o tarjeta del registro).
 */
export function useEntrarConGoogle(rol) {
    const router = useRouter()
    const authStore = useAuthStore()

    const cargandoGoogle = ref(false)
    const errorGoogle = ref('')

    // Datos de una cuenta nueva que aún debe completar su registro (abre la ventana flotante)
    const registroPendiente = ref(null)

    // Igual que el login normal: guarda la sesión y redirige según el rol que devolvió el backend
    function entrarAlDashboard(data) {
        registroPendiente.value = null
        authStore.iniciarSesion(data.token, data.user)
        router.push(data.user.rol === 'instructor' ? '/instructor/dashboard' : '/estudiante/dashboard')
    }

    // Google devolvió la credencial: el backend decide si entra o si debe completar el registro
    async function alRecibirCredencial(credencial) {
        errorGoogle.value = ''
        cargandoGoogle.value = true

        try {
            const data = await loginConGoogle(credencial, rol.value)

            if (data.requiere_registro) {
                registroPendiente.value = {
                    registroToken: data.registro_token,
                    nombre: data.nombre,
                    email: data.email,
                    rol: rol.value
                }
            } else {
                entrarAlDashboard(data)
            }
        } catch (err) {
            errorGoogle.value = mensajeDeErrorGoogle(err)
        } finally {
            cargandoGoogle.value = false
        }
    }

    // El usuario cerró la ventana sin terminar: no se crea ninguna cuenta
    function cancelarRegistro() {
        registroPendiente.value = null
    }

    return { cargandoGoogle, errorGoogle, registroPendiente, alRecibirCredencial, entrarAlDashboard, cancelarRegistro }
}
