// Texto y clase del chip de una semana según lo que manda el backend. Vive en un solo lugar porque
// lo usan la lista lateral, el selector de 360 px y el título del panel: si cambiara en uno solo,
// la misma semana diría cosas distintas en cada sitio.
// `generandoAqui` = esta pantalla está generando AHORA esa semana (no otra persona ni el servidor).
export function estadoSemana(semana, generandoAqui = false) {
    // Sin liberar: no se mira estado_generacion, porque su contenido ni siquiera se puede abrir
    if (!semana.disponible) return { texto: 'Próximamente', clase: 'bloqueada' }

    switch (semana.estado_generacion) {
        case 'listo': return { texto: 'Lista', clase: 'listo' }
        case 'generando':
            return generandoAqui
                ? { texto: 'Generando…', clase: 'generando', girando: true }
                : { texto: 'En preparación', clase: 'preparacion' }
        case 'error': return { texto: 'Error', clase: 'error' }
        default: return { texto: 'Pendiente', clase: 'pendiente' }
    }
}
