<template>
    <aside class="registro-info">

        <div class="brand">
            <img src="@/assets/icons/cerebro.png" alt="Logo TraineeIQ" class="brand-logo" />
            <h1>TRAINEE<span class="gradient-text">IQ</span></h1>
        </div>

        <div class="hero">
            <h2>Crea tu cuenta</h2>
            <p>Únete a TraineeIQ y potencia tu aprendizaje con inteligencia artificial, recomendaciones personalizadas y
                proyectos prácticos.</p>
        </div>

        <!-- Espacio reservado para la ilustración — se agrega al final, como acordamos -->
        <Transition name="fade-slide" mode="out-in">
            <div class="role-info-card" :key="rol">
                <h4>{{ infoActual.titulo }}</h4>
                <ul class="role-info-list">
                    <li v-for="punto in infoActual.puntos" :key="punto">
                        <Check :size="16" />
                        {{ punto }}
                    </li>
                </ul>
            </div>
        </Transition>

        <ul class="benefits-list">
            <li v-for="beneficio in beneficios" :key="beneficio.titulo" class="benefit-item">
                <span class="benefit-icon">
                    <component :is="beneficio.icono" :size="20" />
                </span>
                <div>
                    <h3>{{ beneficio.titulo }}</h3>
                    <p>{{ beneficio.descripcion }}</p>
                </div>
            </li>
        </ul>

        <div class="trust-box">
            <ShieldCheck :size="20" />
            <div>
                <h4>Seguro y confiable</h4>
                <p>Tu información está protegida con los más altos estándares de seguridad.</p>
            </div>
        </div>

    </aside>
</template>

<script setup>
// component :is nos permite guardar el ícono como dato y renderizarlo dinámicamente
// en el v-for, en vez de escribir 3 bloques casi idénticos a mano
import { BookOpen, Brain, Rocket, ShieldCheck } from 'lucide-vue-next'
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'

const props = defineProps({ rol: String })

const infoPorRol = {
    estudiante: {
        titulo: 'Como Estudiante podrás:',
        puntos: [
            'Acceder a las guías de tu instructor o subir las tuyas propias',
            'Recibir recomendaciones personalizadas de la IA',
            'Desarrollar mini proyectos prácticos a tu ritmo'
        ]
    },
    instructor: {
        titulo: 'Como Instructor podrás:',
        puntos: [
            'Crear y administrar grupos de aprendizaje',
            'Cargar guías para tus estudiantes',
            'Hacer seguimiento al progreso de cada aprendiz'
        ]
    }
}

const infoActual = computed(() => infoPorRol[props.rol] ?? infoPorRol.estudiante)

const beneficios = [
    { titulo: 'Aprende a tu ritmo', descripcion: 'Accede a guías y contenidos organizados por semanas.', icono: BookOpen },
    { titulo: 'IA que te guía', descripcion: 'Recibe recomendaciones personalizadas según tu avance.', icono: Brain },
    { titulo: 'Proyectos prácticos', descripcion: 'Desarrolla mini proyectos y fortalece tus habilidades.', icono: Rocket }
]
</script>

<style scoped>
.registro-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: clamp(1rem, 2.5vh, 1.8rem);
    padding: clamp(1.5rem, 4vh, 3rem);
    background: var(--color-background);
    color: var(--color-text);
    font-family: Puritan;
    animation: slideInPanel .6s ease;
    min-width: 0;
    overflow-y: auto;
}

@keyframes slideInPanel {
    from {
        opacity: 0;
        transform: translateX(30px);
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
}

.brand h1 {
    margin: 0;
    color: var(--color-text);
}

.gradient-text {
    background: linear-gradient(90deg, #7C3AED, #A855F7);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    -webkit-text-fill-color: transparent;
}

.hero h2 {
    margin: 0 0 .5rem 0;
    font-size: clamp(1.6rem, 3vh, 2rem);
    font-weight: 700;
    color: var(--color-text);
}

.hero p {
    color: var(--color-text-secondary);
    font-size: .95rem;
    line-height: 1.6;
}

.illustration-placeholder {
    min-height: clamp(80px, 14vh, 160px);
    /* TODO: reemplazar por la ilustración (unDraw/Icons8) al final del proyecto */
}

.role-info-card {
    min-height: clamp(80px, 14vh, 160px);
    padding: 1rem 1.2rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
}

.role-info-card h4 {
    margin: 0 0 .6rem 0;
    color: var(--color-primary);
    font-size: .9rem;
}

.role-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.role-info-list li {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-size: .82rem;
    color: var(--color-text-secondary);
}

.role-info-list svg {
    color: var(--color-primary);
    flex-shrink: 0;
}

/* Animación de transición al cambiar de rol */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all .25s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(8px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

.benefits-list {
    display: flex;
    flex-direction: column;
    gap: clamp(.8rem, 2vh, 1.2rem);
    list-style: none;
    padding: 0;
    margin: 0;
}

.benefit-item {
    display: flex;
    align-items: flex-start;
    gap: .9rem;
    padding: .6rem;
    border-radius: 12px;
    transition: .25s;
    animation: apareceItem .5s ease backwards;
}

/* El truco del "escalonado": cada tarjeta espera un poco más que la anterior */
.benefit-item:nth-child(1) { animation-delay: .1s; }
.benefit-item:nth-child(2) { animation-delay: .25s; }
.benefit-item:nth-child(3) { animation-delay: .4s; }

@keyframes apareceItem {
    from {
        opacity: 0;
        transform: translateX(-15px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.benefit-item:hover {
    background: var(--color-surface);
    transform: translateX(4px);
}

.benefit-item:hover .benefit-icon {
    background: var(--color-primary);
    color: white;
    transform: scale(1.1) rotate(-6deg);
}

.benefit-icon {
    transition: .25s;
}

.benefit-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--color-surface);
    border: 1px solid var(--color-card);
    color: var(--color-primary);
}

.benefit-item h3 {
    margin: 0 0 .2rem 0;
    font-size: .95rem;
    color: var(--color-text);
}

.benefit-item p {
    margin: 0;
    font-size: .82rem;
    color: var(--color-text-secondary);
    line-height: 1.5;
}

.trust-box {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    padding: 1rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
    color: var(--color-primary);
}

.trust-box h4 {
    margin: 0 0 .2rem 0;
    font-size: .85rem;
    color: var(--color-text);
}

.trust-box p {
    margin: 0;
    font-size: .78rem;
    color: var(--color-text-secondary);
    line-height: 1.4;
}

/* CELULAR */
@media (max-width: 700px) {
    .illustration-placeholder {
        display: none;
        /* en mobile priorizamos el formulario, no el espacio decorativo */
    }
}
</style>