<template>
    <!-- novalidate: validamos nosotros para mostrar el error junto al campo, no el globo del navegador -->
    <form novalidate @submit.prevent="enviar">
        <div class="fila">
            <label class="campo" :class="{ invalido: error || errorExterno }">
                <Github :size="18" />
                <input v-model="url" type="url" inputmode="url" placeholder="https://github.com/usuario/proyecto"
                    aria-label="Enlace del repositorio de GitHub" :aria-invalid="Boolean(error || errorExterno)"
                    :disabled="deshabilitado || guardando" @input="error = ''" />
            </label>
            <button type="submit" class="btn btn-secundario" :disabled="sinCambios || deshabilitado || guardando">
                <Save :size="16" />
                {{ guardando ? 'Guardando…' : repoGuardado ? 'Cambiar repositorio' : 'Guardar repositorio' }}
            </button>
        </div>
        <!-- error local (URL mal escrita) o el message del servidor (409, 422...) -->
        <p v-if="error || errorExterno" class="error" role="alert">{{ error || errorExterno }}</p>
        <p v-else class="nota">Solo repositorios públicos de GitHub, sin ".git" al final.</p>
    </form>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Save, Github } from 'lucide-vue-next'
import { validarRepoGitHub } from '@/utils/validaciones'

const props = defineProps({
    // Repositorio que ya tiene guardado el aprendiz (para mostrarlo y saber si cambió)
    repoGuardado: { type: String, default: '' },
    guardando: { type: Boolean, default: false },
    // Hay una evaluación corriendo: el backend rechaza (409) cambiar el repositorio mientras tanto
    deshabilitado: { type: Boolean, default: false },
    // Error que viene del servidor al guardar (el de la URL mal escrita se calcula aquí mismo)
    errorExterno: { type: String, default: '' }
})
const emit = defineEmits(['guardar'])

const url = ref('')
const error = ref('')

// La entrega llega después de montar el formulario: se rellena el campo con el repositorio guardado
// solo si el aprendiz todavía no escribió nada, para no pisar lo que está tecleando
watch(() => props.repoGuardado, (valor) => { if (!url.value) url.value = valor ?? '' }, { immediate: true })

const sinCambios = computed(() => url.value.trim() === props.repoGuardado)

// Validamos ANTES de avisar a la vista: una URL mal escrita nunca sale del formulario
function enviar() {
    if (props.guardando || props.deshabilitado) return // seguro extra: el botón ya está deshabilitado
    error.value = validarRepoGitHub(url.value)
    if (error.value) return
    emit('guardar', url.value.trim())
}
</script>

<style scoped>
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
        justify-content: center;
        padding: .75rem 1rem;
    }
}
</style>
