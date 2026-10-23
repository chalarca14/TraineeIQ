<template>
    <main class="instructor-dashboard">

        <section class="welcome-banner">
            <h1>¡Hola, {{ primerNombre }}! 👋</h1>
            <p>Desde aquí vas a administrar tus grupos y las guías de tus aprendices.</p>
        </section>

        <section class="stats-grid">
            <StatCard v-for="stat in estadisticas" :key="stat.etiqueta" v-bind="stat" />
        </section>

        <div class="empty-state">
            <Users :size="32" />
            <p>Crear grupos y subir guías llegará pronto.</p>
        </div>

    </main>
</template>

<script setup>
import { computed } from 'vue'
import { Users, BookOpen, GraduationCap } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import StatCard from '@/components/dashboard/StatCard.vue'

const authStore = useAuthStore()
const primerNombre = computed(() => authStore.usuario?.name?.split(' ')[0] ?? 'instructor')

// Datos de prueba, se reemplazan cuando conectemos GrupoController
const estadisticas = [
    { etiqueta: 'Grupos activos', valor: 2, detalle: 'a tu cargo', icono: Users },
    { etiqueta: 'Estudiantes', valor: 38, detalle: 'en tus grupos', icono: GraduationCap },
    { etiqueta: 'Guías subidas', valor: 5, detalle: 'en total', icono: BookOpen }
]
</script>

<style scoped>
.instructor-dashboard {
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

.welcome-banner {
    padding: 1.5rem 1.8rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--color-card);
    border-radius: 16px;
    background: linear-gradient(120deg, rgba(124, 58, 237, .28), var(--color-surface) 70%);
}

.welcome-banner h1 {
    margin: 0 0 .4rem 0;
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.welcome-banner p {
    margin: 0;
    color: var(--color-text-secondary);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 14px;
    color: var(--color-text-secondary);
}

.empty-state p {
    margin: 0;
}
</style>
>>>>>>> origin/rama-frontend
