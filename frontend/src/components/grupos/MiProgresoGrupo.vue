<template>
    <div class="progreso">
        <p v-if="resumen.total === 0" class="vacio">
            Aún no hay mini proyectos en las semanas liberadas, así que no hay avance que mostrar.
        </p>

        <template v-else>
            <!-- Solo el avance propio: el backend no manda (ni el aprendiz debe ver) el de sus compañeros -->
            <section class="promedio">
                <h3>Puntaje promedio</h3>
                <p class="cifra">
                    <template v-if="promedio != null">{{ promedio }}<small>/100</small></template>
                    <template v-else>—</template>
                </p>
                <p class="nota">
                    {{ promedio != null
                        ? (resumen.evaluados === 1 ? 'Puntaje de tu único proyecto evaluado.' : `Promedio de tus ${resumen.evaluados} proyectos evaluados.`)
                        : 'Aparecerá cuando evalúes tu primer mini proyecto.' }}
                    Es orientativo: te ayuda a ver cómo vas, no es una nota.
                </p>
            </section>

            <!-- El mismo resumen de la lista de mini proyectos, en su versión de Modo Grupo (sin XP) -->
            <ResumenProgreso :resumen="resumen" :semanas="semanas" :varias-guias="bloques.length > 1" />
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import ResumenProgreso from '@/components/miniproyectos/ResumenProgreso.vue'
import { pestanaDe, contarPorEstado, puntajePromedio } from '@/utils/estadoEntrega'

// Todo se CALCULA de los mismos bloques que pintan la pestaña Proyectos: así los números no se contradicen
const props = defineProps({ bloques: { type: Array, required: true } })

const proyectos = computed(() => props.bloques.flatMap((b) => b.semanas.flatMap((s) => s.proyectos)))
const resumen = computed(() => contarPorEstado(proyectos.value))
const promedio = computed(() => puntajePromedio(proyectos.value))

// Una fila por semana liberada con proyectos: evaluados sobre el total (en Modo Grupo no hay XP)
const semanas = computed(() =>
    props.bloques.flatMap((b) =>
        b.semanas
            .filter((s) => s.disponible && s.proyectos.length > 0)
            .map((s) => ({
                guiaId: b.guia.id,
                guiaNombre: b.guia.nombre,
                numero_semana: s.numero_semana,
                total: s.proyectos.length,
                evaluados: s.proyectos.filter((p) => pestanaDe(p) === 'evaluado').length
            }))
    )
)
</script>

<style scoped>
.progreso {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vacio {
    color: var(--color-text-secondary);
}

.promedio {
    padding: 1rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
}

h3 {
    font-size: .9rem;
}

/* Color neutro (no verde/rojo): el puntaje es orientativo, no "aprobado" ni "reprobado" */
.cifra {
    margin: .3rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.cifra small {
    color: var(--color-text-secondary);
    font-size: 1rem;
    font-weight: 400;
}

.nota {
    color: var(--color-text-secondary);
    font-size: .82rem;
}
</style>
