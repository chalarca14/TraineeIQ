<template>
    <article class="guia-actual">
        <h2><BookOpen :size="20" /> Mi guía actual</h2>

        <!-- Tres estados: cargando, error, vacío y con datos. Solo se muestra uno -->
        <p v-if="cargando" class="mensaje">Cargando tu guía...</p>
        <p v-else-if="error" class="mensaje error">No se pudo cargar tu guía. Intenta de nuevo más tarde.</p>

        <div v-else-if="!guia" class="mensaje vacio">
            <p>Aún no tienes guías.</p>
            <RouterLink to="/estudiante/guias" class="btn-ver">Subir mi primera guía</RouterLink>
        </div>

        <template v-else>
            <div class="resumen">
                <div>
                    <h3>{{ guia.nombre }}</h3>
                    <span class="chip">Modo Personal</span>
                </div>
                <RouterLink :to="`/estudiante/guias/${guia.id}`" class="btn-ver">
                    Ver detalles <ArrowRight :size="16" />
                </RouterLink>
            </div>

            <div class="progreso">
                <div class="barra"><div class="relleno" :style="{ width: porcentaje + '%' }"></div></div>
                <span>{{ porcentaje }}%</span>
            </div>

            <h4>Temas de la guía</h4>
            <ul class="temas">
                <li v-for="(tema, i) in temasConEstado" :key="tema.id">
                    <TemaEstado :numero="i + 1" :nombre="tema.nombre_tema" :estado="tema.estado" />
                </li>
            </ul>
        </template>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { BookOpen, ArrowRight } from 'lucide-vue-next'
import TemaEstado from '@/components/dashboard/TemaEstado.vue'

const props = defineProps({
    guia: { type: Object, default: null },
    cargando: { type: Boolean, default: false },
    error: { type: Boolean, default: false }
})

// DATO DE EJEMPLO: el backend aún no guarda qué temas completó el estudiante.
// Se reemplaza por el progreso real cuando exista el endpoint.
const TEMAS_HECHOS_EJEMPLO = 1

const temasConEstado = computed(() => {
    const temas = props.guia?.temas ?? []
    return temas.map((tema, i) => ({
        ...tema,
        estado: i < TEMAS_HECHOS_EJEMPLO ? 'hecho' : i === TEMAS_HECHOS_EJEMPLO ? 'actual' : 'pendiente'
    }))
})

const porcentaje = computed(() => {
    const total = temasConEstado.value.length
    // Evita dividir entre cero si la guía no tiene temas
    if (total === 0) return 0
    return Math.round((Math.min(TEMAS_HECHOS_EJEMPLO, total) / total) * 100)
})
</script>

<style scoped>
.guia-actual {
    padding: 1.4rem;
    border: 1px solid var(--color-card);
    border-radius: 14px;
    background: var(--color-surface);
}

h2 {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.resumen {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.resumen h3 {
    margin-bottom: .3rem;
}

.chip {
    padding: .15rem .6rem;
    border-radius: 999px;
    background: rgba(124, 58, 237, .2);
    color: var(--color-primary-light);
    font-size: .72rem;
}

.btn-ver {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1rem;
    border-radius: 10px;
    background: var(--color-primary);
    color: var(--color-text);
    font-size: .85rem;
    text-decoration: none;
    transition: .2s;
}

.btn-ver:hover {
    background: var(--color-primary-hover);
}

.progreso {
    display: flex;
    align-items: center;
    gap: .8rem;
    margin: 1rem 0 1.2rem;
    font-size: .85rem;
    color: var(--color-text-secondary);
}

.barra {
    flex: 1;
    height: 8px;
    border-radius: 999px;
    background: var(--color-background);
    overflow: hidden;
}

.relleno {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    transition: width .4s ease;
}

h4 {
    margin-bottom: .7rem;
    font-size: .9rem;
    color: var(--color-text-secondary);
}

/* Fila horizontal con scroll: en celular no se rompe ni se apila */
.temas {
    display: flex;
    gap: .7rem;
    padding: 0 0 .5rem;
    list-style: none;
    overflow-x: auto;
}

.temas li {
    flex: 0 0 auto;
}

.mensaje {
    color: var(--color-text-secondary);
    font-size: .9rem;
}

.mensaje.error {
    color: var(--color-danger);
}

.vacio {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .8rem;
}
</style>