<script setup>
import { ref, useId } from 'vue'
import ModalBase from '@/components/ui/ModalBase.vue'
import { crearGrupo } from '@/services/grupoService'
import { mensajeDeErrorGrupo } from '@/utils/errores'

// Crear un grupo: nombre (obligatorio) y descripción (opcional). El código de acceso lo genera
// el backend; por eso aquí no se pide.
const emit = defineEmits(['cerrar', 'creado'])

const idNombre = useId()
const idDescripcion = useId()
const idErrorNombre = useId()

const nombre = ref('')
const descripcion = ref('')
const errores = ref({})
const error = ref('')
const enviando = ref(false)
const campoNombre = ref(null)

// Mismas reglas que el backend (nombre obligatorio, máximo 255), revisadas antes de enviar
function validar() {
  errores.value = {}
  const limpio = nombre.value.trim()
  if (!limpio) errores.value.nombre = 'Escribe el nombre del grupo.'
  else if (limpio.length > 255) errores.value.nombre = 'El nombre es demasiado largo (máximo 255 caracteres).'
  return !errores.value.nombre
}

async function enviar() {
  error.value = ''
  if (!validar()) {
    campoNombre.value?.focus()
    return
  }

  enviando.value = true
  try {
    const grupo = await crearGrupo({
      nombre: nombre.value.trim(),
      descripcion: descripcion.value.trim() || null
    })
    emit('creado', grupo)
  } catch (err) {
    // 422: Laravel manda { errors: { campo: ["mensaje"] } }; cada error va junto a su campo
    const porCampo = err.response?.status === 422 ? err.response.data?.errors : null
    if (porCampo) {
      errores.value = { nombre: porCampo.nombre?.[0], descripcion: porCampo.descripcion?.[0] }
    } else {
      error.value = mensajeDeErrorGrupo(err)
    }
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <ModalBase titulo="Crear grupo" :bloqueado="enviando" @cerrar="emit('cerrar')">
    <form class="formulario" novalidate @submit.prevent="enviar">
      <p class="ayuda">Al crearlo te daremos un código de acceso para compartir con tus aprendices.</p>

      <label :for="idNombre">Nombre del grupo</label>
      <input :id="idNombre" ref="campoNombre" v-model="nombre" data-foco-inicial type="text" maxlength="255"
        autocomplete="off" placeholder="Ej: ADSO – Ficha 2873645" :aria-invalid="!!errores.nombre"
        :aria-describedby="idErrorNombre" />
      <p :id="idErrorNombre" class="error">{{ errores.nombre }}</p>

      <label :for="idDescripcion">Descripción <span class="opcional">(opcional)</span></label>
      <textarea :id="idDescripcion" v-model="descripcion" rows="3"
        placeholder="Ej: Grupo de formación en Análisis y Desarrollo de Software"
        :aria-invalid="!!errores.descripcion"></textarea>
      <p class="error">{{ errores.descripcion }}</p>

      <p v-if="error" class="error error--general" role="alert">{{ error }}</p>

      <div class="acciones">
        <button type="button" class="btn btn-secundario" :disabled="enviando" @click="emit('cerrar')">Cancelar</button>
        <button type="submit" class="btn btn-primario" :disabled="enviando">
          {{ enviando ? 'Creando…' : 'Crear grupo' }}
        </button>
      </div>
    </form>
  </ModalBase>
</template>

<style scoped>
.formulario {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-xs);
}

.ayuda {
  margin-bottom: var(--espacio-sm);
  color: var(--color-text-secondary);
  font-size: .9rem;
}

label {
  font-size: .95rem;
  font-weight: 600;
  color: var(--color-text);
}

.opcional {
  color: var(--color-text-secondary);
  font-weight: 400;
}

input,
textarea {
  width: 100%;
  min-height: 44px;
  padding: .7rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  outline: none;
  background: var(--color-background);
  color: var(--color-text);
  font: inherit;
  resize: vertical;
}

input:focus,
textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

[aria-invalid='true'] {
  border-color: var(--color-danger);
}

.error {
  min-height: 1.1rem;
  color: var(--color-danger);
  font-size: .85rem;
}

.error--general {
  min-height: 0;
}

.acciones {
  display: flex;
  gap: var(--espacio-sm);
  justify-content: flex-end;
  margin-top: var(--espacio-sm);
}

@media (max-width: 480px) {
  .acciones {
    flex-direction: column-reverse;
  }
}
</style>
