<script setup>
import { useCargaLenta } from '@/composables/useCargaLenta'

// Envoltura de todo esqueleto: se muestra mientras una vista carga. Dentro va (slot) el esqueleto con
// la forma del contenido de esa vista. Se encarga de lo común a todos:
// - accesibilidad: aria-busy y un "Cargando…" que el lector de pantalla anuncia (el dibujo no se lee);
// - sin parpadeo: el esqueleto aparece pasados 250 ms (antes ocupa su lugar, invisible);
// - carga lenta: pasados 8 s avisa que el servidor (Render) puede estar despertando.
defineProps({
  texto: { type: String, default: 'Cargando…' }
})

// Este componente solo existe mientras se carga: los tiempos cuentan desde que aparece
const { visible, lenta } = useCargaLenta(() => true)
</script>

<template>
  <div class="carga" aria-busy="true">
    <span class="solo-lector" role="status">{{ texto }}</span>
    <div class="carga__esqueleto" :class="{ 'carga__esqueleto--oculto': !visible }" aria-hidden="true">
      <slot />
    </div>
    <p v-if="lenta" class="carga__lenta">
      El servidor está despertando, puede tardar hasta un minuto…
    </p>
  </div>
</template>

<style scoped>
.carga,
.carga__esqueleto {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-md);
}

/* visibility (no display: none): ocupa su lugar desde el principio, así no hay salto cuando aparece */
.carga__esqueleto--oculto {
  visibility: hidden;
}

.carga__lenta {
  color: var(--color-text-secondary);
  font-size: .9rem;
  text-align: center;
}

/* Oculto a la vista, pero el lector de pantalla sí lo lee */
.solo-lector {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
