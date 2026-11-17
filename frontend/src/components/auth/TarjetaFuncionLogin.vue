<script setup>
// Una tarjeta de la columna izquierda del login ("Recomendaciones IA", "Mini proyectos"…).
// Salió de LoginInfo.vue, que pasaba de 300 líneas: las 4 tarjetas eran el mismo bloque repetido
// y la mayor parte de sus estilos (con sus ajustes por tamaño de pantalla) eran de la tarjeta.
defineProps({
  icono: { type: String, required: true },
  titulo: { type: String, required: true },
  texto: { type: String, required: true }
})
</script>

<template>
  <article class="feature-card">
    <!-- alt vacío: el ícono es decorativo, el título ya dice de qué trata la tarjeta -->
    <img :src="icono" alt="" class="feature-icon">
    <div>
      <h3>{{ titulo }}</h3>
      <p>{{ texto }}</p>
    </div>
  </article>
</template>

<style scoped>
/* Mismo orden que tenían en LoginInfo.vue: en CSS, ante la misma especificidad, gana la última regla */
.feature-card {
  background: var(--color-surface);
  border: 1px solid var(--color-card);
  border-radius: 18px;
  padding: clamp(1rem, 2vh, 1.4rem);
  transition: .25s;
  cursor: pointer;
  min-height: clamp(90px, 12vh, 120px);
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  gap: .7rem;
}

.feature-card:hover {
  transform: translateY(-4px);
  border-color: var(--color-primary);
  box-shadow: 0 12px 30px rgba(124, 58, 237, .20);
}

.feature-icon {
  width: 42px;
  height: 42px;
  margin-bottom: 1rem;
  transition: .25s;
}

.feature-card:hover .feature-icon {
  transform: scale(1.08);
}

.feature-card h3 {
  margin-bottom: .6rem;
  color: var(--color-text);
}

.feature-card p {
  color: var(--color-text-secondary);
  line-height: 1.6;
  font-size: .9rem;
}

/* Pantallas con poca altura */
@media (max-height: 800px) {
  .feature-card {
    min-height: 110px;
    padding: 1rem;
  }

  .feature-icon {
    width: 36px;
    height: 36px;
    margin-bottom: .5rem;
  }

  .feature-card h3 {
    font-size: 1rem;
    margin-bottom: .3rem;
  }

  .feature-card p {
    font-size: .8rem;
    line-height: 1.4;
  }
}

@media (max-width: 700px) {
  .feature-card {
    width: 100%;
    min-height: 110px;
    padding: 1rem;
  }

  .feature-icon {
    width: 40px;
    height: 40px;
    margin-bottom: .4rem;
  }

  .feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: .2rem;
  }

  .feature-card p {
    font-size: 1rem;
    line-height: 1.4;
  }
}

@media (min-width: 1849px) {
  .feature-card {
    min-height: 200px;
    padding: 1.5rem;
    width: 100%;
  }

  .feature-icon {
    width: 50px;
    height: 50px;
    margin-bottom: 1rem;
  }

  .feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: .6rem;
  }

  .feature-card p {
    font-size: 1.2rem;
    line-height: 1.6;
  }
}
</style>
