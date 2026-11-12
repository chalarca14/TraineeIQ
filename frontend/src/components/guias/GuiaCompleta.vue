<template>
    <section class="completa" aria-labelledby="titulo-completa">
        <header ref="barra" class="barra">
            <button type="button" class="btn btn-secundario btn-sm" @click="$emit('volver')">
                <ArrowLeft :size="15" aria-hidden="true" /> Volver a la semana
            </button>
        </header>

        <!-- tabindex="-1": permite mandar el foco aquí por código al abrir la vista, para que quien usa
             lector de pantalla o teclado sepa que el contenido cambió. No entra en el orden de Tab. -->
        <h2 id="titulo-completa" ref="titulo" tabindex="-1">Guía completa</h2>

        <p class="aviso-pdf" role="note">
            <Info :size="18" aria-hidden="true" />
            <span>TraineeIQ guarda el texto de la guía, no el PDF original. Conserva tu copia del PDF.</span>
        </p>

        <p v-if="cargando" class="estado" role="status">Cargando la guía completa…</p>

        <div v-else-if="error" class="error" role="alert">
            <span>{{ error }}</span>
            <button type="button" class="btn btn-secundario btn-sm" @click="cargar">Reintentar</button>
        </div>

        <p v-else-if="!semanas.length" class="estado">Esta guía todavía no tiene texto.</p>

        <template v-else>
            <article v-for="s in semanas" :key="s.numero_semana" class="semana">
                <h3>Semana {{ s.numero_semana }}<template v-if="s.titulo">: {{ s.titulo }}</template></h3>

                <!-- El backend no manda el texto de lo no liberado (llega null), pero se revisan las dos
                     cosas: así nunca se pinta una semana bloqueada aunque cambie ese detalle -->
                <p v-if="!s.disponible || s.contenido_texto == null" class="proximamente">
                    <Lock :size="15" aria-hidden="true" /> Próximamente
                </p>
                <!-- Organizado en títulos, párrafos y código (TextoGuia); como texto, nunca v-html -->
                <TextoGuia v-else-if="s.contenido_texto.trim()" :texto="s.contenido_texto" />
                <p v-else class="estado">Esta semana no trae texto.</p>
            </article>

            <!-- Solo un "Volver" a la vista: este aparece cuando el de arriba ya salió de la pantalla
                 (guía larga, al final). En una guía corta los dos se verían juntos, así que se oculta. -->
            <button v-show="!arribaVisible" type="button" class="btn btn-secundario btn-sm abajo" @click="$emit('volver')">
                <ArrowLeft :size="15" aria-hidden="true" /> Volver a la semana
            </button>
        </template>
    </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { ArrowLeft, Info, Lock } from 'lucide-vue-next'
import { getTextoGuia } from '@/services/guiaService'
import TextoGuia from '@/components/guias/TextoGuia.vue'
import { mensajeDeError } from '@/utils/errores'

// Todas las semanas como un documento continuo. Funciona igual en los dos modos: el backend ya
// filtra lo que el usuario no tiene liberado (título sí, texto null), así que aquí no se decide nada.
const props = defineProps({ guiaId: { type: Number, required: true } })
defineEmits(['volver'])

const titulo = ref(null)
const barra = ref(null)
// true mientras el "Volver" de arriba se ve en pantalla (al abrir, siempre se ve)
const arribaVisible = ref(true)
let observador = null
const semanas = ref([])
const cargando = ref(true)
const error = ref('')

async function cargar() {
    cargando.value = true
    error.value = ''
    try {
        semanas.value = [...(await getTextoGuia(props.guiaId))].sort((a, b) => a.numero_semana - b.numero_semana)
    } catch (e) {
        error.value = mensajeDeError(e)
    } finally {
        cargando.value = false
    }
}

onMounted(() => {
    cargar()
    // El título existe desde el primer momento: el foco no espera a que llegue el texto
    nextTick(() => titulo.value?.focus())

    // IntersectionObserver avisa cuando un elemento entra o sale de la pantalla, sin escuchar cada
    // movimiento del scroll (más liviano que un evento 'scroll'). Funciona aunque quien scrollea
    // sea .app-main y no la ventana.
    observador = new IntersectionObserver(([entrada]) => {
        arribaVisible.value = entrada.isIntersecting
    })
    observador.observe(barra.value)
})

// Al salir de la vista se deja de observar, para no dejar el observador vivo en memoria
onBeforeUnmount(() => observador?.disconnect())
</script>

<style scoped>
.completa {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
    padding: 1.1rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
}

.barra {
    display: flex;
}

h2 {
    font-size: 1.15rem;
}

/* El foco por código no necesita el aro: no es un control, solo orienta a quien usa lector de pantalla */
h2:focus {
    outline: none;
}

h3 {
    font-size: 1rem;
    overflow-wrap: anywhere;
}

.aviso-pdf {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    margin: 0;
    padding: .6rem .8rem;
    border-radius: 10px;
    background: color-mix(in srgb, var(--color-info) 12%, transparent);
    font-size: .85rem;
    overflow-wrap: anywhere;
}

.aviso-pdf svg {
    flex-shrink: 0;
    margin-top: .1rem;
    color: var(--color-info);
}

.semana {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-border);
}

.proximamente {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    align-self: flex-start;
    padding: .25rem .7rem;
    border-radius: 999px;
    background: var(--color-card);
    color: var(--color-text-secondary);
    font-size: .8rem;
    font-weight: 700;
}

.estado {
    color: var(--color-text-secondary);
}

.error {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .6rem;
    padding: .7rem .9rem;
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-danger) 12%, transparent);
    color: var(--color-danger);
    font-size: .88rem;
    overflow-wrap: anywhere;
}

.abajo {
    align-self: flex-start;
}

@media (max-width: 520px) {
    .completa { padding: .9rem; }
}
</style>
