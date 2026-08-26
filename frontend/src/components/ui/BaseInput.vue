<template>
    <div class="input-wrapper">

        <slot name="icon"></slot>

        <input :type="type" :placeholder="placeholder" :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)" />

        <slot name="action"></slot>

    </div>
</template>


<script setup>

defineProps({
    label: String,
    type: {
        type: String,
        default: 'text'
    },

    placeholder: String,
    modelValue: String
})

defineEmits(['update:modelValue'])
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
        padding: .8rem;
    }
}

@media (max-width: 700px){
    .input-wrapper{
        padding: 0 .7rem;
        gap: 1rem;
    }

    input{
        width: 100px;
        padding: .8rem;
    }
}
</style>