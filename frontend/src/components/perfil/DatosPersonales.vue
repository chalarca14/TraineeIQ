<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { User, Mail, Shield, Pencil } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import { ETIQUETA_ROL } from '@/utils/usuario'

// Datos de la cuenta. Solo el NOMBRE se edita (lápiz): el correo es con lo que se inicia sesión (también
// con Google) y cambiarlo sin verificarlo podría dejar a alguien fuera de su cuenta.
// La petición la hace la vista (Perfil.vue); este componente solo pide el nombre y lo valida.
const props = defineProps({
  nombre: { type: String, default: '' },
  correo: { type: String, default: '' },
  rol: { type: String, default: '' },
  guardando: { type: Boolean, default: false },
  error: { type: String, default: '' },
  exito: { type: String, default: '' }
})
const emit = defineEmits(['guardar'])

const editando = ref(false)
const borrador = ref('')
const errorLocal = ref('')
const campo = ref(null)

const filasFijas = computed(() => [
  { icono: Mail, etiqueta: 'Correo electrónico', valor: props.correo },
  { icono: Shield, etiqueta: 'Rol', valor: ETIQUETA_ROL[props.rol] ?? props.rol }
])

async function empezar() {
  borrador.value = props.nombre
  errorLocal.value = ''
  editando.value = true
  // El foco va al campo para escribir de una vez (y para quien usa teclado)
  await nextTick()
  campo.value?.focus()
}

// Mismas reglas que el backend (2 a 255 caracteres), revisadas antes de enviar
function guardar() {
  const nombre = borrador.value.trim()
  if (nombre.length < 2) errorLocal.value = 'El nombre debe tener al menos 2 caracteres.'
  else if (nombre.length > 255) errorLocal.value = 'El nombre es demasiado largo (máximo 255 caracteres).'
  else if (nombre === props.nombre) { editando.value = false; return }
  else errorLocal.value = ''
  if (!errorLocal.value) emit('guardar', nombre)
}

// Cuando la vista confirma que se guardó, se vuelve a la lectura
watch(() => props.exito, (mensaje) => { if (mensaje) editando.value = false })
</script>

<template>
  <section class="datos">
    <h2><User :size="20" /> Datos personales</h2>

    <dl>
      <div class="fila">
        <Medalla :icono="User" :size="36" />
        <div class="texto">
          <dt><label for="campo-nombre">Nombre completo</label></dt>
          <dd v-if="!editando">{{ nombre || '—' }}</dd>
          <dd v-else>
            <form class="edicion" novalidate @submit.prevent="guardar" @keydown.esc="editando = false">
              <input id="campo-nombre" ref="campo" v-model="borrador" type="text" maxlength="255" autocomplete="name"
                :aria-invalid="Boolean(errorLocal || error)" />
              <div class="acciones">
                <button type="submit" class="btn btn-primario btn-sm" :disabled="guardando">
                  {{ guardando ? 'Guardando…' : 'Guardar' }}
                </button>
                <button type="button" class="btn btn-secundario btn-sm" :disabled="guardando" @click="editando = false">
                  Cancelar
                </button>
              </div>
            </form>
          </dd>
        </div>
        <button v-if="!editando" type="button" class="lapiz" aria-label="Editar nombre" @click="empezar">
          <Pencil :size="17" />
        </button>
      </div>

      <div v-for="f in filasFijas" :key="f.etiqueta" class="fila">
        <Medalla :icono="f.icono" :size="36" />
        <div class="texto">
          <dt>{{ f.etiqueta }}</dt>
          <dd>{{ f.valor || '—' }}</dd>
        </div>
      </div>
    </dl>

    <p v-if="editando && (errorLocal || error)" class="aviso aviso--error" role="alert">{{ errorLocal || error }}</p>
    <p v-else-if="exito" class="aviso aviso--exito" role="status">{{ exito }}</p>
    <p class="nota">El correo no se puede cambiar: es con el que inicias sesión.</p>
  </section>
</template>

<style scoped>
.datos {
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

.fila {
  display: flex;
  align-items: center;
  gap: .9rem;
  padding: .8rem 0;
  border-bottom: 1px solid var(--color-border);
}

.texto {
  flex: 1;
  min-width: 0;
}

dt {
  color: var(--color-text-secondary);
  font-size: .8rem;
}

dd {
  overflow-wrap: anywhere;
  font-weight: 600;
}

.lapiz {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border: 1px solid var(--color-card);
  border-radius: 10px;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
}

.lapiz:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.edicion {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
  margin-top: .3rem;
}

.edicion input {
  flex: 1 1 200px;
  min-width: 0;
  padding: .55rem .75rem;
  border: 1px solid var(--color-card);
  border-radius: 10px;
  background: var(--color-background);
  color: var(--color-text);
  font: inherit;
}

.edicion input:focus {
  border-color: var(--color-primary);
  outline: none;
}

.acciones {
  display: flex;
  gap: .5rem;
}

.aviso {
  margin-top: .8rem;
  padding: .6rem .8rem;
  border-radius: 10px;
  font-size: .88rem;
  overflow-wrap: anywhere;
}

.aviso--exito {
  background: color-mix(in srgb, var(--color-success) 14%, transparent);
  color: var(--color-success);
}

.aviso--error {
  background: color-mix(in srgb, var(--color-danger) 12%, transparent);
  color: var(--color-danger);
}

.nota {
  margin-top: .8rem;
  color: var(--color-text-secondary);
  font-size: .82rem;
}
</style>
