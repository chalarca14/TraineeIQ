<template>

    <button :type="type" :class="['base-button', variant]">

        <slot></slot>

    </button>

</template>

<script setup>

defineProps({

    type: {
        type: String,
        default: 'button'
    },

    variant: {
        type: String,
        default: 'primary'
    }

})

</script>

<style scoped>
.base-button {
    width: 100%;
    padding: 1rem;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition:
        background-color .25s ease,
        transform .2s ease,
        box-shadow .25s ease;
}

.base-button:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(124, 58, 237, .25);
}

.base-button:disabled {
    opacity: .7;
    cursor: not-allowed;
}

.primary {

    background: linear-gradient(90deg,
            var(--color-primary),
            var(--color-primary-light));
    color: white;

}

.primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(124, 58, 237, .35);
}


</style>