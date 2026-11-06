<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ChatEncabezado from '@/components/chat/ChatEncabezado.vue'
import ChatMensaje from '@/components/chat/ChatMensaje.vue'
import ChatEntrada from '@/components/chat/ChatEntrada.vue'
import ChatVacio from '@/components/chat/ChatVacio.vue'
import ChatEscribiendo from '@/components/chat/ChatEscribiendo.vue'
import ChatErrorEnvio from '@/components/chat/ChatErrorEnvio.vue'
import PanelConversaciones from '@/components/chat/PanelConversaciones.vue'
import { crearConversacion, enviarMensaje, obtenerConversacion } from '@/services/chatIaService'
import { usePanelConversaciones } from '@/composables/usePanelConversaciones'
import { obtenerGuia } from '@/services/guiaService'
import { mensajeDeError } from '@/utils/errores'

const route = useRoute()
const router = useRouter()

// --- Qué pide la URL ---
// /estudiante/ia/:conversacionId reabre una conversación guardada.
// /estudiante/ia?guiaId=3 empieza una guiada (RF-14); /estudiante/ia sola, una libre (RF-15).
// Number() + Number.isInteger descarta valores raros como ?guiaId=abc (caen en modo libre).
const entero = (valor) => {
  const n = Number(valor)
  return Number.isInteger(n) && n > 0 ? n : null
}
const idRuta = computed(() => entero(route.params.conversacionId))
const guiaIdRuta = computed(() => entero(route.query.guiaId))

// --- Estado del chat abierto ---
// tipo y guiaId viven en estado propio (no se leen de la URL) porque al crearse la conversación
// la URL cambia a /ia/:id y pierde el ?guiaId: el chat guiado no debe volverse libre por eso.
const conversacionId = ref(null)  // null = aún no existe; se crea al enviar el primer mensaje
const tipo = ref('libre')
const guiaId = ref(null)
const guiaNombre = ref('')
const mensajes = ref([])
const texto = ref('')
const pendiente = ref('')         // mensaje del usuario mientras espera a la IA
const enviando = ref(false)
const error = ref('')
const cargandoConversacion = ref(false)
const errorConversacion = ref('')
const contenedor = ref(null)
const guiado = computed(() => tipo.value === 'guiada')

// "Generación" del chat: sube cada vez que se abre otra conversación o una nueva. Toda respuesta
// que llega tarde (la IA tarda) compara su generación con la actual y, si ya no coincide, se
// descarta en vez de pintarse en una conversación que el usuario ya no está viendo.
let generacion = 0

// --- Panel de conversaciones (lista + cajón móvil): ver composables/usePanelConversaciones.js ---
const {
  conversaciones, cargandoLista, errorLista, panelAbierto, panel, encabezado,
  cargarLista, reintentarLista, abrirPanel, cerrarPanel
} = usePanelConversaciones()

// Baja el scroll hasta el último mensaje (esperamos a que Vue pinte primero)
async function bajarScroll() {
  await nextTick()
  if (contenedor.value) contenedor.value.scrollTop = contenedor.value.scrollHeight
}

// Solo para la etiqueta de contexto: si falla, el chat sigue funcionando con un nombre genérico.
async function cargarNombreGuia(gen) {
  guiaNombre.value = ''
  if (!guiaId.value) return
  const id = guiaId.value
  try {
    const guia = await obtenerGuia(id)
    if (gen === generacion) guiaNombre.value = guia.nombre
  } catch {
    if (gen === generacion) guiaNombre.value = `#${id}`
  }
}

// Abre lo que diga la URL desde cero. Se usa al entrar y cuando cambia la conversación o el modo.
async function abrirDesdeRuta() {
  const gen = ++generacion
  panelAbierto.value = false
  conversacionId.value = null
  mensajes.value = []
  texto.value = ''
  pendiente.value = ''
  error.value = ''
  errorConversacion.value = ''
  enviando.value = false

  if (!idRuta.value) {
    tipo.value = guiaIdRuta.value ? 'guiada' : 'libre'
    guiaId.value = guiaIdRuta.value
    cargandoConversacion.value = false
    cargarNombreGuia(gen)
    return
  }

  cargandoConversacion.value = true
  try {
    const conversacion = await obtenerConversacion(idRuta.value)
    if (gen !== generacion) return
    conversacionId.value = conversacion.id
    tipo.value = conversacion.tipo
    guiaId.value = conversacion.guia_id ?? null
    mensajes.value = conversacion.mensajes ?? []
    cargarNombreGuia(gen)
    bajarScroll()
  } catch (e) {
    if (gen !== generacion) return
    // Con respuesta del servidor (403, 404, 500) la conversación no existe o no es suya
    errorConversacion.value = e.response
      ? 'No pudimos abrir esta conversación. Puede que no exista o no sea tuya.'
      : mensajeDeError(e)
  } finally {
    if (gen === generacion) cargandoConversacion.value = false
  }
}

async function enviar() {
  const contenido = texto.value.trim()
  if (!contenido || enviando.value) return

  error.value = ''
  enviando.value = true
  pendiente.value = contenido
  bajarScroll()
  const gen = generacion // el chat desde el que se envía

  try {
    // La conversación se crea con el PRIMER mensaje, no al entrar a la pantalla,
    // para no dejar conversaciones vacías en la base de datos.
    // Si el envío falla después de crearla, conversacionId ya quedó guardado:
    // al reintentar NO se crea otra.
    if (!conversacionId.value) {
      const conversacion = await crearConversacion(tipo.value, guiaId.value, contenido)
      if (gen !== generacion) { cargarLista(); return } // cambió de chat mientras se creaba
      conversacionId.value = conversacion.id
      // La URL pasa a /ia/:id para poder volver a esta conversación. Es replace y no push (no
      // llena el historial del navegador con la pantalla vacía). El watcher de la ruta ve que
      // ese id ya es el abierto y NO reinicia el chat.
      router.replace({ name: 'ChatIAConversacion', params: { conversacionId: conversacion.id } })
      cargarLista()
    }

    const idEnviado = conversacionId.value
    const { mensaje_usuario, respuesta_ia } = await enviarMensaje(idEnviado, contenido)
    // La IA puede tardar: si mientras tanto abrió otra conversación, la respuesta se guarda en el
    // servidor (ya está en el panel) pero NO se pinta en la pantalla de otra conversación.
    if (gen !== generacion || conversacionId.value !== idEnviado) { cargarLista(); return }

    mensajes.value.push(mensaje_usuario, respuesta_ia)
    texto.value = '' // solo se limpia si salió bien, así puedes reintentar
    cargarLista()    // la conversación sube al primer lugar de la lista
  } catch (e) {
    if (gen === generacion) error.value = mensajeDeError(e)
  } finally {
    if (gen === generacion) {
      pendiente.value = ''
      enviando.value = false
      bajarScroll()
    }
  }
}

onMounted(() => {
  cargarLista()
  abrirDesdeRuta()
})

// Vue reutiliza este componente al cambiar de ruta (misma vista, otro parámetro), así que hay que
// reaccionar a mano. Excepción: al crear una conversación hacemos router.replace a su propio id;
// ese cambio no debe reiniciar nada porque el chat ya es esa conversación.
watch(() => [route.params.conversacionId, route.query.guiaId], () => {
  if (idRuta.value && idRuta.value === conversacionId.value) return
  abrirDesdeRuta()
})
</script>

<template>
  <section class="chat">
    <ChatEncabezado ref="encabezado" :guiado="guiado" :guia-nombre="guiaNombre" :panel-abierto="panelAbierto"
      @abrir-panel="abrirPanel" />

    <div class="chat__cuerpo">
      <!-- Clic fuera: el fondo oscuro solo existe con el cajón abierto (y solo en pantallas angostas) -->
      <div v-if="panelAbierto" class="chat__fondo" @click="cerrarPanel"></div>

      <PanelConversaciones ref="panel" :conversaciones="conversaciones" :cargando="cargandoLista" :error="errorLista"
        :activa-id="conversacionId" :abierto="panelAbierto" @cerrar="cerrarPanel" @reintentar="reintentarLista" />

      <div class="chat__principal">
        <div ref="contenedor" class="chat__mensajes" aria-live="polite">
          <p v-if="cargandoConversacion" class="chat__estado">Cargando la conversación…</p>
          <p v-else-if="errorConversacion" class="chat__estado chat__estado--error">{{ errorConversacion }}</p>

          <ChatVacio v-else-if="!mensajes.length && !pendiente" :guiado="guiado" :guia-nombre="guiaNombre" />

          <template v-else>
            <ChatMensaje v-for="m in mensajes" :key="m.id" :mensaje="m" />

            <!-- Mensaje en vuelo y estado "escribiendo" -->
            <ChatMensaje v-if="pendiente" :mensaje="{ emisor: 'usuario', contenido: pendiente }" />
            <ChatEscribiendo v-if="enviando" />
          </template>
        </div>

        <!-- Error con reintento: el texto sigue en la caja, reintentar vuelve a enviarlo -->
        <ChatErrorEnvio v-if="error" :mensaje="error" @reintentar="enviar" />

        <ChatEntrada v-model="texto" :deshabilitado="enviando || cargandoConversacion || Boolean(errorConversacion)"
          @enviar="enviar" />
      </div>
    </div>
  </section>
</template>

<style scoped>
/* El chat se pega a los 4 bordes de .app-main (position: relative en AppLayout), así mide
   EXACTAMENTE el alto disponible y la página no hace scroll. Solo la lista de mensajes (y la
   del panel) hacen scroll; encabezado, error y caja de escribir se quedan siempre a la vista. */
.chat {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  gap: .8rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: clamp(.8rem, 3vw, 1.5rem);
  box-sizing: border-box;
  overflow: hidden;
}

/* Panel + chat en dos columnas; position: relative es la referencia del cajón en celular */
.chat__cuerpo {
  position: relative;
  flex: 1;
  min-height: 0;
  display: grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap: 1rem;
}

.chat__principal {
  display: flex;
  flex-direction: column;
  gap: .8rem;
  min-height: 0;
}

.chat__mensajes {
  flex: 1;
  min-height: 0; /* sin esto la lista crece en vez de hacer scroll */
  display: flex;
  flex-direction: column;
  gap: .8rem;
  overflow-y: auto;
  padding: 1rem;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 35%, var(--color-surface)), var(--color-surface));
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.chat__estado {
  margin: auto;
  color: var(--color-text-secondary);
  text-align: center;
}

.chat__estado--error { color: var(--color-danger); }

.chat__fondo { display: none; }

/* Celular y tablet: una sola columna; el panel pasa a ser un cajón (ver PanelConversaciones) */
@media (max-width: 900px) {
  .chat__cuerpo { grid-template-columns: minmax(0, 1fr); }

  .chat__fondo {
    display: block;
    position: absolute;
    inset: 0;
    z-index: 10;
    border-radius: 16px;
    background: rgba(0, 0, 0, .55);
  }
}
</style>
