import api from './api'

// Mientras el backend no esté listo, deja esto en true y todo funciona con datos de prueba.
// Cuando Santiago confirme que la subida responde, ponlo en false.
const USAR_DATOS_PRUEBA = false

// Datos de prueba con la misma forma que la respuesta real del backend
let guiasPrueba = [
    {
        id: 1,
        nombre: 'JavaScript desde cero',
        origen: 'personal',
        temas: [
            { id: 11, nombre_tema: 'Variables y tipos de datos', descripcion: 'Cómo guardar y tipar la información.', orden: 1 },
            { id: 12, nombre_tema: 'Funciones', descripcion: 'Declarar, llamar y reutilizar código.', orden: 2 },
            { id: 13, nombre_tema: 'DOM y eventos', descripcion: 'Leer y modificar la página desde JS.', orden: 3 },
            { id: 14, nombre_tema: 'Fetch API', descripcion: 'Consumir datos de un servidor.', orden: 4 }
        ]
    },
    {
        id: 2,
        nombre: 'Introducción a Vue 3',
        origen: 'personal',
        temas: [
            { id: 21, nombre_tema: 'Reactividad', descripcion: 'ref, reactive y computed.', orden: 1 },
            { id: 22, nombre_tema: 'Componentes y props', descripcion: 'Dividir la interfaz en piezas.', orden: 2 },
            { id: 23, nombre_tema: 'Vue Router', descripcion: 'Navegar entre vistas.', orden: 3 }
        ]
    }
]

const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

/** Devuelve las guías personales del estudiante con sus temas. */
export async function listarMisGuias() {
    if (USAR_DATOS_PRUEBA) {
        await esperar(400)
        return [...guiasPrueba]
    }

    const { data } = await api.get('/guias')
    // Lista paginada de Laravel: las guías vienen en data.data.
    // El backend devuelve también las de grupo; aquí solo mostramos las personales
    return data.data.filter((guia) => guia.origen === 'personal')
}

/** Sube un PDF; el backend lo lee y lo divide en temas. */
export async function subirGuia({ nombre, archivo }) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(1200) // simula el tiempo de leer el PDF y dividirlo con la IA
        const nueva = {
            id: Date.now(),
            nombre,
            origen: 'personal',
            temas: [
                { id: Date.now() + 1, nombre_tema: 'Introducción', descripcion: 'Contexto general de la guía.', orden: 1 },
                { id: Date.now() + 2, nombre_tema: 'Conceptos clave', descripcion: 'Las ideas principales.', orden: 2 },
                { id: Date.now() + 3, nombre_tema: 'Práctica guiada', descripcion: 'Ejercicios para aplicar lo visto.', orden: 3 }
            ]
        }
        guiasPrueba = [nueva, ...guiasPrueba]
        return nueva
    }

    const formData = new FormData()
    formData.append('nombre', nombre)
    formData.append('archivo', archivo)
    // Sin 'origen' (lo decide el backend) y sin Content-Type (el navegador lo genera solo).
    // El timeout es de 120 s porque el backend lee el PDF y llama a la IA
    const { data } = await api.post('/guias', formData, { timeout: 120000 })
    return data.guia
}

/** Devuelve una guía con todos sus temas (para la pantalla de detalle). */
export async function obtenerGuia(id) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(300)
        const guia = guiasPrueba.find((g) => g.id === Number(id))
        if (!guia) throw new Error('Guía no encontrada')
        return guia
    }

    const { data } = await api.get(`/guias/${id}`)
    return data.guia
}