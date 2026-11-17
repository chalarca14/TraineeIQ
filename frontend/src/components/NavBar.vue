<template>
    <header class="navbar">

        <!-- Solo visible en tablet/celular: abre el menú lateral -->
        <button class="icon-btn menu-btn" type="button" aria-label="Abrir menú" @click="$emit('toggle-menu')">
            <Menu :size="22" />
        </button>

        <div class="brand-mini">
            <img src="@/assets/icons/cerebro.png" alt="TraineeIQ" class="brand-mini-logo" />
            <span class="brand-text">TRAINEE<strong class="gradient-text">IQ</strong></span>
        </div>

        <div class="navbar-right">
            <SwitchModo v-if="esEstudiante" />

            <!-- Una sola campana para los dos roles: a dónde lleva cada aviso lo decide el componente -->
            <CampanaNotificaciones />

            <div class="user-menu" ref="menuRef" @keydown.esc="menuAbierto = false">
                <button class="user-btn" type="button" :aria-expanded="menuAbierto" @click="menuAbierto = !menuAbierto">
                    <span class="avatar">{{ iniciales }}</span>
                    <span class="user-text">
                        <strong>{{ nombreUsuario }}</strong>
                        <small>{{ etiquetaRol }}</small>
                    </span>
                    <ChevronDown :size="16" class="chevron" />
                </button>

                <div v-if="menuAbierto" class="dropdown">
                    <!-- Cada rol tiene su ruta de Perfil (misma vista, protegida por rol en el router) -->
                    <RouterLink :to="rutaPerfil" class="dropdown-item" @click="menuAbierto = false">
                        <UserRound :size="16" />
                        Perfil
                    </RouterLink>
                    <button type="button" class="dropdown-item" @click="salir">
                        <LogOut :size="16" />
                        Cerrar sesión
                    </button>
                </div>
            </div>
        </div>

    </header>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Menu, ChevronDown, LogOut, UserRound } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useCerrarSesion } from '@/composables/useCerrarSesion'
import SwitchModo from './SwitchModo.vue'
import CampanaNotificaciones from './CampanaNotificaciones.vue'

defineEmits(['toggle-menu'])

const authStore = useAuthStore()
const { cerrarSesion } = useCerrarSesion()

const menuAbierto = ref(false)
const menuRef = ref(null)

const esEstudiante = computed(() => authStore.rolUsuario === 'estudiante')
const nombreUsuario = computed(() => authStore.usuario?.name ?? 'Usuario')
const etiquetaRol = computed(() => (esEstudiante.value ? 'Estudiante' : 'Instructor'))
const rutaPerfil = computed(() => (esEstudiante.value ? '/estudiante/perfil' : '/instructor/perfil'))

// "Ana Sofía Rivera" -> "AS". Como no hay foto de perfil en la base de datos, usamos iniciales
const iniciales = computed(() =>
    nombreUsuario.value
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((palabra) => palabra[0].toUpperCase())
        .join('')
)

function salir() {
    menuAbierto.value = false
    cerrarSesion()
}

// Cierra el menú desplegable si se hace clic en cualquier lugar fuera de él
function cerrarSiClickFuera(evento) {
    if (menuRef.value && !menuRef.value.contains(evento.target)) {
        menuAbierto.value = false
    }
}

onMounted(() => document.addEventListener('click', cerrarSiClickFuera))
// Importante: quitar el listener al destruir el componente, si no queda "colgado" en memoria
onBeforeUnmount(() => document.removeEventListener('click', cerrarSiClickFuera))
</script>

<style scoped>
.navbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: .8rem clamp(1rem, 3vw, 2rem);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-card);
    font-family: Puritan;
    position: relative;
    z-index: 30;
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

.navbar-right {
    display: flex;
    align-items: center;
    gap: .8rem;
    margin-left: auto;
}

.icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid var(--color-card);
    border-radius: 12px;
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: .2s;
}

.icon-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.user-menu {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .3rem .5rem;
    border: none;
    border-radius: 12px;
    background: transparent;
    color: var(--color-text);
    cursor: pointer;
    font-family: inherit;
}

.user-btn:hover {
    background: var(--color-background);
}

.avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-light));
    color: white;
    font-size: .8rem;
    font-weight: 700;
}

.user-text {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    line-height: 1.2;
}

.user-text strong {
    font-size: .85rem;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-text small {
    font-size: .72rem;
    color: var(--color-text-secondary);
}

.dropdown {
    position: absolute;
    top: calc(100% + .5rem);
    right: 0;
    min-width: 180px;
    padding: .4rem;
    border: 1px solid var(--color-card);
    border-radius: 12px;
    background: var(--color-surface);
    box-shadow: 0 12px 30px rgba(0, 0, 0, .4);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: .6rem;
    width: 100%;
    padding: .6rem .8rem;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: var(--color-text);
    font-size: .85rem;
    font-family: inherit;
    /* "Perfil" es un enlace (RouterLink): sin subrayado, igual que el botón de cerrar sesión */
    text-decoration: none;
    min-height: 44px;
    cursor: pointer;
}

.dropdown-item:hover {
    background: var(--color-background);
    color: var(--color-primary);
}

/* La hamburguesa solo existe en tablet y celular. Se escribe .icon-btn.menu-btn (dos clases) para
   ganarle en especificidad al display: flex de .icon-btn; con solo .menu-btn no se ocultaba. */
.icon-btn.menu-btn {
    display: none;
}

/* TABLET Y CELULAR: aparece la hamburguesa */
@media (max-width: 900px) {
    .icon-btn.menu-btn {
        display: flex;
    }
}

/* CELULAR: solo el avatar, sin nombre ni logo escrito */
@media (max-width: 600px) {
    .user-text,
    .chevron,
    .brand-text {
        display: none;
    }

    .navbar {
        gap: .6rem;
    }
}
</style>