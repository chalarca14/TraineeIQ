// Textos de los pasos que muestra AnimacionCarga.vue. Viven en un solo lugar para que el aprendiz
// y el instructor vean exactamente lo mismo. Son orientativos: el backend no informa en qué paso va.

// Subir una guía: el backend lee el PDF o el Excel y lo divide en semanas (POST /guias)
export const PASOS_SUBIDA = [
    'Leyendo el archivo',
    'Separando la guía en semanas',
    'Organizando los temas'
]

// Generar una semana: temas, recomendaciones y mini proyectos (POST /guias/{id}/semanas/{n}/generar)
export const PASOS_SEMANA = [
    'Generando los temas',
    'Buscando recomendaciones',
    'Creando los mini proyectos',
    'Revisando el contenido'
]
