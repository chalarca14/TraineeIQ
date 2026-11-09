<template>
    <article class="proyecto">
        <div class="cabecera">
            <EtiquetaNivel :valor="proyecto.dificultad" />
            <!-- Error: etiqueta roja, pero el proyecto sigue en "Por entregar" (el aprendiz puede reintentar) -->
            <span v-if="conError" class="etiqueta-error"><CircleAlert :size="13" /> Error</span>
            <span v-else-if="estado" class="etiqueta-estado">{{ estado }}</span>
        </div>

        <!-- La guía va en su propia línea, encima del título: así cabe el texto completo -->
        <p v-if="proyecto.guiaNombre" class="tema" :title="proyecto.guiaNombre">{{ proyecto.guiaNombre }}</p>

        <div class="titulo-fila">
            <span class="icono" :style="{ '--c': visual.color }" aria-hidden="true">
                <component :is="visual.icono" :size="20" />
            </span>
            <h3>{{ proyecto.titulo }}</h3>
        </div>
        <p class="descripcion"><TextoConCodigo :texto="proyecto.descripcion" /></p>

        <div v-if="tecnologias.length" class="chips">
            <TecnologiaChip v-for="t in tecnologias" :key="t.etiqueta" :tecnologia="t" />
        </div>

        <!-- Puntaje orientativo del backend, en color neutro: sin colores por rango (no hay "bueno" ni "malo") -->
        <ul v-if="evaluado" class="datos">
            <li><strong>{{ entrega.puntaje }}/100</strong> <span>puntaje orientativo</span></li>
            <li v-if="fecha">Evaluado: {{ fecha }}</li>
            <!-- En Modo Personal, el mejor puntaje es el XP. Se muestra tal como llega, nunca se calcula -->
            <li v-if="esPersonal && entrega.mejor_puntaje != null">XP: {{ entrega.mejor_puntaje }}</li>
        </ul>

        <button type="button" class="btn btn-primario btn-sm accion" @click="$emit('abrir', proyecto)">
            {{ textoBoton(proyecto) }} <ArrowRight :size="15" />
        </button>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowRight, CircleAlert } from 'lucide-vue-next'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import TecnologiaChip from '@/components/miniproyectos/TecnologiaChip.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'
import { iconoDelProyecto, tecnologiasDelProyecto } from '@/utils/iconosProyecto'
import { formatearFecha } from '@/utils/fechas'
import { hayError, etiquetaDeEstado, textoBoton } from '@/utils/estadoEntrega'

const props = defineProps({
    // Proyecto de GET /guias/{id}/mini-proyectos, con su `entrega` (o null)
    proyecto: { type: Object, required: true },
    // El XP solo existe en Modo Personal
    esPersonal: { type: Boolean, default: false }
})
defineEmits(['abrir'])

const entrega = computed(() => props.proyecto.entrega)
const evaluado = computed(() => entrega.value?.estado === 'evaluado')
const conError = computed(() => hayError(props.proyecto))
const estado = computed(() => etiquetaDeEstado(props.proyecto))
const fecha = computed(() => formatearFecha(entrega.value?.evaluado_en))

const visual = computed(() => iconoDelProyecto(props.proyecto))
// Máximo 2 en la tarjeta para que no crezca demasiado; el detalle muestra hasta 3
const tecnologias = computed(() => tecnologiasDelProyecto(props.proyecto).slice(0, 2))
</script>

<style scoped>
.proyecto {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
    transition: border-color .2s, box-shadow .2s, transform .2s;
}

.proyecto:hover {
    border-color: color-mix(in srgb, var(--color-primary) 60%, transparent);
    box-shadow: 0 8px 24px color-mix(in srgb, var(--color-primary) 18%, transparent);
    transform: translateY(-2px);
}

.cabecera {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
}

.etiqueta-error,
.etiqueta-estado {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .15rem .6rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 700;
}

.etiqueta-error {
    background: color-mix(in srgb, var(--color-danger) 16%, transparent);
    color: var(--color-danger);
}

.etiqueta-estado {
    background: var(--color-card);
    color: var(--color-text-secondary);
}

.tema {
    overflow: hidden;
    color: var(--color-text-secondary);
    font-size: .75rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.titulo-fila {
    display: flex;
    align-items: center;
    gap: .6rem;
}

/* Cuadro redondeado: fondo = el color de la tecnología al 16 %, icono = el color puro */
.icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 10px;
    color: var(--c);
    background: color-mix(in srgb, var(--c) 16%, transparent);
}

h3 {
    font-size: 1rem;
}

.descripcion {
    flex: 1;
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.datos {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    list-style: none;
    color: var(--color-text-secondary);
    font-size: .78rem;
}

.datos strong {
    color: var(--color-text);
    font-size: 1rem;
}

.accion {
    align-self: flex-end;
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
}
</style>
