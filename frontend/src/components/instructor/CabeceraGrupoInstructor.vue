<script setup>
import { computed } from 'vue'
import { Users, CalendarDays, CalendarClock } from 'lucide-vue-next'
import BotonCopiarCodigo from './BotonCopiarCodigo.vue'
import { inicialesDe } from '@/utils/usuario'
import { formatearFecha } from '@/utils/fechas'

// Cabecera del detalle del grupo (instructor). Sin "Grupo activo", foto ni "Editar": el backend no
// tiene esos datos ni un endpoint para editar el grupo (PLAN I-2).
const props = defineProps({ grupo: { type: Object, required: true } })

const instructor = computed(() => props.grupo.instructor?.name ?? '')
const estudiantes = computed(() => props.grupo.estudiantes?.length ?? 0)

// semana_actual = hasta dónde se liberó; ultima_semana = la más alta de sus guías (null sin guías)
const textoSemana = computed(() => {
  const { semana_actual: actual, ultima_semana: ultima } = props.grupo
  return ultima ? `Semana ${actual} de ${ultima}` : `Semana ${actual}`
})
</script>

<template>
  <header class="cabecera">
    <span class="icono" aria-hidden="true"><Users :size="34" /></span>

    <div class="texto">
      <h1>{{ grupo.nombre }}</h1>
      <p class="descripcion">{{ grupo.descripcion || 'Este grupo aún no tiene descripción.' }}</p>

      <ul class="datos">
        <li v-if="grupo.created_at"><CalendarDays :size="16" aria-hidden="true" /> Creado: {{ formatearFecha(grupo.created_at) }}</li>
        <li><Users :size="16" aria-hidden="true" /> {{ estudiantes }} {{ estudiantes === 1 ? 'estudiante' : 'estudiantes' }}</li>
        <li><CalendarClock :size="16" aria-hidden="true" /> {{ textoSemana }}</li>
      </ul>

      <BotonCopiarCodigo :codigo="grupo.codigo_acceso" />
    </div>

    <!-- Iniciales en vez de foto: no hay foto de perfil en la base de datos -->
    <div v-if="instructor" class="instructor">
      <span class="avatar" aria-hidden="true">{{ inicialesDe(instructor) }}</span>
      <div>
        <span class="instructor__rol">Instructor</span>
        <strong>{{ instructor }}</strong>
      </div>
    </div>
  </header>
</template>

<style scoped>
.cabecera {
  display: flex;
  align-items: flex-start;
  gap: 1.2rem;
  padding: clamp(1rem, 3vw, 1.6rem);
  border: 1px solid var(--color-border);
  border-radius: 18px;
  background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 28%, var(--color-surface)), var(--color-surface));
}

.icono {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 72px;
  height: 72px;
  border-radius: 16px;
  background: var(--gradiente-primario);
  box-shadow: var(--brillo-primario);
  color: var(--color-text);
}

.texto {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--espacio-sm);
  min-width: 0;
}

h1 {
  font-size: clamp(1.3rem, 3vw, 1.8rem);
  overflow-wrap: anywhere;
}

.descripcion {
  color: var(--color-text-secondary);
}

.datos {
  display: flex;
  flex-wrap: wrap;
  gap: var(--espacio-sm) var(--espacio-lg);
  margin: 0;
  padding: 0;
  list-style: none;
  color: var(--color-text-secondary);
  font-size: .88rem;
}

.datos li {
  display: flex;
  align-items: center;
  gap: .4rem;
}

.datos svg {
  color: var(--color-primary-light);
}

.instructor {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  gap: var(--espacio-sm);
  padding-left: var(--espacio-lg);
  border-left: 1px solid var(--color-border);
}

.avatar {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--gradiente-primario);
  font-weight: 700;
}

.instructor__rol {
  display: block;
  color: var(--color-text-secondary);
  font-size: .8rem;
}

/* Tablet y celular: el instructor pasa debajo, sin la línea separadora */
@media (max-width: 760px) {
  .cabecera {
    flex-wrap: wrap;
  }

  .instructor {
    width: 100%;
    padding: var(--espacio-sm) 0 0;
    border-top: 1px solid var(--color-border);
    border-left: none;
  }
}

@media (max-width: 480px) {
  .icono {
    width: 56px;
    height: 56px;
  }
}
</style>
