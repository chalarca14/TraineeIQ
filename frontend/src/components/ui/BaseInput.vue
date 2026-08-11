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
}

label {
    color: var(--color-text);
    font-size: .95rem;
    font-weight: 600;
}

.input-wrapper {
    display: flex;
    align-items: center;
    background: var(--color-background);
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: 0 .75rem;
    transition: .25s;
}

.input-wrapper:focus-within {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px rgba(124, 58, 237, .15);
}

input {
    flex: 1;
    padding: .85rem;
    border: none;
    outline: none;
    background: none;
    color: var(--color-text);
    font-size: .95rem;
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
</style>