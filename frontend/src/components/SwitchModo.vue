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
            @click="elegir(opcion.valor)"
        >
            <component :is="opcion.icono" :size="15" />
            <span>{{ opcion.etiqueta }}</span>
        </button>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { Users, User } from 'lucide-vue-next'
import { useModoStore } from '@/stores/modo'

const modoStore = useModoStore()
const router = useRouter()

// Cada modo tiene contenido distinto (guías personales vs. de grupo), así que al cambiar de
// modo volvemos al inicio en vez de dejar al aprendiz en una pantalla del modo anterior.
// La navegación va aquí y no en el store: el store solo guarda datos, no conoce las rutas.
async function elegir(modo) {
    // Si pulsa el modo que ya está activo, no hay cambio y no lo movemos de pantalla
    if (modo === modoStore.modoActivo) return
    await modoStore.cambiarModo(modo)
    router.push({ name: 'EstudianteDashboard' })
}

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