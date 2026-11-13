<script setup>
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'

// Recuadro "Como Estudiante/Instructor podrás:" del registro. Cambia con la pestaña de rol que
// elige el usuario, con una animación corta. Salió de RegistroInfo.vue, que pasaba de 300 líneas.
const props = defineProps({ rol: { type: String, default: 'estudiante' } })

const INFO_POR_ROL = {
  estudiante: {
    titulo: 'Como Estudiante podrás:',
    puntos: [
      'Acceder a las guías de tu instructor o subir las tuyas propias',
      'Recibir recomendaciones personalizadas de la IA',
      'Desarrollar mini proyectos prácticos a tu ritmo'
    ]
  },
  instructor: {
    titulo: 'Como Instructor podrás:',
    puntos: [
      'Crear y administrar grupos de aprendizaje',
      'Cargar guías para tus estudiantes',
      'Hacer seguimiento al progreso de cada aprendiz'
    ]
  }
}

const infoActual = computed(() => INFO_POR_ROL[props.rol] ?? INFO_POR_ROL.estudiante)
</script>

<template>
  <!-- :key="rol" hace que Vue cambie el recuadro entero al cambiar de rol: así corre la animación -->
  <Transition name="fade-slide" mode="out-in">
    <div :key="rol" class="role-info-card">
      <h4>{{ infoActual.titulo }}</h4>
      <ul class="role-info-list">
        <li v-for="punto in infoActual.puntos" :key="punto">
          <Check :size="16" />
          {{ punto }}
        </li>
      </ul>
    </div>
  </Transition>
</template>

<style scoped>
.role-info-card {
  min-height: clamp(80px, 14vh, 160px);
  padding: 1rem 1.2rem;
  border: 1px solid var(--color-card);
  border-radius: 14px;
  background: var(--color-surface);
}

.role-info-card h4 {
  margin: 0 0 .6rem 0;
  color: var(--color-primary);
  font-size: .9rem;
}

.role-info-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: .5rem;
}

.role-info-list li {
  display: flex;
  align-items: center;
  gap: .6rem;
  font-size: .82rem;
  color: var(--color-text-secondary);
}

.role-info-list svg {
  color: var(--color-primary);
  flex-shrink: 0;
}

/* Animación de transición al cambiar de rol */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all .25s ease;
}

.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
