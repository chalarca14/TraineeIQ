<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <div class="editor">
        <a :href="enlaceEditor" class="btn">Abrir en VS Code</a>
        <a v-if="enlaceNavegador" :href="enlaceNavegador" target="_blank" rel="noopener" class="respaldo">
            Abrir en el navegador (github.dev)
        </a>
        <p class="ayuda">
            {{ repoValido ? 'VS Code te pedirá elegir la carpeta donde clonar el repositorio.' : 'Guarda tu repositorio para clonarlo desde aquí.' }}
            Si no pasa nada, puede que no tengas VS Code:
            <a href="https://code.visualstudio.com/download" target="_blank" rel="noopener">descárgalo aquí</a>.
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { esRepoGithub } from '@/services/miniProyectoService'

const props = defineProps({
    repoUrl: { type: String, default: '' }
})

// El link se valida otra vez porque con él se arma un enlace que abre un programa local
const repoValido = computed(() => esRepoGithub(props.repoUrl))

const enlaceEditor = computed(() => (
    repoValido.value ? `vscode://vscode.git/clone?url=${encodeURIComponent(props.repoUrl)}` : 'vscode://'
))

const enlaceNavegador = computed(() => (
    repoValido.value ? props.repoUrl.replace('https://github.com/', 'https://github.dev/') : null
))
</script>

<style scoped>
.editor {
    display: grid;
    gap: .5rem;
    justify-items: start;
}

.btn {
    padding: .6rem 1.1rem;
    border-radius: 10px;
    background: var(--color-primary);
    color: white;
    font-weight: 600;
    text-decoration: none;
}

.respaldo,
.ayuda a {
    color: var(--color-primary-light);
}

.ayuda {
    color: var(--color-text-secondary);
    font-size: .85rem;
}
</style>
