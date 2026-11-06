<template>
    <header class="hero">
        <div class="brillo" aria-hidden="true"></div>

        <span class="icono" :style="{ '--c': COLORES[color] ?? color }" aria-hidden="true">
            <component :is="icono" :size="40" />
        </span>

        <div class="datos">
            <h1>{{ titulo }}</h1>
            <!-- El slot "descripcion" permite poner contenido con formato; si no, se usa el texto plano -->
            <p v-if="descripcion || $slots.descripcion">
                <slot name="descripcion">{{ descripcion }}</slot>
            </p>
            <div v-if="$slots.etiquetas" class="etiquetas"><slot name="etiquetas" /></div>
        </div>

        <div v-if="$slots.acciones" class="acciones"><slot name="acciones" /></div>

        <!-- Ilustración por defecto; se puede reemplazar con el slot o quitar con sin-ilustracion -->
        <slot v-else-if="!sinIlustracion" name="ilustracion"><IlustracionVentana class="ilu" /></slot>
    </header>
</template>

<script setup>
import IlustracionVentana from '@/components/ui/IlustracionVentana.vue'

// Banner de cabecera de las pantallas: icono grande con brillo, título, texto, etiquetas
// opcionales y, a la derecha, acciones o una ilustración. Reemplaza a los banners sueltos
// de cada vista para que todas tengan el mismo estilo.
defineProps({
    titulo: { type: String, required: true },
    descripcion: { type: String, default: '' },
    icono: { type: [Object, Function], required: true },
    // Nombre de color ('primary', 'info'...) o directamente una variable CSS ('var(--color-info)')
    color: { type: String, default: 'primary' },
    sinIlustracion: { type: Boolean, default: false }
})

const COLORES = {
    primary: 'var(--color-primary-light)',
    info: 'var(--color-info)',
    success: 'var(--color-success)',
    warning: 'var(--color-warning)'
}
</script>

<style scoped>
.hero {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1.2rem;
    overflow: hidden;
    padding: 1.4rem;
    border: 1px solid color-mix(in srgb, var(--color-primary) 40%, var(--color-border));
    border-radius: 18px;
    background: linear-gradient(110deg, color-mix(in srgb, var(--color-primary) 38%, var(--color-surface)), var(--color-surface) 70%);
}

/* Resplandor morado detrás de la ilustración */
.brillo {
    position: absolute;
    top: -40%;
    right: -5%;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    background: radial-gradient(circle, color-mix(in srgb, var(--color-primary-light) 35%, transparent), transparent 65%);
    pointer-events: none;
}

.icono {
    position: relative;
    display: grid;
    place-items: center;
    width: 84px;
    height: 84px;
    flex-shrink: 0;
    border-radius: 20px;
    color: var(--c);
    background: linear-gradient(145deg, color-mix(in srgb, var(--c) 30%, var(--color-card)), color-mix(in srgb, var(--c) 10%, var(--color-surface)));
    box-shadow: 0 8px 28px color-mix(in srgb, var(--c) 35%, transparent), inset 0 0 0 1px color-mix(in srgb, var(--c) 40%, transparent);
}

.datos {
    position: relative;
    flex: 1;
    min-width: 0;
}

h1 {
    font-size: clamp(1.25rem, 3vw, 1.75rem);
    overflow-wrap: anywhere;
}

.datos p {
    margin: .35rem 0 0;
    max-width: 60ch;
    color: var(--color-text-secondary);
}

.etiquetas {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .5rem;
    margin-top: .8rem;
}

.acciones {
    position: relative;
    flex-shrink: 0;
}

/* En pantallas angostas se quita la ilustración para que el título respire */
.ilu {
    position: relative;
}

@media (max-width: 760px) {
    .ilu {
        display: none;
    }
}

@media (max-width: 520px) {
    .hero {
        flex-direction: column;
        align-items: flex-start;
    }

    .icono {
        width: 64px;
        height: 64px;
    }

    .acciones {
        align-self: stretch;
    }
}
</style>
