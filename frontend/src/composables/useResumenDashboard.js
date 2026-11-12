import { ref, computed, watch } from 'vue'
import { useModoStore } from '@/stores/modo'
import { useMiniProyectosSemanas } from '@/composables/useMiniProyectosSemanas'
import { listarMisGrupos } from '@/services/grupoService'
import { getXp } from '@/services/usuarioService'
import { pestanaDe, puntajePromedio } from '@/utils/estadoEntrega'

// Datos del dashboard del aprendiz, según el modo.
//
// Por qué sale de useMiniProyectosSemanas: la lista de mini proyectos y el dashboard deben contar
// lo MISMO (CLAUDE.md / PLAN Tarea 10). Si cada pantalla pidiera y contara por su lado, un proyecto
// podría salir "evaluado" en una y "por entregar" en la otra.
//
// Aquí no se calcula XP ni se decide qué semana se abre: se lee `disponible`, `xp_total` y el
// estado de cada entrega tal como llegan del backend. Solo se cuentan y se ordenan.
export function useResumenDashboard() {
    const modoStore = useModoStore()
    const mini = useMiniProyectosSemanas()

    const esPersonal = computed(() => modoStore.esModoPersonal)

    // Modo Grupo: lista de grupos (semana_actual, nombre) y cuál está elegido en el selector
    const grupos = ref([])
    const cargandoGrupos = ref(false)
    const errorGrupos = ref('')
    const grupoId = ref(null)

    // Modo Personal: XP total. Si falla, solo esa tarjeta lo dice; el resto del dashboard sigue
    const xp = ref(null)
    const errorXp = ref(false)

    async function cargarGrupos() {
        cargandoGrupos.value = true
        errorGrupos.value = ''
        try {
            grupos.value = await listarMisGrupos()
            // Por defecto el primero; si el elegido sigue existiendo, se respeta
            if (!grupos.value.some((g) => g.id === grupoId.value)) {
                grupoId.value = grupos.value[0]?.id ?? null
            }
        } catch {
            errorGrupos.value = 'No se pudieron cargar tus grupos. Intenta de nuevo.'
        } finally {
            cargandoGrupos.value = false
        }
    }

    async function cargarXp() {
        errorXp.value = false
        try {
            xp.value = await getXp()
        } catch {
            errorXp.value = true
        }
    }

    // Lo que depende del modo: XP en Personal, grupos en Grupo. Nunca los dos
    const cargarDelModo = () => (esPersonal.value ? cargarXp() : cargarGrupos())

    async function cargar() {
        await Promise.all([mini.cargar(), cargarDelModo()])
    }

    // useMiniProyectosSemanas ya recarga sus guías al cambiar de modo; aquí solo falta lo propio del modo
    watch(() => modoStore.modoActivo, cargarDelModo)

    const grupo = computed(() => grupos.value.find((g) => g.id === grupoId.value) ?? null)

    // Guías que cuentan: en Personal todas las del modo; en Grupo, solo las del grupo elegido
    const bloques = computed(() =>
        esPersonal.value
            ? mini.bloques.value
            : mini.bloques.value.filter((b) => b.guia.grupo_id === grupoId.value)
    )

    // Los proyectos solo llegan de semanas abiertas: el backend no manda los de las bloqueadas
    const proyectos = computed(() => bloques.value.flatMap((b) => b.semanas.flatMap((s) => s.proyectos)))
    const evaluados = computed(() => proyectos.value.filter((p) => pestanaDe(p) === 'evaluado'))

    // Promedio de la última evaluación de cada proyecto (puntaje de 0 a 100, orientativo). null = "—".
    // La cuenta está en utils/estadoEntrega.js para que el detalle del grupo diga lo mismo
    const promedio = computed(() => puntajePromedio(proyectos.value))

    // Primer proyecto sin evaluar de una semana abierta, en el orden guía → semana → orden
    const siguiente = computed(() => {
        for (const bloque of bloques.value) {
            for (const semana of bloque.semanas) {
                if (!semana.disponible) continue
                const proyecto = [...semana.proyectos]
                    .sort((a, b) => (a.orden ?? 0) - (b.orden ?? 0))
                    .find((p) => pestanaDe(p) !== 'evaluado')
                if (proyecto) return { proyecto, semana, guia: bloque.guia }
            }
        }
        return null
    })

    // Guía actual: la del siguiente proyecto; si todo está evaluado, la primera que cargó bien
    const guiaActual = computed(() => {
        const id = siguiente.value?.guia.id
        return bloques.value.find((b) => b.guia.id === id) ?? bloques.value.find((b) => !b.fallo) ?? null
    })

    // Semana más avanzada que el backend dejó abierta en la guía actual, y cuántas tiene
    const semanaActual = computed(() => {
        const semanas = guiaActual.value?.semanas ?? []
        const abiertas = semanas.filter((s) => s.disponible).map((s) => s.numero_semana)
        return { numero: abiertas.length ? Math.max(...abiertas) : null, total: semanas.length }
    })

    const cargando = computed(() => mini.cargando.value || (!esPersonal.value && cargandoGrupos.value))
    // El composable de la lista avisa con su propio texto ("tus mini proyectos"); aquí se habla del resumen
    const error = computed(() =>
        (mini.error.value ? 'No pudimos cargar tu resumen. Intenta de nuevo.' : '') ||
        (!esPersonal.value ? errorGrupos.value : '')
    )

    // Qué vacío mostrar (o '' si hay algo que enseñar). Grupo sin semanas = ninguna con disponible,
    // lo que incluye un grupo sin guías: semana_actual vale 1 por defecto y no basta para decidirlo
    const vacio = computed(() => {
        if (cargando.value || error.value) return ''
        if (esPersonal.value) return mini.bloques.value.length ? '' : 'sin-guias'
        if (!grupos.value.length) return 'sin-grupos'
        // Una guía que falló al cargar no dice nada de sus semanas: no se afirma "sin semanas"
        if (bloques.value.some((b) => b.fallo)) return ''
        const hayAbiertas = bloques.value.some((b) => b.semanas.some((s) => s.disponible))
        return hayAbiertas ? '' : 'sin-semanas'
    })

    return {
        esPersonal, cargar, cargando, error, vacio,
        grupos, grupoId, grupo, xp, errorXp,
        bloques, proyectos, evaluados, puntajePromedio: promedio, siguiente, guiaActual, semanaActual
    }
}
