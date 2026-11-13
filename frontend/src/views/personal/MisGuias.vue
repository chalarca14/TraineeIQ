<template>
    <main class="mis-guias">

        <EncabezadoPagina titulo="Mis guías" :icono="BookOpen"
            descripcion="Sube tus guías en PDF y las dividimos en temas para estudiarlas con ayuda de la IA.">
            <template #acciones>
                <button class="btn btn-primario" type="button" @click="mostrarForm = !mostrarForm">
                    <Plus :size="18" /> Subir guía
                </button>
            </template>
        </EncabezadoPagina>

        <SubirGuiaForm v-if="mostrarForm" @subida="alSubir" @cancelar="mostrarForm = false" />

        <EstadoVista :cargando="cargando" :error="error" :vacio="guias.length === 0" :icono="BookOpen"
            texto-cargando="Cargando tus guías..." texto-vacio="Aún no has subido ninguna guía."
            descripcion-vacio="Sube tu primer PDF y lo organizamos por temas." @reintentar="cargarGuias">
            <template #cargando>
                <div class="guias-grid"><EsqueletoTarjeta v-for="n in 3" :key="n" :lineas="3" alto="150px" /></div>
            </template>
            <div class="guias-grid">
                <GuiaCard v-for="guia in guias" :key="guia.id" :guia="guia" />
            </div>
        </EstadoVista>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, BookOpen } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import GuiaCard from '@/components/personal/GuiaCard.vue'
import SubirGuiaForm from '@/components/personal/SubirGuiaForm.vue'
import { listarMisGuias } from '@/services/guiaService'

const guias = ref([])
const cargando = ref(true)
const error = ref('')
const mostrarForm = ref(false)

async function cargarGuias() {
    cargando.value = true
    error.value = ''
    try {
        guias.value = await listarMisGuias()
    } catch (err) {
        error.value = 'No se pudieron cargar tus guías. Intenta de nuevo.'
    } finally {
        cargando.value = false
    }
}

function alSubir(guia) {
    guias.value.unshift(guia)
    mostrarForm.value = false
}

onMounted(cargarGuias)
</script>

<style scoped>
.mis-guias {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-width: 1100px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

.guias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
    gap: 1rem;
}
</style>
