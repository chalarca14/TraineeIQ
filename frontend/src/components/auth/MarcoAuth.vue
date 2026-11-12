<template>
    <!-- Mismo marco del login (información a la izquierda, tarjeta a la derecha) para que recuperar la
         contraseña se sienta parte del mismo flujo. Lo comparten /recuperar y /restablecer. -->
    <main class="marco">
        <LoginInfo />

        <section class="tarjeta">
            <header class="cabecera">
                <h2>{{ titulo }} <span>{{ resaltado }}</span></h2>
                <p>{{ descripcion }}</p>
            </header>

            <slot />

            <RouterLink to="/login" class="volver">
                <ArrowLeft :size="16" aria-hidden="true" /> Volver al inicio de sesión
            </RouterLink>
        </section>
    </main>
</template>

<script setup>
import { ArrowLeft } from 'lucide-vue-next'
import LoginInfo from './LoginInfo.vue'

defineProps({
    titulo: { type: String, required: true },
    // Palabra final en morado, como "¡Bienvenido de vuelta!" del login
    resaltado: { type: String, default: '' },
    descripcion: { type: String, default: '' }
})
</script>

<style scoped>
.marco {
    display: grid;
    grid-template-columns: 55% 45%;
    min-height: 100vh;
    min-height: 100dvh;
    width: 100%;
    background-color: var(--color-background);
    overflow-x: hidden;
}

.tarjeta {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
    padding: 2.5rem;
    background: var(--color-surface);
}

.cabecera h2 {
    font-size: 2rem;
    line-height: 1.1;
    color: var(--color-text);
}

.cabecera span {
    color: var(--color-primary);
}

.cabecera p {
    margin-top: .7rem;
    color: var(--color-text-secondary);
}

.volver {
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: .4rem;
    margin-top: .5rem;
    color: var(--color-primary-light);
    font-size: .9rem;
    text-decoration: none;
}

.volver:hover {
    text-decoration: underline;
}

@media (max-width: 900px) {
    .marco {
        grid-template-columns: 45% 55%;
    }
}

@media (max-width: 700px) {
    .marco {
        grid-template-columns: 1fr;
    }

    .tarjeta {
        padding: 2rem 1.5rem;
    }

    .cabecera h2 {
        font-size: 1.6rem;
    }
}
</style>
