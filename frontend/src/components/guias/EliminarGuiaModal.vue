<script setup>
import { ref, computed } from 'vue'
import { TriangleAlert } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { eliminarGuia } from '@/services/guiaService'

// Confirmación para borrar una guía (instructor en su grupo, o estudiante en su Modo Personal).
// Explica con números qué se pierde: el backend borra semanas, mini proyectos, entregas, chats
// guiados, avisos y el archivo, y no se puede deshacer.
const props = defineProps({
  guia: { type: Object, required: true },
  // 'grupo': la borra el instructor y afecta a sus aprendices. 'personal': la borra el estudiante
  contexto: { type: String, default: 'grupo', validator: (v) => ['grupo', 'personal'].includes(v) }
})
const emit = defineEmits(['cerrar', 'eliminada'])

const enviando = ref(false)
const error = ref('')

const semanas = computed(() => {
  const n = props.guia.semanas_totales
  if (!n) return 'sus semanas'
  return n === 1 ? 'su semana' : `sus ${n} semanas`
})

async function confirmar() {
  error.value = ''
  enviando.value = true
  try {
    await eliminarGuia(props.guia.id)
    emit('eliminada', props.guia.id)
  } catch (err) {
    const status = err.response?.status
    if (status === 403) error.value = err.response.data?.message ?? 'No puedes borrar esta guía.'
    else if (status === 404) error.value = 'Esta guía ya no existe. Recarga la página.'
    else if (!err.response) error.value = 'No se pudo conectar con el servidor. Revisa tu conexión.'
    else error.value = 'No se pudo borrar la guía. Intenta de nuevo.'
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <ModalBase :titulo="`¿Eliminar «${guia.nombre}»?`" :bloqueado="enviando" peligro @cerrar="emit('cerrar')">
    <p class="aviso">
      <TriangleAlert :size="20" aria-hidden="true" />
      <span>Esta acción <strong>no se puede deshacer</strong>.</span>
    </p>

    <p class="texto">Se borrará todo lo de esta guía:</p>
    <ul v-if="contexto === 'grupo'" class="lista">
      <li>{{ semanas }}, con sus temas, recomendaciones y mini proyectos;</li>
      <li>las <strong>entregas y evaluaciones</strong> que tus aprendices hicieron en esta guía (pierden ese avance);</li>
      <li>los chats de tus aprendices con la IA sobre esta guía;</li>
      <li>los avisos de esta guía y el archivo que subiste.</li>
    </ul>
    <ul v-else class="lista">
      <li>{{ semanas }}, con sus temas, recomendaciones y mini proyectos;</li>
      <li>tus <strong>entregas y evaluaciones</strong>, y la <strong>experiencia (XP)</strong> que ganaste en ella;</li>
      <li>tus chats con la IA sobre esta guía y el archivo que subiste.</li>
    </ul>

    <p v-if="contexto === 'grupo'" class="texto">El grupo y tus aprendices <strong>no</strong> se borran.</p>
    <p v-else class="texto">Tus otras guías <strong>no</strong> se tocan.</p>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <div class="acciones">
      <!-- El foco empieza en Cancelar: la opción segura, por si se pulsa Enter sin leer -->
      <button type="button" class="btn btn-secundario" data-foco-inicial :disabled="enviando" @click="emit('cerrar')">
        Cancelar
      </button>
      <button type="button" class="btn btn-peligro" :disabled="enviando" @click="confirmar">
        {{ enviando ? 'Eliminando…' : 'Eliminar guía' }}
      </button>
    </div>
  </ModalBase>
</template>

<style scoped>
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

.texto,
.lista {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.texto strong,
.lista strong {
  color: var(--color-text);
}

.lista {
  display: flex;
  flex-direction: column;
  gap: .2rem;
  margin: 0;
  padding-left: 1.2rem;
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
