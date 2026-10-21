<script setup>
import { ref, nextTick } from 'vue'
import { crearConversacion, enviarMensaje } from '@/services/chatIaService'

// --- Estado de la pantalla ---
const tipo = ref('libre')          // 'libre' o 'guiada'
const guiaId = ref('')             // solo se usa en modo guiada
const conversacionId = ref(null)   // null = todavía no hay conversación creada
const mensajes = ref([])           // { id, emisor, contenido }
const texto = ref('')
const cargando = ref(false)
const error = ref('')
const contenedor = ref(null)

// Baja el scroll hasta el último mensaje (esperamos a que Vue pinte primero)
async function bajarScroll() {
  await nextTick()
  if (contenedor.value) contenedor.value.scrollTop = contenedor.value.scrollHeight
}

// Paso 1: crear la conversación en el backend
async function iniciar() {
  error.value = ''
  // Validación frontend: la guía guiada necesita un id
  if (tipo.value === 'guiada' && !guiaId.value) {
    error.value = 'Escribe el ID de la guía para el modo guiado.'
    return
  }
  cargando.value = true
  try {
    const respuesta = await crearConversacion(tipo.value, Number(guiaId.value) || null)
    
    // Extraemos el objeto que viene dentro de la clave 'data' de Laravel
    const conversacion = respuesta.data || respuesta
    conversacionId.value = conversacion.id
    mensajes.value = []
  } catch (e) {
    error.value = e.message
  } finally {
    cargando.value = false
  }
}

// Paso 2: enviar un mensaje; el backend devuelve el del usuario y la respuesta de la IA
async function enviar() {
  const contenido = texto.value.trim()
  if (!contenido || cargando.value) return

  error.value = ''
  cargando.value = true
  try {
    const respuesta = await enviarMensaje(conversacionId.value, contenido)
    
    // Obtenemos los objetos enviados por el controlador (mensaje_usuario y mensaje_ia)
    const datos = respuesta.data || respuesta
    const mensajeUsuario = datos.mensaje_usuario
    const mensajeIa = datos.mensaje_ia || datos.respuesta_ia

    mensajes.value.push(mensajeUsuario, mensajeIa)
    texto.value = '' // solo se limpia si salió bien, así puedes reintentar
  } catch (e) {
    error.value = e.message
  } finally {
    cargando.value = false
    bajarScroll()
  }
}

// Volver a la pantalla de elegir modo
function nuevaConversacion() {
  conversacionId.value = null
  mensajes.value = []
  error.value = ''
}
</script>

<template>
  <section class="chat">
    <h1 class="chat__titulo">Chat IA (prueba)</h1>

    <!-- Pantalla 1: elegir modo -->
    <div v-if="!conversacionId" class="chat__config">
      <label>
        Modo
        <select v-model="tipo">
          <option value="libre">Libre</option>
          <option value="guiada">Guiado (por una guía)</option>
        </select>
      </label>

      <label v-if="tipo === 'guiada'">
        ID de la guía
        <input v-model="guiaId" type="number" min="1" placeholder="Ej: 1" />
      </label>

      <button :disabled="cargando" @click="iniciar">Iniciar conversación</button>
    </div>

    <!-- Pantalla 2: el chat -->
    <template v-else>
      <div ref="contenedor" class="chat__mensajes">
        <p v-if="!mensajes.length" class="chat__estado">Conversación {{ tipo }} lista. Escribe algo 👋</p>

        <div
          v-for="m in mensajes"
          :key="m.id"
          class="burbuja"
          :class="m.emisor === 'usuario' ? 'burbuja--usuario' : 'burbuja--ia'"
        >
          {{ m.contenido }}
        </div>

        <p v-if="cargando" class="chat__estado">La IA está pensando…</p>
      </div>

      <div class="chat__entrada">
        <input
          v-model="texto"
          type="text"
          placeholder="Pregúntale algo a la IA"
          maxlength="2000"
          :disabled="cargando"
          @keyup.enter="enviar"
        />
        <button :disabled="cargando || !texto.trim()" @click="enviar">Enviar</button>
      </div>
      <button class="chat__link" @click="nuevaConversacion">← Nueva conversación</button>
    </template>

    <p v-if="error" class="chat__error" role="alert">{{ error }}</p>
  </section>
</template>

<style scoped>
.chat {
  display: flex;
  flex-direction: column;
  height: 100dvh;
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
  background: #0f1117;
  color: #e5e7eb;
}
.chat__titulo { font-size: 1.25rem; margin-bottom: 1rem; }
.chat__config { display: flex; flex-direction: column; gap: 1rem; max-width: 360px; }
.chat__config label { display: flex; flex-direction: column; gap: 0.3rem; font-size: 0.9rem; }
.chat__config select,
.chat__config input,
.chat__entrada input {
  padding: 0.7rem 0.9rem;
  border-radius: 10px;
  border: 1px solid #2d3348;
  background: #161a24;
  color: #e5e7eb;
}
.chat__mensajes {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 1rem;
  background: #161a24;
  border-radius: 12px;
}
.chat__estado { color: #9ca3af; font-size: 0.9rem; }
.burbuja {
  max-width: 80%;
  padding: 0.6rem 0.9rem;
  border-radius: 12px;
  white-space: pre-wrap;
  line-height: 1.4;
}
.burbuja--usuario { align-self: flex-end; background: #7c3aed; color: #fff; }
.burbuja--ia { align-self: flex-start; background: #232838; }
.chat__entrada { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
.chat__entrada input { flex: 1; }
button {
  padding: 0.7rem 1.2rem;
  border: none;
  border-radius: 10px;
  background: #7c3aed;
  color: #fff;
  cursor: pointer;
}
button:disabled { opacity: 0.5; cursor: not-allowed; }
.chat__link { background: none; color: #9ca3af; margin-top: 0.5rem; align-self: flex-start; padding-left: 0; }
.chat__error { color: #f87171; margin-top: 0.5rem; }

@media (max-width: 700px) {
  .burbuja { max-width: 92%; }
}
</style>