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
        <!-- Las 4 tarjetas son el mismo bloque: un componente y una lista de datos (antes, 4 copias a mano) -->
        <footer class="features">
            <TarjetaFuncionLogin v-for="f in FUNCIONES" :key="f.titulo" v-bind="f" />
            <div class="login-footer">
                <span>© 2026 TraineeIQ</span>
                <span>Todos los derechos reservados</span>
            </div>
        </footer>
    </section>
</template>

<script setup>
import TarjetaFuncionLogin from './TarjetaFuncionLogin.vue'
// Las imágenes se importan para que Vite las incluya en el paquete con su ruta final
import robot from '@/assets/icons/robot.png'
import proyecto from '@/assets/icons/proyecto.png'
import sincronizacion from '@/assets/icons/sincronizacion.png'
import grafico from '@/assets/icons/grafico.png'

const FUNCIONES = [
    { icono: robot, titulo: 'Recomendaciones IA', texto: 'Obten sugerencias personalizadas según tu avance.' },
    { icono: proyecto, titulo: 'Mini proyectos', texto: 'Desarrolla proyectos prácticos alineados al aprendizaje.' },
    { icono: sincronizacion, titulo: 'Sincronización', texto: 'Conecta tus guías del SENA.' },
    { icono: grafico, titulo: 'Seguimiento', texto: 'Monitorea tu avance en tiempo real' }
]
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

/* Pantallas con poca altura (los ajustes de las tarjetas viven en TarjetaFuncionLogin.vue) */
@media (max-height: 800px) {

    .login-info {
        padding: 2rem 2rem;
    }

    .hero {
        gap: 1rem;
        text-align: left;
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

    .login-footer {
        display: none;
    }
}

@media (min-width: 1101px) and (max-width: 2040px) {
    .hero h2 {
        font-size: 2rem;
        text-align: left;
    }

    .gradient-text {
        font-size: 2.3rem;
    }

    .hero p {
        font-size: 1.5rem;
        line-height: 1.4;
        text-align: left;
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
}
</style>
