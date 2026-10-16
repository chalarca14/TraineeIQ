<template>
    <header class="navbar">
        <div class="brand-mini">
            <img src="@/assets/icons/cerebro.png" alt="TraineeIQ" class="brand-mini-logo" />
            <span>TRAINEE<strong class="gradient-text">IQ</strong></span>
        </div>

        <SwitchModo v-if="authStore.rolUsuario === 'estudiante'" />

        <div class="user-info">
            <span class="saludo">Hola, {{ nombreUsuario }}</span>
            <button class="logout-btn" @click="salir">
                <LogOut :size="18" />
                <span>Salir</span>
            </button>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { LogOut } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import SwitchModo from './SwitchModo.vue'

const router = useRouter()
const authStore = useAuthStore()

const nombreUsuario = computed(() => authStore.usuario?.name ?? 'Usuario')

function salir() {
    authStore.cerrarSesion()
    router.push('/login')
}
</script>

<style scoped>
.navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem clamp(1rem, 4vw, 3rem);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-card);
    font-family: Puritan;
}

.brand-mini {
    display: flex;
    align-items: center;
    gap: .6rem;
    font-weight: 700;
    color: var(--color-text);
    flex-shrink: 0;
}

.brand-mini-logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.gradient-text {
    background: linear-gradient(90deg, #7C3AED, #A855F7);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    -webkit-text-fill-color: transparent;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.saludo {
    font-size: .9rem;
    color: var(--color-text-secondary);
    white-space: nowrap;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem .9rem;
    border: 1px solid var(--color-card);
    border-radius: 10px;
    background: transparent;
    color: var(--color-text);
    cursor: pointer;
    font-size: .85rem;
    transition: .2s;
}

.logout-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

/* TABLET: el saludo se esconde primero, ya que el nombre es lo menos crítico */
@media (max-width: 700px) {
    .saludo {
        display: none;
    }
}

/* CELULAR: todo se apila */
@media (max-width: 480px) {
    .navbar {
        flex-wrap: wrap;
        row-gap: .8rem;
    }

    .logout-btn span {
        display: none;
    }
}
</style>