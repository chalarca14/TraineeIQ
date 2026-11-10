<template>
    <!-- Cabecera del detalle: reutiliza el banner compartido y solo aporta icono, textos y etiquetas -->
    <EncabezadoPagina :titulo="proyecto.titulo" :icono="visual.icono" :color="visual.color">
        <template #etiquetas>
            <EtiquetaNivel :valor="proyecto.dificultad" />
            <TecnologiaChip v-for="t in tecnologias" :key="t.etiqueta" :tecnologia="t" />
            <span v-if="etiquetaSemana" class="tag" :title="etiquetaSemana">{{ etiquetaSemana }}</span>
        </template>
    </EncabezadoPagina>
</template>

<script setup>
import { computed } from 'vue'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import TecnologiaChip from '@/components/miniproyectos/TecnologiaChip.vue'
import { iconoDelProyecto, tecnologiasDelProyecto } from '@/utils/iconosProyecto'

const props = defineProps({ proyecto: { type: Object, required: true } })

const visual = computed(() => iconoDelProyecto(props.proyecto))
const tecnologias = computed(() => tecnologiasDelProyecto(props.proyecto))
// GET /mini-proyectos/{id} trae la semana: "Semana 2: Funciones"
const etiquetaSemana = computed(() => {
    const s = props.proyecto.semana
    if (!s) return ''
    return s.titulo ? `Semana ${s.numero_semana}: ${s.titulo}` : `Semana ${s.numero_semana}`
})
</script>

<style scoped>
.tag {
    overflow: hidden;
    max-width: 100%;
    padding: .2rem .7rem;
    border-radius: 999px;
    background: var(--color-card);
    color: var(--color-text-secondary);
    font-size: .72rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
