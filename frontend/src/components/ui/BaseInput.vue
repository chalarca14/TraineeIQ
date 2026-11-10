<template>
    <div class="input-group">

        <label v-if="label" :for="inputId">{{ label }}</label>

        <div class="input-wrapper">
            <slot name="icon"></slot>

            <input
                :id="inputId"
                :type="type"
                :placeholder="placeholder"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
            />

            <slot name="action"></slot>
        </div>

    </div>
</template>

<script setup>
import { useId } from 'vue'

defineProps({
    label: String,
    type: { type: String, default: 'text' },
    placeholder: String,
    modelValue: String
})

defineEmits(['update:modelValue'])

// Genera un id único automáticamente para enlazar <label for="..."> con el <input id="...">
// sin que tengas que pasarlo a mano cada vez que uses este componente
const inputId = useId()
</script>

<style scoped>
.input-group {
    display: flex;
    flex-direction: column;
    gap: .6rem;
    margin-bottom: 1.3rem;
    width: 100%;
    min-width: 0;
}

.input-wrapper {
    display: flex;
    align-items: center;
    width: 100%;
    min-width: 0;
    background: var(--color-background);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 0 .9rem;
    transition: .25s;
}

input {
    flex: 1;
    min-width: 0;
    width: 100%;
    padding: 1rem;
    border: none;
    outline: none;
    background: none;
    color: var(--color-text);
    font-size: .95rem;
}

label {
    color: var(--color-text);
    font-size: .95rem;
    font-weight: 600;
}


.input-wrapper:focus-within {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
}

input::placeholder {
    color: var(--color-text-secondary);
}

.icon {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-secondary);
    margin-right: .8rem;
    flex-shrink: 0;
}

@media (max-width: 700px) {
    .input-wrapper {
        padding: 0 .7rem;
        gap: 1rem;
    }

    input {
        width: 100%;
        padding: .8rem;
    }
}

/* Autocompletado del navegador — forzamos que respete tu tema oscuro */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
    /* Truco: un box-shadow interno del tamaño del input, del color de tu fondo,
       "tapa" el fondo blanco que el navegador quiere forzar */
    box-shadow: 0 0 0 1000px var(--color-background) inset;

    /* Fuerza el color del texto (el navegador también fuerza esto por su cuenta) */
    -webkit-text-fill-color: var(--color-text);

    /* Retrasa la transición de color del navegador casi al infinito,
       para que nunca alcances a ver el parpadeo del amarillo/blanco original */
    transition: background-color 9999s ease-in-out 0s;
}
</style>