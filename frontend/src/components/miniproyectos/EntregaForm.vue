<template>
    <section class="entrega">
        <h3><UploadCloud :size="20" /> Entrega tu repositorio</h3>
        <p class="ayuda">Pega el enlace de tu repositorio de GitHub para que podamos evaluar tu proyecto.</p>

        <!-- novalidate: validamos nosotros para mostrar el error junto al campo, no el globo del navegador -->
        <form novalidate @submit.prevent="enviar">
            <div class="fila">
                <label class="campo" :class="{ invalido: error }">
                    <Github :size="18" />
                    <input v-model="url" type="url" inputmode="url" placeholder="https://github.com/usuario/proyecto"
                        aria-label="Enlace del repositorio de GitHub" :aria-invalid="Boolean(error)"
                        :disabled="deshabilitado" @input="error = ''" />
                </label>
                <button type="submit" class="btn btn-primario" :disabled="deshabilitado || enEvaluacion">
                    <Upload :size="16" /> {{ enEvaluacion ? 'Evaluando…' : 'Subir entrega' }}
                </button>
            </div>
            <!-- error local (URL mal escrita) o del servidor (429, repositorio rechazado...) -->
            <p v-if="error || errorExterno" class="error" role="alert">{{ error || errorExterno }}</p>
            <p v-if="enEvaluacion" class="nota">Hay una evaluación en curso. Espera a que termine para entregar de nuevo.</p>
            <p v-else class="nota">Solo se aceptan repositorios públicos de GitHub.</p>
        </form>
    </section>
</template>

<script setup>
import { ref } from 'vue'
import { Upload, UploadCloud, Github } from 'lucide-vue-next'
import { validarRepoGitHub } from '@/utils/validaciones'

const props = defineProps({
    deshabilitado: { type: Boolean, default: false },
    // Hay una evaluación corriendo: no se puede entregar otra hasta que termine
    enEvaluacion: { type: Boolean, default: false },
    // Error que viene del servidor al enviar (el de la URL mal escrita se calcula aquí mismo)
    errorExterno: { type: String, default: '' }
})
const emit = defineEmits(['entregar'])

const url = ref('')
const error = ref('')

// Validamos ANTES de avisar a la vista: una URL mal escrita nunca sale del formulario
function enviar() {
    if (props.enEvaluacion || props.deshabilitado) return // seguro extra: el botón ya está deshabilitado
    error.value = validarRepoGitHub(url.value)
    if (error.value) return
    emit('entregar', url.value.trim())
}
</script>

<style scoped>
h3 {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .2rem;
    font-size: 1rem;
}

h3 svg {
    color: var(--color-primary-light);
}

.ayuda,
.nota {
    color: var(--color-text-secondary);
    font-size: .82rem;
}

.fila {
    display: flex;
    gap: .6rem;
    margin: .8rem 0 .4rem;
}

.campo {
    display: flex;
    flex: 1;
    min-width: 0;
    align-items: center;
    gap: .55rem;
    padding: 0 .8rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    background: var(--color-background);
    color: var(--color-text-secondary);
    transition: border-color .2s, box-shadow .2s;
}

.campo:focus-within {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-primary) 25%, transparent);
}

.campo.invalido {
    border-color: var(--color-danger);
}

input {
    flex: 1;
    min-width: 0;
    padding: .7rem 0;
    border: none;
    outline: none;
    background: transparent;
    color: var(--color-text);
}

input:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.error {
    margin-bottom: .3rem;
    color: var(--color-danger);
    font-size: .82rem;
}

/* En 360 px el campo y el botón van uno debajo del otro */
@media (max-width: 520px) {
    .fila {
        flex-direction: column;
    }

    .btn {
        padding: .75rem 1rem;
    }
}
</style>
