<template>
    <svg class="ilustracion" viewBox="0 0 220 150" aria-hidden="true">
        <rect class="ventana" x="20" y="14" width="170" height="112" rx="12" />
        <circle class="punto d" cx="38" cy="32" r="4" />
        <circle class="punto w" cx="52" cy="32" r="4" />
        <circle class="punto s" cx="66" cy="32" r="4" />
        <rect class="linea p" x="36" y="52" width="70" height="7" rx="3.5" />
        <rect class="linea i" x="50" y="68" width="100" height="7" rx="3.5" />
        <rect class="linea s" x="50" y="84" width="64" height="7" rx="3.5" />
        <rect class="linea w" x="36" y="100" width="88" height="7" rx="3.5" />
        <rect class="flota" x="150" y="2" width="44" height="34" rx="10" />
        <text class="flota-texto" x="172" y="25" text-anchor="middle">&lt;/&gt;</text>
    </svg>
</template>

<style scoped>
/* Ventana de código dibujada con SVG: decorativa, sin imágenes externas */
.ilustracion {
    position: relative;
    width: 190px;
    flex-shrink: 0;
}

.ventana {
    fill: var(--color-background);
    stroke: color-mix(in srgb, var(--color-primary-light) 60%, transparent);
    stroke-width: 1.5;
}

.punto.d { fill: var(--color-danger); }
.punto.w { fill: var(--color-warning); }
.punto.s { fill: var(--color-success); }

.linea { opacity: .85; }
.linea.p { fill: var(--color-primary-light); }
.linea.i { fill: var(--color-info); }
.linea.s { fill: var(--color-success); }
.linea.w { fill: var(--color-warning); }

.flota {
    fill: var(--color-primary);
}

.flota-texto {
    fill: var(--color-text);
    font-size: 15px;
    font-weight: 700;
}
</style>
