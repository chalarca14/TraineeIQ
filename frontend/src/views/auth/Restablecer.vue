<template>
    <MarcoAuth titulo="Crea una nueva" resaltado="contraseña"
        descripcion="Debe tener al menos 8 caracteres. Al guardarla se cierran las sesiones abiertas.">
        <!-- Sin token o sin correo en la URL el enlace está incompleto: no tiene sentido mostrar el formulario -->
        <div v-if="!enlaceCompleto" class="aviso aviso--error" role="alert">
            Este enlace no es válido.
            <RouterLink to="/recuperar">Pide uno nuevo</RouterLink>.
        </div>

        <div v-else-if="exito" class="listo">
            <p class="aviso aviso--exito" role="status">{{ exito }}</p>
            <RouterLink to="/login" class="btn btn-primario">Ir a iniciar sesión</RouterLink>
        </div>

        <form v-else novalidate @submit.prevent="guardar">
            <div class="campo">
                <BaseInput v-model="password" :type="tipo" label="Nueva contraseña" placeholder="Mínimo 8 caracteres">
                    <template #icon><Lock :size="18" aria-hidden="true" /></template>
                    <template #action>
                        <button type="button" class="ver" :aria-label="ver ? 'Ocultar contraseñas' : 'Mostrar contraseñas'"
                            @click="ver = !ver">
                            <EyeOff v-if="ver" :size="18" /><Eye v-else :size="18" />
                        </button>
                    </template>
                </BaseInput>
            </div>

            <div class="campo">
                <BaseInput v-model="confirmacion" :type="tipo" label="Confirma la contraseña" placeholder="Escríbela otra vez">
                    <template #icon><Lock :size="18" aria-hidden="true" /></template>
                </BaseInput>
                <p class="error-campo">{{ errorCampos }}</p>
            </div>

            <!-- Enlace vencido o ya usado: el backend manda el texto; se ofrece pedir otro -->
            <p v-if="error" class="aviso aviso--error" role="alert">
                {{ error }} <RouterLink to="/recuperar">Pedir otro enlace</RouterLink>
            </p>

            <BaseButton type="submit" variant="primary" :disabled="enviando">
                {{ enviando ? 'Guardando…' : 'Guardar contraseña' }}
            </BaseButton>
        </form>
    </MarcoAuth>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { Lock, Eye, EyeOff } from 'lucide-vue-next'
import MarcoAuth from '@/components/auth/MarcoAuth.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { restablecerPassword } from '@/services/authService'
import { mensajeDeErrorCuenta } from '@/utils/errores'

// El correo de recuperación trae ?token=...&email=... (lo arma el backend con FRONTEND_URL)
const route = useRoute()
const token = computed(() => String(route.query.token ?? ''))
const correo = computed(() => String(route.query.email ?? ''))
const enlaceCompleto = computed(() => Boolean(token.value && correo.value))

const password = ref('')
const confirmacion = ref('')
const ver = ref(false)
const tipo = computed(() => (ver.value ? 'text' : 'password'))
const errorCampos = ref('')
const error = ref('')
const exito = ref('')
const enviando = ref(false)

async function guardar() {
    error.value = ''
    // Mismas reglas que el backend, revisadas antes de enviar
    if (password.value.length < 8) errorCampos.value = 'La contraseña debe tener al menos 8 caracteres.'
    else if (password.value !== confirmacion.value) errorCampos.value = 'Las contraseñas no coinciden.'
    else errorCampos.value = ''
    if (errorCampos.value) return

    enviando.value = true
    try {
        exito.value = await restablecerPassword({ token: token.value, correo: correo.value, password: password.value })
        // Ya no hacen falta en memoria: no dejar la contraseña escrita en la pantalla
        password.value = ''
        confirmacion.value = ''
    } catch (err) {
        error.value = mensajeDeErrorCuenta(err)
    } finally {
        enviando.value = false
    }
}
</script>

<style scoped>
form,
.listo {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
    max-width: 480px;
}

.listo .btn {
    align-self: flex-start;
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

.ver {
    display: flex;
    border: none;
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
}

.ver:hover {
    color: var(--color-primary);
}

.aviso {
    padding: .7rem .9rem;
    border-radius: 10px;
    font-size: .9rem;
    overflow-wrap: anywhere;
}

.aviso a {
    color: inherit;
    font-weight: 700;
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
