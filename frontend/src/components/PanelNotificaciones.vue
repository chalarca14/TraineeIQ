<template>
    <section class="panel" aria-labelledby="titulo-campana">
        <header class="cabecera">
            <h2 id="titulo-campana">Notificaciones</h2>
            <button v-if="noLeidas" type="button" class="enlace" :disabled="marcando" @click="$emit('marcar-todas')">
                <CheckCheck :size="15" aria-hidden="true" /> Marcar todas como leídas
            </button>
        </header>

        <!-- "Cargando" solo la primera vez: en las consultas de cada minuto la lista ya está en pantalla -->
        <CargaEsqueleto v-if="cargando && !avisos.length" texto="Cargando notificaciones…">
            <EsqueletoLista :filas="3" :con-icono="false" />
        </CargaEsqueleto>

        <div v-else-if="error" class="error" role="alert">
            <span>{{ error }}</span>
            <button v-if="puedeReintentar" type="button" class="enlace" @click="$emit('reintentar')">Reintentar</button>
        </div>

        <p v-else-if="!avisos.length" class="estado">No tienes notificaciones por ahora.</p>

        <ul v-else class="lista">
            <li v-for="a in avisos" :key="a.id">
                <button type="button" class="aviso" :class="{ nueva: !a.leida_en }" @click="$emit('abrir', a)">
                    <span class="punto" aria-hidden="true" />
                    <span class="texto">
                        <!-- El mensaje llega armado del backend: se muestra como texto, nunca con v-html -->
                        <span>{{ a.mensaje }}</span>
                        <small>{{ detalle(a) }}</small>
                    </span>
                    <span v-if="!a.leida_en" class="sr-only">(sin leer)</span>
                </button>
            </li>
        </ul>
    </section>
</template>

<script setup>
import { CheckCheck } from 'lucide-vue-next'
import { fechaRelativa, horaCorta } from '@/utils/fechas'
import CargaEsqueleto from '@/components/ui/CargaEsqueleto.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'

// Solo dibuja la lista: los datos y las acciones (marcar, navegar) los maneja CampanaNotificaciones.
defineProps({
    avisos: { type: Array, required: true },
    noLeidas: { type: Number, default: 0 },
    cargando: { type: Boolean, default: false },
    marcando: { type: Boolean, default: false },
    error: { type: String, default: '' },
    puedeReintentar: { type: Boolean, default: true }
})
defineEmits(['abrir', 'marcar-todas', 'reintentar'])

// "Grupo Demo SENA · Hoy · 09:55 a. m."
function detalle(aviso) {
    const fecha = aviso.updated_at ?? aviso.created_at
    return [aviso.datos?.grupo, fechaRelativa(fecha), horaCorta(fecha)].filter(Boolean).join(' · ')
}
</script>

<style scoped>
.panel {
    position: absolute;
    top: calc(100% + .5rem);
    right: 0;
    width: 360px;
    max-height: min(70vh, 480px);
    overflow-y: auto;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
    box-shadow: 0 12px 30px rgba(0, 0, 0, .4);
}

.cabecera {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    padding: .8rem 1rem;
    border-bottom: 1px solid var(--color-card);
}

h2 {
    font-size: 1rem;
}

.enlace {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    border: none;
    background: none;
    color: var(--color-primary-light);
    font: inherit;
    font-size: .8rem;
    font-weight: 700;
    cursor: pointer;
}

.enlace:disabled {
    opacity: .6;
    cursor: wait;
}

.estado,
.error {
    padding: 1rem;
    color: var(--color-text-secondary);
    font-size: .88rem;
}

.error {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    color: var(--color-danger);
}

.lista {
    list-style: none;
}

.lista li + li {
    border-top: 1px solid var(--color-card);
}

.aviso {
    display: flex;
    gap: .6rem;
    width: 100%;
    padding: .8rem 1rem;
    border: none;
    background: none;
    color: var(--color-text-secondary);
    font: inherit;
    font-size: .88rem;
    text-align: left;
    cursor: pointer;
}

.aviso:hover {
    background: var(--color-card);
}

.aviso.nueva {
    color: var(--color-text);
}

/* El punto morado marca lo no leído; en los leídos queda el espacio para que el texto no salte */
.punto {
    flex-shrink: 0;
    width: 8px;
    height: 8px;
    margin-top: .4rem;
    border-radius: 50%;
}

.nueva .punto {
    background: var(--color-primary-light);
}

.texto {
    display: flex;
    flex-direction: column;
    gap: .25rem;
    min-width: 0;
    overflow-wrap: anywhere;
}

small {
    color: var(--color-text-secondary);
    font-size: .76rem;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

/* Celular: el panel ocupa todo el ancho bajo la barra superior (fixed, porque la campana no está en la
   orilla derecha y un panel absoluto de 360 px se saldría por la izquierda) */
@media (max-width: 600px) {
    .panel {
        position: fixed;
        top: 4.3rem;
        right: 1rem;
        left: 1rem;
        width: auto;
    }
}
</style>
