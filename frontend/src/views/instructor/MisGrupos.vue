<template>
    <main class="mis-grupos">
        <EncabezadoPagina titulo="Mis grupos" :icono="Users"
            descripcion="Gestiona tus grupos de formación y comparte su código de acceso con tus aprendices.">
            <template #acciones>
                <button ref="botonCrear" type="button" class="btn btn-primario" @click="creando = true">
                    <Plus :size="18" aria-hidden="true" /> Crear grupo
                </button>
            </template>
        </EncabezadoPagina>

        <!-- Este EstadoVista cubre cargando y error. Sin grupos, las cifras se ven en 0 y el vacío va abajo -->
        <EstadoVista :cargando="cargando" :error="error" texto-cargando="Cargando tus grupos…" @reintentar="cargar">
            <template #cargando>
                <div class="estadisticas">
                    <EsqueletoTarjeta v-for="n in 3" :key="n" />
                </div>
                <div class="grupos-grid">
                    <EsqueletoTarjeta v-for="n in 3" :key="n" :lineas="5" :con-icono="false" alto="340px" />
                </div>
            </template>

            <section class="estadisticas" aria-label="Resumen de tus grupos">
                <StatCard v-for="stat in estadisticas" :key="stat.etiqueta" v-bind="stat" />
            </section>

            <EstadoVista v-if="!grupos.length" vacio :icono="Users" texto-vacio="Aún no tienes grupos"
                descripcion-vacio="Crea tu primer grupo y comparte su código de acceso con tus aprendices.">
                <template #vacio>
                    <button type="button" class="btn btn-primario btn-sm" @click="creando = true">
                        <Plus :size="16" aria-hidden="true" /> Crear mi primer grupo
                    </button>
                </template>
            </EstadoVista>

            <template v-else>
                <!-- Solo filtra la lista ya cargada: no hay búsqueda en el backend -->
                <label class="buscador">
                    <Search :size="18" aria-hidden="true" />
                    <input ref="campoBusqueda" v-model="busqueda" type="search" placeholder="Buscar por nombre de grupo…"
                        aria-label="Buscar por nombre de grupo" />
                </label>

                <p v-if="!filtrados.length" class="sin-resultados">Ningún grupo coincide con “{{ busqueda }}”.</p>
                <div v-else class="grupos-grid">
                    <GrupoInstructorCard v-for="grupo in filtrados" :key="grupo.id" :grupo="grupo"
                        @eliminar="porEliminar = $event" />
                </div>
            </template>
        </EstadoVista>

        <CrearGrupoModal v-if="creando" @cerrar="creando = false" @creado="alCrear" />
        <EliminarGrupoModal v-if="porEliminar" :grupo="porEliminar" @cerrar="porEliminar = null"
            @eliminado="alEliminar" />

        <!-- Avisos de crear/eliminar para el lector de pantalla (y a la vista, un momento) -->
        <p class="aviso" role="status">{{ aviso }}</p>
    </main>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Users, Plus, Search, BookOpen, GraduationCap } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import GrupoInstructorCard from '@/components/instructor/GrupoInstructorCard.vue'
import CrearGrupoModal from '@/components/instructor/CrearGrupoModal.vue'
import EliminarGrupoModal from '@/components/instructor/EliminarGrupoModal.vue'
import { listarGrupos } from '@/services/grupoService'

const grupos = ref([])
const cargando = ref(true)
const error = ref('')
const busqueda = ref('')
const creando = ref(false)
const porEliminar = ref(null)
const aviso = ref('')
const botonCrear = ref(null)
const campoBusqueda = ref(null)

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        grupos.value = await listarGrupos()
    } catch {
        error.value = 'No se pudieron cargar tus grupos. Intenta de nuevo.'
    } finally {
        cargando.value = false
    }
}

const filtrados = computed(() => {
    const texto = busqueda.value.trim().toLowerCase()
    if (!texto) return grupos.value
    return grupos.value.filter((g) => g.nombre.toLowerCase().includes(texto))
})

// Cifras con los conteos que ya manda GET /grupos: sin peticiones extra
const suma = (campo) => grupos.value.reduce((total, g) => total + (g[campo] ?? 0), 0)
const estadisticas = computed(() => [
    { etiqueta: 'Grupos', valor: grupos.value.length, detalle: 'que administras', icono: Users, color: 'primary' },
    { etiqueta: 'Estudiantes', valor: suma('estudiantes_count'), detalle: 'en tus grupos', icono: GraduationCap, color: 'info' },
    { etiqueta: 'Guías asignadas', valor: suma('guias_count'), detalle: 'en tus grupos', icono: BookOpen, color: 'success' }
])

// El backend responde 201 con el grupo, pero sin conteos ni semana: se completan con los valores
// de un grupo nuevo, así la tarjeta aparece al instante sin volver a pedir la lista
function alCrear(grupo) {
    creando.value = false
    grupos.value = [
        { estudiantes_count: 0, guias_count: 0, mini_proyectos_count: 0, semana_actual: 1, ...grupo },
        ...grupos.value
    ]
    busqueda.value = ''
    aviso.value = `Grupo «${grupo.nombre}» creado. Código de acceso: ${grupo.codigo_acceso}.`
}

async function alEliminar(id) {
    const nombre = porEliminar.value?.nombre
    porEliminar.value = null
    grupos.value = grupos.value.filter((g) => g.id !== id)
    aviso.value = `Grupo «${nombre}» eliminado.`
    // La papelera que abrió la ventana desapareció con su tarjeta: el foco iría a ninguna parte.
    // Se lleva al buscador (o a "Crear grupo" si ya no quedan grupos) para seguir con el teclado
    await nextTick()
    ;(campoBusqueda.value ?? botonCrear.value)?.focus()
}

onMounted(cargar)
</script>

<style scoped>
.mis-grupos {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

.estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: var(--espacio-md);
}

/* 3 columnas en escritorio; se reducen solas hasta 1 en celular */
.grupos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 290px), 1fr));
    gap: var(--espacio-md);
}

.buscador {
    display: flex;
    align-items: center;
    gap: .7rem;
    padding: 0 1rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
    color: var(--color-text-secondary);
}

.buscador:focus-within {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

.buscador input {
    flex: 1;
    min-width: 0;
    min-height: 44px;
    border: none;
    outline: none;
    background: transparent;
    color: var(--color-text);
    font: inherit;
}

.sin-resultados {
    color: var(--color-text-secondary);
}

.aviso {
    min-height: 1.2rem;
    color: var(--color-success);
    font-size: .88rem;
    text-align: center;
}
</style>
