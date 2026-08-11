<template>

    <RouterLink :to="to" :class="['base-link', variant]">

        <slot></slot>

    </RouterLink>

</template>

<script setup>

defineProps({

    to: {
        type: String,
        default: '/'
    },

    variant: {
        type: String,
        default: 'primary'
    }

})

</script>

<style scoped>
.base-link {

    color: var(--color-primary);

    text-decoration: none;

    font-weight: 600;

    transition:
        color .25s ease,
        text-decoration-color .25s ease;

}

.base-link:hover {

    color: red;

    text-decoration: underline;

    text-underline-offset: 4px;

}
</style>