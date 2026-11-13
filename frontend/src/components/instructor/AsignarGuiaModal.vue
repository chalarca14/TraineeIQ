<script setup>
import { ref, useId } from 'vue'
import { FileUp, Info } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { asignarGuia } from '@/services/guiaService'

// Asignar una guía (PDF) al grupo. El backend lee el PDF y lo divide en semanas; el contenido de
// cada semana se genera DESPUÉS, al abrir la guía (cola de stores/contenido.js).
const props = defineProps({ grupoId: { type: Number, required: true } })
// subida: { guia, yaExistia }
const emit = defineEmits(['cerrar', 'subida'])

const MAX_MB = 10
const idNombre = useId()
const idArchivo = useId()
const idSemanas = useId()

const nombre = ref('')
const archivo = ref(null)
const semanas = ref('')
const errores = ref({})
const error = ref('')
const enviando = ref(false)

function elegirArchivo(evento) {
  archivo.value = evento.target.files[0] ?? null
  // Un error viejo ("Selecciona un PDF") no debe quedarse después de elegir uno
  errores.value.archivo = ''
}

// Mismas reglas que el backend, revisadas antes de enviar (y de gastar la IA)
function validar() {
  const e = {}
  if (!nombre.value.trim()) e.nombre = 'Escribe el nombre de la guía.'
  else if (nombre.value.trim().length > 255) e.nombre = 'El nombre es demasiado largo (máximo 255 caracteres).'

  if (!archivo.value) e.archivo = 'Selecciona el PDF de la guía.'
  else if (archivo.value.type !== 'application/pdf') e.archivo = 'El archivo debe ser un PDF.'
  else if (archivo.value.size > MAX_MB * 1024 * 1024) e.archivo = `El PDF no puede pesar más de ${MAX_MB} MB.`

  // Opcional: vacío = lo decide el backend según el PDF
  const n = Number(semanas.value)
  if (semanas.value !== '' && (!Number.isInteger(n) || n < 1 || n > 52)) {
    e.semanas = 'Escribe un número de semanas entre 1 y 52, o déjalo vacío.'
  }
  errores.value = e
  return Object.keys(e).length === 0
}

async function enviar() {
  error.value = ''
  if (!validar()) return

  enviando.value = true
  try {
    const resultado = await asignarGuia({
      nombre: nombre.value.trim(),
      archivo: archivo.value,
      grupoId: props.grupoId,
      semanasTotales: semanas.value === '' ? null : Number(semanas.value)
    })
    emit('subida', resultado)
  } catch (err) {
    const status = err.response?.status
    const datos = err.response?.data
    if (status === 422 && datos?.errors) {
      // Laravel: { errors: { campo: ["mensaje"] } }. Cada error va junto a su campo
      errores.value = {
        nombre: datos.errors.nombre?.[0],
        archivo: datos.errors.archivo?.[0],
        semanas: datos.errors.semanas_totales?.[0]
      }
      if (datos.errors.grupo_id) error.value = datos.errors.grupo_id[0]
    } else if (status === 422) {
      // Por ejemplo, un PDF escaneado sin texto: el backend explica qué pasó
      error.value = datos?.message ?? 'No pudimos leer texto en ese PDF.'
    } else if (status === 403) {
      error.value = 'No tienes acceso a este grupo.'
    } else if (status === 503) {
      error.value = datos?.message ?? 'La IA está ocupada, intenta en un momento.'
    } else if (err.code === 'ECONNABORTED') {
      error.value = 'La lectura del PDF tardó demasiado. Intenta de nuevo.'
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
  <ModalBase titulo="Asignar guía al grupo" :bloqueado="enviando" @cerrar="emit('cerrar')">
    <form class="formulario" novalidate @submit.prevent="enviar">
      <!-- Igual que "Crear grupo": una frase bajo el título da aire y explica qué pasa al asignar -->
      <p class="ayuda">La guía se divide en semanas y la IA prepara el contenido de cada semana.</p>

      <label :for="idNombre">Nombre de la guía</label>
      <input :id="idNombre" v-model="nombre" data-foco-inicial type="text" maxlength="255" autocomplete="off"
        placeholder="Ej: Introducción a HTML y CSS" :aria-invalid="!!errores.nombre" />
      <p class="error">{{ errores.nombre }}</p>

      <label :for="idArchivo">Archivo PDF <span class="opcional">(máximo {{ MAX_MB }} MB)</span></label>
      <label class="archivo" :class="{ 'archivo--lleno': archivo, 'archivo--error': errores.archivo }">
        <FileUp :size="22" aria-hidden="true" />
        <span>{{ archivo ? archivo.name : 'Elegir el PDF de la guía' }}</span>
        <input :id="idArchivo" type="file" accept="application/pdf" class="oculto" @change="elegirArchivo" />
      </label>
      <p class="error" role="alert">{{ errores.archivo }}</p>

      <label :for="idSemanas">Semanas totales <span class="opcional">(opcional, de 1 a 52)</span></label>
      <input :id="idSemanas" v-model="semanas" type="number" min="1" max="52" inputmode="numeric"
        placeholder="Si lo dejas vacío, se calcula según la guía" :aria-invalid="!!errores.semanas" />
      <p class="error">{{ errores.semanas }}</p>

      <!-- Se ve ANTES de subir: el PDF original no se conserva (el servidor no tiene disco persistente) -->
      <p class="aviso-pdf" role="note">
        <Info :size="18" aria-hidden="true" />
        <span>TraineeIQ guarda el texto de la guía, no el PDF original. Conserva tu copia del PDF.</span>
      </p>

      <p v-if="error" class="error error--general" role="alert">{{ error }}</p>

      <div class="acciones">
        <button type="button" class="btn btn-secundario" :disabled="enviando" @click="emit('cerrar')">Cancelar</button>
        <button type="submit" class="btn btn-primario" :disabled="enviando">
          {{ enviando ? 'Leyendo el PDF…' : 'Asignar guía' }}
        </button>
      </div>
      <p v-if="enviando" class="espera" role="status">Esto puede tardar hasta un par de minutos. No cierres esta ventana.</p>
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

input[type='text'],
input[type='number'] {
  width: 100%;
  min-height: 44px;
  padding: .7rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  outline: none;
  background: var(--color-background);
  color: var(--color-text);
  font: inherit;
}

input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

[aria-invalid='true'] {
  border-color: var(--color-danger);
}

/* Zona del archivo: todo el recuadro abre el selector (es la etiqueta del input oculto) */
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

/* Oculto a la vista, pero sigue recibiendo el foco con Tab y funciona con lector de pantalla */
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

.aviso-pdf {
  display: flex;
  align-items: flex-start;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md);
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-info) 12%, transparent);
  color: var(--color-text-secondary);
  font-size: .85rem;
}

.aviso-pdf svg {
  flex-shrink: 0;
  color: var(--color-info);
}

.acciones {
  display: flex;
  gap: var(--espacio-sm);
  justify-content: flex-end;
  margin-top: var(--espacio-sm);
}

.espera {
  color: var(--color-text-secondary);
  font-size: .82rem;
  text-align: right;
}

@media (max-width: 480px) {
  .acciones {
    flex-direction: column-reverse;
  }

  .espera {
    text-align: center;
  }
}
</style>
