<template>
    <form class="subir-form" @submit.prevent="enviar" novalidate>
        <h2>Subir una guía</h2>

        <div class="field-group">
            <BaseInput v-model="nombre" label="Nombre de la guía" placeholder="Ej: JavaScript desde cero" />
            <p class="input-error">{{ errores.nombre }}</p>
        </div>

        <div class="field-group">
            <label class="file-drop" :class="{ lleno: archivo }">
                <FileUp :size="24" />
                <span>{{ archivo ? archivo.name : 'Haz clic para elegir un PDF (máx. 10 MB)' }}</span>
                <input type="file" accept="application/pdf" class="sr-only" @change="elegirArchivo" />
            </label>
            <button v-if="puedeReintentar" type="button" class="btn-cancelar" :disabled="cargando"
                @click="enviar">Reintentar</button>
        </div>

        <p class="input-error">{{ error }}</p>
        <button v-if="puedeReintentar" type="button" class="btn-cancelar" :disabled="cargando"
            @click="enviar">Reintentar</button>

        <div class="acciones">
            <button type="button" class="btn-cancelar" :disabled="cargando" @click="$emit('cancelar')">Cancelar</button>
            <BaseButton type="submit" :disabled="cargando">
                {{ cargando ? 'Leyendo y dividiendo en temas...' : 'Subir y dividir en temas' }}
            </BaseButton>
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue'
import { FileUp } from 'lucide-vue-next'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { subirGuia } from '@/services/guiaService'

const emit = defineEmits(['subida', 'cancelar'])

const MAX_MB = 10

const nombre = ref('')
const archivo = ref(null)
const cargando = ref(false)
const error = ref('')
const errores = ref({ nombre: '', archivo: '' })
const puedeReintentar = ref(false)

function elegirArchivo(evento) {
    archivo.value = evento.target.files[0] ?? null
}

function validar() {
    errores.value = { nombre: '', archivo: '' }
    let esValido = true

    if (!nombre.value.trim()) {
        errores.value.nombre = 'Escribe un nombre para la guía.'
        esValido = false
    }
    if (!archivo.value) {
        errores.value.archivo = 'Selecciona un archivo PDF.'
        esValido = false
    } else if (archivo.value.type !== 'application/pdf') {
        errores.value.archivo = 'El archivo debe ser un PDF.'
        esValido = false
    } else if (archivo.value.size > MAX_MB * 1024 * 1024) {
        errores.value.archivo = `El PDF no puede pesar más de ${MAX_MB} MB.`
        esValido = false
    }
    return esValido
}

async function enviar() {
    error.value = ''
    puedeReintentar.value = false
    if (!validar()) return

    cargando.value = true
    try {
        // El servicio arma el FormData; aquí solo le pasamos los datos
        const guia = await subirGuia({
            nombre: nombre.value.trim(),
            archivo: archivo.value
        })
        emit('subida', guia)
    } catch (err) {
        const status = err.response?.status
        const datos = err.response?.data

        if (status === 422 && datos?.errors) {
            errores.value.nombre = datos.errors.nombre?.[0] ?? ''
            errores.value.archivo = datos.errors.archivo?.[0] ?? ''
        } else if (status === 422) {
            error.value = datos?.message ?? 'No pudimos leer texto en ese PDF.'
        } else if (status === 403) {
            error.value = 'No tienes permiso para hacer esto.'
        } else if (status === 503 || err.code === 'ECONNABORTED') {
            error.value = datos?.message ?? 'La IA está tardando demasiado. Intenta de nuevo.'
            puedeReintentar.value = true
        } else {
            error.value = 'No se pudo subir la guía.'
        }
    } finally {
        cargando.value = false
    }
}
</script>

<style scoped>
.subir-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
}

.subir-form h2 {
    margin: 0;
    font-size: 1.1rem;
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: .3rem;
}

.input-error {
    margin: 0;
    min-height: 1rem;
    color: var(--color-danger);
    font-size: .82rem;
}

.file-drop {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .5rem;
    padding: 1.5rem;
    border: 2px dashed var(--color-card);
    border-radius: 14px;
    color: var(--color-text-secondary);
    text-align: center;
    cursor: pointer;
    transition: .2s;
}

.file-drop:hover,
.file-drop.lleno {
    border-color: var(--color-primary);
    color: var(--color-text);
}

.acciones {
    display: flex;
    gap: .8rem;
    justify-content: flex-end;
}

.acciones :deep(.base-button) {
    width: auto;
    padding-inline: 1.5rem;
}

.btn-cancelar {
    padding: .7rem 1.2rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: transparent;
    color: var(--color-text);
    cursor: pointer;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
}

@media (max-width: 600px) {
    .acciones {
        flex-direction: column-reverse;
    }
}
</style>