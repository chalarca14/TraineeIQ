<template>
    <main class="dashboard-content">
        <BannerBienvenida :nombre="primerNombre"
            mensaje="Te damos la bienvenida a tu espacio de aprendizaje. Aquí tienes un resumen de tu progreso." />

        <section class="stats-grid">
            <StatCard v-for="stat in estadisticas" :key="stat.etiqueta" v-bind="stat" />
        </section>

        <div class="fila-principal">
            <GuiaActualCard :guia="guiaActual" :cargando="cargandoGuias" :error="errorGuias" />
            <SiguientePaso :guia="guiaActual" :cargando="cargandoGuias" :error="errorGuias" />
        </div>
    </main>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { BookOpen, CalendarDays, Star, Target } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { listarMisGuias } from '@/services/guiaService'
import StatCard from '@/components/dashboard/StatCard.vue'
import BannerBienvenida from '@/components/dashboard/BannerBienvenida.vue'
import GuiaActualCard from '@/components/dashboard/GuiaActualCard.vue'
import SiguientePaso from '@/components/dashboard/SiguientePaso.vue'

const authStore = useAuthStore()

// POR SI EL USUARIO NO TRAE NOMBRE
const primerNombre = computed(() => authStore.usuario?.name?.split(' ')[0] ?? 'estudiante')


//GUIAS ACTIVAS - DATOS REALES
const errorGuias = ref(false)

const guias = ref(null)        // null = todavía cargando


onMounted(async () => {
    try {
        guias.value = await listarMisGuias()
    } catch (error) {
        console.error('Error al obtener las guías:', error)
        errorGuias.value = true
    }
})


//DATOS DE EJEMPLOS: El backend aun no tiene semana, XP, ni progreso.
//CUANDO EXISTAN LOS ENDPOINTS, ESTO SE DEBE REEMPLAZAR POR DATOS REALES

const resumenEjemplo = {
    semanaActual: 5,
    semanasTotales: 12,
    xp: 850,
    xpEstaSemana: 120,
    progreso: 68 // Porcentaje de progreso en la guía
}

const totalGuias = computed(() => guias.value?.length ?? null)
const guiaActual = computed(() => guias.value?.[0] ?? null)
const cargandoGuias = computed(() => guias.value === null && !errorGuias.value)

// computed porque depende de totalGuias y errorGuias, que cambian cuando llega la respuesta
const estadisticas = computed(() => [
    {
        etiqueta: 'Guías activas',
        valor: errorGuias.value ? '—' : (totalGuias.value ?? '…'),
        detalle: errorGuias.value ? 'No se pudo cargar' : 'en Modo Personal',
        icono: BookOpen
    },
    {
        etiqueta: 'Semana actual',
        valor: resumenEjemplo.semanaActual,
        detalle: `de ${resumenEjemplo.semanasTotales}`,
        icono: CalendarDays
    },
    {
        etiqueta: 'XP acumulados',
        valor: resumenEjemplo.xp,
        detalle: `+${resumenEjemplo.xpEstaSemana} esta semana`,
        icono: Star
    },
    {
        etiqueta: 'Progreso general',
        valor: `${resumenEjemplo.progreso}%`,
        progreso: resumenEjemplo.progreso,
        icono: Target
    }
])

</script>

<style scoped>
.dashboard-page {
    min-height: 100dvh;
    background: var(--color-background);
    color: var(--color-text);
    font-family: Puritan;
}


.dashboard-section {
    margin-bottom: 2.5rem;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.section-header h2 {
    margin: 0;
    font-size: 1.15rem;
}

.btn-secundario {
    display: flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .9rem;
    border: 1px solid var(--color-card);
    border-radius: 10px;
    background: transparent;
    color: var(--color-text-secondary);
    font-size: .82rem;
    cursor: not-allowed;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: .5rem;
    padding: 2.5rem 1rem;
    border: 1px dashed var(--color-card);
    border-radius: 14px;
    color: var(--color-text-secondary);
}

.empty-state p {
    margin: 0;
    color: var(--color-text);
    font-weight: 600;
}

.empty-state span {
    font-size: .85rem;
}

.grupos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.grupo-card {
    padding: 1.2rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
    transition: .2s;
}

.grupo-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-3px);
}

.grupo-card h3 {
    margin: 0 0 .4rem 0;
    font-size: 1rem;
}

.grupo-descripcion {
    margin: 0 0 .8rem 0;
    font-size: .82rem;
    color: var(--color-text-secondary);
    line-height: 1.4;
}

.grupo-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: .78rem;
}

.grupo-meta .codigo {
    font-family: monospace;
    background: var(--color-background);
    padding: .2rem .5rem;
    border-radius: 6px;
    color: var(--color-primary);
}


.dashboard-content {
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

.dashboard-content h1 {
    margin: 0 0 .4rem 0;
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.subtitulo {
    color: var(--color-text-secondary);
    margin-bottom: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}


/* Guía actual (más ancha) + siguiente paso, lado a lado */
.fila-principal {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
    align-items: stretch;
    margin-bottom: 2rem;
}

/* Sin esto, la fila de temas con scroll ensancha la columna */
.fila-principal > * {
    min-width: 0;
}

/* CELULAR / TABLET: se apilan */
@media (max-width: 900px) {
    .fila-principal {
        grid-template-columns: 1fr;
    }
}
</style>
