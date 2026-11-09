<template>
  <div class="evaluacion">

    <EstadoVista :cargando="cargando" :error="error" :vacio="!entrega" texto-cargando="Cargando la evaluación..."
      :icono="Star" texto-vacio="Aún no has entregado tu proyecto"
      descripcion-vacio="Guarda el enlace de tu repositorio y pide la evaluación de la IA."
      @reintentar="$emit('recargar')">
      <template #vacio>
        <button type="button" class="btn btn-primario" @click="$emit('ir-descripcion')">Ir a Descripción</button>
      </template>

      <!-- Cabecera de la entrega: qué repositorio y cuándo se evaluó -->
      <header class="entrega">
        <span class="entregado"><CircleCheck :size="16" /> Repositorio entregado</span>
        <span v-if="fecha" class="dato">Evaluado: {{ fecha }}</span>
        <!-- Solo se enlaza si es un repositorio de GitHub: un valor raro del backend (por ejemplo
             "javascript:...") se muestra como texto, nunca como enlace. -->
        <a v-if="repoSeguro" class="repo" :href="repoSeguro" target="_blank" rel="noopener noreferrer">
          <Github :size="15" /> {{ repoCorto }} <ExternalLink :size="13" />
        </a>
        <span v-else-if="entrega.repo_url" class="repo">{{ entrega.repo_url }}</span>
      </header>

      <!-- El message del backend (422, 409, 429, 503) se muestra SIEMPRE. En "pendiente" y "error" lo
           pintan sus propios bloques; en los demás estados (por ejemplo, volver a evaluar un proyecto
           ya evaluado) se muestra aquí, si no el error quedaría invisible detrás del resultado anterior. -->
      <p v-if="errorEvaluar && !['pendiente', 'error'].includes(entrega.estado)" class="aviso-error" role="alert">
        <TriangleAlert :size="16" /> {{ errorEvaluar }}
      </p>

      <!-- Estados según lo que diga el backend: evaluando (aquí o sin espera), evaluado, pendiente, error -->
      <EvaluacionEnCurso v-if="evaluando || entrega.estado === 'evaluando'" :esperando="evaluando"
        :revisando="revisando" @revisar="$emit('revisar')" />

      <ResultadoEvaluacion v-else-if="entrega.estado === 'evaluado' && entrega.evaluacion" :entrega="entrega"
        :es-personal="esPersonal" @entregar-de-nuevo="$emit('ir-descripcion')" />

      <section v-else-if="entrega.estado === 'pendiente'" class="pendiente">
        <h2>Tu repositorio está guardado</h2>
        <p>Todavía no lo has evaluado. Cuando quieras, pide la evaluación de la IA.</p>
        <button type="button" class="btn btn-primario" :disabled="intentosRestantes === 0" @click="$emit('evaluar')">
          Evaluar mi código
        </button>
        <p v-if="intentosRestantes === 0" class="nota">Ya usaste tus evaluaciones de hoy. Vuelve mañana.</p>
        <p v-if="errorEvaluar" class="error-texto" role="alert">{{ errorEvaluar }}</p>
      </section>

      <EvaluacionError v-else :mensaje="errorEvaluar" :intentos-restantes="intentosRestantes" :evaluando="evaluando"
        @reintentar="$emit('evaluar')" @cambiar-enlace="$emit('ir-descripcion')" />
    </EstadoVista>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Star, CircleCheck, Github, ExternalLink, TriangleAlert } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EvaluacionEnCurso from '@/components/miniproyectos/EvaluacionEnCurso.vue'
import ResultadoEvaluacion from '@/components/miniproyectos/ResultadoEvaluacion.vue'
import EvaluacionError from '@/components/miniproyectos/EvaluacionError.vue'
import { esRepoGithub } from '@/services/miniProyectoService'
import { formatearFecha, horaCorta } from '@/utils/fechas'

// Pestaña Evaluación: decide cuál estado mostrar según lo que diga el backend
// (sin entrega · pendiente · evaluando · evaluado · error). No calcula nada.
const props = defineProps({
  entrega: { type: Object, default: null },
  cargando: { type: Boolean, default: false },
  error: { type: String, default: '' },
  evaluando: { type: Boolean, default: false },   // esta pantalla espera la respuesta de evaluar
  revisando: { type: Boolean, default: false },
  errorEvaluar: { type: String, default: '' },
  intentosRestantes: { type: Number, default: null },
  esPersonal: { type: Boolean, default: false }
})
defineEmits(['recargar', 'ir-descripcion', 'revisar', 'evaluar'])

const fecha = computed(() => {
  const f = formatearFecha(props.entrega?.evaluado_en)
  const h = horaCorta(props.entrega?.evaluado_en)
  return f ? `${f}${h ? `, ${h}` : ''}` : ''
})

const repoSeguro = computed(() => (esRepoGithub(props.entrega?.repo_url ?? '') ? props.entrega.repo_url : ''))
const repoCorto = computed(() => repoSeguro.value.replace('https://github.com/', ''))
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
  margin-bottom: 1rem;
  font-size: .85rem;
}

.entregado {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  color: var(--color-success);
  font-weight: 600;
}

.dato {
  color: var(--color-text-secondary);
}

.repo {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  min-width: 0;
  color: var(--color-primary-light);
  overflow-wrap: anywhere;
}

.pendiente {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .6rem;
  padding: 2.5rem 1rem;
  border: 1px dashed var(--color-border);
  border-radius: 16px;
  color: var(--color-text-secondary);
  text-align: center;
}

.pendiente h2 {
  color: var(--color-text);
  font-size: 1.1rem;
}

.nota {
  font-size: .85rem;
}

.error-texto {
  color: var(--color-danger);
  font-size: .85rem;
}

.aviso-error {
  display: flex;
  align-items: flex-start;
  gap: .5rem;
  margin-bottom: 1rem;
  padding: .7rem .9rem;
  border-radius: 12px;
  background: color-mix(in srgb, var(--color-danger) 12%, transparent);
  color: var(--color-danger);
  font-size: .88rem;
  overflow-wrap: anywhere;
}

.aviso-error svg {
  flex-shrink: 0;
  margin-top: .1rem;
}
</style>
