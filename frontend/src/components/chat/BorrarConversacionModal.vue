<script setup>
import { ref, computed, onMounted } from 'vue'
import { TriangleAlert } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { contarConversaciones, eliminarConversacion, eliminarHistorial } from '@/services/chatIaService'
import { mensajeDeError } from '@/utils/errores'

// Confirmación para borrar chats con la IA. Sirve para los dos casos, según la prop que llegue:
// - `conversacion`: borra solo esa conversación (papelera de cada fila del panel o del Historial);
// - `tipo` ('libre' o 'guiada'): borra todo el historial de ese tipo de chat. Antes de confirmar
//   pregunta al backend cuántas hay, para decir con número qué se pierde.
// Llama a la API aquí mismo (como EliminarGuiaModal) y avisa con 'borrado' cuando termina.
const props = defineProps({
  conversacion: { type: Object, default: null },
  tipo: { type: String, default: null, validator: (v) => v === null || ['libre', 'guiada'].includes(v) }
})
const emit = defineEmits(['cerrar', 'borrado'])

const enviando = ref(false)
const error = ref('')
const total = ref(null)      // cuántas se borrarán (solo al borrar el historial); null = aún no se sabe
const contando = ref(false)

const deUna = computed(() => Boolean(props.conversacion))
const nombreTipo = computed(() => (props.tipo === 'guiada' ? 'chat guía' : 'chat libre'))

const titulo = computed(() => (deUna.value
  ? '¿Borrar esta conversación?'
  : `¿Borrar tu historial del ${nombreTipo.value}?`))

// "tus 4 conversaciones", "tu única conversación"… Si no se pudo contar, se dice en general
const textoTotal = computed(() => {
  const n = total.value
  if (n === null) return `todas tus conversaciones del ${nombreTipo.value}`
  return n === 1 ? `tu única conversación del ${nombreTipo.value}` : `tus ${n} conversaciones del ${nombreTipo.value}`
})
const sinNada = computed(() => !deUna.value && total.value === 0)

onMounted(async () => {
  if (deUna.value) return
  contando.value = true
  try {
    total.value = await contarConversaciones(props.tipo)
  } catch {
    total.value = null // no es grave: el aviso se dice sin número y se puede borrar igual
  } finally {
    contando.value = false
  }
})

async function confirmar() {
  error.value = ''
  enviando.value = true
  try {
    if (deUna.value) {
      await eliminarConversacion(props.conversacion.id)
      emit('borrado', { id: props.conversacion.id })
    } else {
      const eliminadas = await eliminarHistorial(props.tipo)
      emit('borrado', { tipo: props.tipo, eliminadas })
    }
  } catch (err) {
    const status = err.response?.status
    if (status === 403) error.value = 'Esta conversación no es tuya.'
    else if (status === 404) error.value = 'Esta conversación ya no existe. Recarga la página.'
    else error.value = mensajeDeError(err)
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <ModalBase :titulo="titulo" :bloqueado="enviando" peligro @cerrar="emit('cerrar')">
    <p v-if="!sinNada" class="aviso">
      <TriangleAlert :size="20" aria-hidden="true" />
      <span>Esta acción <strong>no se puede deshacer</strong>.</span>
    </p>

    <p v-if="deUna" class="texto">
      Se borrarán <strong>todos los mensajes</strong> de esta conversación con la IA. Tus otras conversaciones
      <strong>no</strong> se tocan.
    </p>
    <p v-else-if="contando" class="texto">Contando tus conversaciones…</p>
    <p v-else-if="sinNada" class="texto">No tienes conversaciones del {{ nombreTipo }} para borrar.</p>
    <template v-else>
      <p class="texto">Se borrarán <strong>{{ textoTotal }}</strong>, con todos sus mensajes.</p>
      <p class="texto">Tus conversaciones del {{ tipo === 'guiada' ? 'chat libre' : 'chat guía' }} <strong>no</strong> se tocan.</p>
    </template>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <div class="acciones">
      <!-- El foco empieza en Cancelar: la opción segura, por si se pulsa Enter sin leer -->
      <button type="button" class="btn btn-secundario" data-foco-inicial :disabled="enviando" @click="emit('cerrar')">
        {{ sinNada ? 'Cerrar' : 'Cancelar' }}
      </button>
      <button v-if="!sinNada" type="button" class="btn btn-peligro" :disabled="enviando || contando" @click="confirmar">
        {{ enviando ? 'Borrando…' : deUna ? 'Borrar conversación' : 'Borrar historial' }}
      </button>
    </div>
  </ModalBase>
</template>

<style scoped>
/* Mismos estilos que EliminarGuiaModal y EliminarGrupoModal */
.aviso {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md);
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-danger) 12%, transparent);
  color: var(--color-danger);
  font-size: .9rem;
}

.aviso svg {
  flex-shrink: 0;
}

.texto {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.texto strong {
  color: var(--color-text);
}

.error {
  color: var(--color-danger);
  font-size: .85rem;
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
