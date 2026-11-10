<template>
    <section class="entrega">
        <h3><UploadCloud :size="20" /> Entrega tu repositorio</h3>
        <p class="ayuda">
            Primero guarda el enlace de tu repositorio de GitHub y luego pide la evaluación con IA.
        </p>

        <!-- Paso 1: guardar el repositorio (PUT) -->
        <EntregaForm :repo-guardado="entrega?.repo_url ?? ''" :guardando="guardando"
            :deshabilitado="evaluando || enCursoAjeno" :error-externo="errorGuardar" @guardar="$emit('guardar', $event)" />

        <!-- Paso 2: evaluar (POST). Solo existe cuando ya hay un repositorio guardado -->
        <div v-if="entrega?.repo_url" class="evaluar">
            <p class="guardado">
                <CircleCheck :size="16" /> Repositorio guardado:
                <!-- Solo se enlaza si es un repositorio de GitHub: un valor raro del backend
                     (por ejemplo "javascript:...") se muestra como texto, nunca como enlace -->
                <a v-if="repoSeguro" :href="repoSeguro" target="_blank" rel="noopener noreferrer">{{ repoCorto }}</a>
                <span v-else>{{ entrega.repo_url }}</span>
            </p>

            <button type="button" class="btn btn-primario" :disabled="!puedeEvaluar" @click="$emit('evaluar')">
                <Sparkles :size="16" /> {{ evaluando ? 'Evaluando…' : 'Evaluar mi código' }}
            </button>

            <!-- El número lo calcula el backend (tope diario); aquí solo se muestra -->
            <p v-if="intentosRestantes !== null" class="nota">
                {{ intentosRestantes === 0
                    ? 'Ya usaste tus evaluaciones de hoy. Vuelve mañana.'
                    : `Te quedan ${intentosRestantes} ${intentosRestantes === 1 ? 'evaluación' : 'evaluaciones'} hoy.` }}
            </p>
            <p v-if="evaluando" class="nota" role="status">
                La IA está revisando tu repositorio. Puede tardar hasta 3 minutos: no cierres esta pestaña.
            </p>
            <p v-else-if="enCursoAjeno" class="nota" role="status">
                Tu entrega figura como "evaluando". Ve a la pestaña Evaluación y pulsa "Revisar de nuevo".
            </p>
            <p v-if="errorEvaluar" class="error" role="alert">{{ errorEvaluar }}</p>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue'
import { UploadCloud, CircleCheck, Sparkles } from 'lucide-vue-next'
import EntregaForm from '@/components/miniproyectos/EntregaForm.vue'
import { esRepoGithub } from '@/services/miniProyectoService'

const props = defineProps({
    entrega: { type: Object, default: null },
    intentosRestantes: { type: Number, default: null },
    guardando: { type: Boolean, default: false },
    evaluando: { type: Boolean, default: false },
    errorGuardar: { type: String, default: '' },
    errorEvaluar: { type: String, default: '' }
})
defineEmits(['guardar', 'evaluar'])

// El backend dice "evaluando" pero esta pantalla no está esperando (otra pestaña o se interrumpió)
const enCursoAjeno = computed(() => props.entrega?.estado === 'evaluando' && !props.evaluando)

const puedeEvaluar = computed(() =>
    Boolean(props.entrega?.repo_url) && !props.evaluando && !props.guardando && !enCursoAjeno.value &&
    props.intentosRestantes !== 0
)

const repoSeguro = computed(() => (esRepoGithub(props.entrega?.repo_url ?? '') ? props.entrega.repo_url : ''))
const repoCorto = computed(() => repoSeguro.value.replace('https://github.com/', ''))
</script>

<style scoped>
h3 {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .2rem;
    font-size: 1rem;
}

h3 svg {
    color: var(--color-primary-light);
}

.ayuda,
.nota {
    color: var(--color-text-secondary);
    font-size: .82rem;
}

.evaluar {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .55rem;
    margin-top: .9rem;
    padding-top: .9rem;
    border-top: 1px solid var(--color-border);
}

.guardado {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    font-size: .88rem;
    overflow-wrap: anywhere;
}

.guardado svg {
    color: var(--color-success);
}

.guardado a {
    color: var(--color-primary-light);
}

.error {
    color: var(--color-danger);
    font-size: .85rem;
}

@media (max-width: 520px) {
    .btn {
        align-self: stretch;
        justify-content: center;
    }
}
</style>
