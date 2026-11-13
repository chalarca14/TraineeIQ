<script setup>
import { ref } from 'vue'
import { Mail, Lock, Eye, EyeOff } from 'lucide-vue-next'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseLink from '@/components/ui/BaseLink.vue'

// Formulario del login: correo, contraseña y su validación. Salió de LoginForm.vue (pasaba de 300
// líneas). Este componente solo valida y avisa con "enviar"; la llamada al backend la hace LoginForm,
// que es quien sabe a qué rol entra y a dónde llevar al usuario después.
defineProps({
  cargando: { type: Boolean, default: false },
  // Error general del backend ("Usuario no registrado.", "Credenciales incorrectas"…)
  error: { type: String, default: '' }
})
const emit = defineEmits(['enviar'])

const correo = ref('')
const password = ref('')
const mostrarPassword = ref(false)
const errores = ref({ correo: '', password: '' })

const validarCorreo = (texto) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(texto)

// Se valida antes de enviar: así no se llama al backend con un formulario que ya sabemos que está mal
function enviar() {
  errores.value = { correo: '', password: '' }

  if (!correo.value) errores.value.correo = 'El correo es obligatorio.'
  else if (!validarCorreo(correo.value)) errores.value.correo = 'Ingresa un correo válido.'

  if (!password.value) errores.value.password = 'La contraseña es obligatoria.'
  else if (password.value.length < 8) errores.value.password = 'La contraseña debe tener al menos 8 caracteres.'

  if (errores.value.correo || errores.value.password) return
  emit('enviar', { correo: correo.value, password: password.value })
}
</script>

<template>
  <form @submit.prevent="enviar">

    <div class="field-group">
      <BaseInput compacto v-model="correo" label="Correo electrónico" placeholder="Ingresa tu correo">
        <template #icon>
          <Mail :size="18" stroke="white" />
        </template>
      </BaseInput>
      <p class="input-error">{{ errores.correo }}</p>
    </div>

    <div class="field-group">
      <BaseInput compacto v-model="password" :type="mostrarPassword ? 'text' : 'password'" label="Contraseña"
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
      <p class="input-error">{{ errores.password }}</p>
    </div>

    <!-- Mensaje del backend: se muestra como texto (nunca v-html) -->
    <p v-if="error" class="error-message">
      {{ error }}
    </p>

    <div class="forgot-password">
      <BaseLink to="/recuperar">¿Olvidaste tu contraseña?</BaseLink>
    </div>

    <BaseButton type="submit" variant="primary" :disabled="cargando">
      {{ cargando ? "Iniciando sesión..." : "Iniciar sesión →" }}
    </BaseButton>

  </form>
</template>

<style scoped>
/* Login compacto (como el registro): con el botón de Google ya no cabía en una pantalla de
   portátil y aparecía scroll. Campos de 44 px y espacios de variables.css */
form {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  width: 100%;
  max-width: 480px;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: .3rem;         /* espacio chico entre el input y su propio mensaje de error */
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
  margin-top: -.6rem;
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

.error-message {
  color: var(--color-danger);
  font-size: .9rem;
  margin-bottom: .2rem;
}

.input-error {
  margin-top: 0;        /* el gap del field-group ya separa el input de su error */
  min-height: 1rem;
  color: var(--color-danger);
  font-size: .82rem;
  font-weight: 500;
}

@media (max-height: 800px) {
  .forgot-password a {
    font-size: .8rem;
  }
}

@media (max-width: 700px) {
  form {
    max-width: 100%;
  }
}
</style>
