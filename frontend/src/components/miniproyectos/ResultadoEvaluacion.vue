<template>
  <div class="resultado">
    <!-- Ánimo por entregar, el MISMO con cualquier puntaje: la evaluación es orientativa, no hay "aprobado" -->
    <section class="tarjeta animo">
      <RobotAnimo tamano="sm" mensaje="¡Buen trabajo por entregar! Revisa las sugerencias para seguir mejorando." />
    </section>

    <!-- Puntaje y resumen de la IA. Siempre se dice que es orientativa. -->
    <section class="tarjeta principal">
      <AnilloPuntaje :puntaje="puntaje" />

      <div class="veredicto">
        <span class="insignia"><Info :size="15" /> Evaluación orientativa</span>
        <h2>Evaluado: {{ puntaje ?? '—' }}/100</h2>
        <!-- Texto de la IA: se pinta con {{ }} (texto plano), nunca con v-html -->
        <p v-if="evaluacion.resumen">{{ evaluacion.resumen }}</p>
      </div>
    </section>

    <!-- Si la IA solo pudo leer una parte del repositorio, el puntaje se debe leer con cuidado -->
    <p v-if="evaluacion.recortado" class="aviso" role="note">
      <TriangleAlert :size="16" /> Solo se revisó una parte de tu repositorio (era muy grande),
      así que esta evaluación puede no reflejar todo tu código.
    </p>

    <section class="tarjeta franja">
      <div class="dato">
        <Medalla :icono="RotateCcw" color="info" :size="44" />
        <div>
          <small>Evaluaciones de este proyecto</small>
          <strong class="valor">{{ entrega.intentos }}</strong>
        </div>
      </div>
      <!-- En Modo Personal el mejor puntaje cuenta como XP. Se muestra tal como llega: no se calcula -->
      <div v-if="esPersonal && entrega.mejor_puntaje != null" class="dato">
        <Medalla :icono="Star" color="warning" :size="44" />
        <div>
          <small>XP de este proyecto (tu mejor puntaje)</small>
          <strong class="valor">{{ entrega.mejor_puntaje }}</strong>
        </div>
      </div>
      <button type="button" class="btn btn-secundario" @click="$emit('entregar-de-nuevo')">
        Cambiar o volver a evaluar <ArrowRight :size="16" />
      </button>
    </section>

    <div class="doble">
      <section v-if="criterios.length" class="tarjeta">
        <h3><Medalla :icono="Code" :size="30" /> Criterios de evaluación</h3>
        <CriteriosEvaluados :criterios="criterios" />
      </section>

      <section v-if="requisitos.length" class="tarjeta">
        <h3>
          <Medalla :icono="ListChecks" color="success" :size="30" /> Requisitos
          <span class="conteo">{{ cumplidos }} / {{ requisitos.length }}</span>
        </h3>
        <ListaRequisitos :requisitos="requisitos" />
      </section>
    </div>

    <section v-if="practicas.length" class="tarjeta">
      <h3><Medalla :icono="ShieldCheck" color="info" :size="30" /> Buenas prácticas</h3>
      <ListaRequisitos :requisitos="practicas" />
    </section>

    <section v-if="sugerencias.length" class="tarjeta">
      <h3><Medalla :icono="Lightbulb" color="warning" :size="30" /> Sugerencias de la IA</h3>
      <ul class="sugerencias">
        <li v-for="(s, i) in sugerencias" :key="i">
          <Check :size="16" :stroke-width="3" /> <TextoConCodigo :texto="s" />
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Info, TriangleAlert, Star, ArrowRight, RotateCcw, ListChecks, Code, Lightbulb, Check, ShieldCheck } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import RobotAnimo from '@/components/ui/RobotAnimo.vue'
import AnilloPuntaje from '@/components/miniproyectos/AnilloPuntaje.vue'
import CriteriosEvaluados from '@/components/miniproyectos/CriteriosEvaluados.vue'
import ListaRequisitos from '@/components/miniproyectos/ListaRequisitos.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'

// Resultado de una entrega con estado "evaluado". Muestra exactamente lo que devolvió el backend
// (entrega.evaluacion); este componente no decide ni calcula nada: sin umbrales, sin "aprobado".
const props = defineProps({
  entrega: { type: Object, required: true },
  esPersonal: { type: Boolean, default: false }
})
defineEmits(['entregar-de-nuevo'])

const evaluacion = computed(() => props.entrega.evaluacion ?? {})
const puntaje = computed(() => evaluacion.value.puntaje ?? props.entrega.puntaje ?? null)

// El backend nombra los campos distinto a ListaRequisitos ({ requisito, cumple }): se traducen aquí.
// Si alguna lista no llega, su sección simplemente no se muestra.
const aItems = (lista, campoTexto, campoCumple) =>
  (lista ?? []).map((x) => ({ texto: x[campoTexto], cumplido: Boolean(x[campoCumple]), comentario: x.comentario }))

const criterios = computed(() => evaluacion.value.criterios ?? [])
const requisitos = computed(() => aItems(evaluacion.value.requisitos, 'requisito', 'cumple'))
const practicas = computed(() => aItems(evaluacion.value.buenas_practicas, 'practica', 'cumple'))
const sugerencias = computed(() => evaluacion.value.sugerencias ?? [])
const cumplidos = computed(() => requisitos.value.filter((r) => r.cumplido).length)
</script>

<style scoped>
.resultado {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.tarjeta {
  padding: 1.1rem 1.2rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

/* Franja de ánimo: menos alto que las demás tarjetas, el robot ya marca el espacio */
.animo {
  padding-block: .6rem;
}

.principal {
  display: flex;
  align-items: center;
  gap: 1.4rem;
  border-color: color-mix(in srgb, var(--color-primary) 45%, transparent);
  box-shadow: 0 0 32px color-mix(in srgb, var(--color-primary) 12%, transparent);
}

.veredicto { min-width: 0; }
.veredicto h2 { margin: .5rem 0 .3rem; font-size: 1.35rem; }
.veredicto p { color: var(--color-text-secondary); overflow-wrap: anywhere; }

.insignia {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  padding: .2rem .75rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-info) 16%, transparent);
  color: var(--color-info);
  font-size: .78rem;
  font-weight: 700;
}

.aviso {
  display: flex;
  align-items: flex-start;
  gap: .5rem;
  padding: .7rem .9rem;
  border-radius: 12px;
  background: color-mix(in srgb, var(--color-warning) 14%, transparent);
  color: var(--color-warning);
  font-size: .85rem;
}

.aviso svg { flex-shrink: 0; margin-top: .1rem; }

.franja {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.2rem;
}

.franja .btn { margin-left: auto; }

.dato { display: flex; align-items: center; gap: .8rem; }
.dato small { display: block; color: var(--color-text-secondary); font-size: .78rem; }
.valor { font-size: 1.4rem; }

.doble {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
  gap: 1rem;
}

h3 {
  display: flex;
  align-items: center;
  gap: .6rem;
  margin-bottom: .8rem;
  font-size: 1rem;
}

.conteo {
  margin-left: auto;
  padding: .1rem .6rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
  color: var(--color-primary-light);
  font-size: .78rem;
}

.sugerencias {
  display: flex;
  flex-direction: column;
  gap: .5rem;
  list-style: none;
}

.sugerencias li {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  font-size: .9rem;
}

.sugerencias svg { flex-shrink: 0; margin-top: .2rem; color: var(--color-success); }

@media (max-width: 520px) {
  .principal { flex-direction: column; text-align: center; }
  .franja .btn { width: 100%; margin-left: 0; justify-content: center; }
}
</style>
