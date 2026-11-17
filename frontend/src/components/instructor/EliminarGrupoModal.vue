<script setup>
import { ref, computed, useId } from 'vue'
import { TriangleAlert } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { eliminarGrupo } from '@/services/grupoService'
import { mensajeDeErrorGrupo } from '@/utils/errores'

// Confirmación para borrar un grupo. Es propia (no window.confirm) para poder explicar TODO lo que se
// borra: el backend elimina guías, semanas, mini proyectos, entregas, chats guiados y avisos del grupo.
// Se abre desde "Mis grupos" (la tarjeta trae guias_count y estudiantes_count) y desde el detalle del
// grupo (trae guias_count y la lista de estudiantes).
const props = defineProps({ grupo: { type: Object, required: true } })
const emit = defineEmits(['cerrar', 'eliminado'])

const idConfirmacion = useId()
const enviando = ref(false)
const error = ref('')
const confirmacion = ref('')

const totalGuias = computed(() => props.grupo.guias_count ?? null)
const totalAprendices = computed(() => props.grupo.estudiantes_count ?? props.grupo.estudiantes?.length ?? null)

// "3 guías", "1 aprendiz"… Sin el dato (null) se dice en general: "sus guías", "sus aprendices"
const textoGuias = computed(() => {
  const n = totalGuias.value
  if (n === null) return 'sus guías'
  return n === 1 ? 'su guía' : `sus ${n} guías`
})
const textoAprendices = computed(() => {
  const n = totalAprendices.value
  if (n === null) return 'los aprendices'
  return n === 1 ? 'el aprendiz inscrito' : `los ${n} aprendices inscritos`
})

// Para borrar hay que escribir el nombre del grupo: evita borrar un grupo entero por un clic sin querer.
// Se ignoran mayúsculas y espacios de los extremos
const nombreCorrecto = computed(
  () => confirmacion.value.trim().toLowerCase() === props.grupo.nombre.trim().toLowerCase()
)

async function confirmar() {
  if (!nombreCorrecto.value) return
  error.value = ''
  enviando.value = true
  try {
    await eliminarGrupo(props.grupo.id)
    emit('eliminado', props.grupo.id)
  } catch (err) {
    error.value = mensajeDeErrorGrupo(err)
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <ModalBase :titulo="`¿Eliminar «${grupo.nombre}»?`" :bloqueado="enviando" peligro @cerrar="emit('cerrar')">
    <p class="aviso">
      <TriangleAlert :size="20" aria-hidden="true" />
      <span>Esta acción <strong>no se puede deshacer</strong>.</span>
    </p>

    <p class="texto">Se borrará todo lo de este grupo:</p>
    <ul class="lista">
      <li><strong>{{ textoGuias }}</strong>, con sus semanas, temas, recomendaciones y mini proyectos;</li>
      <li>las <strong>entregas y evaluaciones</strong> de {{ textoAprendices }} en este grupo;</li>
      <li>los chats de los aprendices con la IA sobre esas guías;</li>
      <li>los avisos (notificaciones) del grupo y los archivos de las guías.</li>
    </ul>
    <p class="texto">
      El <strong>código de acceso</strong> deja de funcionar. Las cuentas de los aprendices <strong>no</strong>
      se borran: solo dejan de estar en el grupo, y conservan sus guías del Modo Personal.
    </p>

    <form class="confirmar" novalidate @submit.prevent="confirmar">
      <label :for="idConfirmacion">Para confirmar, escribe el nombre del grupo: <strong>{{ grupo.nombre }}</strong></label>
      <input :id="idConfirmacion" v-model="confirmacion" type="text" autocomplete="off" spellcheck="false"
        :disabled="enviando" />

      <p v-if="error" class="error" role="alert">{{ error }}</p>

      <div class="acciones">
        <!-- El foco empieza en Cancelar: la opción segura, por si se pulsa Enter sin leer -->
        <button type="button" class="btn btn-secundario" data-foco-inicial :disabled="enviando" @click="emit('cerrar')">
          Cancelar
        </button>
        <button type="submit" class="btn btn-peligro" :disabled="enviando || !nombreCorrecto">
          {{ enviando ? 'Eliminando…' : 'Eliminar grupo' }}
        </button>
      </div>
    </form>
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

.confirmar {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-xs);
  margin-top: var(--espacio-sm);
}

.confirmar label {
  color: var(--color-text-secondary);
  font-size: .9rem;
  overflow-wrap: anywhere;
}

.confirmar label strong {
  color: var(--color-text);
}

/* Mismo campo que los formularios de grupo (CrearGrupoModal), con el foco en rojo: es para borrar */
.confirmar input {
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

.confirmar input:focus {
  border-color: var(--color-danger);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-danger) 15%, transparent);
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
