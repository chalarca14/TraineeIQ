import { ref, computed, toValue } from 'vue'
import { listarGuiasDeGrupo, getSemanas } from '@/services/guiaService'
import { getMiniProyectosGuia } from '@/services/miniProyectoService'
import { getTodasEntregasGrupo } from '@/services/grupoService'

/**
 * Datos de la pestaña "Semanas y proyectos" del instructor (Tarea I-4), ya cruzados por semana.
 * grupo: ref o función con el grupo de GET /grupos/{id} (semana_actual, ultima_semana, estudiantes).
 *
 * De dónde sale cada cosa:
 * - guías del grupo: GET /guias?grupo_id=
 * - título de cada semana en cada guía: GET /guias/{id}/semanas
 * - mini proyectos: GET /guias/{id}/mini-proyectos. OJO: al instructor el backend le manda los de TODAS
 *   las semanas; por decisión de Santi no ve los de semanas sin liberar, así que se filtran aquí.
 * - entregas: GET /grupos/{id}/entregas, todas las páginas. Solo llegan los que guardaron un repositorio.
 */
export function useSemanasGrupo(grupo) {
    const guias = ref([])
    const entregas = ref([])
    const cargando = ref(true)
    const error = ref('')

    async function cargar() {
        const g = toValue(grupo)
        cargando.value = true
        error.value = ''
        try {
            const lista = await listarGuiasDeGrupo(g.id)
            // Por guía: sus semanas y sus proyectos. Las entregas, a la vez (no dependen de las guías)
            const [detalles, todas] = await Promise.all([
                Promise.all(lista.map(async (guia) => {
                    const [semanas, proyectos] = await Promise.all([getSemanas(guia.id), getMiniProyectosGuia(guia.id)])
                    return { guia, semanas, proyectos }
                })),
                getTodasEntregasGrupo(g.id)
            ])
            guias.value = detalles
            entregas.value = todas
        } catch {
            error.value = 'No se pudieron cargar las semanas del grupo. Intenta de nuevo.'
        } finally {
            cargando.value = false
        }
    }

    // Entregas de cada mini proyecto: { idProyecto: [entregas] }
    const entregasPorProyecto = computed(() => {
        const mapa = {}
        for (const e of entregas.value) {
            const id = e.mini_proyecto?.id
            if (id != null) (mapa[id] ??= []).push(e)
        }
        return mapa
    })

    // Una fila por semana del grupo: de 1 a la última semana de sus guías
    const semanas = computed(() => {
        const g = toValue(grupo)
        const actual = g.semana_actual ?? 1
        const total = Math.max(g.ultima_semana ?? 0, ...guias.value.map((d) => d.semanas.length), 0)

        return Array.from({ length: total }, (_, i) => {
            const numero = i + 1
            const liberada = numero <= actual
            const porGuia = guias.value.map(({ guia, semanas: lista, proyectos }) => ({
                guia,
                // La guía puede tener menos semanas que el grupo: entonces no aparece en esta fila
                existe: lista.some((s) => s.numero_semana === numero),
                titulo: lista.find((s) => s.numero_semana === numero)?.titulo ?? '',
                proyectos: liberada
                    ? proyectos.filter((p) => p.semana?.numero_semana === numero)
                        .sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0))
                    : []
            })).filter((x) => x.existe)

            // Estudiantes distintos que entregaron al menos un proyecto de esta semana
            const quienes = new Set(entregas.value
                .filter((e) => e.mini_proyecto?.semana?.numero_semana === numero)
                .map((e) => e.estudiante?.id))

            return {
                numero,
                estado: numero < actual ? 'liberada' : numero === actual ? 'actual' : 'bloqueada',
                // El título de la semana en cada guía, sin repetir: "Funciones · Estructuras de control"
                titulo: [...new Set(porGuia.map((g) => g.titulo).filter(Boolean))].join(' · '),
                porGuia,
                entregaron: quienes.size
            }
        })
    })

    return { guias, entregas, cargando, error, cargar, semanas, entregasPorProyecto }
}
