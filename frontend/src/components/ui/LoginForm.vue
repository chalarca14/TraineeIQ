<template>

    <section class="login-form">

        <header class="form-header">

            <h2>
                ¡Bienvenido
                <span>de vuelta!</span>
            </h2>

            <p>
                Ingresa tu cuenta de TraineeIQ
            </p>

        </header>

        <div class="role-container">

            <RoleSelector v-model="rol" />

        </div>

        <!-- Continuar con Google: usa el rol de la pestaña, igual que el formulario -->
        <BotonGoogle ref="botonGoogle" :cargando="cargandoGoogle" :error="errorGoogle" @credencial="alRecibirCredencial" />

        <CompletarRegistroGoogleModal v-if="registroPendiente" :registro="registroPendiente"
            @cerrar="cancelarRegistro" @registrado="entrarAlDashboard" />

        <!-- Correo, contraseña y su validación: ver CamposLogin.vue -->
        <CamposLogin :cargando="cargando" :error="error" @enviar="iniciarSesion" />

        <footer class="register-footer">

            <span>

                ¿No tienes cuenta?

            </span>

            <RouterLink to="/registro">

                Regístrate

            </RouterLink>

        </footer>

    </section>

</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { login } from '@/services/authService'
import RoleSelector from '../auth/RoleSelector.vue'
import CamposLogin from '../auth/CamposLogin.vue'
import BotonGoogle from '../auth/BotonGoogle.vue'
import CompletarRegistroGoogleModal from '../auth/CompletarRegistroGoogleModal.vue'
import { useEntrarConGoogle } from '@/composables/useEntrarConGoogle'

const router = useRouter()
const authStore = useAuthStore()

const rol = ref('estudiante')

// Continuar con Google: la lógica está en el composable, compartida con el registro
const { cargandoGoogle, errorGoogle, registroPendiente, botonGoogle, alRecibirCredencial, entrarAlDashboard, cancelarRegistro } =
    useEntrarConGoogle(rol)
const cargando = ref(false)

// Error general del backend ("Usuario no registrado.", rol equivocado, servidor caído, etc.)
const error = ref('')

// CamposLogin ya validó el correo y la contraseña antes de avisar
const iniciarSesion = async ({ correo, password }) => {
    error.value = ''
    cargando.value = true

    try {
        // Llamamos al servicio, que ya sabe cómo hablar con el backend
        const data = await login(correo, password, rol.value)

        // Guardamos el token y el usuario en el store (y en localStorage)
        authStore.iniciarSesion(data.token, data.user)

        // Redirigimos según el rol que devolvió el backend,
        // no según lo que haya elegido el RoleSelector visualmente
        if (data.user.rol === 'instructor') {
            router.push('/instructor/dashboard')
        } else {
            router.push('/estudiante/dashboard')
        }

    } catch (err) {
        // Si el backend respondió con un mensaje (usuario no registrado, rol equivocado, etc.)
        // lo mostramos; si no, damos un mensaje genérico
        error.value = err.response?.data?.message || 'No se pudo iniciar sesión. Intenta de nuevo.'

    } finally {
        // Se ejecuta siempre, haya éxito o error — evita que el botón
        // se quede en "Iniciando sesión..." para siempre si algo falla
        cargando.value = false
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Puritan:wght@400;700&display=swap');

.login-form {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding: 2.5rem;
    background: var(--color-surface);
    animation: slideRight .6s ease;
    font-family: Puritan;
    min-width: 0;
}

@media (max-width: 1100px) {
    .login-form {
        padding: 3rem 2rem;
    }
}

@media (max-width: 600px) {
    .login-form {
        padding: 2rem 1.5rem;
    }

    .form-header {
        margin-bottom: clamp(.8rem, 2vh, 1.5rem);
    }

    .form-header h2 {
        font-size: clamp(1.5rem, 3vh, 2rem);
    }

    /* .role-selector es la raíz de RoleSelector: el estilo scoped del padre sí la alcanza */
    .role-selector {
        gap: .5rem;
    }
}

@keyframes slideRight {

    from {
        opacity: 0;
        transform: translateX(40px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }

}

.form-header {
    margin-bottom: 1.2rem;
}

.form-header h2 {
    font-size: 2rem;
    line-height: 1.1;
    color: var(--color-text);
}

.form-header span {
    color: var(--color-primary);
}

.form-header p {
    margin-top: .7rem;
    color: var(--color-text-secondary);
}

.register-footer {
    margin-top: var(--espacio-xs);
    display: flex;
    justify-content: center;
    align-items: center;
    gap: .5rem;
    color: var(--color-text-secondary);
}

.register-footer a {
    color: var(--color-primary);
    font-weight: 600;
    text-decoration: none;
}

.register-footer a:hover {
    color: var(--color-primary-light);
}

@media (max-height: 800px) {
    .login-form {
        padding: 3rem;
        gap: var(--espacio-md);
    }

    .form-header h2 {
        font-size: 1.8rem;
    }

    .form-header p {
        font-size: .85rem;
    }

    .register-footer {
        font-size: .8rem;
    }
}

@media (min-width: 413px) {


    .login-form {
        padding: 2rem;
        gap: var(--espacio-md);
    }

    .form-header p {
        font-size: 1rem;
    }

    .form-header h2 {
        font-size: 2.1rem;
    }

}

@media (max-width: 700px) {

    .login-form {
        padding: 2rem 1.5rem;
    }

    .form-header {
        margin-bottom: 1.5rem;
    }

    .form-header h2 {
        font-size: 2rem;
    }

    .role-container {
        max-width: 100%;
    }

}
</style>
