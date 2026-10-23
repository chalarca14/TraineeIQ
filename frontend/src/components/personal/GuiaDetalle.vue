<template>
    <main class="guia-detalle">

        <RouterLink to="/estudiante/guias" class="volver">
            <ArrowLeft :size="16" />
            Mis guías
        </RouterLink>

        <p v-if="cargando" class="estado">Cargando la guía...</p>
        <p v-else-if="error" class="estado error">{{ error }}</p>

        <template v-else>
            <header class="page-header">
                <h1>{{ guia.nombre }}</h1>
                <p>{{ guia.temas.length }} temas. Abre uno para generar recomendaciones y mini proyectos con la IA.</p>
            </header>

            <div class="temas-lista">
                <TemaCard v-for="tema in guia.temas" :key="tema.id" :tema="tema" :guia-id="guia.id" />
            </div>
        </template>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft } from 'lucide-vue-next'
import TemaCard from '@/components/personal/TemaCard.vue'
import { obtenerGuia } from '@/services/guiaService'

const route = useRoute()
const guia = ref(null)
const cargando = ref(true)
const error = ref('')

onMounted(async () => {
    try {
        guia.value = await obtenerGuia(route.params.id)
    } catch (err) {
        error.value = 'No se pudo cargar la guía.'
    } finally {
        cargando.value = false
    }
})
</script>

<style scoped>
.guia-detalle {
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 900px;
    margin: 0 auto;
}

.volver {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-bottom: 1rem;
    color: var(--color-text-secondary);
    font-size: .85rem;
    text-decoration: none;
}

.volver:hover {
    color: var(--color-primary);
}

.page-header h1 {
    margin: 0 0 .3rem 0;
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.page-header p {
    margin: 0 0 1.5rem 0;
    color: var(--color-text-secondary);
}

.temas-lista {
    display: flex;
    flex-direction: column;
    gap: .8rem;
}

.estado {
    color: var(--color-text-secondary);
}

.estado.error {
    color: var(--color-danger);
}
</style>