<template>
    <main class="dashboard-content">
        <BannerBienvenida :nombre="primerNombre"
            mensaje="Te damos la bienvenida a tu espacio de aprendizaje. Aquí tienes un resumen de tu progreso." />

        <section class="stats-grid">
            <StatCard v-for="stat in estadisticas" :key="stat.etiqueta" v-bind="stat" />
        </section>

        <GuiaActualCard :guia="guiaActual" :cargando="cargandoGuias" :error="errorGuias" />

        <section class="dashboard-section">
            <h2>Guía activa de la semana</h2>

            <div v-if="guiaActiva" class="guia-card">
                <div class="guia-info">
                    <span class="guia-grupo">{{ guiaActiva.grupoNombre }}</span>
                    <h3>{{ guiaActiva.nombre }}</h3>

                    <div class="progreso-wrapper">
                        <div class="progreso-barra">
                            <div class="progreso-relleno" :style="{ width: porcentajeProgreso + '%' }"></div>
                        </div>
                        <span class="progreso-texto">Semana {{ guiaActiva.semanaActual }} de {{
                            guiaActiva.semanasTotales }}</span>
                    </div>
                </div>

                <button class="btn-primario" disabled title="Próximamente">
                    <FileText :size="16" />
                    Ver guía
                </button>
            </div>

            <div v-else class="empty-state">
                <BookOpen :size="32" />
                <p>No tienes una guía activa esta semana.</p>
            </div>
        </section>


        <section class="dashboard-section">
            <h2>Accesos rápidos</h2>

            <div class="accesos-grid">
                <div v-for="acceso in accesosRapidos" :key="acceso.titulo" class="acceso-card">
                    <div class="acceso-icon">
                        <component :is="acceso.icono" :size="24" />
                    </div>
                    <div>
                        <h3>{{ acceso.titulo }}</h3>
                        <p>{{ acceso.descripcion }}</p>
                    </div>
                    <span class="badge-proximamente">Próximamente</span>
                </div>
            </div>
        </section>
    </main>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { Plus, Users, FileText, BookOpen, Sparkles, Rocket, CalendarDays, Star, Target } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { listarMisGuias } from '@/services/guiaService'
import StatCard from '@/components/dashboard/StatCard.vue'
import BannerBienvenida from '@/components/dashboard/BannerBienvenida.vue'
import GuiaActualCard from '@/components/dashboard/GuiaActualCard.vue'

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

// DATOS DE PRUEBA

const grupos = [
    {
        id: 1,
        nombre: 'ADSO 2847520',
        descripcion: 'Análisis y Desarrollo de Software — Ficha 2847520',
        semanaActual: 12,
        codigoAcceso: 'ADSO-X7K2'
    },

    {
        id: 2,
        nombre: 'Refuerzo JavaScript',
        descripcion: 'Grupo de nivelación en fundamentos de JS',
        semanaActual: 4,
        codigoAcceso: 'JS-REF9'
    }
]


// Dato de prueba — en el backend real, esta sería la guía de tipo 'grupo'
// cuyo grupo_id coincide con el grupo donde el estudiante tiene semana_actual más reciente

const guiaActiva = {
    nombre: 'Guía 12 — Consumo de APIs con Fetch',
    grupoNombre: 'ADSO 2847520',
    semanaActual: 12,
    semanasTotales: 20
}

const porcentajeProgreso = computed(() =>
    Math.round((guiaActiva.semanaActual / guiaActiva.semanasTotales) * 100)
)

// Estos dos quedan "apagados" hasta que construyamos esas tarjetas del backlog

const accesosRapidos = [
    {
        titulo: 'Recomendaciones IA',
        descripcion: 'Sugerencias personalizadas según tu avance en la guía.',
        icono: Sparkles
    },
    {
        titulo: 'Mini Proyectos',
        descripcion: 'Ejercicios prácticos clasificados por dificultad.',
        icono: Rocket
    }
]

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

.guia-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 1.5rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
}

.guia-info {
    flex: 1;
    min-width: 0;
}

.guia-grupo {
    font-size: .78rem;
    color: var(--color-primary);
    font-weight: 600;
}

.guia-info h3 {
    margin: .3rem 0 .9rem 0;
    font-size: 1.05rem;
}

.progreso-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.progreso-barra {
    flex: 1;
    height: 8px;
    border-radius: 999px;
    background: var(--color-background);
    overflow: hidden;
}

.progreso-relleno {
    height: 100%;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    border-radius: 999px;
    transition: width .4s ease;
}

.progreso-texto {
    font-size: .8rem;
    color: var(--color-text-secondary);
    white-space: nowrap;
}

.btn-primario {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .7rem 1.2rem;
    border: none;
    border-radius: 10px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    color: white;
    font-weight: 600;
    font-size: .85rem;
    cursor: not-allowed;
    opacity: .6;
    flex-shrink: 0;
}

.accesos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.acceso-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.2rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
    opacity: .7;
}

.acceso-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 44px;
    border-radius: 10px;
    background: var(--color-background);
    color: var(--color-primary);
}

.acceso-card h3 {
    margin: 0 0 .3rem 0;
    font-size: .95rem;
}

.acceso-card p {
    margin: 0;
    font-size: .8rem;
    color: var(--color-text-secondary);
    line-height: 1.4;
}

.badge-proximamente {
    position: absolute;
    top: .8rem;
    right: .8rem;
    font-size: .68rem;
    padding: .2rem .5rem;
    border-radius: 999px;
    background: rgba(124, 58, 237, .15);
    color: var(--color-primary);
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}


/* CELULAR */
@media (max-width: 700px) {
    .guia-card {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-primario {
        justify-content: center;
    }
}
</style>
