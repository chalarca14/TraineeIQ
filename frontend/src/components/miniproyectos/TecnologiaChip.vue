<template>
    <span class="chip" :style="{ '--c': tecnologia.color }">
        <!-- Con logo oficial si lo tenemos; si no, una insignia de color con la abreviatura -->
        <span v-if="logo" class="insignia con-logo" aria-hidden="true"><img :src="logo" alt="" /></span>
        <b v-else class="insignia" aria-hidden="true">{{ tecnologia.abrev }}</b>
        {{ tecnologia.etiqueta }}
    </span>
</template>

<script setup>
import { computed } from 'vue'
import python from '@/assets/logos/python.svg'
import javascript from '@/assets/logos/javascript.svg'
import java from '@/assets/logos/java.svg'
import csharp from '@/assets/logos/csharp.svg'
import html from '@/assets/logos/html5.svg'
import css from '@/assets/logos/css3.svg'

const props = defineProps({ tecnologia: { type: Object, required: true } })

// Los logos van por nombre de tecnología (el mismo que devuelve tecnologiasDelProyecto).
// Se mapean aquí, y no en utils/, para que utils siga siendo JavaScript puro y fácil de probar.
// SQL, Vue y PHP no tienen logo aún: usan la insignia con abreviatura.
const LOGOS = { Python: python, JavaScript: javascript, Java: java, 'C#': csharp, HTML: html, CSS: css }

const logo = computed(() => LOGOS[props.tecnologia.etiqueta] ?? null)
</script>

<style scoped>
.chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .15rem .65rem .15rem .2rem;
    border: 1px solid color-mix(in srgb, var(--c) 35%, transparent);
    border-radius: 999px;
    background: color-mix(in srgb, var(--c) 10%, var(--color-surface));
    color: var(--color-text);
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}

.insignia {
    display: grid;
    place-items: center;
    min-width: 22px;
    height: 22px;
    padding: 0 .25rem;
    border-radius: 999px;
    background: var(--c);
    color: var(--color-background);
    font-size: .62rem;
}

/* Fondo claro detrás del logo: los logos de marca están pensados para fondos claros */
.insignia.con-logo {
    /* Tamaño FIJO: con width:100% en la imagen y sin ancho en el contenedor, el SVG tomaba su
       tamaño natural (grande) y deformaba toda la etiqueta */
    width: 22px;
    padding: 3px;
    background: var(--color-text);
}

.insignia img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}
</style>
