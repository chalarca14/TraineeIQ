<script setup>
import { computed } from 'vue'
import { Users, CalendarDays, ArrowRight } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'

// "Tus grupos" del dashboard del instructor: acceso rápido a cada grupo. Sin barra de avance ni
// "guía actual" del boceto: esos datos no existen por grupo (el avance se calcula en Estadísticas, I-7).
const props = defineProps({
  grupos: { type: Array, required: true },
  cuantos: { type: Number, default: 3 }
})

const visibles = computed(() => props.grupos.slice(0, props.cuantos))
const COLORES = ['var(--color-primary-light)', 'var(--color-info)', 'var(--color-success)', 'var(--color-warning)']
</script>

<template>
  <PanelTarjeta titulo="Tus grupos" :icono="Users">
    <template #accion>
      <RouterLink :to="{ name: 'InstructorMisGrupos' }" class="enlace">
        {{ grupos.length > cuantos ? `Ver los ${grupos.length}` : 'Ver todos' }}
      </RouterLink>
    </template>

    <ul class="lista">
      <li v-for="g in visibles" :key="g.id" :style="{ '--c': COLORES[g.id % COLORES.length] }">
        <span class="icono" aria-hidden="true"><Users :size="20" /></span>
        <div class="datos">
          <strong>{{ g.nombre }}</strong>
          <span class="meta">
            <span><Users :size="14" aria-hidden="true" /> {{ g.estudiantes_count ?? 0 }}
              {{ g.estudiantes_count === 1 ? 'estudiante' : 'estudiantes' }}</span>
            <span><CalendarDays :size="14" aria-hidden="true" /> Semana {{ g.semana_actual ?? 1 }}</span>
          </span>
        </div>
        <RouterLink :to="{ name: 'InstructorGrupoDetalle', params: { id: g.id } }" class="btn btn-secundario btn-sm"
          :aria-label="`Ver el grupo ${g.nombre}`">
          Ver grupo <ArrowRight :size="15" aria-hidden="true" />
        </RouterLink>
      </li>
    </ul>
  </PanelTarjeta>
</template>

<style scoped>
.enlace {
  color: var(--color-primary-light);
  font-size: .88rem;
  text-decoration: none;
}

.enlace:hover {
  text-decoration: underline;
}

.lista {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

.lista li {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-sm) var(--espacio-md);
  padding: var(--espacio-sm) var(--espacio-md);
  border: 1px solid var(--color-card);
  border-left: 3px solid var(--c);
  border-radius: 12px;
  background: var(--color-background);
}

.icono {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--c) 20%, var(--color-card));
  color: var(--c);
}

.datos {
  display: flex;
  flex: 1 1 160px;
  flex-direction: column;
  gap: .15rem;
  min-width: 0;
}

.datos strong {
  overflow-wrap: anywhere;
}

.meta {
  display: flex;
  flex-wrap: wrap;
  gap: .2rem var(--espacio-md);
  color: var(--color-text-secondary);
  font-size: .82rem;
}

.meta span {
  display: inline-flex;
  align-items: center;
  gap: .3rem;
}
</style>
