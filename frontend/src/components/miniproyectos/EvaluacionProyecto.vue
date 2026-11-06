<template>
  <div class="evaluacion">

    <EstadoVista :cargando="cargando" :error="error" :vacio="!entrega" texto-cargando="Cargando la evaluación..."
      :icono="Star" texto-vacio="Aún no has entregado tu proyecto"
      descripcion-vacio="Cuando entregues el enlace de tu repositorio, aquí verás la evaluación de la IA."
      @reintentar="$emit('recargar')">
      <template #vacio>
        <button type="button" class="btn btn-primario" @click="$emit('ir-descripcion')">Ir a Descripción</button>
      </template>

      <!-- Cabecera de la entrega: cuándo, qué repositorio y qué intento -->
      <header class="entrega">
        <span class="entregado"><CircleCheck :size="16" /> Proyecto entregado</span>
        <span v-if="fecha" class="dato">{{ fecha }}</span>
        <span v-if="entrega.intento" class="dato">Intento {{ entrega.intento }}</span>
        <!-- Solo se enlaza si es un repositorio de GitHub: un valor raro del backend (por ejemplo
             "javascript:...") se muestra como texto, nunca como enlace. -->
        <a v-if="repoSeguro" class="repo" :href="repoSeguro" target="_blank" rel="noopener noreferrer">
          <Github :size="15" /> {{ repoCorto }} <ExternalLink :size="13" />
        </a>
        <span v-else-if="entrega.repo_url" class="repo">{{ entrega.repo_url }}</span>
      </header>

      <EvaluacionEnCurso v-if="enCurso" :sondeo-agotado="sondeoAgotado" @revisar="$emit('revisar')" />

      <ResultadoEvaluacion v-else-if="resultado" :entrega="entrega" :siguiente-id="siguienteId"
        @entregar-de-nuevo="$emit('ir-descripcion')" />

      <EvaluacionError v-else :entrega="entrega" :enviando="enviando" @reintentar="$emit('reintentar-envio')"
        @cambiar-enlace="$emit('ir-descripcion')" />
    </EstadoVista>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Star, CircleCheck, Github, ExternalLink } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EvaluacionEnCurso from '@/components/miniproyectos/EvaluacionEnCurso.vue'
import ResultadoEvaluacion from '@/components/miniproyectos/ResultadoEvaluacion.vue'
import EvaluacionError from '@/components/miniproyectos/EvaluacionError.vue'
import { formatearFecha, horaCorta } from '@/utils/fechas'

// Pestaña Evaluación: decide cuál de los 5 estados mostrar según lo que diga el backend
// (sin entrega · evaluando · necesita mejoras · aprobado · error). No calcula nada.
const props = defineProps({
  entrega: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
  error: { type: String, default: '' },
  sondeoAgotado: { type: Boolean, default: false },
  enviando: { type: Boolean, default: false },
  siguienteId: { type: Number, default: null }
})
defineEmits(['recargar', 'ir-descripcion', 'revisar', 'reintentar-envio'])

const enCurso = computed(() => ['pendiente', 'evaluando'].includes(props.entrega?.estado))
const resultado = computed(() => ['aprobado', 'necesita_mejoras'].includes(props.entrega?.estado))

const fecha = computed(() => {
  const dia = formatearFecha(props.entrega?.fecha_entrega)
  return dia ? `${dia} · ${horaCorta(props.entrega.fecha_entrega)}` : ''
})

const REPO_GITHUB = /^https:\/\/github\.com\/[^\s]+$/i
const repoSeguro = computed(() => (REPO_GITHUB.test(props.entrega?.repo_url ?? '') ? props.entrega.repo_url : ''))
const repoCorto = computed(() => repoSeguro.value.replace(/^https:\/\/github\.com\//i, ''))
</script>

<style scoped>
.evaluacion {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.entrega {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .5rem 1rem;
  font-size: .85rem;
}

.entregado {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .25rem .8rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-success) 16%, transparent);
  color: var(--color-success);
  font-weight: 700;
}

.dato { color: var(--color-text-secondary); }

.repo {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  max-width: 100%;
  overflow: hidden;
  color: var(--color-primary-light);
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
