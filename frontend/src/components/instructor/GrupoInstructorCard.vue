<script setup>
import { computed } from 'vue'
import { Users, CalendarDays, ArrowRight, Trash2 } from 'lucide-vue-next'
import BotonCopiarCodigo from './BotonCopiarCodigo.vue'

// Tarjeta de un grupo del instructor: datos, código de acceso con "Copiar" y acciones.
// Sin foto ni sello "Activo": el backend no tiene esos datos (PLAN I-1).
const props = defineProps({ grupo: { type: Object, required: true } })
defineEmits(['eliminar'])

// Mismos colores que las tarjetas del aprendiz, según el id, para distinguir los grupos de un vistazo
const COLORES = ['var(--color-primary-light)', 'var(--color-info)', 'var(--color-success)', 'var(--color-warning)']
const color = computed(() => COLORES[props.grupo.id % COLORES.length])

const estudiantes = computed(() => props.grupo.estudiantes_count ?? 0)
// Un grupo recién creado llega sin semana_actual: el backend empieza en 1
const semana = computed(() => props.grupo.semana_actual ?? 1)
</script>

<template>
  <article class="grupo" :style="{ '--c': color }">
    <div class="portada" aria-hidden="true">
      <Users :size="40" />
    </div>

    <div class="cuerpo">
      <h3 :title="grupo.nombre">{{ grupo.nombre }}</h3>
      <p class="descripcion">{{ grupo.descripcion || 'Este grupo aún no tiene descripción.' }}</p>

      <dl class="datos">
        <div>
          <dt><Users :size="16" aria-hidden="true" /> Estudiantes</dt>
          <dd>{{ estudiantes }}</dd>
        </div>
        <div>
          <dt><CalendarDays :size="16" aria-hidden="true" /> Semana actual</dt>
          <dd>{{ semana }}</dd>
        </div>
      </dl>

      <BotonCopiarCodigo :codigo="grupo.codigo_acceso" />

      <div class="acciones">
        <!-- RouterLink y no botón: es navegación (se puede abrir en otra pestaña) -->
        <RouterLink :to="{ name: 'InstructorGrupoDetalle', params: { id: grupo.id } }" class="btn btn-primario">
          Ver grupo <ArrowRight :size="16" aria-hidden="true" />
        </RouterLink>
        <button type="button" class="eliminar" :aria-label="`Eliminar el grupo ${grupo.nombre}`"
          @click="$emit('eliminar', grupo)">
          <Trash2 :size="18" />
        </button>
      </div>
    </div>
  </article>
</template>

<style scoped>
.grupo {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
  transition: border-color .2s, box-shadow .2s;
}

.grupo:hover {
  border-color: color-mix(in srgb, var(--c) 60%, transparent);
  box-shadow: 0 8px 28px color-mix(in srgb, var(--c) 18%, transparent);
}

.portada {
  display: grid;
  place-items: center;
  height: 90px;
  color: var(--c);
  background: linear-gradient(135deg, color-mix(in srgb, var(--c) 45%, var(--color-card)), color-mix(in srgb, var(--c) 8%, var(--color-surface)));
}

.cuerpo {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--espacio-sm);
  padding: 1.1rem;
}

h3 {
  overflow: hidden;
  font-size: 1.1rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.descripcion {
  display: -webkit-box;
  overflow: hidden;
  color: var(--color-text-secondary);
  font-size: .88rem;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-clamp: 2;
}

.datos {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--espacio-sm);
  padding-block: var(--espacio-sm);
  border-block: 1px solid var(--color-border);
}

.datos dt {
  display: flex;
  align-items: center;
  gap: .35rem;
  color: var(--color-text-secondary);
  font-size: .78rem;
}

.datos dt svg {
  color: var(--c);
}

.datos dd {
  font-size: 1.15rem;
  font-weight: 700;
}

.acciones {
  display: flex;
  gap: var(--espacio-sm);
  margin-top: auto;
}

.acciones .btn {
  flex: 1;
  min-height: 44px;
}

.eliminar {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--color-card);
  border-radius: 12px;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: .2s;
}

.eliminar:hover {
  border-color: var(--color-danger);
  color: var(--color-danger);
}
</style>
