<script setup>
import { ref, computed } from 'vue'
import { Users, Search } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import { inicialesDe } from '@/utils/usuario'

// Estudiantes inscritos en el grupo (vienen en GET /grupos/{id}; el nombre está en "name", no en
// "nombre"). El filtro busca solo en esta lista ya cargada: no hay búsqueda en el backend.
const props = defineProps({ estudiantes: { type: Array, default: () => [] } })

const filtro = ref('')
const filtrados = computed(() => {
  const texto = filtro.value.trim().toLowerCase()
  if (!texto) return props.estudiantes
  return props.estudiantes.filter((e) =>
    (e.name ?? '').toLowerCase().includes(texto) || (e.email ?? '').toLowerCase().includes(texto))
})
</script>

<template>
  <PanelTarjeta :titulo="`Estudiantes del grupo (${estudiantes.length})`" :icono="Users">
    <p v-if="!estudiantes.length" class="vacio">
      Aún no hay estudiantes. Comparte el código de acceso para que se unan.
    </p>

    <template v-else>
      <!-- El filtro solo aparece si la lista es larga: con pocos nombres estorba más de lo que ayuda -->
      <label v-if="estudiantes.length > 6" class="filtro">
        <Search :size="16" aria-hidden="true" />
        <input v-model="filtro" type="search" placeholder="Filtrar por nombre o correo…"
          aria-label="Filtrar estudiantes por nombre o correo" />
      </label>

      <p v-if="!filtrados.length" class="vacio">Ningún estudiante coincide con “{{ filtro }}”.</p>
      <ul v-else class="lista">
        <li v-for="e in filtrados" :key="e.id">
          <span class="avatar" aria-hidden="true">{{ inicialesDe(e.name) }}</span>
          <div class="datos">
            <strong>{{ e.name }}</strong>
            <span>{{ e.email }}</span>
          </div>
        </li>
      </ul>
    </template>
  </PanelTarjeta>
</template>

<style scoped>
.vacio {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.filtro {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding: 0 var(--espacio-md);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  background: var(--color-background);
  color: var(--color-text-secondary);
}

.filtro:focus-within {
  border-color: var(--color-primary);
}

.filtro input {
  flex: 1;
  min-width: 0;
  min-height: 44px;
  border: none;
  outline: none;
  background: transparent;
  color: var(--color-text);
  font: inherit;
}

/* Con muchos estudiantes la lista no estira la página: tiene su propio scroll */
.lista {
  display: flex;
  flex-direction: column;
  max-height: 360px;
  margin: 0;
  padding: 0;
  overflow-y: auto;
  list-style: none;
}

.lista li {
  display: flex;
  align-items: center;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) 0;
  border-bottom: 1px solid var(--color-border);
}

.lista li:last-child {
  border-bottom: none;
}

.avatar {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-primary) 30%, var(--color-card));
  color: var(--color-primary-light);
  font-size: .85rem;
  font-weight: 700;
}

.datos {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.datos strong,
.datos span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.datos span {
  color: var(--color-text-secondary);
  font-size: .82rem;
}
</style>
