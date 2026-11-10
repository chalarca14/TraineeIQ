<template>
  <p class="escribiendo">
    La IA está escribiendo<span class="puntos"><i>.</i><i>.</i><i>.</i></span>
  </p>
</template>

<style scoped>
.escribiendo {
  align-self: flex-start;
  color: var(--color-text-secondary);
  font-size: .85rem;
}

/* Tres puntos que parpadean uno tras otro */
.puntos i { font-style: normal; animation: parpadeo 1.2s infinite; }
.puntos i:nth-child(2) { animation-delay: .2s; }
.puntos i:nth-child(3) { animation-delay: .4s; }

@keyframes parpadeo {
  0%, 60%, 100% { opacity: .2; }
  30% { opacity: 1; }
}
</style>
