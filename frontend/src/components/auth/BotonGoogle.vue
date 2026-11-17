<template>
    <!-- Sin VITE_GOOGLE_CLIENT_ID no se muestra nada: queda solo el formulario normal -->
    <div v-if="googleDisponible" class="boton-google">
        <p v-if="recomendado" class="recomendado">
            <ShieldCheck :size="16" />
            <span><strong>Recomendado:</strong> con Google tu correo queda verificado y no dependes solo de una contraseña.</span>
        </p>

        <!-- Google dibuja aquí su botón oficial; mientras responde el backend se bloquea.
             tabindex="-1": permite devolverle el foco por código si el iframe aún no está -->
        <div ref="contenedor" class="contenedor" tabindex="-1" :class="{ ocupado: cargando }" :aria-busy="cargando"></div>

        <p v-if="cargando" class="estado">Verificando tu cuenta de Google...</p>
        <p v-else-if="error || errorCarga" class="error" role="alert">{{ error || errorCarga }}</p>

        <div class="separador"><span>{{ separador }}</span></div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ShieldCheck } from 'lucide-vue-next'
import { googleDisponible, renderizarBotonGoogle } from '@/composables/useGoogle'

const props = defineProps({
    // Muestra el aviso de que registrarse con Google es lo recomendado
    recomendado: { type: Boolean, default: false },
    // 'continue_with' (login) o 'signup_with' (registro)
    texto: { type: String, default: 'continue_with' },
    // Texto de la línea que separa el botón del formulario normal
    separador: { type: String, default: 'o' },
    cargando: { type: Boolean, default: false },
    error: { type: String, default: '' }
})

const emit = defineEmits(['credencial'])

// Devuelve el foco al botón (al cancelar la ventana "Completa tu registro"). El botón vive dentro
// del iframe de Google: se enfoca el iframe, y si todavía no existe, el contenedor
function enfocar() {
    const iframe = contenedor.value?.querySelector('iframe')
    ;(iframe ?? contenedor.value)?.focus()
}
defineExpose({ enfocar })

const contenedor = ref(null)
const errorCarga = ref('')

onMounted(async () => {
    if (!googleDisponible) return

    try {
        await renderizarBotonGoogle(contenedor.value, (credencial) => emit('credencial', credencial), props.texto)
    } catch (err) {
        errorCarga.value = 'No se pudo cargar el botón de Google. Puedes usar tu correo y contraseña.'
    }
})
</script>

<style scoped>
.boton-google {
    display: flex;
    flex-direction: column;
    gap: .6rem;
    margin-block: .8rem;
}

.recomendado {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    padding: .6rem .8rem;
    border: 1px solid color-mix(in srgb, var(--color-primary) 40%, transparent);
    border-radius: 12px;
    background: color-mix(in srgb, var(--color-primary) 10%, transparent);
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.recomendado svg {
    flex-shrink: 0;
    margin-top: .1rem;
    color: var(--color-primary);
}

.recomendado strong {
    color: var(--color-text);
}

/* color-scheme: light porque la página es dark (variables.css) y el iframe de Google es light.
   Si no coinciden, Chrome pinta el iframe con fondo blanco opaco, y como el iframe es más ancho
   que el botón redondeado, se veía un rectángulo blanco a los lados */
.contenedor {
    display: flex;
    justify-content: center;
    min-height: 44px;
    color-scheme: light;
    outline: none;
}

.contenedor.ocupado {
    opacity: .5;
    pointer-events: none;
}

.estado,
.error {
    text-align: center;
    font-size: .85rem;
}

.estado {
    color: var(--color-text-secondary);
}

.error {
    color: var(--color-danger);
}

.separador {
    display: flex;
    align-items: center;
    gap: .8rem;
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.separador::before,
.separador::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--color-border);
}
</style>
