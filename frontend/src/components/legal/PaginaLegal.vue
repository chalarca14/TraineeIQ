<template>
    <!-- Marco común de las páginas legales: no piden sesión y se pueden leer desde Google -->
    <main class="pagina-legal">
        <article class="documento">
            <header class="encabezado">
                <RouterLink to="/login" class="volver">
                    <ArrowLeft :size="16" />
                    Volver a TraineeIQ
                </RouterLink>
                <p class="marca">TraineeIQ</p>
                <h1>{{ titulo }}</h1>
                <p class="fecha">Última actualización: {{ actualizado }}</p>
            </header>

            <div class="contenido">
                <slot />
            </div>

            <footer class="pie">
                <p>¿Preguntas? Escríbenos a
                    <a href="mailto:traineeiq.app@gmail.com">traineeiq.app@gmail.com</a>.
                </p>
                <nav aria-label="Documentos legales">
                    <RouterLink to="/privacidad">Política de privacidad</RouterLink>
                    <RouterLink to="/terminos">Términos y condiciones</RouterLink>
                </nav>
            </footer>
        </article>
    </main>
</template>

<script setup>
import { ArrowLeft } from 'lucide-vue-next'

defineProps({
    titulo: { type: String, required: true },
    actualizado: { type: String, required: true }
})
</script>

<style scoped>
.pagina-legal {
    min-height: 100vh;
    padding: 2rem 1rem;
    background: var(--color-background);
    color: var(--color-text);
}

.documento {
    width: min(100%, 760px);
    margin: 0 auto;
    padding: clamp(1.2rem, 4vw, 2.5rem);
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
}

.volver {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    color: var(--color-text-secondary);
    font-size: .9rem;
    text-decoration: none;
}

.volver:hover {
    color: var(--color-primary);
}

.marca {
    margin-top: 1.5rem;
    color: var(--color-primary);
    font-weight: 700;
    letter-spacing: .04em;
}

h1 {
    margin-top: .3rem;
    font-size: clamp(1.5rem, 4vw, 2rem);
}

.fecha {
    margin-top: .4rem;
    color: var(--color-text-secondary);
    font-size: .9rem;
}

.contenido {
    margin-top: 1.5rem;
    line-height: 1.65;
}

/* Los textos vienen por slot: :deep para dar formato a sus títulos, párrafos y listas */
.contenido :deep(h2) {
    margin: 1.8rem 0 .6rem;
    font-size: 1.15rem;
}

.contenido :deep(p),
.contenido :deep(ul) {
    margin-bottom: .8rem;
    color: var(--color-text-secondary);
}

.contenido :deep(ul) {
    padding-left: 1.3rem;
}

.contenido :deep(li) {
    margin-bottom: .35rem;
}

.contenido :deep(strong) {
    color: var(--color-text);
}

.pie {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: space-between;
    margin-top: 2rem;
    padding-top: 1.2rem;
    border-top: 1px solid var(--color-border);
    color: var(--color-text-secondary);
    font-size: .9rem;
}

.pie a {
    color: var(--color-primary);
}

.pie nav {
    display: flex;
    gap: 1rem;
}
</style>
