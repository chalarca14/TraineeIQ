<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <form class="repo" novalidate @submit.prevent="guardar">
        <label for="repo-url">Link de tu repositorio en GitHub</label>
        <div class="fila">
            <input id="repo-url" v-model.trim="link" type="url" placeholder="https://github.com/usuario/repositorio" />
            <button type="submit" class="btn" :disabled="guardando || !hayCambios">
                {{ guardando ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>
        <p v-if="error" class="mensaje error">{{ error }}</p>
        <p v-else-if="exito" class="mensaje exito">Repositorio guardado.</p>
    </form>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { esRepoGithub, guardarRepo } from '@/services/miniProyectoService'

const props = defineProps({
    miniProyectoId: { type: Number, required: true },
    repoUrl: { type: String, default: '' }
})

const emit = defineEmits(['guardado'])

const link = ref(props.repoUrl)
const guardando = ref(false)
const error = ref('')
const exito = ref(false)

const hayCambios = computed(() => link.value !== props.repoUrl)

// La entrega llega después de montar el formulario
watch(() => props.repoUrl, (valor) => {
    link.value = valor
})

// Valida el link antes de enviarlo y avisa a la vista cuando queda guardado
async function guardar() {
    error.value = ''
    exito.value = false

    if (!esRepoGithub(link.value)) {
        error.value = 'El link debe tener la forma https://github.com/usuario/repositorio.'
        return
    }

    guardando.value = true

    try {
        emit('guardado', await guardarRepo(props.miniProyectoId, link.value))
        exito.value = true
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos guardar el repositorio.'
    } finally {
        guardando.value = false
    }
}
</script>

<style scoped>
.repo {
    display: grid;
    gap: .5rem;
}

.fila {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
}

input {
    flex: 1;
    min-width: 240px;
    padding: .6rem .8rem;
    border: 1px solid var(--color-card);
    border-radius: 10px;
    background: var(--color-surface);
    color: var(--color-text);
}

.btn {
    padding: .5rem 1rem;
    border: none;
    border-radius: 10px;
    background: var(--color-primary);
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.mensaje.error {
    color: var(--color-danger);
}

.mensaje.exito {
    color: var(--color-success);
}
</style>
