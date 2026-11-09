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
        // Modo Grupo: solo se piden las semanas liberadas. El backend genera también las bloqueadas
        // (no lo impide), así que el filtro tiene que estar aquí o se gastaría Gemini en semanas que
        // el aprendiz todavía no puede ver.
        soloLiberadas: false,
        // Número de la semana que ESTA pantalla está generando ahora mismo. Sirve para distinguirla de
        // una que el servidor reporta "generando" por otra persona o porque quedó caída.
        generandoAqui: null,
        // true mientras "Revisar de nuevo" consulta el estado una vez
        revisando: false,
        error: '',
        aviso: ''
    }),

    getters: {
        // Datos de la semana abierta, tal como vienen en la lista
        semanaSeleccionada: (state) => state.semanas.find((s) => s.numero_semana === state.semanaActiva) ?? null,

        // Sirve para volver a consultar mientras alguna semana se está preparando
        hayGenerando: (state) => state.semanas.some((s) => s.estado_generacion === 'generando')
    },

    actions: {
        // Carga las semanas de una guía, abre la primera disponible y lanza la cola.
        // opciones.soloLiberadas = true en Modo Grupo: no pide generar semanas no liberadas.
        async abrirGuia(guiaId, opciones = {}) {
            this.cerrarGuia()
            this.guiaId = guiaId
            this.soloLiberadas = Boolean(opciones.soloLiberadas)

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

            this.semanas = semanas

            const primera = this.semanas.find((s) => s.disponible) ?? this.semanas[0]
            if (primera) {
                await this.seleccionarSemana(primera.numero_semana)
            }

            this.procesarCola()
        },

        // Abre una semana. Si sigue pendiente, vuelve a lanzar la cola
        async seleccionarSemana(numero) {
            this.semanaActiva = numero
            this.contenido = null
            await this.cargarContenido()

            if (this.semanaSeleccionada?.estado_generacion === 'pendiente') {
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

        // Pide las semanas una por una, sin botón. También incluye las que están en
        // 'generando': el backend responde al instante y retoma las que quedaron caídas
        async procesarCola() {
            if (this.procesandoCola) {
                return
            }

            const id = ++idColaVigente
            const porGenerar = this.semanas
                .filter((s) => ['pendiente', 'generando'].includes(s.estado_generacion))
                .filter((s) => !this.soloLiberadas || s.disponible)
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

        // Vuelve a intentar una semana en error (solo lo ve el dueño de la guía)
        async reintentar(numero) {
            this.aviso = ''
            await this.generar(numero, true)
        },

        // Genera una semana. Retorna false si la cola debe detenerse
        async generar(numero, forzar) {
            const guiaId = this.guiaId
            this.actualizarSemana(numero, { estado_generacion: 'generando' })
            this.generandoAqui = numero

            try {
                const semana = await generarSemana(guiaId, numero, forzar)

                if (guiaId === this.guiaId) {
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

                this.semanas = semanas

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

        // Actualiza solo los datos de la lista, sin guardar el contenido que traiga la respuesta
        actualizarSemana(numero, { estado_generacion, error_mensaje, disponible }) {
            const semana = this.semanas.find((s) => s.numero_semana === numero)
            if (!semana) {
                return
            }

            semana.estado_generacion = estado_generacion
            semana.error_mensaje = error_mensaje ?? null
            semana.disponible = disponible ?? semana.disponible
        },

        // Vuelve a pedir la lista, por ejemplo después de liberar semanas
        async refrescarSemanas() {
            try {
                this.semanas = await getSemanas(this.guiaId)
            } catch (e) {
                this.error = e.response?.data?.message ?? 'No pudimos actualizar las semanas.'
            }
        },

        // Detiene la cola en curso y limpia el estado
        cerrarGuia() {
            idColaVigente++
            this.$reset()
        }
    }
})
