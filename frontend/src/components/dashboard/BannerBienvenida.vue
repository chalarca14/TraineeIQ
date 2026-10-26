<template>
    <section class="welcome-banner">
        <h1>¡Hola, {{ nombre }}! 👋</h1>
        <p>{{ mensaje }}</p>
    </section>
</template>

<script setup>
// Recibe el texto desde afuera: así el mismo banner sirve para estudiante e instructor
defineProps({
    nombre: { type: String, required: true },
    mensaje: { type: String, required: true }
})
</script>

<style scoped>
.welcome-banner {
    padding: 1.5rem 1.8rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--color-card);
    border-radius: 16px;
    background: linear-gradient(120deg, rgba(124, 58, 237, .28), var(--color-surface) 70%);
}

.welcome-banner h1 {
    margin: 0 0 .4rem 0;
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.welcome-banner p {
    margin: 0;
    color: var(--color-text-secondary);
}
</style>