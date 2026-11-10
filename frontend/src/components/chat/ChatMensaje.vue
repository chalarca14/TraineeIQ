<script setup>
import { computed } from 'vue'
import { Bot } from 'lucide-vue-next'
import ChatMensajeIa from '@/components/chat/ChatMensajeIa.vue'

// El backend marca al autor en "emisor": 'usuario' o 'ia'.
const props = defineProps({
  mensaje: { type: Object, required: true }
})

const esUsuario = computed(() => props.mensaje.emisor === 'usuario')

// La hora es un detalle: si el mensaje no trae fecha válida, simplemente no se muestra.
const hora = computed(() => {
  const fecha = new Date(props.mensaje.created_at)
  if (Number.isNaN(fecha.getTime())) return ''
  return fecha.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' })
})
</script>

<template>
  <div class="mensaje" :class="{ 'mensaje--usuario': esUsuario }">
    <span v-if="!esUsuario" class="mensaje__avatar"><Bot :size="18" /></span>

    <div class="mensaje__burbuja">
      <!-- La IA responde en Markdown: se renderiza y SANITIZA en ChatMensajeIa.
           Lo que escribe el usuario se queda como texto plano (no necesita Markdown ni v-html). -->
      <p v-if="esUsuario" class="mensaje__texto">{{ mensaje.contenido }}</p>
      <ChatMensajeIa v-else :contenido="mensaje.contenido" />
      <time v-if="hora" class="mensaje__hora">{{ hora }}</time>
    </div>
  </div>
</template>

<style scoped>
.mensaje {
  display: flex;
  align-items: flex-end;
  gap: .6rem;
  max-width: 85%;
  align-self: flex-start;
}

.mensaje--usuario {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.mensaje__avatar {
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  flex-shrink: 0;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-primary) 20%, var(--color-card));
  color: var(--color-primary-light);
}

.mensaje__burbuja {
  /* la burbuja de la IA puede traer bloques de código anchos: así hacen scroll dentro de ella */
  min-width: 0;
  max-width: 100%;
  padding: .65rem .9rem;
  border-radius: 14px;
  border-bottom-left-radius: 4px;
  background: var(--color-card);
}

.mensaje--usuario .mensaje__burbuja {
  border-radius: 14px;
  border-bottom-right-radius: 4px;
  background: var(--gradiente-primario);
  color: var(--color-text);
}

.mensaje__texto {
  margin: 0;
  line-height: 1.45;
  /* respeta los saltos de línea de la IA y parte palabras muy largas (URLs, código) */
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.mensaje__hora {
  display: block;
  margin-top: .25rem;
  font-size: .7rem;
  text-align: right;
  opacity: .65;
}

@media (max-width: 600px) {
  .mensaje { max-width: 95%; }
}
</style>
