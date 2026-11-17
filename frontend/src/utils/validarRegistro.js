// Validación del formulario de registro, antes de enviarlo al backend. Es una función pura: recibe los
// datos y devuelve un error por campo ('' = sin error). Salió de RegistroForm.vue (pasaba de 300 líneas).

const esCorreo = (texto) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(texto)

export function validarRegistro({ nombre, correo, password, confirmarPassword, aceptaTerminos }) {
    const errores = { nombre: '', correo: '', password: '', confirmarPassword: '', aceptaTerminos: '' }

    if (!nombre.trim()) errores.nombre = 'El nombre es obligatorio.'

    if (!correo) errores.correo = 'El correo es obligatorio.'
    else if (!esCorreo(correo)) errores.correo = 'Ingresa un correo válido.'

    if (!password) errores.password = 'La contraseña es obligatoria.'
    else if (password.length < 8) errores.password = 'Debe tener al menos 8 caracteres.'

    // Esta comparación solo existe en el frontend: el backend no recibe ni valida un campo de
    // confirmación, así que es responsabilidad nuestra
    if (password && confirmarPassword !== password) errores.confirmarPassword = 'Las contraseñas no coinciden.'

    if (!aceptaTerminos) errores.aceptaTerminos = 'Debes aceptar los términos para continuar.'

    return errores
}

// true si ningún campo tiene error
export const sinErrores = (errores) => Object.values(errores).every((e) => !e)
