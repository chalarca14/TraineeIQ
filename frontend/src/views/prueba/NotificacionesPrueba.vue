<!-- PRUEBA-BACKEND: borrar cuando exista la campanita definitiva -->
<template>
    <main class="prueba">
        <RouterLink to="/prueba/guias" class="volver">Volver a las guías</RouterLink>

        <header class="encabezado">
            <h1>🔔 Notificaciones <span v-if="noLeidas > 0" class="contador">{{ noLeidas }}</span></h1>
            <button type="button" class="btn" :disabled="noLeidas === 0 || marcando" @click="marcarTodas">
                Marcar todas como leídas
            </button>
        </header>

        <p v-if="cargando" class="estado">Cargando avisos...</p>
        <p v-else-if="error" class="estado error">{{ error }}</p>
        <p v-else-if="avisos.length === 0" class="estado">Aún no tienes avisos.</p>

        <ul v-else class="lista">
            <li v-for="aviso in avisos" :key="aviso.id" class="aviso" :class="{ nuevo: !aviso.leida_en }">
                <p>{{ aviso.mensaje }}</p>
                <small>{{ new Date(aviso.updated_at).toLocaleString('es-CO') }}</small>
                <button v-if="!aviso.leida_en" type="button" class="enlace" @click="marcar(aviso)">
                    Marcar como leída
                </button>
            </li>
        </ul>
    </main>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { getNotificaciones, marcarLeida, marcarTodasLeidas } from '@/services/notificacionService'

// Sin tiempo real: se vuelve a consultar cada minuto
const SEGUNDOS_ENTRE_CONSULTAS = 60

const avisos = ref([])
const noLeidas = ref(0)
const cargando = ref(true)
const marcando = ref(false)
const error = ref('')
let temporizador = null

// Trae la primera página de avisos y el número de no leídos
async function cargar() {
    error.value = ''

    try {
        const datos = await getNotificaciones()
        avisos.value = datos.notificaciones.data
        noLeidas.value = datos.no_leidas
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos cargar tus avisos.'
    } finally {
        cargando.value = false
    }
}

// Marca un aviso y baja el contador sin volver a pedir la lista
async function marcar(aviso) {
    try {
        const actualizado = await marcarLeida(aviso.id)
        aviso.leida_en = actualizado.leida_en
        noLeidas.value = Math.max(0, noLeidas.value - 1)
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos marcar el aviso.'
    }
}

// Marca todos los avisos pendientes y recarga la lista
async function marcarTodas() {
    marcando.value = true

    try {
        await marcarTodasLeidas()
        await cargar()
    } catch (e) {
        error.value = e.response?.data?.message ?? 'No pudimos marcar los avisos.'
    } finally {
        marcando.value = false
    }
}

onMounted(() => {
    cargar()
    temporizador = setInterval(cargar, SEGUNDOS_ENTRE_CONSULTAS * 1000)
})

// Detiene la consulta periódica al salir de la vista
onUnmounted(() => clearInterval(temporizador))
</script>

<style scoped>
.prueba {
    display: grid;
    gap: 1rem;
    padding: clamp(1.5rem, 4vw, 3rem);
    max-width: 800px;
    margin: 0 auto;
}

.encabezado {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
}

h1 {
    font-size: clamp(1.4rem, 3vw, 1.8rem);
}

.contador {
    padding: .1rem .55rem;
    border-radius: 999px;
    background: var(--color-danger);
    color: white;
    font-size: .9rem;
    vertical-align: middle;
}

.volver,
.estado,
small {
    color: var(--color-text-secondary);
}

.estado.error {
    color: var(--color-danger);
}

.lista {
    display: grid;
    gap: .75rem;
    padding: 0;
    list-style: none;
}

.aviso {
    display: grid;
    gap: .35rem;
    padding: 1rem;
    border-radius: 14px;
    background: var(--color-card);
}

.aviso.nuevo {
    border-left: 4px solid var(--color-primary);
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

.enlace {
    justify-self: start;
    padding: 0;
    border: none;
    background: none;
    color: var(--color-primary-light);
    cursor: pointer;
}
</style>
