<script setup>
import { reactive, onBeforeUnmount } from 'vue'
import { KeyRound, Info } from 'lucide-vue-next'
import { validarCambioPassword, MIN_PASSWORD } from '@/utils/validaciones'

// Formulario de cambio de contraseña. Solo valida y avisa ("cambiar"); la vista es quien llama al
// servicio y decide qué mensaje mostrar. Las contraseñas viven únicamente en estas variables
// locales: no se guardan en localStorage/sessionStorage ni se imprimen en consola.
const props = defineProps({
  enviando: { type: Boolean, default: false },
  errorExterno: { type: String, default: '' },  // error del servidor (por código HTTP)
  exito: { type: String, default: '' },         // mensaje de éxito
  esEjemplo: { type: Boolean, default: false }  // el endpoint real aún no existe: se avisa en pantalla
})
const emit = defineEmits(['cambiar'])

const CAMPOS = [
  { clave: 'actual', etiqueta: 'Contraseña actual', autocompletar: 'current-password' },
  { clave: 'nueva', etiqueta: 'Nueva contraseña', autocompletar: 'new-password' },
  { clave: 'confirmacion', etiqueta: 'Repetir nueva contraseña', autocompletar: 'new-password' }
]

const valores = reactive({ actual: '', nueva: '', confirmacion: '' })
const errores = reactive({ actual: '', nueva: '', confirmacion: '' })

function limpiar() {
  for (const c of CAMPOS) {
    valores[c.clave] = ''
    errores[c.clave] = ''
  }
}

function enviar() {
  if (props.enviando) return // seguro extra: el botón ya está deshabilitado
  Object.assign(errores, validarCambioPassword(valores))
  if (Object.values(errores).some(Boolean)) return
  emit('cambiar', { actual: valores.actual, nueva: valores.nueva })
}

// La vista lo llama cuando el cambio salió bien, para no dejar contraseñas escritas en pantalla
defineExpose({ limpiar })
// Al salir de la pestaña no queda ninguna contraseña en memoria del componente
onBeforeUnmount(limpiar)
</script>

<template>
  <section class="cambio">
    <h2><KeyRound :size="20" /> Cambiar contraseña</h2>

    <p v-if="esEjemplo" class="ejemplo" role="note">
      <Info :size="16" />
      <span>Función de ejemplo: todavía no se cambia la contraseña de verdad porque falta el endpoint
        <code>PATCH /usuario/password</code> en el backend.</span>
    </p>

    <!-- novalidate: validamos nosotros para mostrar el error junto al campo, no el globo del navegador -->
    <form novalidate @submit.prevent="enviar">
      <div v-for="c in CAMPOS" :key="c.clave" class="grupo">
        <label :for="`pw-${c.clave}`">{{ c.etiqueta }}</label>
        <input :id="`pw-${c.clave}`" v-model="valores[c.clave]" type="password" :autocomplete="c.autocompletar"
          :class="{ invalido: errores[c.clave] }" :aria-invalid="Boolean(errores[c.clave])" :disabled="enviando"
          @input="errores[c.clave] = ''" />
        <p v-if="errores[c.clave]" class="error" role="alert">{{ errores[c.clave] }}</p>
      </div>

      <p class="ayuda">La nueva contraseña debe tener al menos {{ MIN_PASSWORD }} caracteres.</p>

      <p v-if="errorExterno" class="error" role="alert">{{ errorExterno }}</p>
      <p v-if="exito" class="exito" role="status">{{ exito }}</p>

      <button type="submit" class="btn btn-primario" :disabled="enviando">
        {{ enviando ? 'Guardando…' : 'Cambiar contraseña' }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.cambio {
  padding: 1.2rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

h2 {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: .8rem;
  font-size: 1.05rem;
}

h2 svg {
  color: var(--color-primary-light);
}

.ejemplo {
  display: flex;
  gap: .5rem;
  margin-bottom: 1rem;
  padding: .6rem .8rem;
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-warning) 14%, transparent);
  color: var(--color-warning);
  font-size: .82rem;
}

.ejemplo svg {
  flex-shrink: 0;
  margin-top: .15rem;
}

form {
  display: flex;
  flex-direction: column;
  gap: .9rem;
  max-width: 420px;
}

.grupo {
  display: flex;
  flex-direction: column;
  gap: .4rem;
}

label {
  font-size: .9rem;
  font-weight: 600;
}

input {
  padding: .75rem .9rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-background);
  color: var(--color-text);
  transition: border-color .2s, box-shadow .2s;
}

input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 25%, transparent);
}

input.invalido {
  border-color: var(--color-danger);
}

input:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.ayuda {
  color: var(--color-text-secondary);
  font-size: .82rem;
}

.error {
  color: var(--color-danger);
  font-size: .82rem;
}

.exito {
  color: var(--color-success);
  font-size: .9rem;
}

.btn {
  align-self: flex-start;
}

@media (max-width: 520px) {
  .btn {
    align-self: stretch;
  }
}
</style>
