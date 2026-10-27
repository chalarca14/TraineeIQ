<template>
    <main class="mis-guias">

        <header class="page-header">
            <div>
                <h1>Mis guías</h1>
                <p>Sube tus guías en PDF y las dividimos en temas para estudiarlas con ayuda de la IA.</p>
            </div>
            <button class="btn-subir" type="button" @click="mostrarForm = !mostrarForm">
                <Plus :size="18" />
                Subir guía
            </button>
        </header>

        <SubirGuiaForm v-if="mostrarForm" @subida="alSubir" @cancelar="mostrarForm = false" />

        <p v-if="cargando" class="estado">Cargando tus guías...</p>
        <p v-else-if="error" class="estado error">{{ error }}</p>

        <div v-else-if="guias.length === 0" class="empty-state">
            <BookOpen :size="32" />
            <p>Aún no has subido ninguna guía.</p>
            <span>Sube tu primer PDF y lo organizamos por temas.</span>
        </div>

        <div v-else class="guias-grid">
            <article v-for="guia in guias" :key="guia.id" class="guia-card">
                <span class="guia-icon">
                    <BookOpen :size="22" />
                </span>
                <div class="guia-info">
                    <h3>{{ guia.nombre }}</h3>
                    <p>{{ guia.temas?.length ?? 0 }} temas · {{ guia.created_at }}</p>

                    <RouterLink :to="`/estudiante/guias/${guia.id}`" class="btn-ver">Ver temas</RouterLink>
                </div>
                <button type="button" class="btn-ver" disabled title="Próximamente">Ver temas</button>
            </article>
        </div>

    </main>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, BookOpen } from 'lucide-vue-next'
import SubirGuiaForm from '@/components/personal/SubirGuiaForm.vue'
import { listarMisGuias } from '@/services/guiaService'

const guias = ref([])
const cargando = ref(true)
const error = ref('')
const mostrarForm = ref(false)

async function cargarGuias() {
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
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.page-header h1 {
    margin: 0 0 .3rem 0;
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.page-header p {
    margin: 0;
    color: var(--color-text-secondary);
}

.btn-subir {
    display: flex;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1.2rem;
    border: none;
    border-radius: 12px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    color: white;
    font-weight: 600;
    cursor: pointer;
    flex-shrink: 0;
}

.estado {
    color: var(--color-text-secondary);
}

.estado.error {
    color: var(--color-danger);
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
    text-align: center;
}

.empty-state p {
    margin: 0;
    color: var(--color-text);
    font-weight: 600;
}

.guias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}


.guia-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 12px;
    background: rgba(124, 58, 237, .18);
    color: var(--color-primary);
}


.btn-ver {
    padding: .5rem .9rem;
    border: 1px solid var(--color-card);
    border-radius: 10px;
    color: var(--color-text);
    font-size: .8rem;
    text-decoration: none;
    transition: .2s;
}

.btn-ver:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

@media (max-width: 600px) {
    .page-header {
        flex-direction: column;
    }
}
</style>