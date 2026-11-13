<template>
  <RouterView />
  <!-- Pantalla de entrada con el robot: una vez por sesión, encima de la app (que ya se pinta debajo) -->
  <EntradaRobot />
</template>

<script setup>
import EntradaRobot from '@/components/ui/EntradaRobot.vue'
</script>
