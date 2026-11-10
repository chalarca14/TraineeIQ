<template>
    <div class="descripcion">

        <section class="bloque">
            <h3><Medalla :icono="Info" color="info" /> Información del proyecto</h3>
            <!-- La descripción vive solo aquí (el banner ya no la repite) -->
            <p class="texto"><TextoConCodigo :texto="proyecto.descripcion" /></p>
            <dl>
                <div><dt>Nivel</dt><dd><EtiquetaNivel :valor="proyecto.dificultad" /></dd></div>
            </dl>
        </section>

        <section v-if="pasos.length" class="bloque">
            <h3><Medalla :icono="Code" /> Instrucciones</h3>
            <ol>
                <li v-for="(paso, i) in pasos" :key="i"><TextoConCodigo :texto="paso" /></li>
            </ol>
        </section>

        <!-- Requisitos, buenas prácticas y criterios los genera la IA junto con el proyecto y llegan en
             GET /mini-proyectos/{id}. Cada bloque se muestra solo si trae algo. Los requisitos son
             casillas de solo lectura: quien verifica es la IA al evaluar. Todo se pinta como texto. -->
        <section v-if="requisitos.length" class="bloque">
            <h3><Medalla :icono="ListChecks" color="success" /> Requisitos del proyecto</h3>
            <ListaRequisitos :requisitos="requisitos" />
        </section>

        <section v-if="practicas.length" class="bloque">
            <h3><Medalla :icono="ShieldCheck" color="info" /> Buenas prácticas</h3>
            <ul class="puntos">
                <li v-for="(p, i) in practicas" :key="i"><Check :size="15" :stroke-width="3" /> <TextoConCodigo :texto="p" /></li>
            </ul>
        </section>

        <section v-if="criterios.length" class="bloque">
            <h3><Medalla :icono="Scale" color="warning" /> Cómo se evalúa</h3>
            <p class="texto">La IA puntúa estos criterios; cada uno aporta su peso al puntaje (orientativo).</p>
            <ul class="criterios">
                <li v-for="(c, i) in criterios" :key="i">
                    <span>{{ c.criterio }}</span><b>{{ c.peso }} %</b>
                </li>
            </ul>
        </section>

        <section class="entregar">
            <div class="columnas">
                <div class="vscode">
                    <h3><Medalla :icono="Rocket" /> Desarrolla tu proyecto</h3>
                    <p class="texto">Puedes trabajar en tu computador con Visual Studio Code.</p>
                    <!-- Un enlace web NO puede crear el proyecto en el computador del aprendiz: el navegador
                         no tiene permiso para escribir carpetas ni archivos. Lo máximo que puede hacer es
                         pedirle al sistema que abra VS Code con el protocolo vscode://, y solo funciona si
                         ya está instalado. Por eso va el texto de apoyo con el enlace oficial de descarga. -->
                    <a class="btn btn-info btn-vscode" href="vscode://">
                        <Code :size="18" /> Abrir en Visual Studio Code <ExternalLink :size="15" />
                    </a>
                    <p class="nota">
                        Si no se abre, <a href="https://code.visualstudio.com/download" target="_blank"
                            rel="noopener noreferrer">descarga VS Code</a>.
                    </p>
                </div>

                <!-- Guardar el repositorio y pedir la evaluación (dos pasos, ver EntregaProyecto) -->
                <EntregaProyecto :entrega="entrega" :intentos-restantes="intentosRestantes" :guardando="guardando"
                    :evaluando="evaluando" :error-guardar="errorGuardar" :error-evaluar="errorEvaluar"
                    @guardar="$emit('guardar', $event)" @evaluar="$emit('evaluar')" />
            </div>
            <p class="aviso"><Info :size="15" /> Asegúrate de que tu repositorio sea público y contenga todo el código del proyecto.</p>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Info, Code, ExternalLink, Rocket, ListChecks, ShieldCheck, Scale, Check } from 'lucide-vue-next'
import Medalla from '@/components/ui/Medalla.vue'
import EtiquetaNivel from '@/components/personal/EtiquetaNivel.vue'
import EntregaProyecto from '@/components/miniproyectos/EntregaProyecto.vue'
import ListaRequisitos from '@/components/miniproyectos/ListaRequisitos.vue'
import TextoConCodigo from '@/components/miniproyectos/TextoConCodigo.vue'

const props = defineProps({
    // GET /mini-proyectos/{id}
    proyecto: { type: Object, required: true },
    // Estado de la entrega (viene de useEntrega) para el bloque de guardar y evaluar
    entrega: { type: Object, default: null },
    intentosRestantes: { type: Number, default: null },
    guardando: { type: Boolean, default: false },
    evaluando: { type: Boolean, default: false },
    errorGuardar: { type: String, default: '' },
    errorEvaluar: { type: String, default: '' }
})
defineEmits(['guardar', 'evaluar'])

// Si el backend no manda alguna lista, su bloque simplemente no se muestra
const requisitos = computed(() => props.proyecto.requisitos_funcionales ?? [])
const practicas = computed(() => props.proyecto.buenas_practicas ?? [])
const criterios = computed(() => props.proyecto.criterios_evaluacion ?? [])

// "contenido" llega como texto: "1. Crea los botones.\n2. Guarda los números..."
// Lo partimos por líneas y quitamos el "1." o "-" del inicio, porque la lista ya numera.
const pasos = computed(() =>
    (props.proyecto.contenido ?? '')
        .split('\n')
        .map((linea) => linea.replace(/^\s*(\d+[.)]|[-*•])\s*/, '').trim())
        .filter(Boolean)
)
</script>

<style scoped>
.descripcion {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.bloque,
.entregar {
    padding: 1.1rem 1.2rem;
    border: 1px solid var(--color-border);
    border-radius: 16px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

h3 {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: .6rem;
    font-size: 1.02rem;
}

.texto,
.nota {
    color: var(--color-text-secondary);
    font-size: .9rem;
}

dl {
    display: flex;
    flex-wrap: wrap;
    gap: .6rem 2rem;
    margin-top: .8rem;
    padding-top: .8rem;
    border-top: 1px solid var(--color-border);
}

dl div {
    display: flex;
    align-items: center;
    gap: .6rem;
}

dt {
    color: var(--color-text-secondary);
    font-size: .85rem;
}

/* Buenas prácticas y criterios: listas simples de solo lectura */
.puntos,
.criterios {
    display: flex;
    flex-direction: column;
    gap: .55rem;
    list-style: none;
}

.puntos li {
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    font-size: .9rem;
}

.puntos svg {
    flex-shrink: 0;
    margin-top: .2rem;
    color: var(--color-success);
}

.criterios {
    margin-top: .6rem;
}

.criterios li {
    display: flex;
    justify-content: space-between;
    gap: .8rem;
    font-size: .9rem;
}

.criterios b {
    flex-shrink: 0;
    color: var(--color-primary-light);
}

/* Instrucciones: números en círculos morados, como en el boceto */
ol {
    display: flex;
    flex-direction: column;
    gap: .7rem;
    list-style: none;
    counter-reset: paso;
}

ol li {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    font-size: .92rem;
    counter-increment: paso;
}

ol li::before {
    content: counter(paso);
    display: grid;
    place-items: center;
    width: 24px;
    height: 24px;
    flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(145deg, var(--color-primary), var(--color-primary-light));
    color: var(--color-text);
    font-size: .75rem;
    font-weight: 700;
}

/* Bloque de entrega: dos columnas, con borde y resplandor morado para que destaque */
.entregar {
    border-color: color-mix(in srgb, var(--color-primary) 55%, transparent);
    box-shadow: 0 0 32px color-mix(in srgb, var(--color-primary) 14%, transparent);
}

.columnas {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.35fr);
    gap: 1.5rem;
}

.vscode {
    padding-right: 1.5rem;
    border-right: 1px solid var(--color-border);
}

/* El aspecto del botón viene de las clases globales .btn .btn-info; aquí solo su separación */
.btn-vscode {
    margin: .8rem 0 .5rem;
}

.nota a {
    color: var(--color-primary-light);
}

.aviso {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: 1rem;
    padding-top: .8rem;
    border-top: 1px solid var(--color-border);
    color: var(--color-text-secondary);
    font-size: .8rem;
}

@media (max-width: 760px) {
    .columnas {
        grid-template-columns: minmax(0, 1fr);
    }

    .vscode {
        padding: 0 0 1.2rem;
        border-right: none;
        border-bottom: 1px solid var(--color-border);
    }

}
</style>
