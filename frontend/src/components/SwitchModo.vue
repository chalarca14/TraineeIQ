<template>
    <div class="switch-modo" role="radiogroup" aria-label="Modo de uso">
        <button
            v-for="opcion in opciones"
            :key="opcion.valor"
            type="button"
            class="switch-option"
            :class="{ activo: modoStore.modoActivo === opcion.valor }"
            role="radio"
            :aria-checked="modoStore.modoActivo === opcion.valor"
            @click="modoStore.cambiarModo(opcion.valor)"
        >
            <component :is="opcion.icono" :size="15" />
            <span>{{ opcion.etiqueta }}</span>
        </button>
    </div>
</template>

<script setup>
import { Users, User } from 'lucide-vue-next'
import { useModoStore } from '@/stores/modo'

const modoStore = useModoStore()

const opciones = [
    { valor: 'grupo', etiqueta: 'Grupo', icono: Users },
    { valor: 'personal', etiqueta: 'Personal', icono: User }
]
</script>

<style scoped>
.switch-modo {
    display: inline-flex;
    padding: .25rem;
    border-radius: 999px;
    background: var(--color-background);
    border: 1px solid var(--color-card);
    gap: .2rem;
}

.switch-option {
    display: flex;
    align-items: center;
    gap: .4rem;
    padding: .4rem .9rem;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: var(--color-text-secondary);
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: .2s;
}

.switch-option.activo {
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    color: white;
}

.switch-option:hover:not(.activo) {
    color: var(--color-text);
}

/* En pantallas chicas, solo el ícono, sin texto, para que quepa en el NavBar */
@media (max-width: 600px) {
    .switch-option span {
        display: none;
    }

    .switch-option {
        padding: .5rem .7rem;
    }
}
</style>