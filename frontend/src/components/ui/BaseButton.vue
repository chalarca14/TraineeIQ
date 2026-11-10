<template>
    <button :type="type" :class="['base-button', variant]" @click="crearOnda">
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

// Efecto "ripple": crea un círculo que se expande desde el punto exacto donde se hizo clic
function crearOnda(event) {
    const boton = event.currentTarget

    if (boton.disabled) return

    const onda = document.createElement('span')
    onda.classList.add('onda-click')

    // El diámetro debe cubrir todo el botón sin importar en qué esquina se haga clic
    const tamano = Math.max(boton.clientWidth, boton.clientHeight)
    onda.style.width = onda.style.height = `${tamano}px`

    // Centramos la onda exactamente en el punto del clic, relativo al botón (no a la pantalla)
    const rect = boton.getBoundingClientRect()
    onda.style.left = `${event.clientX - rect.left - tamano / 2}px`
    onda.style.top = `${event.clientY - rect.top - tamano / 2}px`

    boton.appendChild(onda)

    // La eliminamos del DOM al terminar su propia animación,
    // para no ir acumulando <span> invisibles en cada clic
    onda.addEventListener('animationend', () => onda.remove())
}
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
    position: relative;
    overflow: hidden;
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
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    color: white;
}

.primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(124, 58, 237, .35);
}

:deep(.onda-click) {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, .5);
    transform: scale(0);
    animation: expandirOnda .6s ease-out;
    pointer-events: none;
}

@keyframes expandirOnda {
    to {
        transform: scale(2.5);
        opacity: 0;
    }
}
</style>