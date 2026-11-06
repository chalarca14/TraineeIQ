<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Bot, RotateCcw } from 'lucide-vue-next'
import ChatEncabezado from '@/components/chat/ChatEncabezado.vue'
import ChatMensaje from '@/components/chat/ChatMensaje.vue'
import ChatEntrada from '@/components/chat/ChatEntrada.vue'
import { crearConversacion, enviarMensaje } from '@/services/chatIaService'
import { obtenerGuia } from '@/services/guiaService'
import { mensajeDeError } from '@/utils/errores'

const route = useRoute()

// --- Modo del chat ---
// Sin ?guiaId el chat es libre (RF-15). Con ?guiaId es guiado (RF-14).
// Number() + Number.isInteger descarta valores raros como ?guiaId=abc (caen en modo libre).
const guiaId = computed(() => {
  const id = Number(route.query.guiaId)
  return Number.isInteger(id) && id > 0 ? id : null
})
const guiado = computed(() => guiaId.value !== null)
const guiaNombre = ref('')

// --- Estado de la conversación ---
const conversacionId = ref(null)  // null = aún no existe; se crea al enviar el primer mensaje
const mensajes = ref([])
const texto = ref('')
const pendiente = ref('')         // mensaje del usuario mientras espera a la IA
const enviando = ref(false)
const error = ref('')
const contenedor = ref(null)

// Baja el scroll hasta el último mensaje (esperamos a que Vue pinte primero)
async function bajarScroll() {
  await nextTick()
  if (contenedor.value) contenedor.value.scrollTop = contenedor.value.scrollHeight
}

// Solo para la etiqueta de contexto: si falla, el chat sigue funcionando con un nombre genérico.
async function cargarNombreGuia() {
  guiaNombre.value = ''
  if (!guiado.value) return
  try {
    const guia = await obtenerGuia(guiaId.value)
    guiaNombre.value = guia.nombre
  } catch {
    guiaNombre.value = `#${guiaId.value}`
  }
}

async function enviar() {
  const contenido = texto.value.trim()
  if (!contenido || enviando.value) return

  error.value = ''
  enviando.value = true
  pendiente.value = contenido
  bajarScroll()

  try {
    // La conversación se crea con el PRIMER mensaje, no al entrar a la pantalla,
    // para no dejar conversaciones vacías en la base de datos.
    // Si el envío falla después de crearla, conversacionId ya quedó guardado:
    // al reintentar NO se crea otra.
    if (!conversacionId.value) {
      const conversacion = await crearConversacion(guiado.value ? 'guiada' : 'libre', guiaId.value)
      conversacionId.value = conversacion.id
    }

    const { mensaje_usuario, respuesta_ia } = await enviarMensaje(conversacionId.value, contenido)
    mensajes.value.push(mensaje_usuario, respuesta_ia)
    texto.value = '' // solo se limpia si salió bien, así puedes reintentar
  } catch (e) {
    error.value = mensajeDeError(e)
  } finally {
    pendiente.value = ''
    enviando.value = false
    bajarScroll()
  }
}

// Empieza de cero: se usa al entrar y cuando cambia el modo (ej. de ?guiaId=3 a libre)
function reiniciar() {
  conversacionId.value = null
  mensajes.value = []
  texto.value = ''
  pendiente.value = ''
  error.value = ''
  cargarNombreGuia()
}

onMounted(cargarNombreGuia)
// Si el usuario pulsa "IA" en el sidebar estando en un chat guiado, Vue reutiliza este
// componente (misma ruta, otro query), así que hay que reiniciar a mano.
watch(guiaId, reiniciar)
</script>

<template>
  <section class="chat">
    <ChatEncabezado :guiado="guiado" :guia-nombre="guiaNombre" />

    <div ref="contenedor" class="chat__mensajes" aria-live="polite">
      <!-- Vacío -->
      <div v-if="!mensajes.length && !pendiente" class="chat__vacio">
        <Bot :size="40" />
        <p v-if="guiado">Pregúntame sobre el contenido de esta guía.</p>
        <p v-else>Escribe tu primera pregunta para empezar la conversación.</p>
      </div>

      <ChatMensaje v-for="m in mensajes" :key="m.id" :mensaje="m" />

      <!-- Mensaje en vuelo y estado "escribiendo" -->
      <ChatMensaje v-if="pendiente" :mensaje="{ emisor: 'usuario', contenido: pendiente }" />
      <p v-if="enviando" class="chat__escribiendo">
        La IA está escribiendo<span class="puntos"><i>.</i><i>.</i><i>.</i></span>
      </p>
    </div>

    <!-- Error con reintento: el texto sigue en la caja, reintentar vuelve a enviarlo -->
    <div v-if="error" class="chat__error" role="alert">
      <span>{{ error }}</span>
      <button type="button" @click="enviar"><RotateCcw :size="14" /> Reintentar</button>
    </div>

    <ChatEntrada v-model="texto" :deshabilitado="enviando" @enviar="enviar" />
  </section>
</template>

<style scoped>
/* El chat se pega a los 4 bordes de .app-main (position: relative en AppLayout), así mide
   EXACTAMENTE el alto disponible y la página no hace scroll. Con max-width + margin auto
   sigue centrado en escritorio. Solo la lista de mensajes hace scroll; encabezado, error
   y caja de escribir son hermanos fuera de ella, por eso siempre se ven. */
.chat {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  gap: .8rem;
  max-width: 900px;
  margin: 0 auto;
  padding: clamp(.8rem, 3vw, 1.5rem);
  box-sizing: border-box;
  overflow: hidden;
}

.chat__mensajes {
  flex: 1;
  min-height: 0; /* sin esto la lista crece en vez de hacer scroll */
  display: flex;
  flex-direction: column;
  gap: .8rem;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: var(--color-text-secondary) transparent;
  padding: 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.chat__vacio {
  margin: auto;
  text-align: center;
  color: var(--color-text-secondary);
}

.chat__vacio p { margin: .5rem 0 0; }

.chat__escribiendo {
  margin: 0;
  align-self: flex-start;
  color: var(--color-text-secondary);
  font-size: .85rem;
}

/* Tres puntos que parpadean uno tras otro */
.puntos i { font-style: normal; animation: parpadeo 1.2s infinite; }
.puntos i:nth-child(2) { animation-delay: .2s; }
.puntos i:nth-child(3) { animation-delay: .4s; }

@keyframes parpadeo {
  0%, 60%, 100% { opacity: .2; }
  30% { opacity: 1; }
}

.chat__error {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: .8rem;
  padding: .6rem .9rem;
  border: 1px solid var(--color-danger);
  border-radius: 12px;
  background: rgba(239, 68, 68, .1);
  color: var(--color-danger);
  font-size: .88rem;
}

.chat__error button {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
  flex-shrink: 0;
  padding: .35rem .7rem;
  border: 1px solid var(--color-danger);
  border-radius: 8px;
  background: transparent;
  color: var(--color-danger);
  cursor: pointer;
}

.chat__error button:hover { background: rgba(239, 68, 68, .15); }
</style>
