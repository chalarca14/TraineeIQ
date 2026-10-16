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
.registro-page {
    display: grid;
    grid-template-columns: 55% 45%;
    height: 100vh;
    height: 100dvh;
    width: 100%;
    background-color: var(--color-background);
    overflow: hidden;
}

/* TABLET */
@media (max-width: 900px) {
    .registro-page {
        grid-template-columns: 45% 55%;
        height: auto;
        min-height: 100dvh;
        overflow: visible;
    }
}

/* CELULAR */
@media (max-width: 700px) {
    .registro-page {
        grid-template-columns: 1fr;
        height: auto;
        min-height: 100dvh;
        overflow: visible;
    }
}
</style>