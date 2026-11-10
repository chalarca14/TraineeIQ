<template>
    <div class="contenido">

        <section v-if="temas.length" class="seccion">
            <h4><Medalla :icono="BookOpen" :size="26" /> Temas</h4>
            <div class="lista">
                <TemaDesplegable v-for="t in temas" :key="t.id" :tema="t" />
            </div>
        </section>

        <section v-if="recomendaciones.length" class="seccion">
            <h4><Medalla :icono="Sparkles" :size="26" /> Recomendaciones de la IA</h4>
            <div class="tarjetas">
                <article v-for="r in recomendaciones" :key="r.id" class="tarjeta">
                    <div class="tarjeta-top">
                        <strong>{{ r.leido.titulo }}</strong>
                        <EtiquetaNivel :valor="r.nivel" />
                    </div>
                    <p><TextoConCodigo :texto="r.leido.descripcion" /></p>
                    <!-- Campos nuevos: las semanas generadas antes no los traen, por eso cada uno lleva v-if.
                         Todo se pinta con {{ }} (texto), nunca con v-html, porque viene de la IA. -->
                    <p v-if="r.leido.tema || r.leido.tema_semana" class="relacion">
                        <template v-if="r.leido.tema"><strong>Estudia:</strong> {{ r.leido.tema }}</template>
                        <template v-if="r.leido.tema && r.leido.tema_semana"> · </template>
                        <template v-if="r.leido.tema_semana"><strong>Te ayuda con:</strong> {{ r.leido.tema_semana }}</template>
                    </p>
                    <p v-if="r.leido.por_que" class="por-que">{{ r.leido.por_que }}</p>
                </article>
            </div>
        </section>

        <section v-if="miniProyectos.length" class="seccion">
            <h4><Medalla :icono="Rocket" color="info" :size="26" /> Mini proyectos</h4>
            <div class="tarjetas">
                <article v-for="m in miniProyectos" :key="m.id" class="tarjeta">
                    <div class="tarjeta-top">
                        <strong>{{ m.titulo }}</strong>
                        <EtiquetaNivel :valor="m.dificultad" />
                    </div>
                    <p><TextoConCodigo :texto="m.descripcion" /></p>
                    <RouterLink class="btn btn-secundario btn-sm" :to="{ name: 'MiniProyectoDetalle', params: { id: m.id } }">
                        Ver proyecto <ArrowRight :size="15" />
                    </RouterLink>
                </article>
            </div>
        </section>

        <p v-if="vacio" class="vacio">Esta semana todavía no trae contenido.</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { BookOpen, Sparkles, Rocket, ArrowRight } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'
import TemaDesplegable from '@/components/guias/TemaDesplegable.vue'
import { leerContenido } from '@/utils/recomendaciones'

// Contenido de UNA semana ya lista: GET /guias/{id}/semanas/{n} con sus temas, recomendaciones y
// mini proyectos. Todo el texto viene de la IA y se pinta como texto plano (nunca v-html).
const props = defineProps({ semana: { type: Object, required: true } })

const ordenar = (lista) => [...(lista ?? [])].sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0))

const temas = computed(() => ordenar(props.semana.temas))
// El contenido de cada recomendación puede venir como texto con JSON o como objeto: se normaliza aquí
const recomendaciones = computed(() =>
    ordenar(props.semana.recomendaciones).map((r) => ({ ...r, leido: leerContenido(r) }))
)
const miniProyectos = computed(() => ordenar(props.semana.mini_proyectos))
const vacio = computed(() => !temas.value.length && !recomendaciones.value.length && !miniProyectos.value.length)
</script>

<style scoped>
.contenido {
    display: flex;
    flex-direction: column;
    gap: 1.3rem;
}

h4 {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: .7rem;
    font-size: .98rem;
}

.lista {
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.tarjetas {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 240px), 1fr));
    gap: .8rem;
}

.tarjeta {
    display: flex;
    flex-direction: column;
    gap: .5rem;
    padding: .9rem;
    border: 1px solid var(--color-border);
    border-radius: 14px;
    background: var(--color-surface);
}

.tarjeta-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .6rem;
}

.tarjeta-top strong {
    min-width: 0;
    font-size: .92rem;
    overflow-wrap: anywhere;
}

.tarjeta p {
    flex: 1;
    color: var(--color-text-secondary);
    font-size: .85rem;
    overflow-wrap: anywhere;
}

/* La línea "Estudia / Te ayuda con" se distingue de la descripción sin usar colores nuevos */
.tarjeta .relacion {
    flex: none;
    color: var(--color-text);
    font-size: .8rem;
}

.tarjeta .por-que {
    flex: none;
    font-style: italic;
}

.tarjeta .btn {
    align-self: flex-start;
}

.vacio {
    color: var(--color-text-secondary);
}
</style>
