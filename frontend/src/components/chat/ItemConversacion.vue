<script setup>
import { computed } from 'vue'
import { MessageSquare, BookOpen, Trash2 } from 'lucide-vue-next'
import { fechaRelativa, horaCorta } from '@/utils/fechas'

// Una fila del panel de conversaciones: el enlace que reabre la conversación y, a su lado, la
// papelera para borrarla. Son dos elementos hermanos (un botón no puede ir dentro de un enlace).
// La papelera no borra: avisa al panel con 'borrar', y el panel abre la ventana de confirmación.
const props = defineProps({
  conversacion: { type: Object, required: true },
  activa: { type: Boolean, default: false }
})
defineEmits(['abrir', 'borrar'])

// El backend hoy guarda el mismo título para todas las libres ("Conversacion libre"); si llega
// vacío usamos uno genérico. La fecha y la hora bajo el título son lo que las distingue.
const titulo = computed(() =>
  props.conversacion.titulo || (props.conversacion.tipo === 'guiada' ? 'Conversación guiada' : 'Chat libre'))
const meta = computed(() =>
  [fechaRelativa(props.conversacion.updated_at), horaCorta(props.conversacion.updated_at)].filter(Boolean).join(' · '))
const guiada = computed(() => props.conversacion.tipo === 'guiada')
</script>

<template>
  <div class="fila">
    <RouterLink :to="{ name: 'ChatIAConversacion', params: { conversacionId: conversacion.id } }" class="item"
      :class="{ activa }" :aria-current="activa ? 'page' : undefined" @click="$emit('abrir')">
      <component :is="guiada ? BookOpen : MessageSquare" :size="16" class="item__icono" />
      <span class="item__texto">
        <span class="item__titulo" :title="titulo">{{ titulo }}</span>
        <span class="item__meta">
          <span class="etiqueta" :class="conversacion.tipo">{{ guiada ? 'Guiada' : 'Libre' }}</span>
          {{ meta }}
        </span>
      </span>
    </RouterLink>

    <!-- Papelera discreta: aparece al pasar el mouse o al llegar con Tab; en pantallas táctiles, siempre -->
    <button type="button" class="borrar" title="Borrar conversación"
      :aria-label="`Borrar la conversación ${titulo}${meta ? `, ${meta}` : ''}`" @click="$emit('borrar', conversacion)">
      <Trash2 :size="15" aria-hidden="true" />
    </button>
  </div>
</template>

<style scoped>
/* La papelera se monta sobre la esquina derecha de la fila, sin empujar el texto */
.fila {
  position: relative;
}

.item {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  padding: .55rem 2.6rem .55rem .6rem;
  border: 1px solid transparent;
  border-radius: 10px;
  color: var(--color-text);
  text-decoration: none;
  transition: background-color .2s, border-color .2s;
}

.item:hover { background: color-mix(in srgb, var(--color-primary) 10%, transparent); }

/* La conversación abierta va resaltada */
.item.activa {
  border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
}

.item__icono {
  flex-shrink: 0;
  margin-top: .15rem;
  color: var(--color-primary-light);
}

.item__texto {
  display: flex;
  flex-direction: column;
  gap: .2rem;
  min-width: 0;
}

.item__titulo {
  overflow: hidden;
  font-size: .85rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .4rem;
  color: var(--color-text-secondary);
  font-size: .72rem;
}

.etiqueta {
  padding: .05rem .45rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-info) 18%, transparent);
  color: var(--color-info);
  font-size: .66rem;
  font-weight: 600;
}

.etiqueta.guiada {
  background: color-mix(in srgb, var(--color-success) 18%, transparent);
  color: var(--color-success);
}

/* Misma papelera que las tarjetas de guía y de grupo (GuiaCard, GrupoInstructorCard), más pequeña:
   la fila del panel es angosta. Oculta con opacity (no display) para que Tab la siga alcanzando */
.borrar {
  position: absolute;
  top: 50%;
  right: .35rem;
  display: grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border: 1px solid transparent;
  border-radius: 9px;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  opacity: 0;
  transform: translateY(-50%);
  transition: .2s;
}

.fila:hover .borrar,
.borrar:focus-visible {
  opacity: 1;
}

.borrar:hover,
.borrar:focus-visible {
  border-color: var(--color-danger);
  color: var(--color-danger);
}

.borrar:focus-visible {
  outline: 2px solid var(--color-primary-light);
  outline-offset: 2px;
}

/* Celular, tablet o cajón: sin mouse no hay "pasar por encima", así que la papelera siempre se ve */
@media (hover: none), (max-width: 900px) {
  .borrar { opacity: 1; }
}
</style>
