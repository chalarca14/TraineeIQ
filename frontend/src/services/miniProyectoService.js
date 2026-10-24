import api from './api'

const USAR_DATOS_PRUEBA = false
const esperar = (ms) => new Promise((resolve) => setTimeout(resolve, ms))

export async function generarMiniProyectos({ guiaId, temaId }) {
    if (USAR_DATOS_PRUEBA) {
        await esperar(1500)
        return [
            { id: 1, guia_id: guiaId, tema_id: temaId, titulo: 'Calculadora básica', descripcion: 'Suma, resta, multiplica y divide.', dificultad: 'basico', contenido: '1. Crea los botones.\n2. Guarda los números en variables.\n3. Muestra el resultado en pantalla.' },
            { id: 2, guia_id: guiaId, tema_id: temaId, titulo: 'Lista de tareas', descripcion: 'Agrega y elimina tareas.', dificultad: 'intermedio', contenido: '1. Crea el formulario.\n2. Guarda las tareas en un arreglo.\n3. Pinta la lista y permite borrar.' },
            { id: 3, guia_id: guiaId, tema_id: temaId, titulo: 'Consumo de una API pública', descripcion: 'Muestra datos reales en una tarjeta.', dificultad: 'avanzado', contenido: '1. Elige una API.\n2. Haz la petición con fetch.\n3. Maneja la carga y los errores.' }
        ]
    }
    // tema_id es opcional: sin él se generan sobre toda la guía
    const { data } = await api.post('/mini-proyectos/generar', { guia_id: guiaId, tema_id: temaId }, { timeout: 120000 })
    return data.mini_proyectos
}