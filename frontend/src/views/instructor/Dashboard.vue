<template>
    <div>
        <h1>Dashboard del Instructor</h1>
        <p>Bienvenido al dashboard del instructor.</p>
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