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
/* Contenedor central: limita el ancho para que no se estire en pantallas grandes */
.dashboard-content {
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

/* Las 4 tarjetas de estadísticas: se acomodan solas según el ancho disponible */
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
.fila-principal>* {
    min-width: 0;
}

/* Celular / tablet: se apilan */
@media (max-width: 900px) {
    .fila-principal {
        grid-template-columns: 1fr;
    }
}
</style>