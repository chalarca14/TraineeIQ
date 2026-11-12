<template>
    <header class="cabecera">
        <!-- No hay imagen del grupo en el backend: el mismo icono de las tarjetas de "Mis grupos" -->
        <span class="icono" aria-hidden="true"><Code2 :size="34" /></span>

        <div class="texto">
            <h1>{{ grupo.nombre }}</h1>
            <p class="descripcion">{{ grupo.descripcion || 'Este grupo aún no tiene descripción.' }}</p>

            <!-- Sin "Activo", Nivel ni Duración (tachados o inexistentes en el backend) -->
            <ul class="datos">
                <li v-if="instructor"><UserRound :size="16" aria-hidden="true" /> Instructor: {{ instructor }}</li>
                <li v-if="grupo.semana_actual != null">
                    <CalendarDays :size="16" aria-hidden="true" /> {{ textoSemana }}
                </li>
            </ul>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue'
import { Code2, UserRound, CalendarDays } from 'lucide-vue-next'

const props = defineProps({ grupo: { type: Object, required: true } })

// El backend hoy llama "profesor" a la relación en algunas respuestas: se aceptan las dos (igual que GrupoCard)
const instructor = computed(() => props.grupo.instructor?.name ?? props.grupo.profesor?.name ?? '')

// semana_actual = hasta dónde liberó el instructor. ultima_semana = la semana más alta de sus guías
// (null si aún no hay guías): con ella se dice "3 de 8"; sin ella, solo el número. No se calcula nada.
const textoSemana = computed(() => {
    const { semana_actual: actual, ultima_semana: ultima } = props.grupo
    return ultima ? `Semana actual: ${actual} de ${ultima}` : `Semana actual: ${actual}`
})
</script>

<style scoped>
.cabecera {
    display: flex;
    gap: 1.2rem;
    align-items: flex-start;
    padding: clamp(1rem, 3vw, 1.6rem);
    border: 1px solid var(--color-border);
    border-radius: 18px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 28%, var(--color-surface)), var(--color-surface));
}

.icono {
    display: grid;
    flex-shrink: 0;
    place-items: center;
    width: 72px;
    height: 72px;
    border-radius: 16px;
    background: var(--gradiente-primario);
    box-shadow: var(--brillo-primario);
    color: var(--color-text);
}

.texto {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    min-width: 0;
}

h1 {
    font-size: clamp(1.3rem, 3vw, 1.8rem);
    overflow-wrap: anywhere;
}

.descripcion {
    color: var(--color-text-secondary);
    overflow-wrap: anywhere;
}

.datos {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem 1.4rem;
    margin-top: .3rem;
    list-style: none;
    color: var(--color-text-secondary);
    font-size: .88rem;
}

.datos li {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    overflow-wrap: anywhere;
}

@media (max-width: 520px) {
    .cabecera {
        flex-direction: column;
    }

    .icono {
        width: 56px;
        height: 56px;
    }
}
</style>
