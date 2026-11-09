<template>
    <article class="tema" :class="{ abierto }">
        <!-- aria-expanded le dice al lector de pantalla si el panel está abierto; aria-controls, cuál es el panel -->
        <button type="button" class="cabecera" :aria-expanded="abierto" :aria-controls="idPanel" @click="abierto = !abierto">
            <span class="orden" aria-hidden="true">{{ tema.orden }}</span>
            <strong>{{ tema.nombre_tema }}</strong>
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
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-surface);
    transition: border-color .2s;
}

.tema.abierto,
.tema:hover {
    border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
}

.cabecera {
    display: flex;
    align-items: center;
    gap: .8rem;
    width: 100%;
    padding: .7rem .9rem;
    border: none;
    background: none;
    color: var(--color-text);
    font-size: .92rem;
    text-align: left;
    cursor: pointer;
}

.cabecera strong {
    flex: 1;
    min-width: 0;
    overflow-wrap: anywhere;
}

.orden {
    display: grid;
    place-items: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    border-radius: 50%;
    background: color-mix(in srgb, var(--color-primary) 22%, var(--color-card));
    color: var(--color-primary-light);
    font-size: .75rem;
    font-weight: 700;
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
    padding: 0 .9rem .9rem 3.2rem;
    color: var(--color-text-secondary);
    font-size: .88rem;
    overflow-wrap: anywhere;
}

@media (prefers-reduced-motion: reduce) {
    .chevron {
        transition: none;
    }
}
</style>
