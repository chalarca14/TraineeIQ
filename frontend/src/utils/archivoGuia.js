// Reglas del archivo de una guía, compartidas por "Subir guía" (Modo Personal) y "Asignar guía"
// (instructor). Son las mismas del backend (StoreGuiaRequest y TamanoExcelGuia): se revisan antes de
// enviar para no gastar la subida ni la IA en un archivo que igual se rechazaría.

// El Excel pesa menos porque ocupa mucha más memoria al leerse en el servidor
export const MAX_MB_PDF = 10
export const MAX_MB_EXCEL = 2

// Extensión y tipo: algunos navegadores en Windows no informan el tipo de un .xlsx
export const TIPOS_ACEPTADOS =
    '.pdf,.xlsx,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

/** 'pdf', 'excel' o null, según la extensión del nombre (el tipo MIME no siempre llega). */
export function tipoDeArchivo(archivo) {
    const nombre = archivo.name.toLowerCase()
    if (nombre.endsWith('.pdf')) return 'pdf'
    if (nombre.endsWith('.xlsx')) return 'excel'
    return null
}

/** Devuelve el mensaje de error del archivo, o '' si está bien. */
export function errorDeArchivoGuia(archivo) {
    if (!archivo) return 'Selecciona un PDF o un Excel (.xlsx).'

    const tipo = tipoDeArchivo(archivo)
    if (!tipo) return 'El archivo debe ser un PDF o un Excel (.xlsx).'

    const maxMb = tipo === 'excel' ? MAX_MB_EXCEL : MAX_MB_PDF
    if (archivo.size > maxMb * 1024 * 1024) {
        return `El ${tipo === 'excel' ? 'Excel' : 'PDF'} no puede pesar más de ${maxMb} MB.`
    }
    return ''
}
