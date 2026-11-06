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

        <p v-if="cargando" class="estado">Cargando tus guías...</p>

        <div v-else-if="error" class="estado error">
            <p>{{ error }}</p>
            <button type="button" class="btn btn-secundario btn-sm" @click="cargarGuias">Reintentar</button>
        </div>

        <div v-else-if="guias.length === 0" class="empty-state">
            <Medalla :icono="BookOpen" :size="56" />
            <p>Aún no has subido ninguna guía.</p>
            <span>Sube tu primer PDF y lo organizamos por temas.</span>
        </div>

        <div v-else class="guias-grid">
            <GuiaCard v-for="guia in guias" :key="guia.id" :guia="guia" />
        </div>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, BookOpen } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import Medalla from '@/components/ui/Medalla.vue'
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

.estado {
    color: var(--color-text-secondary);
}

.estado.error {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .6rem;
    padding: 2rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 16px;
    color: var(--color-danger);
    text-align: center;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 16px;
    color: var(--color-text-secondary);
    text-align: center;
}

.empty-state p {
    color: var(--color-text);
    font-weight: 600;
}

.guias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
    gap: 1rem;
}
</style>
