<script setup>
import { computed } from 'vue'
import { User, Mail, Shield } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import { ETIQUETA_ROL } from '@/utils/usuario'

// Datos de la cuenta en solo lectura. No hay lápiz de edición porque el backend todavía no tiene
// un endpoint para cambiar nombre o correo. Genérico como PerfilCabecera: todo llega por props.
const props = defineProps({
  nombre: { type: String, default: '' },
  correo: { type: String, default: '' },
  rol: { type: String, default: '' }
})

const filas = computed(() => [
  { icono: User, etiqueta: 'Nombre completo', valor: props.nombre },
  { icono: Mail, etiqueta: 'Correo electrónico', valor: props.correo },
  { icono: Shield, etiqueta: 'Rol', valor: ETIQUETA_ROL[props.rol] ?? props.rol }
])
</script>

<template>
  <section class="datos">
    <h2><User :size="20" /> Datos personales</h2>

    <dl>
      <div v-for="f in filas" :key="f.etiqueta" class="fila">
        <Medalla :icono="f.icono" :size="36" />
        <div class="texto">
          <dt>{{ f.etiqueta }}</dt>
          <dd>{{ f.valor || '—' }}</dd>
        </div>
      </div>
    </dl>

    <p class="nota">Por ahora tu nombre y tu correo no se pueden editar.</p>
  </section>
</template>

<style scoped>
.datos {
  padding: 1.2rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

h2 {
  display: flex;
  align-items: center;
  gap: .5rem;
  margin-bottom: .8rem;
  font-size: 1.05rem;
}

h2 svg {
  color: var(--color-primary-light);
}

.fila {
  display: flex;
  align-items: center;
  gap: .9rem;
  padding: .8rem 0;
  border-bottom: 1px solid var(--color-border);
}

.texto {
  min-width: 0;
}

dt {
  color: var(--color-text-secondary);
  font-size: .8rem;
}

dd {
  overflow-wrap: anywhere;
  font-weight: 600;
}

.nota {
  margin-top: .8rem;
  color: var(--color-text-secondary);
  font-size: .82rem;
}
</style>
