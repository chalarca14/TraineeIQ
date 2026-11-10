<template>
    <!-- Teleport: el modal se pinta en <body> para que ningún contenedor con overflow lo recorte -->
    <Teleport to="body">
        <!-- mousedown.self: solo cierra si el clic empieza en el fondo, no al soltar el ratón
            después de seleccionar texto dentro del campo -->
        <div class="fondo" @mousedown.self="cerrar">
            <form class="modal" role="dialog" aria-modal="true" :aria-labelledby="idTitulo" novalidate
                @submit.prevent="enviar">
                <button type="button" class="cerrar" aria-label="Cerrar" :disabled="cargando" @click="cerrar">
                    <X :size="18" />
                </button>

                <h2 :id="idTitulo">Unirse a un grupo</h2>
                <p class="ayuda">Escribe el código de acceso que te dio tu instructor.</p>

                <label :for="idCampo">Código de acceso</label>
                <input :id="idCampo" ref="campo" v-model="codigo" type="text" autocomplete="off"
                    placeholder="Ej: AB12CD" :aria-invalid="!!mensaje" :aria-describedby="idError" />

                <p :id="idError" class="error" role="alert">{{ mensaje }}</p>

                <div class="acciones">
                    <button type="button" class="btn btn-secundario" :disabled="cargando" @click="cerrar">Cancelar</button>
                    <button type="submit" class="btn btn-primario" :disabled="cargando">
                        {{ cargando ? 'Uniéndote...' : 'Unirme' }}
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted, useId } from 'vue'
import { X } from 'lucide-vue-next'
import { unirseAGrupo } from '@/services/grupoService'
import { mensajeDeErrorUnirse } from '@/utils/errores'

const emit = defineEmits(['cerrar', 'unido'])

const idTitulo = useId()
const idCampo = useId()
const idError = useId()

const codigo = ref('')
const mensaje = ref('')
const cargando = ref(false)
const campo = ref(null)

function cerrar() {
    // No se cierra mientras se envía: evita perder la respuesta del servidor
    if (!cargando.value) emit('cerrar')
}

// Escape se escucha en document: el listener del fondo solo recibe teclas si hay foco dentro
function alTeclear(evento) {
    if (evento.key === 'Escape') cerrar()
}

async function enviar() {
    mensaje.value = ''

    // Solo se recortan los espacios de los extremos; las mayúsculas las decide el backend
    const limpio = codigo.value.trim()
    if (!limpio) {
        mensaje.value = 'Escribe el código de acceso del grupo.'
        campo.value?.focus()
        return
    }

    cargando.value = true
    try {
        const grupo = await unirseAGrupo(limpio)
        emit('unido', grupo)
    } catch (err) {
        mensaje.value = mensajeDeErrorUnirse(err)
        campo.value?.focus()
    } finally {
        cargando.value = false
    }
}

onMounted(() => {
    campo.value?.focus()
    document.addEventListener('keydown', alTeclear)
})
onUnmounted(() => document.removeEventListener('keydown', alTeclear))
</script>

<style scoped>
.fondo {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: grid;
    place-items: center;
    padding: 1rem;
    background: color-mix(in srgb, var(--color-background) 75%, transparent);
    backdrop-filter: blur(3px);
}

.modal {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .6rem;
    width: min(100%, 420px);
    padding: 1.5rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: var(--color-surface);
    box-shadow: var(--brillo-primario-fuerte);
}

h2 {
    padding-right: 2rem;
    font-size: 1.2rem;
    color: var(--color-text);
}

.ayuda {
    color: var(--color-text-secondary);
    font-size: .9rem;
}

label {
    margin-top: .4rem;
    font-size: .95rem;
    font-weight: 600;
    color: var(--color-text);

}

input {
    width: 100%;
    padding: .9rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    outline: none;
    background: var(--color-background);
    color: var(--color-text);
    font-size: 1rem;
    transition: .25s;
}

input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

input[aria-invalid='true'] {
    border-color: var(--color-danger);
}

.error {
    min-height: 1.2rem;
    color: var(--color-danger);
    font-size: .85rem;
}

.acciones {
    display: flex;
    gap: .7rem;
    justify-content: flex-end;
}

.cerrar {
    position: absolute;
    top: .8rem;
    right: .8rem;
    display: grid;
    place-items: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 10px;
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
}

.cerrar:hover:not(:disabled) {
    background: var(--color-card);
    color: var(--color-text);
}

@media (max-width: 480px) {
    .acciones {
        flex-direction: column-reverse;
    }

    .acciones .btn {
        justify-content: center;
    }
}
</style>
