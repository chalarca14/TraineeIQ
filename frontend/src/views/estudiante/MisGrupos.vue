<template>
    <main class="mis-grupos">

        <!-- Sin botón en el banner: el de unirse vive en el estado vacío y, con grupos, en la columna
             derecha. Así no hay dos botones iguales a la vista a la vez. -->
        <EncabezadoPagina titulo="Mis grupos" :icono="Users" :descripcion="resumen" />

        <div class="contenido" :class="{ 'con-lateral': grupos.length > 0 }">
            <div class="principal">
                <!-- El buscador solo aparece si hay grupos que buscar -->
                <label v-if="grupos.length" class="buscador">
                    <Search :size="18" aria-hidden="true" />
                    <input v-model="busqueda" type="search" placeholder="Buscar por nombre de grupo..."
                        aria-label="Buscar por nombre de grupo" />
                </label>

                <EstadoVista :cargando="cargando" :error="error" :vacio="grupos.length === 0" :icono="Users"
                    texto-cargando="Cargando tus grupos..." texto-vacio="Aún no perteneces a ningún grupo"
                    descripcion-vacio="Pídele el código de acceso a tu instructor para unirte."
                    @reintentar="cargarGrupos">
                    <!-- Buscador y tarjetas. La columna "¿Tienes otro código?" no se adelanta: depende de
                         si hay grupos, y eso se sabe solo al terminar de cargar -->
                    <template #cargando>
                        <EsqueletoBloque alto="2.8rem" radio="12px" />
                        <div class="grupos-grid"><EsqueletoTarjeta v-for="n in 2" :key="n" :lineas="3" alto="280px" /></div>
                    </template>
                    <template #vacio>
                        <button class="btn btn-primario" type="button" @click="mostrarModal = true">
                            <Plus :size="18" /> Unirse a un grupo
                        </button>
                    </template>

                    <p v-if="filtrados.length === 0" class="sin-resultados">
                        Ningún grupo coincide con “{{ busqueda }}”.
                    </p>
                    <div v-else class="grupos-grid">
                        <GrupoCard v-for="grupo in filtrados" :key="grupo.id" :grupo="grupo" />
                    </div>
                </EstadoVista>
            </div>

            <!-- Columna derecha: solo con grupos (como la tarjeta "Unirse" del boceto 401) -->
            <aside v-if="grupos.length" class="lateral">
                <section class="tarjeta-unirse">
                    <span class="tarjeta-unirse__icono" aria-hidden="true"><KeyRound :size="22" /></span>
                    <h2>¿Tienes otro código?</h2>
                    <p>Únete a otro grupo con el código que te dio tu instructor.</p>
                    <button class="btn btn-primario" type="button" @click="mostrarModal = true">
                        <Plus :size="18" /> Unirse a un grupo
                    </button>
                </section>
            </aside>
        </div>

        <UnirseGrupoModal v-if="mostrarModal" @cerrar="mostrarModal = false" @unido="alUnirse" />

    </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Users, Plus, Search, KeyRound } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoTarjeta from '@/components/ui/EsqueletoTarjeta.vue'
import EsqueletoBloque from '@/components/ui/EsqueletoBloque.vue'
import GrupoCard from '@/components/grupos/GrupoCard.vue'
import UnirseGrupoModal from '@/components/grupos/UnirseGrupoModal.vue'
import { listarMisGrupos } from '@/services/grupoService'

const grupos = ref([])
const cargando = ref(true)
const error = ref('')
const busqueda = ref('')
const mostrarModal = ref(false)

// Busca en el navegador (la lista ya está cargada): sin llamadas extra al backend
const filtrados = computed(() => {
    const texto = busqueda.value.trim().toLowerCase()
    if (!texto) return grupos.value
    return grupos.value.filter((g) => g.nombre.toLowerCase().includes(texto))
})

// Resumen del banner. Las tarjetas de guías y mini proyectos se agregan cuando el backend mande los conteos
const resumen = computed(() => {
    if (cargando.value || error.value) return 'Los grupos de formación a los que perteneces.'
    const n = grupos.value.length
    if (n === 0) return 'Aún no perteneces a ningún grupo.'
    return `Perteneces a ${n} ${n === 1 ? 'grupo' : 'grupos'}.`
})

async function cargarGrupos() {
    cargando.value = true
    error.value = ''
    try {
        grupos.value = await listarMisGrupos()
    } catch (err) {
        error.value = 'No se pudieron cargar tus grupos. Intenta de nuevo.'
    } finally {
        cargando.value = false
    }
}

// Tras unirse se recarga desde el backend (no se agrega a mano) para ver el grupo con sus datos completos
function alUnirse() {
    mostrarModal.value = false
    cargarGrupos()
}

onMounted(cargarGrupos)
</script>

<style scoped>
.mis-grupos {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    max-width: 1100px;
    margin: 0 auto;
    padding: clamp(1rem, 4vw, 3rem);
}

/* Sin grupos es una sola columna; con grupos se suma la columna derecha */
.contenido {
    display: grid;
    gap: 1.2rem;
}

.contenido.con-lateral {
    grid-template-columns: minmax(0, 1fr) 280px;
    align-items: start;
}

.principal {
    display: flex;
    flex-direction: column;
    gap: 1.2rem;
    min-width: 0;
}

.tarjeta-unirse {
    display: flex;
    flex-direction: column;
    gap: .6rem;
    padding: 1.2rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

.tarjeta-unirse__icono {
    display: grid;
    place-items: center;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(145deg, color-mix(in srgb, var(--color-primary) 35%, var(--color-card)), color-mix(in srgb, var(--color-primary) 10%, var(--color-surface)));
    color: var(--color-primary-light);
}

.tarjeta-unirse h2 {
    font-size: 1.05rem;
}

.tarjeta-unirse p {
    color: var(--color-text-secondary);
    font-size: .88rem;
}

.tarjeta-unirse .btn {
    justify-content: center;
    margin-top: .3rem;
}

/* Tablet y celular: la tarjeta pasa arriba, a todo el ancho */
@media (max-width: 900px) {
    .contenido.con-lateral {
        grid-template-columns: minmax(0, 1fr);
    }

    .lateral {
        order: -1;
    }
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
    transition: .25s;
}

.buscador:focus-within {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

.buscador input {
    flex: 1;
    min-width: 0;
    padding: .9rem 0;
    border: none;
    outline: none;
    background: none;
    color: var(--color-text);
    font-size: .95rem;
}

.sin-resultados {
    padding: 2rem 1rem;
    color: var(--color-text-secondary);
    text-align: center;
}

.grupos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
    gap: 1rem;
}
</style>
