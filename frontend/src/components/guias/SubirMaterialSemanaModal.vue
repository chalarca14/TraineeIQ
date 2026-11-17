<script setup>
import { ref, useId } from 'vue'
import { FileUp } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { subirMaterialSemana } from '@/services/guiaService'
import { MAX_MB_PDF, MAX_MB_EXCEL, TIPOS_ACEPTADOS, errorDeArchivoGuia } from '@/utils/archivoGuia'

// Ventana para adjuntar una guía detallada (PDF o Excel .xlsx) a UNA semana de la planeación.
// Solo la abre el instructor dueño de una guía de grupo. El backend lee el texto del archivo; la IA
// lo usa después, junto con la planeación, al preparar esa semana.
const props = defineProps({
  guiaId: { type: Number, required: true },
  numero: { type: Number, required: true }
})
// subida: la respuesta del backend, { message, material, semana_lista }
const emit = defineEmits(['cerrar', 'subida'])

const idNombre = useId()
const idArchivo = useId()

const nombre = ref('')
const archivo = ref(null)
const errores = ref({})
const error = ref('')
const enviando = ref(false)

// Guarda el archivo elegido y borra el error viejo del campo
function elegirArchivo(evento) {
  archivo.value = evento.target.files[0] ?? null
  errores.value.archivo = ''
}

// Mismas reglas que el backend, revisadas antes de enviar para no gastar la subida
function validar() {
  const e = {}
  if (!nombre.value.trim()) e.nombre = 'Escribe el nombre de la guía.'
  else if (nombre.value.trim().length > 255) e.nombre = 'El nombre es demasiado largo (máximo 255 caracteres).'

  const errorArchivo = errorDeArchivoGuia(archivo.value)
  if (errorArchivo) e.archivo = errorArchivo

  errores.value = e
  return Object.keys(e).length === 0
}

// Envía la guía y traduce cada respuesta de error a un mensaje amigable
async function enviar() {
  error.value = ''
  if (!validar()) return

  enviando.value = true
  try {
    const datos = await subirMaterialSemana(props.guiaId, props.numero, {
      nombre: nombre.value.trim(),
      archivo: archivo.value
    })
    emit('subida', datos)
  } catch (err) {
    const status = err.response?.status
    const datos = err.response?.data
    if (status === 422 && datos?.errors) {
      // Laravel: { errors: { campo: ["mensaje"] } }, cada error junto a su campo
      errores.value = { nombre: datos.errors.nombre?.[0], archivo: datos.errors.archivo?.[0] }
    } else if (status === 422) {
      // Sin texto legible, archivo repetido, tope de 5 guías o texto demasiado largo: el backend lo explica
      error.value = datos?.message ?? 'No pudimos usar ese archivo.'
    } else if (status === 403) {
      error.value = datos?.message ?? 'Solo el instructor del grupo puede agregar guías a esta semana.'
    } else if (err.code === 'ECONNABORTED') {
      error.value = 'La lectura de la guía tardó demasiado. Intenta de nuevo.'
    } else if (!err.response) {
      error.value = 'No se pudo conectar con el servidor. Revisa tu conexión.'
    } else {
      error.value = 'No se pudo subir la guía. Intenta de nuevo.'
    }
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <ModalBase :titulo="`Agregar guía a la semana ${numero}`" :bloqueado="enviando" @cerrar="emit('cerrar')">
    <form class="formulario" novalidate @submit.prevent="enviar">
      <p class="ayuda">La IA usará esta guía, junto con la planeación, para las recomendaciones y los mini proyectos de esta semana.</p>

      <label :for="idNombre">Nombre de la guía</label>
      <input :id="idNombre" v-model="nombre" data-foco-inicial type="text" maxlength="255" autocomplete="off"
        placeholder="Ej: Guía de formularios en HTML" :aria-invalid="!!errores.nombre" />
      <p class="error">{{ errores.nombre }}</p>

      <label :for="idArchivo">Archivo <span class="opcional">(PDF hasta {{ MAX_MB_PDF }} MB o Excel .xlsx hasta {{ MAX_MB_EXCEL }} MB)</span></label>
      <label class="archivo" :class="{ 'archivo--lleno': archivo, 'archivo--error': errores.archivo }">
        <FileUp :size="22" aria-hidden="true" />
        <span>{{ archivo ? archivo.name : 'Elegir el PDF o el Excel de la guía' }}</span>
        <input :id="idArchivo" type="file" :accept="TIPOS_ACEPTADOS" class="oculto" @change="elegirArchivo" />
      </label>
      <p class="error" role="alert">{{ errores.archivo }}</p>

      <p v-if="error" class="error error--general" role="alert">{{ error }}</p>
      <p v-if="enviando" class="ayuda" role="status">Leyendo la guía… puede tardar unos segundos.</p>

      <div class="acciones">
        <button type="button" class="btn btn-secundario" :disabled="enviando" @click="emit('cerrar')">Cancelar</button>
        <button type="submit" class="btn btn-primario" :disabled="enviando">
          {{ enviando ? 'Subiendo…' : 'Agregar guía' }}
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

input[type='text'] {
  width: 100%;
  min-height: 44px;
  padding: .7rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  outline: none;
  background: var(--color-background);
  color: var(--color-text);
  font: inherit;
  font-family: var(--fuente-campos);
}

input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

[aria-invalid='true'] {
  border-color: var(--color-danger);
}

/* Todo el recuadro abre el selector (es la etiqueta del input oculto) */
.archivo {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  min-height: 56px;
  padding: var(--espacio-sm) var(--espacio-md);
  border: 1px dashed var(--color-card);
  border-radius: 12px;
  background: var(--color-background);
  color: var(--color-text-secondary);
  font-weight: 400;
  cursor: pointer;
  overflow-wrap: anywhere;
}

.archivo:hover,
.archivo:focus-within {
  border-color: var(--color-primary);
}

.archivo--lleno {
  border-style: solid;
  color: var(--color-text);
}

.archivo--error {
  border-color: var(--color-danger);
}

.archivo svg {
  flex-shrink: 0;
  color: var(--color-primary-light);
}

/* Oculto a la vista, pero recibe el foco con Tab y funciona con lector de pantalla */
.oculto {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
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
