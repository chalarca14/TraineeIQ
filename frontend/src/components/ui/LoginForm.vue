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

            <RoleSelector />

        </div>

        <form @submit.prevent="iniciarSesion">

            <BaseInput v-model="correo" label="Correo electrónico" placeholder="Ingresa tu correo">

                <template #icon>
                    <Mail :size="18" />
                </template>

            </BaseInput>

            <!-- ALERTA -->

            <p v-if="errores.correo" class="input-error">
                {{ errores.correo }}
            </p>

            <BaseInput v-model="password" :type="mostrarPassword ? 'text' : 'password'" label="Contraseña"
                placeholder="Ingresa tu contraseña">

                <template #icon>
                    <Lock :size="18" />
                </template>

                <template #action>
                    <button type="button" class="toggle-password" @click="mostrarPassword = !mostrarPassword">
                        <Eye v-if="!mostrarPassword" :size="18" />
                        <EyeOff v-else :size="18" />
                    </button>
                </template>

            </BaseInput>

            <!-- ALERTA -->
            <p v-if="errores.password" class="input-error">
                {{ errores.password }}
            </p>

            <!-- MENSAJE DE ERROR -->

            <p v-if="error" class="error-message">
                {{ error }}
            </p>

            <!-- LINK "¿Olvidaste tu contraseña?" -->
            <div class="forgot-password">

                <BaseLink to="/recuperar">

                    ¿Olvidaste tu contraseña?

                </BaseLink>

            </div>


            <!-- BOTON INGRESAR -->
            <BaseButton type="submit" variant="primary" :disabled="cargando">
                {{ cargando ? "Iniciando sesión..." : "Iniciar sesión →" }}
            </BaseButton>

        </form>

        <div class="divider">

            <span>o continúa con</span>

        </div>

        <div class="social-login">

            <SocialButton>

                <template #icon>

                    <img src="@/assets/icons/google.png" alt="Google" class="social-icon">

                </template>

                Google

            </SocialButton>

            <SocialButton>

                <template #icon>

                    <img src="@/assets/icons/microsoft.png" alt="Microsoft" class="social-icon">

                </template>

                Microsoft

            </SocialButton>

        </div>

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
import RoleSelector from '../auth/RoleSelector.vue'
import BaseInput from './BaseInput.vue'
import BaseButton from './BaseButton.vue'
import SocialButton from './SocialButton.vue'
import BaseLink from './BaseLink.vue'

import {
    Mail,
    Lock,
    Eye,
    EyeOff
} from 'lucide-vue-next'

const correo = ref('')
const password = ref('')
const mostrarPassword = ref(false)
const cargando = ref(false)
const errores = ref({
    correo: '',
    password: ''
})

const validarCorreo = (correo) => {

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)

}

const iniciarSesion = async () => {

    let formularioValido = true

    if (!correo.value) {
        errores.value.correo = 'El correo es obligatorio.'
        formularioValido = false
    }
    else if (!validarCorreo(correo.value)) {

        errores.value.correo = "Ingresa un correo válido."
        formularioValido = false
    }

    if (!password.value) {
        errores.value.password = 'La contraseña es obligatoria.'
        formularioValido = false
    }
    else if (password.value.length < 8) {

        errores.value.password = "La contraseña debe tener al menos 8 caracteres."
        formularioValido = false

    }

    if (!formularioValido) return

    cargando.value = true

    console.log("Correo:", correo.value)
    console.log("Password:", password.value)

    setTimeout(() => {
        cargando.value = false
    }, 2000)

}

</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Puritan:wght@400;700&display=swap');

form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.login-form {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    padding: 2.5rem;
    background: var(--color-surface);
    animation: slideRight .6s ease;
    font-family: Puritan;
}

@media (max-width:1100px) {
    .login-form {
        padding: 3rem 2rem;
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

.toggle-password {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: .2s;
}

.toggle-password:hover {
    color: var(--color-primary);
}

.forgot-password {
    display: flex;
    justify-content: flex-end;
    margin-top: -.3rem;
    margin-bottom: .2rem;
}

.forgot-password a {
    color: var(--color-primary);
    text-decoration: none;
    font-size: .9rem;
    transition: .25s;
}

.forgot-password a:hover {
    color: var(--color-primary-light);
}


.social-login {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.social-icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

.register-footer {
    margin-top: 1.2rem;
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


.divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.2rem 0;
    color: var(--color-text-secondary);
    font-size: .9rem;

}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--color-border);

}

.error-message {
    color: var(--color-danger);
    font-size: .9rem;
    margin-top: -.5rem;
    margin-bottom: .5rem;
}

.input-error {
    margin-top: .45rem;
    color: var(--color-danger);
    font-size: .82rem;
    font-weight: 500;
}
</style>