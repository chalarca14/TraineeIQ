<template>
    <main class="registro-page">
        <RegistroForm v-model:rol="rolSeleccionado" />
        <RegistroInfo :rol="rolSeleccionado" />
    </main>
</template>


<script setup>
import RegistroInfo from '@/components/auth/RegistroInfo.vue'
import RegistroForm from '@/components/ui/RegistroForm.vue'
import { ref } from 'vue'

const rolSeleccionado = ref('estudiante')

</script>

<style scoped>
/* min-height (no height fijo): si el formulario no cabe, la página crece y se hace scroll completo.
   Antes, con height: 100dvh + overflow: hidden, cada panel tenía su propio scroll y su contenido
   centrado se salía por arriba, donde el scroll no llega (el título quedaba cortado) */
.registro-page {
    display: grid;
    grid-template-columns: 55% 45%;
    min-height: 100vh;
    min-height: 100dvh;
    width: 100%;
    background-color: var(--color-background);
    /* La animación de entrada de RegistroInfo lo desplaza 30 px: clip evita que eso abra un scroll
       horizontal por un instante (a diferencia de hidden, no crea otro contenedor con scroll) */
    overflow-x: clip;
}

/* TABLET */
@media (max-width: 900px) {
    .registro-page {
        grid-template-columns: 45% 55%;
    }
}

/* CELULAR */
@media (max-width: 700px) {
    .registro-page {
        grid-template-columns: 1fr;
    }
}
</style>