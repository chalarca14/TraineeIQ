<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <main class="prueba">
        <header class="page-header">
            <h1>Guías por semanas (prueba)</h1>
            <p>Pantalla temporal para probar el contenido por semana.</p>
        </header>

        <p v-if="cargando" class="estado">Cargando guías...</p>
        <p v-else-if="error" class="estado error">{{ error }}</p>
        <p v-else-if="guias.length === 0" class="estado">No tienes guías disponibles.</p>

        <div v-else class="lista">
            <RouterLink v-for="guia in guias" :key="guia.id" :to="`/prueba/guias/${guia.id}`" class="guia">
                <strong>{{ guia.nombre }}</strong>
                <span>{{ guia.origen === 'personal' ? 'Personal' : 'De grupo' }}</span>
            </RouterLink>
        </div>
    </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { listarGuias } from '@/services/guiaService'

const guias = ref([])
const cargando = ref(true)
const error = ref('')

onMounted(async () => {
    try {
        guias.value = await listarGuias()
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos cargar las guías.'
    } finally {
        cargando.value = false
    }
})
</script>

<style scoped>
.prueba {
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 1.5rem;
}

.page-header h1 {
    font-size: clamp(1.4rem, 3vw, 1.8rem);
    margin-bottom: .3rem;
}

.page-header p,
.estado,
.guia span {
    color: var(--color-text-secondary);
}

.estado.error {
    color: var(--color-danger);
}

.lista {
    display: grid;
    gap: .75rem;
}

.guia {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-radius: 14px;
    background: var(--color-card);
    color: var(--color-text);
    text-decoration: none;
}
</style>
