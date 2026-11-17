<script setup>
import { ref, computed, watch, onMounted, useId } from 'vue'
import { Plus } from 'lucide-vue-next'
import { listarGuias } from '@/services/guiaService'
import { useModoStore } from '@/stores/modo'

// "Nueva conversación" con su tipo: Chat libre (sin guía) o Chat guía (sobre una guía elegida).
// No crea la conversación aquí: solo lleva a la ruta del chat (/estudiante/ia o ?guiaId=). La conversación
// se crea al enviar el primer mensaje, así abrir un chat y arrepentirse no deja chats vacíos en el Historial.
const emit = defineEmits(['cerrar'])
const modoStore = useModoStore()

const idSelect = useId()
const tipo = ref('libre')
const guias = ref([])
const guiaId = ref(null)
const cargando = ref(true)
const error = ref(false)
const primerRadio = ref(null)

// "Chat guía" solo tiene sentido si hay guías en el modo activo
const sinGuias = computed(() => !cargando.value && guias.value.length === 0)

const destino = computed(() =>
  tipo.value === 'guiada' && guiaId.value
    ? { name: 'ChatIA', query: { guiaId: guiaId.value } }
    : { name: 'ChatIA' })

// Las mismas guías que ven Mini proyectos y el dashboard: GET /guias trae personales y de grupo,
// y cada modo se queda con las suyas comparando guia.origen con el modo activo
async function cargar() {
  const modo = modoStore.modoActivo
  cargando.value = true
  error.value = false
  try {
    const todas = await listarGuias()
    // Si el aprendiz cambió de modo mientras llegaba la respuesta, esta ya no sirve
    if (modo !== modoStore.modoActivo) return
    guias.value = todas.filter((g) => g.origen === modo)
  } catch {
    guias.value = []
    error.value = true
  } finally {
    if (modo === modoStore.modoActivo) cargando.value = false
  }
  // La primera guía queda elegida; sin guías se vuelve a "Chat libre" para no dejar una opción bloqueada marcada
  guiaId.value = guias.value[0]?.id ?? null
  if (!guias.value.length) tipo.value = 'libre'
}

onMounted(cargar)
watch(() => modoStore.modoActivo, cargar)

// El panel lo usa al abrir el cajón en el celular: el foco entra por el primer control
defineExpose({ enfocar: () => primerRadio.value?.focus() })
</script>

<template>
  <div class="nueva">
    <fieldset class="tipos">
      <legend>Tipo de chat</legend>
      <label class="tipo" :class="{ activo: tipo === 'libre' }">
        <input ref="primerRadio" v-model="tipo" type="radio" name="tipo-chat" value="libre" /> Chat libre
      </label>
      <label class="tipo" :class="{ activo: tipo === 'guiada', bloqueado: sinGuias }">
        <input v-model="tipo" type="radio" name="tipo-chat" value="guiada" :disabled="cargando || sinGuias"
          aria-describedby="ayuda-guia" /> Chat guía
      </label>
    </fieldset>

    <p v-if="sinGuias" id="ayuda-guia" class="ayuda">
      {{ error ? 'No pudimos cargar tus guías.' : 'Sube o únete a una guía para usar el chat guiado.' }}
    </p>

    <div v-if="tipo === 'guiada' && guias.length" class="campo">
      <label :for="idSelect">Guía</label>
      <select :id="idSelect" v-model="guiaId">
        <option v-for="g in guias" :key="g.id" :value="g.id">{{ g.nombre }}</option>
      </select>
    </div>

    <!-- Al navegar se cierra el cajón (en celular), igual que al abrir una conversación de la lista -->
    <RouterLink :to="destino" class="btn btn-primario" @click="emit('cerrar')">
      <Plus :size="18" aria-hidden="true" /> Nueva conversación
    </RouterLink>
  </div>
</template>

<style scoped>
.nueva {
  display: flex;
  flex-direction: column;
  gap: .6rem;
}

.tipos {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .25rem;
  margin: 0;
  padding: .25rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-background);
}

/* La leyenda existe para el lector de pantalla; a la vista el control ya se explica solo */
legend {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

/* Cada opción es la etiqueta entera de su radio: todo el botón es clicable y mide 40 px de alto */
.tipo {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
  border-radius: 9px;
  color: var(--color-text-secondary);
  font-size: .85rem;
  font-weight: 600;
  cursor: pointer;
}

/* El radio queda oculto a la vista, pero sigue recibiendo el foco y las flechas del teclado */
.tipo input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.tipo.activo {
  background: var(--color-primary);
  color: var(--color-text);
}

.tipo:has(input:focus-visible) {
  outline: 2px solid var(--color-primary-light);
  outline-offset: 2px;
}

.tipo.bloqueado {
  opacity: .45;
  cursor: not-allowed;
}

.ayuda {
  color: var(--color-text-secondary);
  font-size: .78rem;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: .3rem;
}

.campo label {
  color: var(--color-text-secondary);
  font-size: .8rem;
}

select {
  width: 100%;
  min-height: 40px;
  padding: .45rem .7rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-background);
  color: var(--color-text);
  font: inherit;
  font-size: .88rem;
}

select:focus {
  border-color: var(--color-primary);
  outline: none;
}
</style>
