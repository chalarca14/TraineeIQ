<template>
    <article class="tema-card" :class="{ abierta: abierto }">
        <button type="button" class="tema-header" :aria-expanded="abierto" @click="abierto = !abierto">
            <span class="tema-orden">{{ tema.orden }}</span>
            <strong>{{ tema.nombre_tema }}</strong>
            <ChevronDown :size="18" class="chevron" :class="{ girado: abierto }" />
        </button>

        <div v-if="abierto" class="tema-body">
            <p class="tema-descripcion">{{ tema.descripcion }}</p>

            <div class="tema-acciones">
                <button type="button" class="btn btn-secundario btn-sm" :disabled="cargandoRec" @click="pedirRecomendaciones">
                    <Sparkles :size="16" />
                    {{ cargandoRec ? 'Generando...' : 'Generar recomendaciones' }}
                </button>
                <button type="button" class="btn btn-secundario btn-sm" :disabled="cargandoMini" @click="pedirMiniProyectos">
                    <Rocket :size="16" />
                    {{ cargandoMini ? 'Generando...' : 'Generar mini proyectos' }}
                </button>
            </div>

            <p v-if="errorRec" class="error">{{ errorRec }}</p>
            <p v-if="errorMini" class="error">{{ errorMini }}</p>

            <section v-if="recomendaciones.length" class="resultados">
                <h4><Medalla :icono="Sparkles" :size="26" /> Recomendaciones</h4>
                <div v-for="r in recomendaciones" :key="r.id" class="resultado">
                    <div class="resultado-top">
                        <strong>{{ leerContenido(r).titulo }}</strong>
                        <EtiquetaNivel :valor="r.nivel" />
                    </div>
                    <p><TextoConCodigo :texto="leerContenido(r).descripcion" /></p>
                </div>
            </section>

            <section v-if="miniProyectos.length" class="resultados">
                <h4><Medalla :icono="Rocket" :size="26" color="info" /> Mini proyectos</h4>
                <div v-for="m in miniProyectos" :key="m.id" class="resultado">
                    <div class="resultado-top">
                        <strong>{{ m.titulo }}</strong>
                        <EtiquetaNivel :valor="m.dificultad" />
                    </div>
                    <p><TextoConCodigo :texto="m.descripcion" /></p>
                    <details>
                        <summary>Ver instrucciones</summary>
                        <p class="pasos"><TextoConCodigo :texto="m.contenido" /></p>
                    </details>
                </div>
            </section>
        </div>
    </article>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronDown, Sparkles, Rocket } from 'lucide-vue-next'
import EtiquetaNivel from './EtiquetaNivel.vue'
import Medalla from '@/components/ui/Medalla.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'
import { generarRecomendaciones, leerContenido } from '@/services/recomendacionServices.js'
import { generarMiniProyectos } from '@/services/miniProyectoService'
import { mensajeDeError } from '@/utils/errores'

const props = defineProps({
    tema: { type: Object, required: true },
    guiaId: { type: [Number, String], required: true }
})

const abierto = ref(false)
const recomendaciones = ref([])
const miniProyectos = ref([])
const cargandoRec = ref(false)
const cargandoMini = ref(false)
const errorRec = ref('')
const errorMini = ref('')

async function pedirRecomendaciones() {
    errorRec.value = ''
    cargandoRec.value = true
    try {
        recomendaciones.value = await generarRecomendaciones(props.tema.id)
    } catch (err) {
        errorRec.value = mensajeDeError(err)
    } finally {
        cargandoRec.value = false
    }
}

async function pedirMiniProyectos() {
    errorMini.value = ''
    cargandoMini.value = true
    try {
        miniProyectos.value = await generarMiniProyectos({ guiaId: props.guiaId, temaId: props.tema.id })
    } catch (err) {
        errorMini.value = mensajeDeError(err)
    } finally {
        cargandoMini.value = false
    }
}
</script>

<style scoped>
.tema-card {
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
    overflow: hidden;
    transition: border-color .2s, box-shadow .2s;
}

.tema-card:hover,
.tema-card.abierta {
    border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
}

.tema-card.abierta {
    box-shadow: 0 8px 28px color-mix(in srgb, var(--color-primary) 14%, transparent);
}

.tema-header {
    display: flex;
    align-items: center;
    gap: .9rem;
    width: 100%;
    padding: 1rem 1.2rem;
    border: none;
    background: transparent;
    color: var(--color-text);
    font-family: inherit;
    font-size: 1rem;
    text-align: left;
    cursor: pointer;
}

.tema-header:hover {
    background: color-mix(in srgb, var(--color-primary) 8%, transparent);
}

/* Número del tema en un círculo con degradado, igual que las instrucciones del mini proyecto */
.tema-orden {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 50%;
    background: var(--gradiente-primario);
    color: var(--color-text);
    font-size: .82rem;
    font-weight: 700;
}

.tema-header strong {
    flex: 1;
    min-width: 0;
    overflow-wrap: anywhere;
}

.chevron {
    flex-shrink: 0;
    color: var(--color-text-secondary);
    transition: transform .2s;
}

.chevron.girado {
    transform: rotate(180deg);
}

.tema-body {
    padding: 0 1.2rem 1.2rem;
}

.tema-descripcion {
    margin: 0 0 1rem 0;
    color: var(--color-text-secondary);
    line-height: 1.5;
}

.tema-acciones {
    display: flex;
    flex-wrap: wrap;
    gap: .6rem;
}

.error {
    margin: .8rem 0 0 0;
    color: var(--color-danger);
    font-size: .85rem;
}

.resultados {
    margin-top: 1.2rem;
}

.resultados h4 {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 0 0 .6rem 0;
    font-size: .92rem;
}

.resultado {
    padding: .9rem 1rem;
    margin-bottom: .6rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-background);
}

.resultado-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .8rem;
}

.resultado p {
    margin: .4rem 0 0 0;
    font-size: .85rem;
    color: var(--color-text-secondary);
    line-height: 1.5;
}

.resultado summary {
    margin-top: .5rem;
    font-size: .8rem;
    color: var(--color-primary-light);
    cursor: pointer;
}

.pasos {
    white-space: pre-line;
}
</style>
