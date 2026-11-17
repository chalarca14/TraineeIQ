<template>
    <main class="instructor-dashboard">
        <BannerBienvenida :nombre="primerNombre" :mensaje="mensaje" />

        <!-- Este EstadoVista solo cubre cargando y error. Sin grupos, las cifras se ven en 0 y el vacío va
             debajo, igual que en el dashboard del aprendiz -->
        <EstadoVista :cargando="cargando" :error="error" texto-cargando="Cargando tu resumen…" @reintentar="cargar">
            <template #cargando>
                <div class="cifras">
                    <EsqueletoTarjeta v-for="n in 4" :key="n" />
                </div>
                <div class="columnas">
                    <EsqueletoTarjeta :lineas="4" :con-icono="false" alto="220px" />
                    <EsqueletoTarjeta :lineas="4" :con-icono="false" alto="220px" />
                </div>
            </template>

            <section class="cifras" aria-label="Resumen de tus grupos">
                <StatCard v-for="c in cifras" :key="c.etiqueta" v-bind="c" />
            </section>

            <EstadoVista v-if="!grupos.length" vacio :icono="Users" texto-vacio="Aún no tienes grupos"
                descripcion-vacio="Crea tu primer grupo y comparte su código de acceso con tus aprendices.">
                <template #vacio>
                    <RouterLink :to="{ name: 'InstructorMisGrupos' }" class="btn btn-primario btn-sm">
                        <Plus :size="16" aria-hidden="true" /> Crear mi primer grupo
                    </RouterLink>
                </template>
            </EstadoVista>

            <div v-else class="columnas">
                <GruposResumen :grupos="grupos" />
                <EntregasRecientes :grupos="grupos" :cuantas="6" titulo="Entregas recientes" />
            </div>
        </EstadoVista>
    </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Users, BookOpen, GraduationCap, Rocket, Plus } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import BannerBienvenida from '@/components/dashboard/BannerBienvenida.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import GruposResumen from '@/components/instructor/GruposResumen.vue'
import EntregasRecientes from '@/components/instructor/EntregasRecientes.vue'
import { listarGrupos } from '@/services/grupoService'

// Dashboard del instructor (Tarea I-6). Todo sale de GET /grupos (los conteos ya vienen en cada grupo)
// y de la primera página de entregas de cada grupo. Sin gráfica en el tiempo, "Progreso promedio",
// "+1 desde la semana pasada" ni "Guías más recientes" (tachada): el backend no tiene esos datos.
const authStore = useAuthStore()
const primerNombre = computed(() => authStore.usuario?.name?.split(' ')[0] ?? 'instructor')

// "Hoy es jueves, 12 de noviembre": la fecha del boceto, en palabras
const hoy = new Date().toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' })
const mensaje = `Hoy es ${hoy}. Aquí tienes el resumen de tus grupos y las últimas entregas de tus aprendices.`

const grupos = ref([])
const cargando = ref(true)
const error = ref('')

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        grupos.value = await listarGrupos()
    } catch {
        error.value = 'No pudimos cargar tu resumen. Intenta de nuevo.'
    } finally {
        cargando.value = false
    }
}

const suma = (campo) => grupos.value.reduce((total, g) => total + (g[campo] ?? 0), 0)
const cifras = computed(() => [
    { etiqueta: 'Grupos', valor: grupos.value.length, detalle: 'que administras', icono: Users, color: 'primary' },
    { etiqueta: 'Estudiantes', valor: suma('estudiantes_count'), detalle: 'en tus grupos', icono: GraduationCap, color: 'info' },
    { etiqueta: 'Guías asignadas', valor: suma('guias_count'), detalle: 'en tus grupos', icono: BookOpen, color: 'success' },
    { etiqueta: 'Mini proyectos', valor: suma('mini_proyectos_count'), detalle: 'en tus guías', icono: Rocket, color: 'warning' }
])

onMounted(cargar)
</script>

<style scoped>
.instructor-dashboard {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-width: 1100px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

.cifras {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: var(--espacio-md);
}

/* Dos columnas independientes: cada una crece con su contenido, sin huecos */
.columnas {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--espacio-md);
    align-items: start;
}

@media (max-width: 900px) {
    .columnas {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
