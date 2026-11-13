<script>
// Este <script> normal (sin "setup") se ejecuta UNA vez para toda la app, no una vez por robot:
// aquí vive el robot que se está reproduciendo, compartido por todos. Al reproducir uno se pausa
// el anterior, así nunca hay más de un video decodificándose a la vez.
let robotActivo = null
</script>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// Robot de TraineeIQ celebrando (saluda, pulgares arriba, aplaude) dentro de un círculo, para dar ánimo
// en momentos clave (Mejora 4). El video es DECORATIVO: el ánimo lo da el texto `mensaje`, que sí lee
// el lector de pantalla. Se cuida el rendimiento: solo se reproduce cuando se ve y nunca dos a la vez.
const props = defineProps({
  // sm 96 px · md 160 px · lg 240 px (en celular md baja a 120 px, ver estilos)
  tamano: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
  mensaje: { type: String, default: '' },
  // Mensaje al lado (fila) o debajo (columna) del robot
  vertical: { type: Boolean, default: false }
})

const VIDEO = '/animaciones/robot-animo.mp4'
const POSTER = '/animaciones/robot-animo-poster.webp'

const reducido = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
// Con movimiento reducido, o si el video falla, queda el póster quieto: nunca bloquea la vista
const usarPoster = ref(reducido)
const video = ref(null)
let observador = null
let enPantalla = false

function reproducir() {
  const v = video.value
  if (!v || usarPoster.value || document.hidden) return
  if (robotActivo && robotActivo !== v) robotActivo.pause()
  robotActivo = v
  // El navegador puede negarse al autoplay aunque esté en silencio: entonces el póster
  v.play()?.catch(() => { usarPoster.value = true })
}

function pausar() {
  video.value?.pause()
}

// Pestaña oculta → pausa; al volver, sigue solo si el robot está en pantalla
function alCambiarVisibilidad() {
  if (document.hidden) pausar()
  else if (enPantalla) reproducir()
}

onMounted(() => {
  if (usarPoster.value) return
  observador = new IntersectionObserver(([entrada]) => {
    enPantalla = entrada.isIntersecting
    if (enPantalla) reproducir()
    else pausar()
  }, { threshold: 0.4 })
  if (video.value) observador.observe(video.value)
  document.addEventListener('visibilitychange', alCambiarVisibilidad)
})

onUnmounted(() => {
  observador?.disconnect()
  document.removeEventListener('visibilitychange', alCambiarVisibilidad)
  if (robotActivo === video.value) robotActivo = null
})
</script>

<template>
  <div class="robot" :class="[`robot--${props.tamano}`, { 'robot--vertical': vertical }]">
    <div class="robot__circulo" aria-hidden="true">
      <img v-if="usarPoster" :src="POSTER" alt="" />
      <!-- preload="metadata": el video no se descarga hasta que el robot aparece en pantalla -->
      <video v-else ref="video" :src="VIDEO" :poster="POSTER" muted loop playsinline preload="metadata"
        @error="usarPoster = true" />
    </div>
    <p v-if="mensaje" class="robot__mensaje">{{ mensaje }}</p>
  </div>
</template>

<style scoped>
.robot {
  display: flex;
  align-items: center;
  gap: var(--espacio-md);
}

.robot--vertical {
  flex-direction: column;
  text-align: center;
}

/* Círculo: cuadrado redondeado + un borde difuminado (el fondo del video es casi negro y se funde con
   el tema oscuro). Así no se ve ninguna esquina ni el "cuadro" del video */
.robot__circulo {
  flex-shrink: 0;
  aspect-ratio: 1 / 1;
  overflow: hidden;
  border-radius: 50%;
  -webkit-mask-image: radial-gradient(circle, #000 66%, transparent 72%);
  mask-image: radial-gradient(circle, #000 66%, transparent 72%);
}

.robot--sm .robot__circulo { width: 96px; }
.robot--md .robot__circulo { width: 160px; }
.robot--lg .robot__circulo { width: 240px; }

.robot__circulo img,
.robot__circulo video {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.robot__mensaje {
  color: var(--color-text);
  font-weight: 600;
  overflow-wrap: anywhere;
}

/* Celular o pantalla baja (portátil de 700 px de alto): el robot mediano no debe ocupar media pantalla
   ni empujar el saludo fuera de la vista */
@media (max-width: 480px), (max-height: 720px) {
  .robot--md .robot__circulo { width: 120px; }
  .robot--lg .robot__circulo { width: 180px; }
}
</style>
