<template>
    <main class="guia-detalle">

        <EstadoVista :cargando="cargando" :error="error" texto-cargando="Cargando la guía..." @reintentar="cargar">
            <BotonVolver to="/estudiante/guias">Volver a Mis guías</BotonVolver>

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
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { Bot, BookOpen } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import BotonVolver from '@/components/ui/BotonVolver.vue'
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

.temas-lista {
    display: flex;
    flex-direction: column;
    gap: .8rem;
}
</style>
