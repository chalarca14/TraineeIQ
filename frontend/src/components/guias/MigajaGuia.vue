<template>
    <!-- Modo Grupo: "Mis grupos › grupo › Guías › guía". Modo Personal: "Mis guías › guía" -->
    <nav class="migaja" aria-label="Migaja de pan">
        <template v-if="esGrupo">
            <RouterLink to="/estudiante/grupos">Mis grupos</RouterLink>
            <ChevronRight :size="14" aria-hidden="true" />
            <span v-if="grupo">{{ grupo.nombre }}</span>
            <ChevronRight v-if="grupo" :size="14" aria-hidden="true" />
            <span>Guías</span>
        </template>
        <RouterLink v-else to="/estudiante/guias">Mis guías</RouterLink>
        <ChevronRight :size="14" aria-hidden="true" />
        <span aria-current="page">{{ nombre }}</span>
    </nav>
</template>

<script setup>
import { ChevronRight } from 'lucide-vue-next'

// El nombre del grupo puede faltar (si falló su carga): la migaja sigue funcionando sin él
defineProps({
    esGrupo: { type: Boolean, default: false },
    grupo: { type: Object, default: null },
    nombre: { type: String, required: true }
})
</script>

<style scoped>
.migaja {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    color: var(--color-text-secondary);
    font-size: .85rem;
}

.migaja a {
    color: var(--color-primary-light);
}

.migaja [aria-current='page'] {
    color: var(--color-text);
    overflow-wrap: anywhere;
}
</style>
