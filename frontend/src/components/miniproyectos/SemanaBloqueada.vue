<template>
    <article class="bloqueada" aria-label="Semana no disponible">
        <span class="candado" aria-hidden="true"><Lock :size="20" /></span>
        <div class="texto">
            <h3>Semana {{ semana.numero_semana }}<template v-if="semana.titulo">: {{ semana.titulo }}</template></h3>
            <!-- Sin contenido: el backend lo responde 403 mientras la semana no esté liberada -->
            <p>{{ motivo }}</p>
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue'
import { Lock } from 'lucide-vue-next'

const props = defineProps({
    semana: { type: Object, required: true },
    // Semana inmediatamente anterior (solo Modo Personal): de ahí sale el XP que se necesita
    anterior: { type: Object, default: null },
    esPersonal: { type: Boolean, default: false }
})

// Los números llegan del backend tal cual; aquí solo se muestran, nunca se calculan
const motivo = computed(() => {
    if (!props.esPersonal) return 'Próximamente: tu instructor aún no libera esta semana.'
    const a = props.anterior
    if (a?.xp_requerido != null) {
        return `Se abre al llegar a ${a.xp_requerido} XP en la semana ${a.numero_semana} (llevas ${a.xp_obtenido ?? 0}).`
    }
    return 'Se abre al completar la semana anterior.'
})
</script>

<style scoped>
.bloqueada {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: .9rem 1rem;
    border: 1px dashed var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
    opacity: .85;
}

.candado {
    display: grid;
    place-items: center;
    width: 40px;
    height: 40px;
    flex-shrink: 0;
    border-radius: 12px;
    background: var(--color-card);
    color: var(--color-text-secondary);
}

.texto {
    min-width: 0;
}

h3 {
    font-size: .95rem;
    color: var(--color-text-secondary);
    overflow-wrap: anywhere;
}

p {
    margin-top: .15rem;
    color: var(--color-text-secondary);
    font-size: .8rem;
}
</style>
