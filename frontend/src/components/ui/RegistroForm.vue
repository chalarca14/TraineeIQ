<template>
    <section class="registro-form">

        <header class="form-header">
            <h2>Registro</h2>
            <p>Completa la información para crear tu cuenta</p>
        </header>

        <form @submit.prevent="registrarse" novalidate>

            <div class="fields-row">
                <div class="field-group">
                    <BaseInput v-model="nombre" label="Nombre completo" placeholder="Ej: Juan Pérez García">
                        <template #icon><User :size="18" /></template>
                    </BaseInput>
                    <p class="input-error">{{ errores.nombre }}</p>
                </div>

                <div class="field-group">
                    <BaseInput v-model="correo" label="Correo electrónico" placeholder="Ej: juan.perez@correo.com">
                        <template #icon><Mail :size="18" /></template>
                    </BaseInput>
                    <p class="input-error">{{ errores.correo }}</p>
                </div>
            </div>

            <div class="fields-row">
                <div class="field-group">
                    <BaseInput v-model="password" :type="mostrarPassword ? 'text' : 'password'" label="Contraseña" placeholder="Mínimo 8 caracteres">
                        <template #icon><Lock :size="18" /></template>
                        <template #action>
                            <button type="button" class="toggle-password" @click="mostrarPassword = !mostrarPassword">
                                <Eye v-if="!mostrarPassword" :size="18" />
                                <EyeOff v-else :size="18" />
                            </button>
                        </template>
                    </BaseInput>
                    <p class="input-error">{{ errores.password }}</p>
                </div>

                <div class="field-group">
                    <BaseInput v-model="confirmarPassword" :type="mostrarConfirmar ? 'text' : 'password'" label="Confirmar contraseña" placeholder="Repite tu contraseña">
                        <template #icon><Lock :size="18" /></template>
                        <template #action>
                            <button type="button" class="toggle-password" @click="mostrarConfirmar = !mostrarConfirmar">
                                <Eye v-if="!mostrarConfirmar" :size="18" />
                                <EyeOff v-else :size="18" />
                            </button>
                        </template>
                    </BaseInput>
                    <p class="input-error">{{ errores.confirmarPassword }}</p>
                </div>
            </div>

            <div class="field-group">
                <label class="rol-label">Rol</label>
                <RoleCardSelector v-model="rolSeleccionado" />
            </div>

            <div class="field-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" v-model="aceptaTerminos" />
                    Acepto los <BaseLink to="/terminos">Términos y Condiciones</BaseLink> y la
                    <BaseLink to="/privacidad">Política de Privacidad</BaseLink>
                </label>
                <p class="input-error">{{ errores.aceptaTerminos }}</p>
            </div>

            <p class="error-message">{{ error }}</p>

            <BaseButton type="submit" :disabled="cargando">
                {{ cargando ? 'Creando cuenta...' : 'Crear cuenta' }}
            </BaseButton>

        </form>

        <p class="login-redirect">
            ¿Ya tienes una cuenta? <BaseLink to="/login">Inicia sesión</BaseLink>
        </p>

    </section>
</template>

<script setup>
import { ref } from 'vue'
import { Mail, Lock, User, Eye, EyeOff } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { registro } from '@/services/authService'
import RoleCardSelector from '../auth/RoleCardSelector.vue'

const router = useRouter()
const authStore = useAuthStore()
const rolSeleccionado = defineModel('rol')

import BaseInput from './BaseInput.vue'
import BaseButton from './BaseButton.vue'
import BaseLink from './BaseLink.vue'

const nombre = ref('')
const correo = ref('')
const password = ref('')
const confirmarPassword = ref('')
const aceptaTerminos = ref(false)

const mostrarPassword = ref(false)
const mostrarConfirmar = ref(false)
const cargando = ref(false)
const error = ref('')

const errores = ref({
    nombre: '',
    correo: '',
    password: '',
    confirmarPassword: '',
    aceptaTerminos: ''
})

const validarCorreo = (correo) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)

const validarFormulario = () => {
    errores.value = { nombre: '', correo: '', password: '', confirmarPassword: '', aceptaTerminos: '' }
    let esValido = true

    if (!nombre.value.trim()) {
        errores.value.nombre = 'El nombre es obligatorio.'
        esValido = false
    }

    if (!correo.value) {
        errores.value.correo = 'El correo es obligatorio.'
        esValido = false
    } else if (!validarCorreo(correo.value)) {
        errores.value.correo = 'Ingresa un correo válido.'
        esValido = false
    }

    if (!password.value) {
        errores.value.password = 'La contraseña es obligatoria.'
        esValido = false
    } else if (password.value.length < 8) {
        errores.value.password = 'Debe tener al menos 8 caracteres.'
        esValido = false
    }

    // Esta comparación solo existe en el frontend — el backend de Jefferson
    // no recibe ni valida un campo de confirmación, así que es responsabilidad nuestra
    if (password.value && confirmarPassword.value !== password.value) {
        errores.value.confirmarPassword = 'Las contraseñas no coinciden.'
        esValido = false
    }

    if (!aceptaTerminos.value) {
        errores.value.aceptaTerminos = 'Debes aceptar los términos para continuar.'
        esValido = false
    }

    return esValido
}

const registrarse = async () => {
    error.value = ''
    if (!validarFormulario()) return

    cargando.value = true

    try {
        const data = await registro(nombre.value, correo.value, password.value, rolSeleccionado.value)

        // Igual que en Login: guardamos sesión y entramos directo, sin pedir que inicie sesión aparte
        authStore.iniciarSesion(data.token, data.user)

        router.push(data.user.rol === 'instructor' ? '/instructor/dashboard' : '/estudiante/dashboard')

    } catch (err) {
        if (err.response?.status === 422 && err.response.data.errors) {
            // Laravel manda un objeto tipo: { email: ["El correo ya está en uso"], ... }
            // Lo repartimos entre los campos correspondientes de nuestro formulario
            const erroresBackend = err.response.data.errors

            if (erroresBackend.email) errores.value.correo = erroresBackend.email[0]
            if (erroresBackend.name) errores.value.nombre = erroresBackend.name[0]
            if (erroresBackend.password) errores.value.password = erroresBackend.password[0]
            if (erroresBackend.rol) error.value = erroresBackend.rol[0]

        } else {
            error.value = 'No se pudo crear la cuenta. Intenta de nuevo.'
        }

    } finally {
        cargando.value = false
    }
}
</script>

<style scoped>

.registro-form {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: clamp(1.5rem, 4vh, 2.5rem);
    background: var(--color-surface);
    font-family: Puritan;
    min-width: 0;
    overflow-y: auto;
}

.rol-label {
    font-size: .85rem;
    color: var(--color-text);
    margin-bottom: .4rem;
    gap: .4rem;
    display: flex;
    font-weight: bold;
}

.form-header {
    text-align: center;
    margin-bottom: clamp(.6rem, 1.5vh, 1.2rem);   /* antes: hasta 1.5rem */
}

.form-header h2 {
    margin: 0 0 .3rem 0;
    color: var(--color-text);
}

.form-header p {
    color: var(--color-text-secondary);
    font-size: .9rem;
}

form {
    display: flex;
    flex-direction: column;
    gap: clamp(.6rem, 1.5vh, 1rem);   /* antes: 1rem fijo — ahora se achica en pantallas bajas */
    margin-top: 1rem;
}


.fields-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 1rem;   /* sin espacio vertical entre sí mismas, solo horizontal entre columnas */
}

.input-error {
    margin: 0;
    min-height: 1rem;
    color: var(--color-danger);
    font-size: .82rem;
    font-weight: 500;
}

.error-message {
    color: var(--color-danger);
    font-size: .9rem;
    min-height: 1.2rem;
    margin: 0;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    font-size: .85rem;
    color: var(--color-text-secondary);
    line-height: 1.5;
}

.toggle-password {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--color-text-secondary);
    display: flex;
}

.login-redirect {
    text-align: center;
    margin-top: 1rem;
    font-size: .9rem;
    color: var(--color-text-secondary);
}

@media (max-width: 700px) {
    .fields-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>