import { ref } from 'vue'
import { generarSemana } from '@/services/guiaService'

/**
 * Prepara con la IA una semana recién liberada, en todas las guías del grupo que la tienen.
 *
 * Por qué aquí: solo se generan semanas liberadas, para no gastar la cuota diaria de Gemini (20
 * llamadas con el modelo principal en el plan gratis). Al liberar la semana N, el instructor pide su
 * contenido una guía por vez: cada llamada tarda unos 15-30 s. Si una falla (IA ocupada), la semana
 * queda pendiente y se genera cuando alguien abra la guía (la cola de stores/contenido.js).
 */
export function usePrepararSemana() {
    // { numero, hechas, total, guia } mientras se prepara; null si no
    const preparando = ref(null)
    // Aviso al terminar si alguna guía no se pudo preparar
    const avisoPreparacion = ref('')

    /**
     * numero: la semana recién liberada.
     * guias: las del composable useSemanasGrupo ({ guia, semanas }), con el estado de cada semana.
     */
    async function preparar(numero, guias) {
        avisoPreparacion.value = ''
        // Solo las guías que tienen esa semana y aún no está lista (no se paga dos veces)
        const pendientes = guias.filter(({ semanas }) =>
            semanas.some((s) => s.numero_semana === numero && s.estado_generacion !== 'listo'))
        if (!pendientes.length) return

        const fallidas = []
        preparando.value = { numero, hechas: 0, total: pendientes.length, guia: '' }

        // Una por una, como la cola: varias a la vez superarían el límite por minuto de Gemini
        for (const { guia } of pendientes) {
            preparando.value = { ...preparando.value, guia: guia.nombre }
            try {
                const semana = await generarSemana(guia.id, numero)
                // 200 con 'error': la IA respondió mal dos veces; el instructor puede reintentar en la guía
                if (semana?.estado_generacion === 'error') fallidas.push(guia.nombre)
            } catch {
                // 503 (IA ocupada) o sin conexión: la semana sigue pendiente
                fallidas.push(guia.nombre)
            }
            preparando.value = { ...preparando.value, hechas: preparando.value.hechas + 1 }
        }

        preparando.value = null
        if (fallidas.length) {
            avisoPreparacion.value = `No se pudo preparar la semana ${numero} en ${fallidas.map((n) => `«${n}»`).join(', ')}. `
                + 'Abre esa guía para reintentarlo (la IA puede estar ocupada).'
        }
    }

    return { preparando, avisoPreparacion, preparar }
}
