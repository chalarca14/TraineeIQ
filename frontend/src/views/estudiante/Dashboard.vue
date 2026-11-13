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

        <!-- Este EstadoVista solo cubre cargando y error. Sin datos no se tapa todo: las tarjetas se ven
             en 0 y el mensaje de vacío va abajo, en los dos modos -->
        <EstadoVista :cargando="cargando" :error="error" texto-cargando="Cargando tu resumen..." @reintentar="cargar">
            <!-- Mismas cuadrículas que el contenido real: así no hay salto cuando llegan los datos -->
            <template #cargando>
                <div class="stats-grid"><EsqueletoTarjeta v-for="n in 4" :key="n" /></div>
                <div class="fila-principal">
                    <EsqueletoTarjeta :lineas="4" :con-icono="false" alto="220px" />
                    <EsqueletoTarjeta :lineas="3" :con-icono="false" alto="220px" />
                </div>
            </template>
            <section class="stats-grid">
                <StatCard v-for="stat in estadisticas" :key="stat.etiqueta" v-bind="stat" />
            </section>

            <!-- Un solo EstadoVista para los tres vacíos; cambian los textos (VACIOS) -->
            <EstadoVista v-if="sinDatos" vacio :icono="vacioActual.icono" :texto-vacio="vacioActual.texto"
                :descripcion-vacio="vacioActual.descripcion">
                <template #vacio>
                    <RouterLink v-if="vacioActual.accion" :to="vacioActual.accion.ruta" class="btn btn-primario btn-sm">
                        {{ vacioActual.accion.texto }}
                    </RouterLink>
                </template>
            </EstadoVista>

            <div v-else class="fila-principal">
                <GuiaActualCard :bloque="guiaActual" :es-personal="esPersonal" :grupo="grupo" />
                <SiguientePaso :siguiente="siguiente" :hay-proyectos="proyectos.length > 0" />
            </div>
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
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import GuiaActualCard from '@/components/dashboard/GuiaActualCard.vue'
import SiguientePaso from '@/components/dashboard/SiguientePaso.vue'

const authStore = useAuthStore()
const {
    esPersonal, cargar, cargando, error, vacio,
    grupos, grupoId, grupo, xp, errorXp,
    bloques, proyectos, evaluados, puntajePromedio, semanaActual, guiaActual, siguiente
} = useResumenDashboard()

// Por si el usuario no trae nombre
const primerNombre = computed(() => authStore.usuario?.name?.split(' ')[0] ?? 'estudiante')

// Un vacío por situación; se muestra debajo de las tarjetas
const VACIOS = {
    'sin-guias': {
        icono: BookOpen,
        texto: 'Aún no tienes una guía personal',
        descripcion: 'Sube tu guía en PDF para generar tu plan semanal y tus mini proyectos.',
        accion: { texto: 'Subir mi primera guía', ruta: '/estudiante/guias' }
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

// Sin guía personal, sin grupo o sin semanas liberadas: las tarjetas muestran 0 (no "—" ni "0 / 0"),
// porque de verdad no hay nada que contar
const sinDatos = computed(() => Boolean(vacio.value))

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
        valor: semanaActual.value.numero ?? (sinDatos.value ? 0 : '—'),
        detalle: sinDatos.value ? 'sin guía activa'
            : semanaActual.value.total ? `de ${semanaActual.value.total}` : 'Sin semanas abiertas',
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
        // Sin semanas liberadas, semana_actual vale 1 por defecto: no se muestra como si hubiera una
        valor: sinDatos.value ? 0 : (grupo.value?.semana_actual ?? '—'),
        detalle: grupo.value?.nombre ?? 'sin grupo',
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
    tarjetaAvance('Evaluados', `${evaluados.value.length} / ${proyectos.value.length}`, '0'),
    {
        etiqueta: 'Puntaje promedio',
        valor: sinDatos.value ? 0 : (puntajePromedio.value ?? '—'),
        detalle: sinDatos.value ? 'aún no tienes evaluaciones' : 'promedio de tus últimas evaluaciones · orientativo',
        icono: Award,
        color: 'warning'
    }
])

// Tarjeta con barra: sin proyectos abiertos no hay barra, solo el aviso. Sin datos: 0 y barra vacía
function tarjetaAvance(etiqueta, valor, valorSinDatos = '0%') {
    const base = { etiqueta, icono: Target, color: 'success' }
    if (sinDatos.value) return { ...base, valor: valorSinDatos, progreso: 0 }
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

/* Mi guía actual (y, al lado, el siguiente paso). min-width:0 evita que la fila de semanas
   con scroll ensanche la columna */
.fila-principal {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1rem;
    align-items: stretch;
}

.fila-principal>* {
    min-width: 0;
}

/* Celular / tablet: se apilan, y "Continúa donde quedaste" queda debajo de la guía */
@media (max-width: 900px) {
    .fila-principal {
        grid-template-columns: 1fr;
    }
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
