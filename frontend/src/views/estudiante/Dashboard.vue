<template>
    <main class="dashboard-content">
        <BannerBienvenida :nombre="primerNombre"
            mensaje="Te damos la bienvenida a tu espacio de aprendizaje. Aquí tienes un resumen de tu progreso." />

        <!-- Varios grupos: el aprendiz elige cuál resumir. Con uno solo no hace falta preguntar -->
        <div v-if="!esPersonal && grupos.length > 1" class="selector-grupo">
            <label for="dashboard-grupo">Grupo</label>
            <select id="dashboard-grupo" v-model="grupoId">
                <option v-for="g in grupos" :key="g.id" :value="g.id">{{ g.nombre }}</option>
            </select>
        </div>

        <EstadoVista :cargando="cargando" :error="error" :vacio="Boolean(vacio)" :icono="vacioActual.icono"
            texto-cargando="Cargando tu resumen..." :texto-vacio="vacioActual.texto"
            :descripcion-vacio="vacioActual.descripcion" @reintentar="cargar">
            <template #vacio>
                <RouterLink v-if="vacioActual.accion" :to="vacioActual.accion.ruta" class="btn btn-primario btn-sm">
                    {{ vacioActual.accion.texto }}
                </RouterLink>
            </template>

            <section class="stats-grid">
                <StatCard v-for="stat in estadisticas" :key="stat.etiqueta" v-bind="stat" />
            </section>
        </EstadoVista>
    </main>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { BookOpen, CalendarDays, Star, Target, Rocket, Award, Users, Lock } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useResumenDashboard } from '@/composables/useResumenDashboard'
import StatCard from '@/components/dashboard/StatCard.vue'
import BannerBienvenida from '@/components/dashboard/BannerBienvenida.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'

const authStore = useAuthStore()
const {
    esPersonal, cargar, cargando, error, vacio,
    grupos, grupoId, grupo, xp, errorXp,
    bloques, proyectos, evaluados, puntajePromedio, semanaActual
} = useResumenDashboard()

// Por si el usuario no trae nombre
const primerNombre = computed(() => authStore.usuario?.name?.split(' ')[0] ?? 'estudiante')

// Un vacío por situación. El de Personal no menciona el PDF: subir la guía se explica en "Mis guías"
const VACIOS = {
    'sin-guias': {
        icono: BookOpen,
        texto: 'Sube tu primera guía',
        descripcion: 'Con ella preparamos tus semanas, recomendaciones y mini proyectos.',
        accion: { texto: 'Ir a Mis guías', ruta: '/estudiante/guias' }
    },
    'sin-grupos': {
        icono: Users,
        texto: 'Aún no perteneces a ningún grupo',
        descripcion: 'Pídele a tu instructor el código del grupo para unirte.',
        accion: { texto: 'Unirme a un grupo', ruta: '/estudiante/grupos' }
    },
    'sin-semanas': {
        icono: Lock,
        texto: 'Tu instructor aún no ha liberado semanas',
        descripcion: 'Cuando libere la primera, aquí verás tu avance y tus mini proyectos.',
        accion: null
    }
}
const vacioActual = computed(() => VACIOS[vacio.value] ?? { icono: null, texto: '', descripcion: '', accion: null })

// Fracción entera de 0 a 100; null si no hay proyectos (no se inventa un 0 %)
const porcentaje = computed(() =>
    proyectos.value.length ? Math.round((evaluados.value.length / proyectos.value.length) * 100) : null
)

// Modo Personal: XP real. Ni "+N esta semana" ni nivel: el backend no los tiene
const tarjetasPersonal = computed(() => [
    {
        etiqueta: 'Guías activas',
        valor: bloques.value.length,
        detalle: 'en Modo Personal',
        icono: BookOpen,
        color: 'primary'
    },
    {
        etiqueta: 'Semana actual',
        valor: semanaActual.value.numero ?? '—',
        detalle: semanaActual.value.total ? `de ${semanaActual.value.total}` : 'Sin semanas abiertas',
        icono: CalendarDays,
        color: 'info'
    },
    {
        etiqueta: 'XP acumulados',
        valor: errorXp.value ? '—' : (xp.value ?? '…'),
        detalle: errorXp.value ? 'No se pudo cargar' : 'en Modo Personal',
        icono: Star,
        color: 'warning'
    },
    tarjetaAvance('Avance de los proyectos abiertos')
])

// Modo Grupo: sin XP ni niveles. El puntaje es orientativo, como la evaluación de la que sale
const tarjetasGrupo = computed(() => [
    {
        etiqueta: 'Semana actual del grupo',
        valor: grupo.value?.semana_actual ?? '—',
        detalle: grupo.value?.nombre ?? '',
        icono: CalendarDays,
        color: 'info'
    },
    {
        etiqueta: 'Mini proyectos disponibles',
        valor: proyectos.value.length,
        detalle: 'de las semanas liberadas',
        icono: Rocket,
        color: 'primary'
    },
    tarjetaAvance('Evaluados', `${evaluados.value.length} / ${proyectos.value.length}`),
    {
        etiqueta: 'Puntaje promedio',
        valor: puntajePromedio.value ?? '—',
        detalle: 'promedio de tus últimas evaluaciones · orientativo',
        icono: Award,
        color: 'warning'
    }
])

// Tarjeta con barra: sin proyectos abiertos no hay barra, solo el aviso
function tarjetaAvance(etiqueta, valor) {
    const base = { etiqueta, icono: Target, color: 'success' }
    if (porcentaje.value === null) return { ...base, valor: '—', detalle: 'Aún no hay proyectos abiertos' }
    return { ...base, valor: valor ?? `${porcentaje.value}%`, progreso: porcentaje.value }
}

const estadisticas = computed(() => (esPersonal.value ? tarjetasPersonal.value : tarjetasGrupo.value))

onMounted(cargar)
</script>

<style scoped>
/* Contenedor central: limita el ancho para que no se estire en pantallas grandes */
.dashboard-content {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    padding: clamp(1rem, 4vw, 3rem);
    max-width: 1100px;
    margin: 0 auto;
}

/* Las 4 tarjetas de estadísticas: se acomodan solas según el ancho disponible */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
}

.selector-grupo {
    display: flex;
    align-items: center;
    gap: .7rem;
}

.selector-grupo label {
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.selector-grupo select {
    min-width: 0;
    max-width: 100%;
    padding: .5rem .8rem;
    border: 1px solid var(--color-card);
    border-radius: 10px;
    background: var(--color-surface);
    color: var(--color-text);
    font: inherit;
}

/* Celular: el selector ocupa todo el ancho, con la etiqueta arriba */
@media (max-width: 600px) {
    .selector-grupo {
        flex-direction: column;
        align-items: stretch;
        gap: .3rem;
    }
}
</style>
