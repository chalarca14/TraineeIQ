<template>
    <!-- El texto de la guía ya organizado en bloques (utils/textoGuia.js). Todo se pinta con {{ }}:
         nunca v-html, así nada del PDF se interpreta como HTML -->
    <div class="texto-guia">
        <!-- Los bloques 'semana' no se pintan: la cabecera de la pantalla ya dice qué semana es -->
        <template v-for="(b, i) in bloques" :key="i">
            <h3 v-if="b.tipo === 'titulo'" class="titulo">{{ b.texto }}</h3>

            <h4 v-else-if="b.tipo === 'subtitulo'" class="subtitulo">{{ b.texto }}</h4>

            <!-- "Duración de la guía: 4 semanas": la etiqueta resaltada, el valor normal -->
            <p v-else-if="b.tipo === 'parrafo'" class="parrafo">
                <strong v-if="b.etiqueta" class="etiqueta">{{ b.etiqueta }}:</strong>
                <TextoConCodigo :texto="b.texto" />
            </p>

            <ul v-else-if="b.tipo === 'lista'" class="lista">
                <li v-for="(item, j) in b.items" :key="j"><TextoConCodigo :texto="item" /></li>
            </ul>

            <figure v-else-if="b.tipo === 'codigo'" class="codigo">
                <figcaption>
                    <span>{{ b.lenguaje }}</span>
                    <button type="button" class="copiar" :aria-label="`Copiar el código (${b.lenguaje})`" @click="copiar(i, b)">
                        <Check v-if="copiado === i" :size="14" aria-hidden="true" />
                        <Copy v-else :size="14" aria-hidden="true" />
                        {{ copiado === i ? 'Copiado' : 'Copiar' }}
                    </button>
                </figcaption>
                <!-- pre conserva la sangría reconstruida; tabindex permite desplazarlo con el teclado -->
                <pre tabindex="0"><code>{{ b.lineas.join('\n') }}</code></pre>
            </figure>

            <aside v-else-if="b.tipo === 'actividad'" class="actividad">
                <strong><ClipboardList :size="18" aria-hidden="true" /> {{ b.titulo }}</strong>
                <p><TextoConCodigo :texto="b.texto" /></p>
            </aside>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { Copy, Check, ClipboardList } from 'lucide-vue-next'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'
import { organizarTexto } from '@/utils/textoGuia'

const props = defineProps({ texto: { type: String, default: '' } })

const bloques = computed(() => organizarTexto(props.texto))

// Índice del bloque recién copiado (para mostrar "Copiado" un momento)
const copiado = ref(null)
let temporizador = null

async function copiar(indice, bloque) {
    clearTimeout(temporizador)
    try {
        await navigator.clipboard.writeText(bloque.lineas.join('\n'))
        copiado.value = indice
        temporizador = setTimeout(() => { copiado.value = null }, 2000)
    } catch {
        // Sin permiso del portapapeles: el código sigue a la vista y se puede seleccionar a mano
        copiado.value = null
    }
}

onUnmounted(() => clearTimeout(temporizador))
</script>

<style scoped>
.texto-guia {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-md);
    max-width: 75ch;
    overflow-wrap: anywhere;
}

.titulo {
    padding-bottom: var(--espacio-sm);
    border-bottom: 1px solid var(--color-card);
    color: var(--color-primary-light);
    font-size: .8rem;
    letter-spacing: .08em;
    text-transform: uppercase;
}

/* Cada tema abre una sección: más espacio arriba que abajo, para que se vea dónde empieza */
.subtitulo {
    margin-top: var(--espacio-sm);
    color: var(--color-text);
    font-size: 1.08rem;
    line-height: 1.35;
}

.subtitulo:first-child,
.titulo + .subtitulo {
    margin-top: 0;
}

.parrafo,
.lista {
    color: color-mix(in srgb, var(--color-text) 88%, var(--color-text-secondary));
    font-size: 1rem;
    line-height: 1.75;
}

/* margin y no un espacio en el HTML: Vue quita los espacios entre etiquetas al compilar la plantilla */
.etiqueta {
    margin-right: .3em;
    color: var(--color-text);
    font-weight: 700;
}

.lista {
    display: flex;
    flex-direction: column;
    gap: .35rem;
    margin: 0;
    padding-left: 1.3rem;
}

.lista li::marker {
    color: var(--color-primary-light);
}

.codigo {
    margin: 0;
    overflow: hidden;
    border: 1px solid var(--color-card);
    border-radius: 12px;
    /* Un poco más claro que el área de lectura, para que el código se distinga como bloque */
    background: color-mix(in srgb, var(--color-background) 55%, var(--color-card));
}

.codigo figcaption {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--espacio-sm);
    padding: .35rem .5rem .35rem .9rem;
    border-bottom: 1px solid var(--color-card);
    background: var(--color-surface);
    color: var(--color-text-secondary);
    font-size: .75rem;
    font-weight: 600;
}

.copiar {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    min-height: 32px;
    padding: .2rem .6rem;
    border: 1px solid transparent;
    border-radius: 8px;
    background: none;
    color: var(--color-text-secondary);
    font: inherit;
    cursor: pointer;
}

.copiar:hover {
    border-color: var(--color-card);
    color: var(--color-text);
}

/* El código no se parte en líneas: si es ancho, el bloque se desplaza de lado (y no la página) */
.codigo pre {
    margin: 0;
    padding: var(--espacio-md);
    overflow-x: auto;
    color: var(--color-text);
    font-family: Consolas, 'Courier New', monospace;
    font-size: .88rem;
    line-height: 1.6;
    white-space: pre;
    tab-size: 4;
}

/* variables.css pone Puritan en TODOS los elementos (selector *): el <code> no hereda la fuente del
   <pre>, así que también se le indica aquí la monoespaciada */
.codigo code {
    font-family: Consolas, 'Courier New', monospace;
}

.codigo pre:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: -2px;
}

.actividad {
    display: flex;
    flex-direction: column;
    gap: var(--espacio-xs);
    padding: var(--espacio-md);
    border: 1px solid color-mix(in srgb, var(--color-warning) 40%, transparent);
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-warning) 10%, transparent);
}

.actividad strong {
    display: flex;
    align-items: center;
    gap: .4rem;
    color: var(--color-warning);
}

.actividad p {
    line-height: 1.7;
}

@media (max-width: 520px) {
    .parrafo,
    .lista {
        font-size: .95rem;
    }

    .codigo pre {
        font-size: .8rem;
    }
}
</style>
