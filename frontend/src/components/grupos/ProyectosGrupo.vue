<template>
    <div class="proyectos">
        <p v-if="!hayProyectos" class="vacio">
            Aún no hay mini proyectos en las semanas liberadas. Aparecerán cuando la IA prepare su contenido.
        </p>

        <section v-for="bloque in bloques" :key="bloque.guia.id" class="guia">
            <h3 :title="bloque.guia.nombre">{{ bloque.guia.nombre }}</h3>

            <!-- Una guía que falla no tumba las demás: aviso propio con su reintento (como en Mini proyectos) -->
            <div v-if="bloque.fallo" class="aviso" role="alert">
                <span>No se pudieron cargar los proyectos de esta guía.</span>
                <button type="button" class="btn btn-secundario btn-sm" :disabled="reintentando.includes(bloque.guia.id)"
                    @click="alReintentar(bloque.guia.id)">
                    {{ reintentando.includes(bloque.guia.id) ? 'Reintentando...' : 'Reintentar' }}
                </button>
            </div>

            <div v-for="semana in bloque.semanas" :key="semana.numero_semana" class="semana">
                <!-- No liberada por el instructor: "Próximamente" con candado, sin contenido (respondería 403) -->
                <SemanaBloqueada v-if="!semana.disponible" :semana="semana" />

                <template v-else>
                    <h4>Semana {{ semana.numero_semana }}<template v-if="semana.titulo">: {{ semana.titulo }}</template></h4>
                    <p v-if="semana.proyectos.length === 0" class="vacio">
                        {{ semana.estado_generacion === 'listo'
                            ? 'Esta semana no tiene mini proyectos.'
                            : 'El contenido de esta semana aún se está preparando.' }}
                    </p>
                    <div v-else class="cuadricula">
                        <ProyectoCard v-for="p in semana.proyectos" :key="p.id" :proyecto="p" @abrir="abrir" />
                    </div>
                </template>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import ProyectoCard from '@/components/miniproyectos/ProyectoCard.vue'
import SemanaBloqueada from '@/components/miniproyectos/SemanaBloqueada.vue'

// `reintentar` llega como función (y no como evento) porque el botón necesita ESPERAR a que
// termine para quitar el "Reintentando..."; un emit no devuelve la promesa.
const props = defineProps({
    bloques: { type: Array, required: true },
    reintentar: { type: Function, required: true }
})

const router = useRouter()
const reintentando = ref([]) // ids de las guías que se están reintentando

// Sin XP: es Modo Grupo, así que ProyectoCard y SemanaBloqueada van sin esPersonal
const hayProyectos = computed(() =>
    props.bloques.some((b) => b.semanas.some((s) => s.proyectos.length > 0))
)

async function alReintentar(guiaId) {
    reintentando.value.push(guiaId)
    try {
        await props.reintentar(guiaId)
    } finally {
        reintentando.value = reintentando.value.filter((id) => id !== guiaId)
    }
}

// La tarjeta solo avisa "abrir este proyecto"; el detalle (Tarea 3) es el mismo para los dos modos
function abrir(proyecto) {
    router.push({ name: 'MiniProyectoDetalle', params: { id: proyecto.id } })
}
</script>

<style scoped>
.proyectos {
    display: flex;
    flex-direction: column;
    gap: 1.4rem;
}

.guia,
.semana {
    display: flex;
    flex-direction: column;
    gap: .7rem;
}

h3 {
    overflow: hidden;
    font-size: 1.05rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

h4 {
    color: var(--color-text-secondary);
    font-size: .92rem;
    overflow-wrap: anywhere;
}

.vacio {
    color: var(--color-text-secondary);
}

.cuadricula {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 220px), 1fr));
    gap: 1rem;
}

.aviso {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    padding: .6rem .8rem;
    border-radius: 10px;
    background: color-mix(in srgb, var(--color-warning) 14%, transparent);
    color: var(--color-warning);
    font-size: .85rem;
}
</style>
