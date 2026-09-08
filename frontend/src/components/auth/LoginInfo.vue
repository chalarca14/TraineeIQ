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
                IA que te guía, recomienda
                y acompaña en tu aprendizaje.
            </p>

        </section>

        <!-- Aquí después irán las cards -->
        <footer class="features">
            <article class="feature-card">
                <img src="@/assets/icons/robot.png" alt="Robot" class="feature-icon">

                <div class="|">
                    <h3>Recomendaciones IA</h3>

                    <p>
                        Obten sugerencias personalizadas según tu avance.
                    </p>
                </div>
            </article>

            <article class="feature-card">
                <img src="@/assets/icons/proyecto.png" alt="Proyecto" class="feature-icon">

                <div class="feature-text">
                    <h3>Mini proyectos</h3>

                    <p>
                        Desarrolla proyectos prácticos alineados al aprendizaje.
                    </p>
                </div>
            </article>

            <article class="feature-card">
                <img src="@/assets/icons/sincronizacion.png" alt="Sincrinizacion" class="feature-icon">

                <div class="feature-text">
                    <h3>Sincronización</h3>

                    <p>
                        Conecta tus guías del SENA.
                    </p>
                </div>
            </article>

            <article class="feature-card">
                <img src="@/assets/icons/grafico.png" alt="" class="feature-icon">

                <div class="feature-text">
                    <h3>Seguimiento</h3>

                    <p>
                        Monitorea tu avance en tiempo real
                    </p>
                </div>
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
    justify-content: space-between;
    padding: clamp(1.5rem, 4vh, 3rem) 3rem;
    /* vertical se achica en pantallas bajas, horizontal se mantiene */
    background: var(--color-background);
    color: var(--color-text);
    font-family: Puritan;
    animation: slideLeft .6s ease;
    min-width: 0;
    overflow-y: auto;
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
    gap: clamp(.8rem, 2vh, 1.5rem);
    max-width: 580px;
    margin: clamp(.8rem, 2vh, 1.5rem) 0;
    text-align: center;
}

.hero h2 {
    margin: 0;
    font-size: 3rem;
    line-height: 1.15;
    /*Interlineado*/
    font-weight: 700;
    color: var(--color-text);
    text-align: center;

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
    gap: clamp(.6rem, 1.5vh, 1rem);
}

.feature-card {
    background: var(--color-surface);
    border: 1px solid var(--color-card);
    border-radius: 18px;
    padding: clamp(1rem, 2vh, 1.4rem);
    transition: .25s;
    cursor: pointer;
    min-height: clamp(90px, 12vh, 120px);
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
    margin-top: clamp(.8rem, 2vh, 2rem);
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--color-text-secondary);
    font-size: .85rem;
    border-top: 1px solid var(--color-card);
    padding-top: clamp(.5rem, 1vh, 1rem);
}

/* Pantallas con poca altura */
@media (max-height: 800px) {

    .login-info {
        padding: 2rem 2rem;
    }

    .hero {
        gap: 1rem;
        display: flex;
        flex-direction: column;
        text-align: left;
        align-items: left;
        /* position: relative;
        bottom: 50px; */

    }

    .hero h2 {
        font-size: 2rem;
        text-align: left;
    }

    .gradient-text {
        font-size: .5rem;

    }

    .hero p {
        font-size: 1.5rem;
        line-height: 1.5;
    }

    .features {
        gap: .8rem;
        /* position: relative;
        bottom: 90px; */
    }

    .feature-card {
        min-height: 110px;
        padding: 1rem;
    }

    .feature-icon {
        width: 36px;
        height: 36px;
        margin-bottom: .5rem;
    }

    .feature-card h3 {
        font-size: 1rem;
        margin-bottom: .3rem;
    }

    .feature-card p {
        font-size: .8rem;
        line-height: 1.4;
    }

    .login-footer {
        margin-top: .5rem;
        padding-top: .7rem;
    }
}

@media (max-width: 700px) {

    .login-info {
        padding: 1.5rem;
        min-height: auto;
        align-items: center;
        text-align: center;
    }

    .brand {
        justify-content: left;
    }

    .brand-logo {
        width: 45px;
        height: 45px;
    }

    .brand h1 {
        font-size: 1.5rem;
    }

    .brand p {
        display: none;
    }

    .hero {
        /* background-color: aqua; */
        margin: 1rem 0;
        gap: .7rem;
        max-width: 500px;
    }

    .hero h2 {
        font-size: 1.5rem;
    }

    .hero p {
        font-size: .9rem;
        line-height: 1.5;
    }

    .features {
        display: grid;
        grid-template-columns: 1fr;
        gap: .8rem;
    }

    .feature-card {
        width: 100%;
        min-height: 110px;
        padding: 1rem;
    }

    .feature-icon {
        width: 40px;
        height: 40px;
        margin-bottom: .4rem;
    }

    .feature-card h3 {
        font-size: 1.5rem;
        margin-bottom: .2rem;
    }

    .feature-card p {
        font-size: 1rem;
        line-height: 1.4;
    }

    .login-footer {
        display: none;
    }
}





@media (min-width: 1101px) and (max-width: 2040px) {
    .hero h2 {
        font-size: 2rem;
        text-align: left;
        /* background-color: rgb(255, 72, 0); */
    }

    .gradient-text {
        font-size: 2.3rem;
    }

    .hero p {
        font-size: 1.5rem;
        line-height: 1.4;
        text-align: left;
    }

    .feature-card:hover {
        transform: translateY(-4px);
        border-color: var(--color-primary);
        box-shadow: 0 12px 30px rgba(124, 58, 237, .20);
    }
}

@media (min-width: 1849px) {

    .brand h1 {
        font-size: 2.5rem;
    }


    .hero h2 {
        font-size: 2rem;
    }

    .gradient-text {
        font-size: 2rem;
    }

    .hero p {
        font-size: 1.2rem;
        line-height: 1.4;
    }

    .features {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.2rem;
    }

    .feature-card {
        min-height: 200px;
        padding: 1.5rem;
        width: 100%;

    }

    .feature-card:hover {
        transform: translateY(-4px);
        border-color: var(--color-primary);
        box-shadow: 0 12px 30px rgba(124, 58, 237, .20);
    }

    .feature-icon {
        width: 50px;
        height: 50px;
        margin-bottom: 1rem;
        transition: .25s;
    }

    .feature-card h3 {
        font-size: 1.5rem;
        margin-bottom: .6rem;
    }

    .feature-card p {
        font-size: 1.2rem;
        line-height: 1.6;
    }

    .feature-card:hover .feature-icon {
        transform: scale(1.08);
    }

}
</style>