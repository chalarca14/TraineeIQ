<script setup>
import { computed, useId } from 'vue'
import { CircleCheck, Star, Lock, LockOpen, ChevronDown, Clock } from 'lucide-vue-next'
import EntregasProyecto from './EntregasProyecto.vue'

// Una semana del grupo en la pestaña "Semanas y proyectos": estado, cuántos entregaron, el botón de
// liberar (solo en la siguiente a la actual) y, al expandirla, sus proyectos por guía.
const props = defineProps({
  semana: { type: Object, required: true },
  estudiantes: { type: Array, default: () => [] },
  entregasPorProyecto: { type: Object, default: () => ({}) },
  abierta: { type: Boolean, default: false },
  proyectoAbierto: { type: Number, default: null },
  // Esta es la semana que sigue a la actual y se puede liberar
  liberable: { type: Boolean, default: false }
})
defineEmits(['alternar', 'liberar', 'alternar-proyecto'])

const idCuerpo = useId()

const ESTADOS = {
  liberada: { texto: 'Liberada', icono: CircleCheck },
  actual: { texto: 'Semana actual', icono: Star },
  bloqueada: { texto: 'Bloqueada', icono: Lock }
}
const estado = computed(() => ESTADOS[props.semana.estado])
const bloqueada = computed(() => props.semana.estado === 'bloqueada')
</script>

<template>
  <li class="fila" :class="[`fila--${semana.estado}`, { abierta }]" :data-semana="semana.numero">
    <div class="cabecera">
      <!-- Todo el título es el botón que expande; "Liberar" va aparte (no se anidan botones) -->
      <button type="button" class="alternar" :aria-expanded="abierta" :aria-controls="idCuerpo" @click="$emit('alternar')">
        <span class="icono" aria-hidden="true"><component :is="estado.icono" :size="20" /></span>
        <span class="textos">
          <span class="numero">Semana {{ semana.numero }}</span>
          <strong>{{ semana.titulo || 'Sin título' }}</strong>
        </span>
        <span class="chip">{{ estado.texto }}</span>
        <span v-if="!bloqueada" class="conteo">{{ semana.entregaron }} de {{ estudiantes.length }} entregaron</span>
        <ChevronDown :size="18" class="flecha" :class="{ girada: abierta }" aria-hidden="true" />
      </button>

      <button v-if="liberable" type="button" class="btn btn-primario btn-sm liberar" @click="$emit('liberar', semana.numero)">
        <LockOpen :size="16" aria-hidden="true" /> Liberar semana {{ semana.numero }}
      </button>
      <span v-else-if="bloqueada" class="despues"><Clock :size="14" aria-hidden="true" /> Disponible después</span>
    </div>

    <div v-if="abierta" :id="idCuerpo" class="cuerpo">
      <!-- Decisión de Santi: de una semana sin liberar el instructor solo ve los títulos, no los proyectos -->
      <template v-if="bloqueada">
        <ul v-if="semana.porGuia.length > 1" class="titulos">
          <li v-for="g in semana.porGuia" :key="g.guia.id"><strong>{{ g.guia.nombre }}:</strong> {{ g.titulo || 'Sin título' }}</li>
        </ul>
        <p class="vacio"><Lock :size="15" aria-hidden="true" /> Libera la semana para ver sus proyectos.</p>
      </template>

      <section v-for="g in semana.porGuia" v-else :key="g.guia.id" class="guia">
        <h4>{{ g.guia.nombre }}<span v-if="g.titulo"> · {{ g.titulo }}</span></h4>
        <p v-if="!g.proyectos.length" class="vacio">Esta semana no trae mini proyectos en esta guía.</p>
        <EntregasProyecto v-for="p in g.proyectos" :key="p.id" :proyecto="p" :estudiantes="estudiantes"
          :entregas="entregasPorProyecto[p.id] ?? []" :abierto="proyectoAbierto === p.id"
          @alternar="$emit('alternar-proyecto', p.id)" />
      </section>
    </div>
  </li>
</template>

<style scoped>
.fila {
  border: 1px solid var(--color-card);
  border-radius: 14px;
  background: var(--color-surface);
  --c: var(--color-success);
}

.fila--actual {
  --c: var(--color-primary-light);
  border-color: color-mix(in srgb, var(--color-primary) 55%, var(--color-card));
}

.fila--bloqueada {
  --c: var(--color-text-secondary);
}

.cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md) var(--espacio-sm) var(--espacio-sm);
}

.alternar {
  display: flex;
  flex: 1 1 300px;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm) var(--espacio-md);
  min-width: 0;
  min-height: 44px;
  padding: var(--espacio-xs);
  border: none;
  border-radius: 10px;
  background: none;
  color: var(--color-text);
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.alternar:hover {
  background: color-mix(in srgb, var(--color-card) 60%, transparent);
}

.icono {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--c) 18%, var(--color-card));
  color: var(--c);
}

.textos {
  display: flex;
  flex: 1 1 150px;
  flex-direction: column;
  min-width: 0;
}

.numero {
  color: var(--color-text-secondary);
  font-size: .8rem;
}

.textos strong {
  overflow-wrap: anywhere;
}

.chip {
  padding: .2rem .7rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--c) 16%, transparent);
  color: var(--c);
  font-size: .75rem;
  font-weight: 700;
}

.conteo,
.despues,
.vacio {
  color: var(--color-text-secondary);
  font-size: .85rem;
}

.despues,
.vacio {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
}

.flecha {
  margin-left: auto;
  color: var(--color-text-secondary);
  transition: transform .2s;
}

.flecha.girada {
  transform: rotate(180deg);
}

.cuerpo {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
  padding: 0 var(--espacio-md) var(--espacio-md);
}

.guia {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
}

h4 {
  font-size: .95rem;
}

h4 span {
  color: var(--color-text-secondary);
  font-weight: 400;
}

.titulos {
  margin: 0;
  padding-left: 1.2rem;
  color: var(--color-text-secondary);
  font-size: .88rem;
}

/* Celular: el botón de liberar ocupa todo el ancho, debajo de la semana */
@media (max-width: 520px) {
  .liberar {
    width: 100%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .flecha {
    transition: none;
  }
}
</style>
