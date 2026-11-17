<script setup>
import { computed, useId } from 'vue'
import { Rocket, ChevronDown, ExternalLink } from 'lucide-vue-next'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import { ESTADO_ENTREGA_INSTRUCTOR } from '@/utils/estadoEntrega'
import { inicialesDe } from '@/utils/usuario'
import { fechaRelativa, horaCorta } from '@/utils/fechas'

// Un mini proyecto de la semana y, al abrirlo, quién entregó y quién no. Solo llegan del backend los
// estudiantes que guardaron un repositorio: los que faltan se sacan de la lista de estudiantes del grupo.
const props = defineProps({
  proyecto: { type: Object, required: true },
  entregas: { type: Array, default: () => [] },
  estudiantes: { type: Array, default: () => [] },
  abierto: { type: Boolean, default: false }
})
defineEmits(['alternar'])

const idPanel = useId()

const entregaron = computed(() => new Set(props.entregas.map((e) => e.estudiante?.id)))
const sinEntregar = computed(() => props.estudiantes.filter((e) => !entregaron.value.has(e.id)))

// Solo se enlaza un repositorio de GitHub (el backend ya lo valida; aquí no se confía en cualquier URL)
const esGithub = (url) => /^https:\/\/github\.com\//.test(url ?? '')
const repoCorto = (url) => url.replace(/^https:\/\//, '')
</script>

<template>
  <article class="proyecto" :class="{ abierto }" :id="`proyecto-${proyecto.id}`">
    <div class="cabecera">
      <span class="icono" aria-hidden="true"><Rocket :size="16" /></span>
      <div class="datos">
        <strong>{{ proyecto.titulo }}</strong>
        <span class="conteo">{{ entregaron.size }} de {{ estudiantes.length }} entregaron</span>
      </div>
      <EtiquetaNivel v-if="proyecto.dificultad" :valor="proyecto.dificultad" />
      <button type="button" class="btn btn-secundario btn-sm" :aria-expanded="abierto" :aria-controls="idPanel"
        @click="$emit('alternar')">
        {{ abierto ? 'Ocultar' : 'Ver entregas' }}
        <ChevronDown :size="15" class="flecha" :class="{ girada: abierto }" aria-hidden="true" />
      </button>
    </div>

    <div v-if="abierto" :id="idPanel" class="cuerpo">
      <p v-if="!entregas.length" class="vacio">Nadie ha entregado este proyecto todavía.</p>

      <ul v-else class="entregas">
        <li v-for="e in entregas" :key="e.id">
          <span class="avatar" aria-hidden="true">{{ inicialesDe(e.estudiante?.name) }}</span>
          <div class="entrega">
            <strong>{{ e.estudiante?.name }}</strong>
            <a v-if="esGithub(e.repo_url)" :href="e.repo_url" target="_blank" rel="noopener noreferrer" class="repo">
              {{ repoCorto(e.repo_url) }} <ExternalLink :size="13" aria-hidden="true" />
              <span class="solo-lector">(se abre en otra pestaña)</span>
            </a>
            <time :datetime="e.updated_at">{{ fechaRelativa(e.updated_at) }}, {{ horaCorta(e.updated_at) }}</time>
          </div>
          <div class="resultado">
            <span class="estado" :class="`estado--${e.estado}`">{{ ESTADO_ENTREGA_INSTRUCTOR[e.estado] ?? e.estado }}</span>
            <!-- Un solo color y sin rangos: el puntaje es orientativo, no una nota (PLAN, regla 5) -->
            <span v-if="e.puntaje != null" class="puntaje">{{ e.puntaje }}/100 · orientativo</span>
          </div>
        </li>
      </ul>

      <p v-if="sinEntregar.length" class="faltan">
        <strong>Sin entregar:</strong> {{ sinEntregar.map((e) => e.name).join(', ') }}
      </p>
    </div>
  </article>
</template>

<style scoped>
.proyecto {
  border: 1px solid var(--color-card);
  border-radius: 12px;
  background: var(--color-background);
}

.proyecto.abierto {
  border-color: color-mix(in srgb, var(--color-primary) 50%, var(--color-card));
}

.cabecera {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md);
}

.icono {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-primary) 22%, var(--color-card));
  color: var(--color-primary-light);
}

.datos {
  display: flex;
  flex: 1 1 160px;
  flex-direction: column;
  min-width: 0;
}

.datos strong {
  overflow-wrap: anywhere;
}

.conteo,
time,
.vacio,
.faltan {
  color: var(--color-text-secondary);
  font-size: .82rem;
}

.flecha {
  transition: transform .2s;
}

.flecha.girada {
  transform: rotate(180deg);
}

.cuerpo {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  padding: 0 var(--espacio-md) var(--espacio-md);
}

.entregas {
  display: flex;
  flex-direction: column;
  margin: 0;
  padding: 0;
  list-style: none;
}

.entregas li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) 0;
  border-top: 1px solid var(--color-border);
}

.avatar {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-primary) 30%, var(--color-card));
  color: var(--color-primary-light);
  font-size: .8rem;
  font-weight: 700;
}

.entrega {
  display: flex;
  flex: 1 1 180px;
  flex-direction: column;
  min-width: 0;
}

.repo {
  display: inline-flex;
  align-items: center;
  gap: .25rem;
  color: var(--color-primary-light);
  font-size: .85rem;
  overflow-wrap: anywhere;
}

.resultado {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: .2rem;
}

.estado {
  padding: .15rem .6rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-info) 18%, transparent);
  color: var(--color-info);
  font-size: .75rem;
  font-weight: 600;
}

.estado--evaluado {
  background: color-mix(in srgb, var(--color-success) 18%, transparent);
  color: var(--color-success);
}

.estado--error {
  background: color-mix(in srgb, var(--color-danger) 18%, transparent);
  color: var(--color-danger);
}

.puntaje {
  color: var(--color-primary-light);
  font-size: .82rem;
  font-weight: 600;
}

.faltan strong {
  color: var(--color-text);
}

.solo-lector {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
}

/* Celular: el estado y el puntaje pasan debajo, alineados a la izquierda */
@media (max-width: 520px) {
  .resultado {
    align-items: flex-start;
    width: 100%;
    padding-left: 44px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .flecha {
    transition: none;
  }
}
</style>
