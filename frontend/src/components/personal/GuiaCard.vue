<template>
    <article class="guia">
        <div class="cabecera">
            <span class="icono" aria-hidden="true"><BookOpen :size="26" /></span>
            <div class="titulo">
                <h3 :title="guia.nombre">{{ guia.nombre }}</h3>
                <p>
                    <span class="dato"><Layers :size="13" /> {{ cantidadTemas }} {{ cantidadTemas === 1 ? 'tema' : 'temas' }}</span>
                    <span v-if="fecha" class="dato"><CalendarDays :size="13" /> {{ fecha }}</span>
                </p>
            </div>
        </div>

        <!-- Vista previa de los primeros temas para reconocer la guía de un vistazo -->
        <ul v-if="temasVista.length" class="temas">
            <li v-for="t in temasVista" :key="t.id" :title="t.nombre_tema">{{ t.nombre_tema }}</li>
            <li v-if="restantes > 0" class="mas">+{{ restantes }}</li>
        </ul>

        <RouterLink :to="`/estudiante/guias/${guia.id}`" class="btn btn-secundario btn-sm">
            Ver temas <ArrowRight :size="15" />
        </RouterLink>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { BookOpen, Layers, CalendarDays, ArrowRight } from 'lucide-vue-next'
import { formatearFecha } from '@/utils/fechas'

const props = defineProps({ guia: { type: Object, required: true } })

const MAX_TEMAS_VISTA = 3

const cantidadTemas = computed(() => props.guia.temas?.length ?? 0)
const temasVista = computed(() => (props.guia.temas ?? []).slice(0, MAX_TEMAS_VISTA))
const restantes = computed(() => cantidadTemas.value - MAX_TEMAS_VISTA)
// El backend manda created_at en formato ISO crudo; aquí lo mostramos como "25 oct 2026"
const fecha = computed(() => formatearFecha(props.guia.created_at))
</script>

<style scoped>
.guia {
    display: flex;
    flex-direction: column;
    gap: .9rem;
    padding: 1.1rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
    transition: border-color .2s, box-shadow .2s, transform .2s;
}

.guia:hover {
    border-color: color-mix(in srgb, var(--color-primary) 60%, transparent);
    box-shadow: 0 8px 28px color-mix(in srgb, var(--color-primary) 18%, transparent);
    transform: translateY(-2px);
}

.cabecera {
    display: flex;
    align-items: center;
    gap: .9rem;
}

.icono {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    flex-shrink: 0;
    border-radius: 14px;
    background: linear-gradient(145deg, color-mix(in srgb, var(--color-primary) 35%, var(--color-card)), color-mix(in srgb, var(--color-primary) 10%, var(--color-surface)));
    box-shadow: 0 6px 18px color-mix(in srgb, var(--color-primary) 30%, transparent);
    color: var(--color-primary-light);
}

.titulo {
    min-width: 0;
}

h3 {
    overflow: hidden;
    font-size: 1.05rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.titulo p {
    display: flex;
    flex-wrap: wrap;
    gap: .3rem .9rem;
    margin-top: .2rem;
}

.dato {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    color: var(--color-text-secondary);
    font-size: .78rem;
}

.temas {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    list-style: none;
}

.temas li {
    overflow: hidden;
    max-width: 100%;
    padding: .2rem .65rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--color-primary) 12%, var(--color-card));
    color: var(--color-text-secondary);
    font-size: .72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.temas .mas {
    color: var(--color-primary-light);
    font-weight: 700;
}

.btn {
    align-self: flex-start;
    margin-top: auto;
}
</style>
