<template>
    <section class="login-info">

        <!-- Logo -->
        <header class="brand">
            <img src="@/assets/icons/cerebro.png" alt="Logo TraineeIQ" class="brand-logo" />

            <div>
                <h1>TRAINEEIQ</h1>
                <p>Powered by IA</p>
            </div>
        </header>

        <!-- Hero -->
        <section class="hero">

            <h2>
                Aprende más allá
                <br>
                de la guía.
            </h2>

            <h2 class="gradient-text">
                Tu mentor inteligente
                <br>
                del SENA te espera.
            </h2>

            <p>
                TrainneIQ utiliza Inteligencia Arttficial para brindarte recomendaciones personalizadas, mini proyectos
                y seguimiento académico en tiempo real.
            </p>

        </section>

        <!-- Aquí después irán las cards -->
        <footer class="features">
            <article class="feature-card">
                <img src="@/assets/icons/robot.png" alt="Robot" class="feature-icon">

                <h3>Recomendaciones IA</h3>

                <p>
                    Obten sugerencias personalizadas según tu avance.
                </p>
            </article>

            <article class="feature-card">
                <img src="@/assets/icons/proyecto.png" alt="Proyecto" class="feature-icon">

                <h3>Mini proyectos</h3>

                <p>
                    Desarrolla proyectos prácticos alineados al aprendizaje.
                </p>
            </article>

            <article class="feature-card">
                <img src="@/assets/icons/sincronizacion.png" alt="Sincrinizacion" class="feature-icon">

                <h3>Sincronización</h3>

                <p>
                    Conecta tus guías del SENA.
                </p>
            </article>

            <article class="feature-card">
                <img src="@/assets/icons/grafico.png" alt="" class="feature-icon">

                <h3>Seguimiento</h3>

                <p>
                    Monitorea tu avance en tiempo real
                </p>
            </article>

            <div class="login-footer">

                <span>© 2026 TraineeIQ</span>

                <span>Todos los derechos reservados</span>
            </div>

        </footer>

    </section>
</template>

<script setup>
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Puritan:wght@400;700&display=swap');

.login-info {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 2rem;
    padding: 3rem;
    background: var(--color-background);
    color: var(--color-text);
    font-family: Puritan;
    animation: slideLeft .6s ease;
}

@keyframes slideLeft {

    from {
        opacity: 0;
        transform: translateX(-30px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width:1100px) {
    .login-info {
        padding: 2rem;
    }
}

.brand {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.brand-logo {
    width: 54px;
    height: 54px;
    object-fit: contain;
    /*Hace que la imagen mantenga su porporciin y no se deforme si se cambia el tamaño*/
}

.brand h1 {
    margin: 0;
    color: var(--color-text);
}

.brand p {
    margin-top: .3rem;
    color: var(--color-text-secondary);
    font-size: .95rem;
}

.hero {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    max-width: 580px;
    margin: 1.5rem 0;
    /*max-width significa: Puedes medir hasta 520px, pero si no cabes, hazme mas pequeño*/
}

.hero h2 {
    margin: 0;
    font-size: 3rem;
    line-height: 1.15;
    /*Interlineado*/
    font-weight: 700;
    color: var(--color-text);

}

.gradient-text {
    background: linear-gradient(90deg, #7C3AED, #A855F7);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    -webkit-text-fill-color: transparent;
}

.hero p {
    color: var(--color-text-secondary);
    font-size: 1rem;
    line-height: 1.8;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
}

.feature-card {
    background: var(--color-surface);
    border: 1px solid var(--color-card);
    border-radius: 18px;
    padding: 1.4rem;
    transition: .25s;
    cursor: pointer;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: .7rem;
}

.feature-card:hover {
    transform: translateY(-4px);
    border-color: var(--color-primary);
    box-shadow: 0 12px 30px rgba(124, 58, 237, .20);
}

.feature-icon {
    width: 42px;
    height: 42px;
    margin-bottom: 1rem;
    transition: .25s;
}

.feature-card:hover .feature-icon {
    transform: scale(1.08);
}

.feature-card h3 {
    margin-bottom: .6rem;
    color: var(--color-text);
}

.feature-card p {
    color: var(--color-text-secondary);
    line-height: 1.6;
    font-size: .9rem;
}

.login-footer {
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--color-text-secondary);
    font-size: .85rem;
    border-top: 1px solid var(--color-card);
    padding-top: 1rem;
}
</style>