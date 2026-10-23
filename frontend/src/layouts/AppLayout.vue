<template>
    <div class="app-layout">
        <NavBar @toggle-menu="sidebarAbierto = !sidebarAbierto" />

        <div class="app-body">
            <SideBar :abierto="sidebarAbierto" @cerrar="sidebarAbierto = false" />

            <!-- Fondo oscuro que cubre la pantalla cuando el menú está abierto en celular -->
            <div v-if="sidebarAbierto" class="backdrop" @click="sidebarAbierto = false"></div>

            <main class="app-main">
                <!-- Aquí Vue Router dibuja la vista hija (Dashboard, Perfil, etc.) -->
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import NavBar from '@/components/NavBar.vue'
import SideBar from '@/components/SideBar.vue'

// Controla si el menú lateral está abierto (solo se nota en celular y tablet)
const sidebarAbierto = ref(false)
</script>

<style scoped>
.app-layout {
    display: flex;
    flex-direction: column;
    height: 100dvh;
    background: var(--color-background);
    color: var(--color-text);
}

.app-body {
    display: flex;
    flex: 1;
    min-height: 0;
    /* clave: sin esto el contenido empuja y se sale de la pantalla */
}

.app-main {
    flex: 1;
    min-width: 0;
    overflow-y: auto;
    /* solo scrollea el contenido; NavBar y SideBar se quedan fijos */
}

.backdrop {
    display: none;
}

@media (max-width: 900px) {
    .backdrop {
        display: block;
        position: fixed;
        inset: 0;
        z-index: 10;
        background: rgba(0, 0, 0, .55);
    }
}
</style>