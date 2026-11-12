import { ref, computed, watch } from 'vue'
import { useModoStore } from '@/stores/modo'
import { listarGuias, getSemanas } from '@/services/guiaService'
import { getMiniProyectosGuia } from '@/services/miniProyectoService'

// Carga los mini proyectos del aprendiz agrupados por GUÍA y SEMANA.
//
// Por qué dos llamadas por guía: GET /guias/{id}/mini-proyectos solo trae los proyectos de las
// semanas YA liberadas para este usuario; para dibujar las semanas bloqueadas (título + candado)
// hace falta GET /guias/{id}/semanas, que las lista todas con `disponible`.
//
// Por qué Promise.allSettled: si una guía falla no se tumban las demás; esa guía queda marcada
// con `fallo` y la vista ofrece "Reintentar" solo para ella.
//
// Opción grupoId: el detalle de un grupo solo quiere las guías de ESE grupo, sin importar el modo
// activo (se puede llegar con un enlace directo estando en Modo Personal). Sin grupoId se filtra
// por modo, como en la lista de mini proyectos y el dashboard.
export function useMiniProyectosSemanas({ grupoId = null } = {}) {
    const modoStore = useModoStore()

    const bloques = ref([]) // [{ guia, fallo, semanas: [{ ...semana, proyectos: [] }] }]
    const cargando = ref(true)
    const error = ref('') // solo si falla la lista de guías (no hay nada que mostrar)

    // Si el aprendiz cambia de modo mientras se carga, la respuesta vieja no debe pisar a la nueva
    let ultimaPeticion = 0

    async function cargarGuia(guia) {
        try {
            const [proyectos, semanas] = await Promise.all([getMiniProyectosGuia(guia.id), getSemanas(guia.id)])
            return { guia, fallo: false, semanas: agruparPorSemana(guia, semanas, proyectos) }
        } catch {
            return { guia, fallo: true, semanas: [] }
        }
    }

    // Une cada proyecto con su semana. Las semanas vienen todas; los proyectos, solo los de las abiertas.
    function agruparPorSemana(guia, semanas, proyectos) {
        const porNumero = new Map(
            [...semanas]
                .sort((a, b) => a.numero_semana - b.numero_semana)
                .map((s) => [s.numero_semana, { ...s, proyectos: [] }])
        )
        for (const p of proyectos) {
            const numero = p.semana?.numero_semana
            // Si por algún motivo la semana no vino en la lista, se crea con lo que trae el proyecto
            if (!porNumero.has(numero)) {
                porNumero.set(numero, { numero_semana: numero, titulo: p.semana?.titulo ?? '', disponible: true, proyectos: [] })
            }
            porNumero.get(numero).proyectos.push({ ...p, guiaId: guia.id, guiaNombre: guia.nombre })
        }
        return [...porNumero.values()]
    }

    async function cargar() {
        const peticion = ++ultimaPeticion
        cargando.value = true
        error.value = ''
        try {
            const guias = await listarGuias()
            // Con grupoId: solo las guías de ese grupo. Sin él: 'personal' o 'grupo' coincide con guia.origen
            const elegidas = grupoId != null
                ? guias.filter((g) => g.grupo_id === grupoId)
                : guias.filter((g) => g.origen === modoStore.modoActivo)
            const resultado = await Promise.all(elegidas.map(cargarGuia))
            if (peticion !== ultimaPeticion) return
            bloques.value = resultado
        } catch (err) {
            if (peticion !== ultimaPeticion) return
            error.value = 'No se pudieron cargar tus mini proyectos. Intenta de nuevo.'
        } finally {
            if (peticion === ultimaPeticion) cargando.value = false
        }
    }

    // Reintenta SOLO la guía que falló, sin recargar todo
    async function reintentarGuia(guiaId) {
        const i = bloques.value.findIndex((b) => b.guia.id === guiaId)
        if (i === -1) return
        const nuevo = await cargarGuia(bloques.value[i].guia)
        bloques.value[i] = nuevo
    }

    // Lista plana de todos los proyectos: de aquí salen pestañas y resumen
    const proyectos = computed(() => bloques.value.flatMap((b) => b.semanas.flatMap((s) => s.proyectos)))

    // Al cambiar de modo se vuelve a cargar: cada modo muestra sus propias guías.
    // Las guías de un grupo no dependen del modo, así que ahí no hace falta recargar
    if (grupoId == null) watch(() => modoStore.modoActivo, cargar)

    return { bloques, proyectos, cargando, error, cargar, reintentarGuia }
}
