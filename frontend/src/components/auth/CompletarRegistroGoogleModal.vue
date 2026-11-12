<template>
    <!-- Teleport: la ventana se pinta en <body> para que ningún contenedor la recorte -->
    <Teleport to="body">
        <div class="fondo" @mousedown.self="cerrar">
            <form class="modal" role="dialog" aria-modal="true" :aria-labelledby="idTitulo" novalidate
                @submit.prevent="enviar">
                <button type="button" class="cerrar" aria-label="Cerrar" :disabled="cargando" @click="cerrar">
                    <X :size="18" />
                </button>

                <h2 :id="idTitulo">Completa tu registro</h2>
                <p class="ayuda">Entraste con <strong>{{ registro.email }}</strong>. Elige tu rol y crea una
                    contraseña: también podrás entrar con tu correo.</p>

                <label :for="idNombre">Nombre completo</label>
                <input :id="idNombre" ref="campoNombre" v-model="nombre" type="text" autocomplete="name"
                    :aria-invalid="!!errores.nombre" />
                <p class="error">{{ errores.nombre }}</p>

                <span class="etiqueta">Rol</span>
                <RoleCardSelector v-model="rol" />
                <p class="error">{{ errores.rol }}</p>

                <label :for="idPassword">Contraseña</label>
                <div class="con-ojo">
                    <input :id="idPassword" v-model="password" :type="mostrar ? 'text' : 'password'"
                        autocomplete="new-password" placeholder="Mínimo 8 caracteres" :aria-invalid="!!errores.password" />
                    <button type="button" class="ojo" :aria-label="mostrar ? 'Ocultar contraseñas' : 'Mostrar contraseñas'"
                        @click="mostrar = !mostrar">
                        <EyeOff v-if="mostrar" :size="18" />
                        <Eye v-else :size="18" />
                    </button>
                </div>
                <p class="error">{{ errores.password }}</p>

                <label :for="idConfirmar">Confirmar contraseña</label>
                <input :id="idConfirmar" v-model="confirmar" :type="mostrar ? 'text' : 'password'"
                    autocomplete="new-password" placeholder="Repite tu contraseña" :aria-invalid="!!errores.confirmar" />
                <p class="error">{{ errores.confirmar }}</p>

                <p v-if="error" class="error general" role="alert">{{ error }}</p>

                <div class="acciones">
                    <button type="button" class="btn btn-secundario" :disabled="cargando" @click="cerrar">Cancelar</button>
                    <button type="submit" class="btn btn-primario" :disabled="cargando">
                        {{ cargando ? 'Creando cuenta...' : 'Crear cuenta' }}
                    </button>
                </div>
            </form>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted, useId } from 'vue'
import { X, Eye, EyeOff } from 'lucide-vue-next'
import RoleCardSelector from './RoleCardSelector.vue'
import { completarRegistroGoogle } from '@/services/authService'
import { mensajeDeErrorGoogle } from '@/utils/errores'

// registro: { registroToken, nombre, email, rol } que devolvió el backend al entrar con Google
const props = defineProps({ registro: { type: Object, required: true } })
const emit = defineEmits(['cerrar', 'registrado'])

const idTitulo = useId()
const idNombre = useId()
const idPassword = useId()
const idConfirmar = useId()

// El nombre viene de Google y el rol de la pestaña elegida; ambos se pueden cambiar
const nombre = ref(props.registro.nombre)
const rol = ref(props.registro.rol)
const password = ref('')
const confirmar = ref('')
const mostrar = ref(false)
const cargando = ref(false)
const error = ref('')
const errores = ref({})
const campoNombre = ref(null)

function cerrar() {
    // No se cierra mientras se envía: evita perder la respuesta del servidor
    if (!cargando.value) emit('cerrar')
}

function alTeclear(evento) {
    if (evento.key === 'Escape') cerrar()
}

// Mismas reglas que el backend, para avisar antes de enviar
function validar() {
    errores.value = {}
    if (!nombre.value.trim()) errores.value.nombre = 'Escribe tu nombre.'
    if (password.value.length < 8) errores.value.password = 'La contraseña debe tener al menos 8 caracteres.'
    if (confirmar.value !== password.value) errores.value.confirmar = 'Las contraseñas no coinciden.'
    return Object.keys(errores.value).length === 0
}

async function enviar() {
    error.value = ''
    if (!validar()) return

    cargando.value = true
    try {
        const data = await completarRegistroGoogle({
            registroToken: props.registro.registroToken,
            nombre: nombre.value.trim(),
            rol: rol.value,
            password: password.value,
            passwordConfirmacion: confirmar.value
        })
        emit('registrado', data)
    } catch (err) {
        const porCampo = err.response?.status === 422 ? err.response.data?.errors : null
        if (porCampo) {
            // Laravel responde { campo: ["mensaje"] }: cada error va junto a su campo
            errores.value = {
                nombre: porCampo.name?.[0],
                rol: porCampo.rol?.[0],
                password: porCampo.password?.[0]
            }
            if (porCampo.registro_token) error.value = porCampo.registro_token[0]
        } else {
            error.value = mensajeDeErrorGoogle(err)
        }
    } finally {
        cargando.value = false
    }
}

onMounted(() => {
    campoNombre.value?.focus()
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
    overflow-y: auto;
    background: color-mix(in srgb, var(--color-background) 75%, transparent);
    backdrop-filter: blur(3px);
}

.modal {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    width: min(100%, 480px);
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
    margin-bottom: .4rem;
    color: var(--color-text-secondary);
    font-size: .9rem;
}

.ayuda strong {
    color: var(--color-text);
    word-break: break-all;
}

label,
.etiqueta {
    font-size: .95rem;
    font-weight: 600;
    color: var(--color-text);
}

input {
    width: 100%;
    padding: .8rem 1rem;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    outline: none;
    background: var(--color-background);
    color: var(--color-text);
    font-size: 1rem;
}

input:focus {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}

input[aria-invalid='true'] {
    border-color: var(--color-danger);
}

.con-ojo {
    position: relative;
}

.con-ojo input {
    padding-right: 3rem;
}

.ojo,
.cerrar {
    position: absolute;
    display: grid;
    place-items: center;
    border: none;
    background: transparent;
    color: var(--color-text-secondary);
    cursor: pointer;
}

.ojo {
    top: 50%;
    right: .6rem;
    transform: translateY(-50%);
}

.cerrar {
    top: .8rem;
    right: .8rem;
    width: 32px;
    height: 32px;
    border-radius: 10px;
}

.cerrar:hover:not(:disabled) {
    background: var(--color-card);
    color: var(--color-text);
}

.error {
    min-height: 1.1rem;
    color: var(--color-danger);
    font-size: .85rem;
}

.acciones {
    display: flex;
    gap: .7rem;
    justify-content: flex-end;
    margin-top: .4rem;
}

@media (max-width: 480px) {
    .acciones {
        flex-direction: column-reverse;
    }
}
</style>
