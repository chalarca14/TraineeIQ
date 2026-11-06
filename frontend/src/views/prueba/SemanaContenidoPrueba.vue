<!-- PRUEBA-BACKEND: borrar cuando exista la vista definitiva -->
<template>
    <section class="semana">
        <h2>Semana {{ semana.numero_semana }}: {{ semana.titulo }}</h2>
        <p class="resumen">{{ semana.resumen }}</p>

        <p v-if="!semana.disponible" class="aviso">
            Próximamente. Tu instructor liberará esta semana cuando el grupo llegue a ella.
        </p>

        <p v-else-if="['pendiente', 'generando'].includes(semana.estado_generacion)" class="aviso">
            Estamos preparando esta semana...
        </p>

        <div v-else-if="semana.estado_generacion === 'error'" class="aviso error">
            <template v-if="esDueno">
                <p>Esta semana no se pudo preparar.</p>
                <button type="button" class="btn" @click="$emit('reintentar')">Reintentar</button>
            </template>
            <p v-else>Esta semana no se pudo preparar, avisa a tu instructor.</p>
        </div>

        <p v-else-if="cargando" class="aviso">Cargando contenido...</p>

        <template v-else-if="contenido">
            <h3>Recomendaciones</h3>
            <article v-for="r in contenido.recomendaciones" :key="r.id" class="item">
                <div class="item-titulo">
                    <strong>{{ r.contenido.titulo }}</strong>
                    <EtiquetaNivel :valor="r.nivel" />
                </div>
                <p>{{ r.contenido.descripcion }}</p>
            </article>

            <h3>Mini proyectos</h3>
            <article v-for="m in contenido.mini_proyectos" :key="m.id" class="item">
                <div class="item-titulo">
                    <strong>{{ m.titulo }}</strong>
                    <EtiquetaNivel :valor="m.dificultad" />
                </div>
                <p>{{ m.descripcion }}</p>
            </article>
        </template>
    </section>
</template>

<script setup>
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'

defineProps({
    semana: { type: Object, required: true },
    contenido: { type: Object, default: null },
    cargando: { type: Boolean, default: false },
    esDueno: { type: Boolean, default: false }
})

defineEmits(['reintentar'])
</script>

<style scoped>
.semana {
    display: grid;
    gap: .75rem;
}

h2 {
    font-size: 1.2rem;
}

h3 {
    margin-top: .75rem;
    font-size: 1rem;
}

.resumen,
.aviso,
.item p {
    color: var(--color-text-secondary);
}

.aviso.error {
    display: grid;
    gap: .75rem;
    justify-items: start;
    color: var(--color-danger);
}

.aviso.error p {
    color: inherit;
}

.item {
    padding: 1rem 1.25rem;
    border-radius: 14px;
    background: var(--color-card);
}

.item-titulo {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: .3rem;
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
</style>
