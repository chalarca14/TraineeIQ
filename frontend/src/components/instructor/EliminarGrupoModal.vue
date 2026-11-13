<script setup>
import { ref } from 'vue'
import { TriangleAlert } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { eliminarGrupo } from '@/services/grupoService'
import { mensajeDeErrorGrupo } from '@/utils/errores'

// Confirmación para borrar un grupo. Es propia (no window.confirm) para poder explicar TODO lo que se
// borra: el backend elimina en cascada guías, semanas, mini proyectos, entregas y avisos del grupo.
const props = defineProps({ grupo: { type: Object, required: true } })
const emit = defineEmits(['cerrar', 'eliminado'])

const enviando = ref(false)
const error = ref('')

async function confirmar() {
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
      <li>sus guías, con sus semanas y mini proyectos;</li>
      <li>las entregas de los aprendices;</li>
      <li>los avisos (notificaciones) del grupo.</li>
    </ul>
    <p class="texto">Las cuentas de los aprendices <strong>no</strong> se borran: solo dejan de estar en el grupo.</p>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <div class="acciones">
      <!-- El foco empieza en Cancelar: la opción segura, por si se pulsa Enter sin leer -->
      <button type="button" class="btn btn-secundario" data-foco-inicial :disabled="enviando" @click="emit('cerrar')">
        Cancelar
      </button>
      <button type="button" class="btn btn-peligro" :disabled="enviando" @click="confirmar">
        {{ enviando ? 'Eliminando…' : 'Eliminar grupo' }}
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

.texto strong {
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
