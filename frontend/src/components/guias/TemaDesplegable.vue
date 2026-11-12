<template>
    <article class="tema" :class="{ abierto }">
        <!-- aria-expanded le dice al lector de pantalla si el panel está abierto; aria-controls, cuál es el panel -->
        <button type="button" class="cabecera" :aria-expanded="abierto" :aria-controls="idPanel" @click="abierto = !abierto">
            <span class="orden" aria-hidden="true">{{ tema.orden }}</span>
            <span class="titulos">
                <strong>{{ tema.nombre_tema }}</strong>
                <!-- Cerrado: el inicio de la descripción, para saber de qué trata sin abrirlo -->
                <span v-if="!abierto && tema.descripcion" class="vista-previa">{{ tema.descripcion }}</span>
            </span>
            <ChevronDown :size="18" class="chevron" :class="{ girado: abierto }" aria-hidden="true" />
        </button>

        <!-- Texto del backend: se pinta con {{ }} (TextoConCodigo), nunca con v-html -->
        <div v-if="abierto" :id="idPanel" class="cuerpo">
            <p><TextoConCodigo :texto="tema.descripcion" /></p>
        </div>
    </article>
</template>

<script setup>
import { ref, useId } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'

defineProps({ tema: { type: Object, required: true } })

const abierto = ref(false)
// useId da un id único por componente: así aria-controls apunta al panel correcto aunque haya muchos temas
const idPanel = useId()
</script>

<style scoped>
.tema {
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-background);
    transition: border-color .2s;
}

.tema.abierto,
.tema:hover {
    border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
}

.cabecera {
    display: flex;
    align-items: center;
    gap: var(--espacio-md);
    width: 100%;
    min-height: 44px;
    padding: var(--espacio-md);
    border: none;
    background: none;
    color: var(--color-text);
    font-size: .92rem;
    text-align: left;
    cursor: pointer;
}

.titulos {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: .2rem;
    min-width: 0;
}

.titulos strong {
    overflow-wrap: anywhere;
}

/* Dos líneas como máximo: es una vista previa, el texto completo se ve al abrir */
.vista-previa {
    display: -webkit-box;
    overflow: hidden;
    color: var(--color-text-secondary);
    font-size: .82rem;
    font-weight: 400;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
}

.orden {
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    border-radius: 50%;
    background: color-mix(in srgb, var(--color-primary) 22%, var(--color-card));
    color: var(--color-primary-light);
    font-size: .85rem;
    font-weight: 700;
}

.tema.abierto .orden {
    background: var(--gradiente-primario);
    color: var(--color-text);
}

.chevron {
    flex-shrink: 0;
    color: var(--color-text-secondary);
    transition: transform .2s;
}

.chevron.girado {
    transform: rotate(180deg);
}

.cuerpo {
    padding: 0 var(--espacio-md) var(--espacio-md) 4rem;
    color: var(--color-text-secondary);
    font-size: .92rem;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

@media (max-width: 520px) {
    .cuerpo {
        padding-left: var(--espacio-md);
    }
}

@media (prefers-reduced-motion: reduce) {
    .chevron {
        transition: none;
    }
}
</style>
