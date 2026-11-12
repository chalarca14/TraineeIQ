<template>
    <MarcoAuth titulo="Recupera tu" resaltado="contraseña"
        descripcion="Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.">
        <form novalidate @submit.prevent="enviar">
            <div class="campo">
                <BaseInput v-model="correo" type="email" label="Correo electrónico" placeholder="Ingresa tu correo">
                    <template #icon><Mail :size="18" aria-hidden="true" /></template>
                </BaseInput>
                <p class="error-campo">{{ errorCorreo }}</p>
            </div>

            <!-- Aviso de éxito: role="status" para que el lector de pantalla lo lea sin mover el foco -->
            <p v-if="exito" class="aviso aviso--exito" role="status">{{ exito }}</p>
            <p v-if="error" class="aviso aviso--error" role="alert">{{ error }}</p>

            <BaseButton type="submit" variant="primary" :disabled="enviando">
                {{ enviando ? 'Enviando…' : exito ? 'Enviar de nuevo' : 'Enviar enlace' }}
            </BaseButton>
        </form>
    </MarcoAuth>
</template>

<script setup>
import { ref } from 'vue'
import { Mail } from 'lucide-vue-next'
import MarcoAuth from '@/components/auth/MarcoAuth.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { pedirEnlaceRecuperacion } from '@/services/authService'
import { mensajeDeErrorCuenta } from '@/utils/errores'

const correo = ref('')
const errorCorreo = ref('')
const error = ref('')
const exito = ref('')
const enviando = ref(false)

// Misma revisión de formato que el login: se valida antes de enviar para no gastar una petición
const FORMATO_CORREO = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

async function enviar() {
    error.value = ''
    exito.value = ''
    const valor = correo.value.trim()
    errorCorreo.value = !valor ? 'Escribe tu correo.' : !FORMATO_CORREO.test(valor) ? 'Escribe un correo válido.' : ''
    if (errorCorreo.value) return

    enviando.value = true
    try {
        // El mensaje del backend es el mismo exista o no la cuenta: se muestra tal cual
        exito.value = await pedirEnlaceRecuperacion(valor)
    } catch (err) {
        error.value = mensajeDeErrorCuenta(err)
    } finally {
        enviando.value = false
    }
}
</script>

<style scoped>
form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
    max-width: 480px;
}

.campo {
    display: flex;
    flex-direction: column;
    gap: .3rem;
}

.error-campo {
    min-height: 1rem;
    margin-top: -1rem;
    color: var(--color-danger);
    font-size: .82rem;
}

.aviso {
    padding: .7rem .9rem;
    border-radius: 10px;
    font-size: .9rem;
    overflow-wrap: anywhere;
}

.aviso--exito {
    background: color-mix(in srgb, var(--color-success) 14%, transparent);
    color: var(--color-success);
}

.aviso--error {
    background: color-mix(in srgb, var(--color-danger) 12%, transparent);
    color: var(--color-danger);
}
</style>
