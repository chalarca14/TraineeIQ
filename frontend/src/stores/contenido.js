import { defineStore } from 'pinia'
import { generarSemana, getSemana, getSemanas } from '@/services/guiaService'

// Pausa entre semanas para no agotar el límite de peticiones de Gemini
const PAUSA_ENTRE_SEMANAS_MS = 2000

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

// Identifica la cola vigente. Va fuera del state porque $reset no debe reiniciarlo:
// si volviera a 0, una cola vieja podría confundirse con la nueva
let idColaVigente = 0

// Store del contenido por semana: lo usan por igual el Modo Grupo y el Modo Personal
export const useContenidoStore = defineStore('contenido', {
    state: () => ({
        guiaId: null,
        semanas: [],
        // Número de la semana abierta y su contenido
        semanaActiva: null,
        contenido: null,
        cargandoContenido: false,
        // true mientras la cola está pidiendo semanas al backend
        procesandoCola: false,
        // Hasta qué semana se puede generar. Solo se generan semanas liberadas, para no gastar la
        // cuota diaria de Gemini (el backend responde 403 a las demás). Para el instructor es la
        // semana_actual del grupo: él ve todas las semanas como disponibles, pero las futuras se
        // preparan al liberarlas. Para el aprendiz (grupo o Modo Personal) es null y manda
        // "disponible", que el backend ya calcula con la semana del grupo o con la experiencia (XP).
        semanaLimite: null,
        // Número de la semana que ESTA pantalla está generando ahora mismo. Sirve para distinguirla de
        // una que el servidor reporta "generando" por otra persona o porque quedó caída.
        generandoAqui: null,
        // true mientras "Revisar de nuevo" consulta el estado una vez
        revisando: false,
        // true si la última generación solo rehízo las recomendaciones (ya había entregas de aprendices)
        soloRecomendaciones: false,
        error: '',
        aviso: ''
    }),

    getters: {
        // Datos de la semana abierta, tal como vienen en la lista
        semanaSeleccionada: (state) => state.semanas.find((s) => s.numero_semana === state.semanaActiva) ?? null,

        // Sirve para volver a consultar mientras alguna semana se está preparando
        hayGenerando: (state) => state.semanas.some((s) => s.estado_generacion === 'generando'),

        // ¿Se puede pedir a Gemini el contenido de esta semana? Ver semanaLimite
        esGenerable: (state) => (semana) =>
            state.semanaLimite != null ? semana.numero_semana <= state.semanaLimite : Boolean(semana.disponible)
    },

    actions: {
        // Carga las semanas de una guía, abre la primera disponible y lanza la cola.
        // opciones.semanaLimite: solo para el instructor, la semana_actual de su grupo (ver el state).
        async abrirGuia(guiaId, opciones = {}) {
            this.cerrarGuia()
            this.guiaId = guiaId
            this.semanaLimite = opciones.semanaLimite ?? null

            let semanas

            try {
                semanas = await getSemanas(guiaId)
            } catch (e) {
                this.error = e.response?.data?.message ?? 'No pudimos cargar las semanas de esta guía.'
                return
            }

            // Si el usuario salió de la vista mientras cargaba, no se lanza la cola
            if (guiaId !== this.guiaId) {
                return
            }

            this.semanas = this.marcarSinLiberar(semanas)

            const primera = this.semanas.find((s) => s.disponible) ?? this.semanas[0]
            if (primera) {
                await this.seleccionarSemana(primera.numero_semana)
            }

            this.procesarCola()
        },

        // Abre una semana. Si sigue pendiente y ya está liberada, vuelve a lanzar la cola
        async seleccionarSemana(numero) {
            this.semanaActiva = numero
            this.contenido = null
            await this.cargarContenido()

            const semana = this.semanaSeleccionada
            if (semana?.estado_generacion === 'pendiente' && this.esGenerable(semana)) {
                this.procesarCola()
            }
        },

        // Trae el contenido de la semana abierta, si ya está listo y liberado
        async cargarContenido() {
            const semana = this.semanaSeleccionada
            if (!semana?.disponible || semana.estado_generacion !== 'listo') {
                return
            }

            const numero = semana.numero_semana
            this.cargandoContenido = true
            this.error = ''

            try {
                const contenido = await getSemana(this.guiaId, numero)

                // Si el usuario cambió de semana mientras cargaba, esta respuesta ya no aplica
                if (numero === this.semanaActiva) {
                    this.contenido = contenido
                }
            } catch (e) {
                this.error = e.response?.data?.message ?? 'No pudimos cargar esta semana.'
            } finally {
                this.cargandoContenido = false
            }
        },

        // Pide las semanas liberadas una por una, sin botón. También incluye las que están en
        // 'generando': el backend responde al instante y retoma las que quedaron caídas
        async procesarCola() {
            if (this.procesandoCola) {
                return
            }

            const id = ++idColaVigente
            const porGenerar = this.semanas
                .filter((s) => ['pendiente', 'generando'].includes(s.estado_generacion))
                .filter((s) => this.esGenerable(s))
                .map((s) => s.numero_semana)

            this.procesandoCola = true
            this.aviso = ''

            for (const numero of porGenerar) {
                const continuar = await this.generar(numero, false)

                // Se cerró la guía mientras se generaba: esta cola ya no debe seguir
                if (id !== idColaVigente) {
                    return
                }

                if (!continuar) {
                    break
                }

                await esperar(PAUSA_ENTRE_SEMANAS_MS)

                if (id !== idColaVigente) {
                    return
                }
            }

            this.procesandoCola = false
        },

        // Vuelve a generar una semana con forzar (solo el dueño de la guía): "Reintentar" de una semana en
        // error y "Actualizar con las guías de la semana". Devuelve { ok, soloRecomendaciones }; quien no
        // lo necesita (el "Reintentar" de siempre) lo ignora. Si falla, el motivo queda en this.aviso.
        async reintentar(numero) {
            this.aviso = ''
            this.soloRecomendaciones = false
            const ok = await this.generar(numero, true)
            return { ok, soloRecomendaciones: ok && this.soloRecomendaciones }
        },

        // Genera una semana. Retorna false si la cola debe detenerse
        async generar(numero, forzar) {
            const guiaId = this.guiaId
            this.actualizarSemana(numero, { estado_generacion: 'generando' })
            this.generandoAqui = numero

            try {
                const semana = await generarSemana(guiaId, numero, forzar)

                if (guiaId === this.guiaId) {
                    this.soloRecomendaciones = Boolean(semana.solo_recomendaciones)
                    this.actualizarSemana(numero, semana)

                    // Si la semana que terminó es la abierta, muestra su contenido
                    if (numero === this.semanaActiva) {
                        await this.cargarContenido()
                    }
                }

                return true
            } catch (e) {
                if (guiaId === this.guiaId) {
                    // El backend la dejó pendiente: se reintenta la próxima vez que se abra
                    this.actualizarSemana(numero, { estado_generacion: 'pendiente' })
                    this.aviso = e.response?.data?.message ?? 'No pudimos preparar esta semana. Intenta más tarde.'
                }

                return false
            } finally {
                // Solo si sigue siendo la misma guía: si se cerró, $reset ya limpió el estado
                if (guiaId === this.guiaId && this.generandoAqui === numero) {
                    this.generandoAqui = null
                }
            }
        },

        // "Revisar de nuevo": UNA consulta de la lista de semanas (sin repetirse sola). Se usa cuando el
        // servidor reporta una semana "generando" que esta pantalla no está generando. Un fallo se avisa
        // con `aviso` y no con `error`, para no tumbar la pantalla entera.
        async revisar() {
            if (this.revisando) {
                return
            }

            const guiaId = this.guiaId
            this.revisando = true
            this.aviso = ''

            try {
                const semanas = await getSemanas(guiaId)
                if (guiaId !== this.guiaId) {
                    return
                }

                this.semanas = this.marcarSinLiberar(semanas)

                if (this.semanaSeleccionada?.estado_generacion === 'listo' && !this.contenido) {
                    await this.cargarContenido()
                }
            } catch (e) {
                if (guiaId === this.guiaId) {
                    this.aviso = e.response?.data?.message ?? 'No pudimos consultar el estado de la semana.'
                }
            } finally {
                if (guiaId === this.guiaId) {
                    this.revisando = false
                }
            }
        },

        // Actualiza solo los datos de la lista, sin guardar el contenido que traiga la respuesta.
        // Los XP también cambian al generar (antes la semana no tenía mini proyectos y su máximo era 0):
        // se copian solo si vienen, porque en Modo Grupo y en los errores no llegan.
        actualizarSemana(numero, { estado_generacion, error_mensaje, disponible, xp_obtenido, xp_maximo, xp_requerido }) {
            const semana = this.semanas.find((s) => s.numero_semana === numero)
            if (!semana) {
                return
            }

            semana.estado_generacion = estado_generacion
            semana.error_mensaje = error_mensaje ?? null
            semana.disponible = disponible ?? semana.disponible

            if (xp_obtenido !== undefined) {
                semana.xp_obtenido = xp_obtenido
            }
            if (xp_maximo !== undefined) {
                semana.xp_maximo = xp_maximo
            }
            if (xp_requerido !== undefined) {
                semana.xp_requerido = xp_requerido
            }
        },

        // Vuelve a pedir la lista, por ejemplo después de liberar semanas
        async refrescarSemanas() {
            try {
                this.semanas = this.marcarSinLiberar(await getSemanas(this.guiaId))
            } catch (e) {
                this.error = e.response?.data?.message ?? 'No pudimos actualizar las semanas.'
            }
        },

        // Marca con sin_liberar las semanas que el instructor ve pero aún no libera: su contenido no se
        // genera hasta liberarlas, y la pantalla lo dice ("Se prepara al liberarla") en vez de "Pendiente"
        marcarSinLiberar(semanas) {
            return semanas.map((s) => ({ ...s, sin_liberar: !this.esGenerable(s) }))
        },

        // Detiene la cola en curso y limpia el estado
        cerrarGuia() {
            idColaVigente++
            this.$reset()
        }
    }
})
