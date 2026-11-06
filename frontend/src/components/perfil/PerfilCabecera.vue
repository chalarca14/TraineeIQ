<script setup>
import { computed } from 'vue'
import { Calendar, Mail } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import { formatearFecha } from '@/utils/fechas'
import { ETIQUETA_ROL, inicialesDe } from '@/utils/usuario'

// Cabecera del perfil. Es genérica a propósito: no lee el store ni conoce el rol del aprendiz,
// todo llega por props, así el perfil del instructor puede reutilizarla tal cual.
const props = defineProps({
  nombre: { type: String, default: '' },
  correo: { type: String, default: '' },
  rol: { type: String, default: '' },
  // Fecha ISO de registro (created_at). Si no llega, la tarjeta "Miembro desde" se oculta.
  miembroDesde: { type: String, default: '' }
})

const iniciales = computed(() => inicialesDe(props.nombre))
const etiquetaRol = computed(() => ETIQUETA_ROL[props.rol] ?? props.rol)
// formatearFecha devuelve '' si la fecha no es válida: así una fecha rota tampoco muestra "NaN"
const desde = computed(() => (props.miembroDesde ? formatearFecha(props.miembroDesde) : ''))
</script>

<template>
  <header class="cabecera">
    <div class="principal">
      <span class="avatar" aria-hidden="true">{{ iniciales }}</span>
      <div class="identidad">
        <h1>{{ nombre }}</h1>
        <span v-if="etiquetaRol" class="rol">{{ etiquetaRol }}</span>
      </div>
    </div>

    <ul class="franja">
      <li v-if="desde">
        <Medalla :icono="Calendar" :size="40" />
        <div><small>Miembro desde</small><strong>{{ desde }}</strong></div>
      </li>
      <li>
        <Medalla :icono="Mail" :size="40" color="info" />
        <div><small>Correo</small><strong>{{ correo }}</strong></div>
      </li>
    </ul>
  </header>
</template>

<style scoped>
.cabecera {
  overflow: hidden;
  border: 1px solid color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
  border-radius: 18px;
  background: var(--color-surface);
}

.principal {
  display: flex;
  align-items: center;
  gap: 1.2rem;
  padding: 1.4rem;
  background: linear-gradient(110deg, color-mix(in srgb, var(--color-primary) 38%, var(--color-surface)), var(--color-surface) 80%);
}

.avatar {
  display: grid;
  place-items: center;
  width: 84px;
  height: 84px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--gradiente-primario);
  box-shadow: var(--brillo-primario);
  color: var(--color-text);
  font-size: 1.8rem;
  font-weight: 700;
}

.identidad {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: .5rem;
  min-width: 0;
}

h1 {
  font-size: clamp(1.25rem, 3vw, 1.75rem);
  overflow-wrap: anywhere;
}

.rol {
  padding: .25rem .9rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-primary) 30%, transparent);
  color: var(--color-primary-light);
  font-size: .8rem;
  font-weight: 600;
}

.franja {
  display: flex;
  flex-wrap: wrap;
  list-style: none;
  border-top: 1px solid var(--color-border);
}

.franja li {
  display: flex;
  flex: 1 1 220px;
  align-items: center;
  gap: .8rem;
  min-width: 0;
  padding: 1rem 1.4rem;
}

.franja div {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

small {
  color: var(--color-text-secondary);
  font-size: .78rem;
}

strong {
  font-size: .95rem;
  font-weight: 600;
  overflow-wrap: anywhere;
}

@media (max-width: 520px) {
  .avatar {
    width: 60px;
    height: 60px;
    font-size: 1.3rem;
  }

  .principal {
    padding: 1rem;
  }
}
</style>
