<template>
  <div class="resultado">

    <!-- Puntaje, veredicto y resumen de la IA -->
    <section class="tarjeta principal" :class="{ aprobado }">
      <AnilloPuntaje :puntaje="entrega.puntaje" :calificacion="entrega.calificacion" :aprobado="aprobado" />

      <div class="veredicto">
        <span class="insignia" :class="{ aprobado }">
          <component :is="aprobado ? CircleCheck : Wrench" :size="15" />
          {{ aprobado ? 'Aprobado' : 'Necesita mejoras' }}
        </span>
        <!-- Títulos neutros a propósito: "Excelente" es una calificación y la decide el backend
             (viene en `calificacion`); el frontend no debe afirmar una nota que no calculó. -->
        <h2>{{ aprobado ? '¡Buen trabajo!' : 'Casi lo logras' }}</h2>
        <p v-if="entrega.resumen">{{ entrega.resumen }}</p>
      </div>
    </section>

    <!-- Aprobado: XP y siguiente paso. Necesita mejoras: número de intento y entregar de nuevo.
         Todo lo decide y calcula el BACKEND; aquí solo se muestra. -->
    <section v-if="aprobado" class="tarjeta franja">
      <div class="dato">
        <Medalla :icono="Star" color="warning" :size="44" />
        <div>
          <small>Experiencia obtenida</small>
          <strong v-if="xp > 0" class="xp">+{{ xp }} XP</strong>
          <strong v-else class="xp xp--ya">Ya los habías ganado</strong>
        </div>
      </div>
      <RouterLink v-if="siguienteId" class="btn btn-primario"
        :to="{ name: 'MiniProyectoDetalle', params: { id: siguienteId } }">
        Continuar al siguiente nivel <ArrowRight :size="16" />
      </RouterLink>
      <RouterLink v-else class="btn btn-secundario" to="/estudiante/mini-proyectos">Volver a mis mini proyectos</RouterLink>
    </section>

    <section v-else class="tarjeta franja">
      <div class="dato">
        <Medalla :icono="RotateCcw" color="warning" :size="44" />
        <div>
          <small>Intento</small>
          <strong class="xp">{{ entrega.intento }}</strong>
        </div>
      </div>
      <button type="button" class="btn btn-primario" @click="$emit('entregar-de-nuevo')">
        Entregar de nuevo <ArrowRight :size="16" />
      </button>
    </section>

    <div class="doble">
      <section v-if="requisitos.length" class="tarjeta">
        <h3>
          <Medalla :icono="ListChecks" color="success" :size="30" /> Revisión de requisitos
          <span class="conteo">{{ cumplidos }} / {{ requisitos.length }}</span>
        </h3>
        <ListaRequisitos :requisitos="requisitos" />
      </section>

      <section v-if="analisis.length" class="tarjeta">
        <h3><Medalla :icono="Code" :size="30" /> Análisis del código</h3>
        <AnalisisCriterios :criterios="analisis" />
      </section>
    </div>

    <section v-if="recomendaciones.length" class="tarjeta">
      <h3><Medalla :icono="Lightbulb" color="warning" :size="30" /> Recomendaciones de la IA</h3>
      <ul class="recomendaciones">
        <!-- Texto de la IA: se pinta con {{ }} (texto plano), nunca con v-html -->
        <li v-for="(r, i) in recomendaciones" :key="i">
          <Check :size="16" :stroke-width="3" /> <TextoConCodigo :texto="r" />
        </li>
      </ul>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { CircleCheck, Wrench, Star, ArrowRight, RotateCcw, ListChecks, Code, Lightbulb, Check } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import AnilloPuntaje from '@/components/miniproyectos/AnilloPuntaje.vue'
import AnalisisCriterios from '@/components/miniproyectos/AnalisisCriterios.vue'
import ListaRequisitos from '@/components/miniproyectos/ListaRequisitos.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'

// Resultado de una entrega ya evaluada: "aprobado" o "necesita_mejoras".
// Se muestra exactamente lo que devolvió el backend; este componente no decide ni calcula nada.
const props = defineProps({
  entrega: { type: Object, required: true },
  siguienteId: { type: Number, default: null }
})
defineEmits(['entregar-de-nuevo'])

const aprobado = computed(() => props.entrega.estado === 'aprobado')
const xp = computed(() => Number(props.entrega.xp_ganado) || 0)
// Si el backend no manda alguna lista, la sección simplemente no se muestra
const requisitos = computed(() => props.entrega.requisitos ?? [])
const analisis = computed(() => props.entrega.analisis ?? [])
const recomendaciones = computed(() => props.entrega.recomendaciones ?? [])
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

.principal {
  display: flex;
  align-items: center;
  gap: 1.4rem;
  border-color: color-mix(in srgb, var(--color-warning) 45%, transparent);
  box-shadow: 0 0 32px color-mix(in srgb, var(--color-warning) 10%, transparent);
}

.principal.aprobado {
  border-color: color-mix(in srgb, var(--color-success) 45%, transparent);
  box-shadow: 0 0 32px color-mix(in srgb, var(--color-success) 12%, transparent);
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
  background: color-mix(in srgb, var(--color-warning) 16%, transparent);
  color: var(--color-warning);
  font-size: .78rem;
  font-weight: 700;
}

.insignia.aprobado {
  background: color-mix(in srgb, var(--color-success) 16%, transparent);
  color: var(--color-success);
}

.franja {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.dato { display: flex; align-items: center; gap: .8rem; }
.dato small { display: block; color: var(--color-text-secondary); font-size: .78rem; }
.xp { color: var(--color-warning); font-size: 1.4rem; }
.xp--ya { font-size: 1rem; }

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

.recomendaciones {
  display: flex;
  flex-direction: column;
  gap: .5rem;
  list-style: none;
}

.recomendaciones li {
  display: flex;
  align-items: flex-start;
  gap: .6rem;
  font-size: .9rem;
}

.recomendaciones svg { flex-shrink: 0; margin-top: .2rem; color: var(--color-success); }

@media (max-width: 520px) {
  .principal { flex-direction: column; text-align: center; }
  .franja .btn { width: 100%; }
}
</style>
