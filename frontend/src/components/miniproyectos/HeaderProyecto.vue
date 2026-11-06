<template>
    <header class="hero">
        <div class="brillo" aria-hidden="true"></div>

        <span class="icono" :style="{ '--c': visual.color }" aria-hidden="true">
            <component :is="visual.icono" :size="40" />
        </span>

        <div class="datos">
            <h1>{{ proyecto.titulo }}</h1>
            <p><TextoConCodigo :texto="proyecto.descripcion" /></p>
            <div class="etiquetas">
                <EtiquetaNivel :valor="proyecto.dificultad" />
                <TecnologiaChip v-for="t in tecnologias" :key="t.etiqueta" :tecnologia="t" />
                <span v-if="etiquetaTema" class="tag" :title="etiquetaTema">{{ etiquetaTema }}</span>
            </div>
        </div>

        <!-- Ilustración decorativa: una ventana de código dibujada con SVG (sin imágenes externas) -->
        <svg class="ilustracion" viewBox="0 0 220 150" aria-hidden="true">
            <rect class="ventana" x="20" y="14" width="170" height="112" rx="12" />
            <circle class="punto d" cx="38" cy="32" r="4" />
            <circle class="punto w" cx="52" cy="32" r="4" />
            <circle class="punto s" cx="66" cy="32" r="4" />
            <rect class="linea p" x="36" y="52" width="70" height="7" rx="3.5" />
            <rect class="linea i" x="50" y="68" width="100" height="7" rx="3.5" />
            <rect class="linea s" x="50" y="84" width="64" height="7" rx="3.5" />
            <rect class="linea w" x="36" y="100" width="88" height="7" rx="3.5" />
            <rect class="flota" x="150" y="2" width="44" height="34" rx="10" />
            <text class="flota-texto" x="172" y="25" text-anchor="middle">&lt;/&gt;</text>
        </svg>
    </header>
</template>

<script setup>
import { computed } from 'vue'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import TecnologiaChip from '@/components/miniproyectos/TecnologiaChip.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'
import { iconoDelProyecto, tecnologiasDelProyecto } from '@/utils/iconosProyecto'

const props = defineProps({ proyecto: { type: Object, required: true } })

const visual = computed(() => iconoDelProyecto(props.proyecto))
const tecnologias = computed(() => tecnologiasDelProyecto(props.proyecto))
const etiquetaTema = computed(() => props.proyecto.tema?.nombre_tema ?? props.proyecto.guiaNombre ?? '')
</script>

<style scoped>
.hero {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.2rem;
    overflow: hidden;
    padding: 1.4rem;
    border: 1px solid color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
    border-radius: 18px;
    background: linear-gradient(110deg, color-mix(in srgb, var(--color-primary) 38%, var(--color-surface)), var(--color-surface) 70%);
}

/* Resplandor morado detrás de la ilustración */
.brillo {
    position: absolute;
    top: -40%;
    right: -5%;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    background: radial-gradient(circle, color-mix(in srgb, var(--color-primary-light) 35%, transparent), transparent 65%);
    pointer-events: none;
}

.icono {
    position: relative;
    display: grid;
    place-items: center;
    width: 84px;
    height: 84px;
    flex-shrink: 0;
    border-radius: 20px;
    color: var(--c);
    background: linear-gradient(145deg, color-mix(in srgb, var(--c) 30%, var(--color-card)), color-mix(in srgb, var(--c) 10%, var(--color-surface)));
    box-shadow: 0 8px 28px color-mix(in srgb, var(--c) 35%, transparent), inset 0 0 0 1px color-mix(in srgb, var(--c) 40%, transparent);
}

.datos {
    position: relative;
    flex: 1;
    min-width: 0;
}

h1 {
    font-size: clamp(1.25rem, 3vw, 1.75rem);
    overflow-wrap: anywhere;
}

.datos p {
    margin: .35rem 0 .8rem;
    max-width: 60ch;
    color: var(--color-text-secondary);
}

.etiquetas {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
}

.tag {
    overflow: hidden;
    max-width: 100%;
    padding: .2rem .7rem;
    border-radius: 999px;
    background: var(--color-card);
    color: var(--color-text-secondary);
    font-size: .72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ilustracion {
    position: relative;
    width: 190px;
    flex-shrink: 0;
}

.ventana {
    fill: var(--color-background);
    stroke: color-mix(in srgb, var(--color-primary-light) 60%, transparent);
    stroke-width: 1.5;
}

.punto.d { fill: var(--color-danger); }
.punto.w { fill: var(--color-warning); }
.punto.s { fill: var(--color-success); }

.linea { opacity: .85; }
.linea.p { fill: var(--color-primary-light); }
.linea.i { fill: var(--color-info); }
.linea.s { fill: var(--color-success); }
.linea.w { fill: var(--color-warning); }

.flota {
    fill: var(--color-primary);
}

.flota-texto {
    fill: var(--color-text);
    font-size: 15px;
    font-weight: 700;
}

/* En pantallas angostas se quita la ilustración y el icono se reduce para que el título respire */
@media (max-width: 760px) {
    .ilustracion {
        display: none;
    }
}

@media (max-width: 520px) {
    .hero {
        flex-direction: column;
        align-items: flex-start;
    }

    .icono {
        width: 64px;
        height: 64px;
    }
}
</style>
