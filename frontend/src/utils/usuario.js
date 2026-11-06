// Textos del rol tal como se muestran al usuario. Vive aquí (y no dentro de cada componente) para que
// el Perfil del aprendiz y el del instructor usen los mismos nombres.
export const ETIQUETA_ROL = {
    estudiante: 'Estudiante',
    instructor: 'Instructor'
}

/** "Ana Sofía Rivera" -> "AS". No hay foto de perfil en la base de datos, por eso se usan las iniciales. */
export function inicialesDe(nombre) {
    return (nombre ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((palabra) => palabra[0].toUpperCase())
        .join('')
}
