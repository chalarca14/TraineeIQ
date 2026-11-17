<template>
    <!-- Aprendiz (solo lectura): cada guía de la semana con su texto ordenado. Sin guías no se pinta nada -->
    <section v-if="!puedeGestionar && lista.length" class="lectura-materiales">
        <article v-for="m in lista" :key="m.id" class="material">
            <h3 class="titulo-material"><Paperclip :size="16" aria-hidden="true" /> Guía de la semana: «{{ m.nombre }}»</h3>
            <TextoGuia :texto="m.contenido_texto ?? ''" />
        </article>
    </section>
    <p v-else-if="!puedeGestionar && error" class="error" role="alert">{{ error }}</p>

    <!-- Instructor dueño de la guía de grupo: agregar, quitar y actualizar la semana con estas guías -->
    <section v-else-if="puedeGestionar" class="gestion" :aria-labelledby="idTitulo">
        <header class="cabecera">
            <h3 :id="idTitulo" class="titulo"><Paperclip :size="17" aria-hidden="true" /> Guías de la semana</h3>
            <button type="button" class="btn btn-secundario btn-sm" :disabled="cargando || actualizando" @click="modalAbierto = true">
                <Plus :size="15" aria-hidden="true" /> Agregar guía de la semana
            </button>
        </header>
        <p class="ayuda">La IA usará estas guías, junto con la planeación, para las recomendaciones y los mini proyectos de esta semana.</p>

        <p v-if="cargando" class="ayuda" role="status">Cargando las guías de la semana…</p>
        <ul v-else-if="lista.length" class="lista">
            <li v-for="m in lista" :key="m.id" class="item">
                <span class="nombre"><FileText :size="15" aria-hidden="true" /> {{ m.nombre }}</span>
                <!-- Confirmación dentro de la misma fila, sin window.confirm -->
                <span v-if="porQuitar === m.id" class="confirmar">
                    <span>¿Quitar esta guía?</span>
                    <button type="button" class="btn btn-peligro-borde btn-sm" :disabled="quitando" @click="quitar(m)">
                        {{ quitando ? 'Quitando…' : 'Sí, quitar' }}
                    </button>
                    <button type="button" class="btn btn-secundario btn-sm" :disabled="quitando" @click="porQuitar = null">Cancelar</button>
                </span>
                <button v-else type="button" class="btn btn-secundario btn-sm" :disabled="actualizando"
                    :aria-label="`Quitar la guía «${m.nombre}»`" @click="porQuitar = m.id">
                    <Trash2 :size="15" aria-hidden="true" /> Quitar
                </button>
            </li>
        </ul>
        <p v-else-if="!error" class="ayuda">Esta semana todavía no tiene guías adjuntas.</p>

        <!-- Semana ya preparada: se puede regenerar con las guías. Si no, se usarán al prepararla -->
        <template v-if="lista.length && !cargando">
            <div v-if="semanaLista || actualizando" class="actualizar">
                <template v-if="confirmandoActualizar">
                    <p class="advertencia" role="note">
                        Se regeneran las recomendaciones y, si ningún aprendiz ha entregado, también los mini proyectos. Usa 1 llamada a la IA.
                    </p>
                    <div class="botones">
                        <button type="button" class="btn btn-primario btn-sm" :disabled="actualizando" @click="actualizar">
                            {{ actualizando ? 'Actualizando…' : 'Sí, actualizar' }}
                        </button>
                        <button type="button" class="btn btn-secundario btn-sm" :disabled="actualizando" @click="confirmandoActualizar = false">Cancelar</button>
                    </div>
                </template>
                <button v-else type="button" class="btn btn-primario btn-sm" @click="confirmandoActualizar = true">
                    <RefreshCw :size="15" aria-hidden="true" /> Actualizar con las guías de la semana
                </button>
            </div>
            <p v-else class="ayuda">Se usarán cuando la semana se prepare.</p>
        </template>

        <p v-if="exito" class="exito" role="status">{{ exito }}</p>
        <p v-if="error" class="error" role="alert">{{ error }}</p>

        <SubirMaterialSemanaModal v-if="modalAbierto" :guia-id="guiaId" :numero="num"
            @cerrar="modalAbierto = false" @subida="alSubir" />
    </section>
</template>

<script setup>
import { ref, computed, watch, useId } from 'vue'
import { Paperclip, Plus, FileText, Trash2, RefreshCw } from 'lucide-vue-next'
import TextoGuia from '@/components/guias/TextoGuia.vue'
import SubirMaterialSemanaModal from '@/components/guias/SubirMaterialSemanaModal.vue'
import { listarMaterialesSemana, eliminarMaterialSemana } from '@/services/guiaService'
import { useContenidoStore } from '@/stores/contenido'

// "Guías de la semana": guías detalladas que el instructor adjunta a una semana de la planeación.
// La guía abierta y la semana salen del store de contenido (el mismo de la cola), para que el panel
// de la semana solo tenga que agregar una línea.
const props = defineProps({
    // Semana; si no llega, la abierta en el store
    numero: { type: Number, default: null },
    // estado_generacion de la semana: con 'listo' se ofrece "Actualizar"
    estado: { type: String, default: '' },
    // Instructor dueño de una guía de grupo: puede agregar, quitar y actualizar
    puedeGestionar: { type: Boolean, default: false },
    // Lista ya cargada (GET .../semanas/{n} la trae dentro de la semana): así no se repite la petición
    materiales: { type: Array, default: null }
})

const store = useContenidoStore()
const idTitulo = useId()

const guiaId = computed(() => store.guiaId)
const num = computed(() => props.numero ?? store.semanaActiva)
const semanaLista = computed(() => props.estado === 'listo')

const lista = ref([])
const cargando = ref(false)
const error = ref('')
const exito = ref('')
const modalAbierto = ref(false)
const porQuitar = ref(null)
const quitando = ref(false)
const confirmandoActualizar = ref(false)
const actualizando = ref(false)

// Trae las guías de la semana (o usa las que ya llegaron por prop) y limpia los mensajes de la anterior
async function cargar() {
    error.value = ''
    exito.value = ''
    porQuitar.value = null
    confirmandoActualizar.value = false

    if (props.materiales) {
        lista.value = [...props.materiales]
        return
    }
    if (!guiaId.value || !num.value) {
        lista.value = []
        return
    }

    const [guia, numero] = [guiaId.value, num.value]
    cargando.value = true
    try {
        const materiales = await listarMaterialesSemana(guia, numero)
        // Si mientras cargaba se cambió de guía o de semana, esta respuesta ya no aplica
        if (guia === guiaId.value && numero === num.value) lista.value = materiales
    } catch (e) {
        lista.value = []
        error.value = e.response?.status === 403
            ? 'No tienes acceso a las guías de esta semana.'
            : 'No pudimos cargar las guías de la semana. Intenta de nuevo más tarde.'
    } finally {
        cargando.value = false
    }
}

watch([guiaId, num, () => props.materiales], cargar, { immediate: true })

// Copia la lista al contenido de la semana abierta, para que la pestaña "Guía" muestre lo mismo sin recargar
function sincronizarContenido() {
    if (store.contenido && store.semanaActiva === num.value) {
        store.contenido.materiales = [...lista.value]
    }
}

// Respuesta de la ventana: agrega la guía y, si la semana ya estaba preparada, ofrece actualizarla
function alSubir(datos) {
    modalAbierto.value = false
    error.value = ''
    if (datos?.material) lista.value.push(datos.material)
    sincronizarContenido()
    exito.value = `Se agregó la guía «${datos?.material?.nombre ?? ''}».`
    confirmandoActualizar.value = Boolean(datos?.semana_lista)
}

// Quita una guía de la semana. Un 404 significa que ya no estaba: también se saca de la lista
async function quitar(material) {
    quitando.value = true
    error.value = ''
    exito.value = ''
    try {
        await eliminarMaterialSemana(guiaId.value, num.value, material.id)
        lista.value = lista.value.filter((m) => m.id !== material.id)
        exito.value = `Se quitó la guía «${material.nombre}».`
    } catch (e) {
        const status = e.response?.status
        if (status === 404) {
            lista.value = lista.value.filter((m) => m.id !== material.id)
        } else {
            error.value = status === 403
                ? (e.response.data?.message ?? 'Solo el instructor del grupo puede quitar guías de esta semana.')
                : 'No se pudo quitar la guía. Intenta de nuevo.'
        }
    } finally {
        quitando.value = false
        porQuitar.value = null
        sincronizarContenido()
    }
}

// Regenera la semana con forzar a través del store (recarga el contenido al terminar).
// soloRecomendaciones = el backend dejó los mini proyectos porque ya había entregas
async function actualizar() {
    actualizando.value = true
    error.value = ''
    exito.value = ''
    try {
        const { ok, soloRecomendaciones } = await store.reintentar(num.value)
        if (ok) {
            exito.value = soloRecomendaciones
                ? 'Se actualizaron las recomendaciones. Los mini proyectos no cambiaron porque ya hay entregas.'
                : 'Se actualizaron las recomendaciones y los mini proyectos.'
        } else {
            error.value = store.aviso || 'No pudimos actualizar la semana. Intenta más tarde.'
        }
    } catch {
        error.value = 'No pudimos actualizar la semana. Intenta más tarde.'
    } finally {
        actualizando.value = false
        confirmandoActualizar.value = false
    }
}
</script>

<style scoped>
.lectura-materiales,
.gestion,
.actualizar {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-sm);
}

/* Misma área de lectura de la pestaña Guía, con el borde de acento en el color secundario */
.material {
    padding: var(--espacio-lg);
    border: 1px solid var(--color-card);
    border-left: 3px solid var(--color-primary-light);
    border-radius: 14px;
    background: var(--color-background);
}

.gestion {
    padding: var(--espacio-md);
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
}

.cabecera,
.item,
.confirmar,
.botones {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--espacio-sm);
}

.cabecera,
.item { justify-content: space-between; }

.titulo,
.titulo-material,
.nombre {
    display: flex;
    align-items: center;
    gap: .4rem;
    min-width: 0;
    color: var(--color-text);
    overflow-wrap: anywhere;
}

.titulo,
.titulo-material { font-size: 1rem; }
.titulo-material { margin-bottom: var(--espacio-sm); }
.titulo svg,
.titulo-material svg,
.nombre svg { flex-shrink: 0; color: var(--color-primary-light); }

.lista {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-xs);
    padding: 0;
    list-style: none;
}

.item {
    padding: var(--espacio-sm) var(--espacio-md);
    border-radius: 10px;
    background: var(--color-background);
}

.actualizar { align-items: flex-start; }

.advertencia {
    padding: .6rem .8rem;
    border-radius: 10px;
    background: color-mix(in srgb, var(--color-warning) 12%, transparent);
    color: var(--color-warning);
}

.ayuda, .confirmar, .advertencia, .exito, .error { font-size: .88rem; }
.ayuda, .confirmar { color: var(--color-text-secondary); }
.exito { color: var(--color-success); }
.error { color: var(--color-danger); }

@media (max-width: 520px) {
    .material { padding: var(--espacio-md); }
}
</style>
