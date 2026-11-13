<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useAtraparFoco } from '@/composables/useAtraparFoco'
import { esperarServidor } from '@/services/servidorService'

// Pantalla de entrada (Mejora 3): el robot sube, saluda y aparece "TraineeIQ · IA académica".
// Se ve UNA vez por sesión del navegador y también sirve de espera cuando el servidor está dormido.
// La app se pinta debajo desde el principio: esta capa solo la tapa, no retrasa su primer pintado.
const VIDEO = '/intro/intro-traineeiq.mp4'
const POSTER = '/intro/intro-poster.webp'
const CLAVE = 'traineeiq.introVista'
// Si el video se traba y nunca dispara "ended", a los 8 s se sigue como si hubiera terminado
const MAX_VIDEO_MS = 8000
const FUNDIDO_MS = 400

// sessionStorage puede fallar (navegación privada, cookies bloqueadas): ahí se muestra la intro y listo
function yaSeVio() {
  try { return sessionStorage.getItem(CLAVE) === '1' } catch { return false }
}
function marcarVista() {
  try { sessionStorage.setItem(CLAVE, '1') } catch { /* sin almacenamiento: no pasa nada */ }
}

const reducido = typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
const visible = ref(!yaSeVio())
// video: reproduciendo · esperando: terminó pero el servidor no responde · saliendo: fundido de salida
const fase = ref(reducido ? 'esperando' : 'video')
const usarPoster = ref(reducido)
const servidorListo = ref(false)

const raiz = ref(null)
const video = ref(null)
const boton = ref(null)
let temporizador = null

useAtraparFoco(raiz)

// Lo que anuncia el lector de pantalla. El aviso de espera también se ve; el saludo no hace falta verlo
const textoEstado = computed(() => fase.value === 'esperando' && !servidorListo.value
  ? 'Despertando el servidor, puede tardar hasta un minuto…'
  : 'Bienvenido a TraineeIQ')

function cerrar() {
  if (fase.value === 'saliendo') return
  clearTimeout(temporizador)
  fase.value = 'saliendo'
  // Con movimiento reducido no hay fundido: se quita al instante
  setTimeout(() => { visible.value = false }, reducido ? 0 : FUNDIDO_MS)
}

// Fin del video: si el servidor ya contestó se entra; si no, queda el último cuadro con el aviso
// (el video NO se repite) y se entra solo cuando conteste. Ver el watch de abajo.
function alTerminar() {
  clearTimeout(temporizador)
  if (servidorListo.value) cerrar()
  else if (fase.value === 'video') fase.value = 'esperando'
}

// El video no cargó o el navegador no dejó reproducirlo: se muestra el póster y se sigue igual
function alFallar() {
  usarPoster.value = true
  alTerminar()
}

watch(servidorListo, (listo) => {
  if (listo && fase.value === 'esperando') cerrar()
})

onMounted(async () => {
  if (!visible.value) return
  // Se marca al abrir, no al cerrar: si recarga a mitad de la intro, no la vuelve a ver
  marcarVista()
  // Con cualquier respuesta (o si no contesta en 90 s) se deja pasar: cada pantalla maneja sus errores
  esperarServidor().finally(() => { servidorListo.value = true })

  await nextTick()
  boton.value?.focus()
  if (reducido) return
  temporizador = setTimeout(alTerminar, MAX_VIDEO_MS)
  // muted + playsinline permiten el autoplay; si aun así el navegador lo bloquea, va el póster
  video.value?.play()?.catch(alFallar)
})

onUnmounted(() => clearTimeout(temporizador))
</script>

<template>
  <div v-if="visible" ref="raiz" class="intro" :class="{ 'intro--saliendo': fase === 'saliendo' }" role="dialog"
    aria-modal="true" aria-label="Bienvenida a TraineeIQ" @keydown.esc="cerrar">
    <!-- Fondo a pantalla completa: el póster ampliado y desenfocado. El video es horizontal y el
         teléfono vertical; así la pantalla entera tiene el brillo del video sin recortar al robot -->
    <img class="intro__fondo" :src="POSTER" alt="" aria-hidden="true" />

    <!-- Decorativo: lo que importa para el lector de pantalla está en el mensaje de estado -->
    <img v-if="usarPoster" class="intro__medio" :src="POSTER" alt="" aria-hidden="true" />
    <video v-else ref="video" class="intro__medio" :src="VIDEO" :poster="POSTER" muted playsinline preload="auto"
      aria-hidden="true" @ended="alTerminar" @error="alFallar" />

    <p class="intro__estado" :class="{ 'intro__estado--visible': fase === 'esperando' && !servidorListo }"
      role="status">
      {{ textoEstado }}
    </p>

    <button ref="boton" type="button" class="btn btn-secundario intro__boton" @click="cerrar">
      {{ reducido ? 'Entrar' : 'Saltar' }}
    </button>
  </div>
</template>

<style scoped>
/* Por encima de todo (las ventanas modales usan 50). El fondo es el mismo azul del video */
.intro {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: grid;
  place-items: center;
  background: var(--color-fondo-intro);
  transition: opacity .4s ease;
}

.intro--saliendo {
  opacity: 0;
  pointer-events: none;
}

/* overflow: hidden para que el fondo ampliado no genere scroll */
.intro {
  overflow: hidden;
}

/* cover + blur: llena toda la pantalla con los colores del video. scale evita el borde claro que deja el blur */
.intro__fondo {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  /* Oscuro y saturado: tiene que parecerse al fondo del video, no a una imagen gris de fondo */
  filter: blur(28px) brightness(.3) saturate(1.3);
  transform: scale(1.15);
}

/* contain: el robot y el texto "TraineeIQ" nunca se recortan. La máscara desvanece los cuatro bordes
   del video hacia el fondo: así no se ve el "cuadrado" del video, en el teléfono ni en el escritorio */
.intro__medio {
  position: relative;
  /* La caja mide exactamente lo que el video (864x496): así la máscara cae sobre sus bordes reales.
     Cabe a lo ancho y a lo alto (100dvh por la proporción del video) y no pasa de 1100 px */
  width: min(100%, 1100px, calc(100dvh * 864 / 496));
  height: auto;
  aspect-ratio: 864 / 496;
  object-fit: contain;
  -webkit-mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent),
    linear-gradient(to bottom, transparent, #000 12%, #000 88%, transparent);
  -webkit-mask-composite: source-in;
  mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent),
    linear-gradient(to bottom, transparent, #000 12%, #000 88%, transparent);
  mask-composite: intersect;
}

/* "Saltar" arriba a la derecha, como en los reproductores: visible desde el primer momento */
.intro__boton {
  position: absolute;
  top: var(--espacio-lg);
  right: var(--espacio-lg);
  min-height: 44px;
}

/* El saludo solo lo lee el lector de pantalla; el aviso de espera sí se ve, abajo y centrado */
.intro__estado {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

.intro__estado--visible {
  bottom: clamp(1.5rem, 8vh, 4rem);
  left: 50%;
  width: min(90%, 460px);
  height: auto;
  overflow: visible;
  clip: auto;
  transform: translateX(-50%);
  color: var(--color-text-secondary);
  font-size: .95rem;
  text-align: center;
  white-space: normal;
}

/* Teléfono en vertical: el video se amplía más allá del ancho para que el robot se vea grande; los lados
   (solo fondo y partículas) quedan fuera y difuminados, y el texto "TraineeIQ" sigue completo */
@media (max-width: 600px) and (orientation: portrait) {
  .intro__medio {
    width: 180%;
    max-width: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .intro {
    transition: none;
  }
}
</style>
