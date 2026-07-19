<template>
    <div>
        <h1>Dashboard del Estudiante</h1>
        <p>Bienvenido al dashboard del estudiante.</p>
    </div>
</template>
<script>
import { useAuthStore } from '../../stores/auth'

export default {
  setup() {
    const auth = useAuthStore()

    return {
      auth
    }
  }
}
</script>