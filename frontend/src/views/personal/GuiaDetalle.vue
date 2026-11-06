<template>
    <main class="guia-detalle">

        <RouterLink to="/estudiante/guias" class="volver">
            <span class="flecha"><ArrowLeft :size="16" /></span> Volver a Mis guías
        </RouterLink>

        <p v-if="cargando" class="estado">Cargando la guía...</p>

        <div v-else-if="error" class="estado error">
            <p>{{ error }}</p>
            <button type="button" class="btn btn-secundario btn-sm" @click="cargar">Reintentar</button>
        </div>

        <template v-else>
            <EncabezadoPagina :titulo="guia.nombre" :icono="BookOpen"
                :descripcion="`${guia.temas.length} ${guia.temas.length === 1 ? 'tema' : 'temas'}. Abre uno para generar recomendaciones y mini proyectos con la IA.`">
                <template #acciones>
                    <!-- Lleva al chat en modo guiado: la IA solo responde sobre esta guía (RF-14) -->
                    <RouterLink :to="{ path: '/estudiante/ia', query: { guiaId: guia.id } }" class="btn btn-primario">
                        <Bot :size="18" /> Abrir chat con la IA
                    </RouterLink>
                </template>
            </EncabezadoPagina>

            <div class="temas-lista">
                <TemaCard v-for="tema in guia.temas" :key="tema.id" :tema="tema" :guia-id="guia.id" />
            </div>
        </template>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ArrowLeft, Bot, BookOpen } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import TemaCard from '@/components/personal/TemaCard.vue'
import { obtenerGuia } from '@/services/guiaService'

const route = useRoute()
const guia = ref(null)
const cargando = ref(true)
const error = ref('')

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        guia.value = await obtenerGuia(route.params.id)
    } catch (err) {
        error.value = 'No se pudo cargar la guía.'
    } finally {
        cargando.value = false
    }
}

onMounted(cargar)
</script>

<style scoped>
.guia-detalle {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    max-width: 960px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

/* Mismo botón "Volver" que el detalle de mini proyecto */
.volver {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: .6rem;
    padding: .3rem .9rem .3rem .3rem;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    background: var(--color-surface);
    color: var(--color-text);
    font-size: .88rem;
    text-decoration: none;
    transition: border-color .2s;
}

.volver:hover {
    border-color: var(--color-primary);
}

.flecha {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--color-primary) 25%, transparent);
    color: var(--color-primary-light);
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
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .6rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 16px;
    color: var(--color-danger);
    text-align: center;
}
</style>
