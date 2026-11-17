<script setup>
import { ref } from 'vue'
import { LockOpen, TriangleAlert } from 'lucide-vue-next'
import ModalBase from '@/components/ui/ModalBase.vue'
import { liberarSemana } from '@/services/grupoService'
import { mensajeDeErrorGrupo } from '@/utils/errores'

// Confirmación para liberar la siguiente semana del grupo. Ventana propia (no window.confirm) para
// explicar bien el efecto: se abre para TODOS los estudiantes en TODAS las guías, y no se deshace.
const props = defineProps({
  grupoId: { type: Number, required: true },
  numero: { type: Number, required: true }
})
const emit = defineEmits(['cerrar', 'liberada'])

const enviando = ref(false)
const error = ref('')

async function confirmar() {
  error.value = ''
  enviando.value = true
  try {
    // El backend responde con la semana actual ya guardada: esa es la que se muestra
    const semanaActual = await liberarSemana(props.grupoId, props.numero)
    emit('liberada', semanaActual ?? props.numero)
  } catch (err) {
    error.value = mensajeDeErrorGrupo(err)
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <ModalBase :titulo="`¿Liberar la semana ${numero}?`" :bloqueado="enviando" @cerrar="emit('cerrar')">
    <p class="texto">
      Se abrirá para <strong>todos los estudiantes</strong> en <strong>todas las guías</strong> del grupo:
      verán su contenido y sus mini proyectos, y les llegará un aviso.
    </p>
    <p class="aviso">
      <TriangleAlert :size="18" aria-hidden="true" />
      <span>Desde aquí no se puede deshacer.</span>
    </p>

    <p v-if="error" class="error" role="alert">{{ error }}</p>

    <div class="acciones">
      <button type="button" class="btn btn-secundario" data-foco-inicial :disabled="enviando" @click="emit('cerrar')">
        Cancelar
      </button>
      <button type="button" class="btn btn-primario" :disabled="enviando" @click="confirmar">
        <LockOpen :size="16" aria-hidden="true" /> {{ enviando ? 'Liberando…' : `Liberar semana ${numero}` }}
      </button>
    </div>
  </ModalBase>
</template>

<style scoped>
.texto {
  color: var(--color-text-secondary);
  font-size: .92rem;
  line-height: 1.6;
}

.texto strong {
  color: var(--color-text);
}

.aviso {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md);
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-warning) 12%, transparent);
  color: var(--color-warning);
  font-size: .88rem;
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
