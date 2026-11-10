<template>
    <article class="grupo" :style="{ '--c': color }">
        <!-- No hay imagen en el backend: un degradado con el icono evita inventar una.
             El color cambia según el id para distinguir los grupos de un vistazo. -->
        <div class="portada" aria-hidden="true">
            <Users :size="44" />
        </div>

        <div class="cuerpo">
            <h3 :title="grupo.nombre">{{ grupo.nombre }}</h3>

            <!-- Solo se muestra si el backend manda el instructor; así no queda una línea vacía -->
            <p v-if="instructor" class="instructor"><UserRound :size="14" /> Instructor: {{ instructor }}</p>

            <p class="descripcion">{{ grupo.descripcion || 'Este grupo aún no tiene descripción.' }}</p>

            <!-- RouterLink y no botón: es navegación, así funciona abrir en otra pestaña y el lector de pantalla
                 lo anuncia como enlace. El nombre de la ruta evita escribir la URL a mano -->
            <RouterLink :to="{ name: 'GrupoDetalle', params: { id: grupo.id } }" class="btn btn-secundario">
                Ver grupo <ArrowRight :size="16" aria-hidden="true" />
            </RouterLink>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { Users, UserRound, ArrowRight } from 'lucide-vue-next'

const props = defineProps({ grupo: { type: Object, required: true } })

// Variables de color existentes (nada de colores sueltos)
const COLORES = ['var(--color-primary-light)', 'var(--color-info)', 'var(--color-success)', 'var(--color-warning)']
const color = computed(() => COLORES[props.grupo.id % COLORES.length])

// El backend hoy llama "profesor" a la relación (pendiente de corregir a "instructor"): se aceptan las dos
const instructor = computed(() => props.grupo.instructor?.name ?? props.grupo.profesor?.name ?? '')
</script>

<style scoped>
.grupo {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
    transition: border-color .2s, box-shadow .2s, transform .2s;
}

.grupo:hover {
    border-color: color-mix(in srgb, var(--c) 60%, transparent);
    box-shadow: 0 8px 28px color-mix(in srgb, var(--c) 18%, transparent);
    transform: translateY(-2px);
}

.portada {
    display: grid;
    place-items: center;
    height: 110px;
    color: var(--c);
    background: linear-gradient(135deg, color-mix(in srgb, var(--c) 45%, var(--color-card)), color-mix(in srgb, var(--c) 8%, var(--color-surface)));
}

.cuerpo {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: .55rem;
    padding: 1.1rem;
}

h3 {
    overflow: hidden;
    font-size: 1.1rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.instructor {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: var(--c);
    font-size: .8rem;
}

.descripcion {
    display: -webkit-box;
    overflow: hidden;
    color: var(--color-text-secondary);
    font-size: .88rem;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
    line-clamp: 3;
}

.btn {
    align-self: stretch;
    justify-content: center;
    margin-top: auto;
}
</style>
