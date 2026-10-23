import api from './api'

// Guarda en el backend el modo preferido del usuario ('grupo' o 'personal')
export async function actualizarModo(modo) {
    const { data } = await api.patch('/usuario/modo', { modo_preferido: modo })
    return data
}

