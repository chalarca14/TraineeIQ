<template>
    <!-- Contenedor principal dividido en dos columnas — invertido respecto al Login -->
    <div class="registro-page">

        <!-- ===== COLUMNA IZQUIERDA — Información del rol ===== -->
        <div class="registro-info">

            <div class="info-header">
                <h1 class="registro-logo"><img src="@/assets/icons/cerebro.png" alt="descripcion" width="50" />
                    TRAINEEIQ</h1>
                <p class="registro-slogan">Plataforma inteligente de acompañamiento académico</p>
            </div>
            <br>
            <br>

            <div class="logo-rol">
                <!-- El signo : activa el modo dinámico de Vue -->
                <img :src="rolInfo.logo" alt="Logo de rol" width="150" />
            </div>


            <!-- Título que cambia según el rol seleccionado -->
            <div class="info-contenido">
                <h2 class="info-titulo">
                    {{ rolInfo.titulo }}
                </h2>
                <p class="info-descripcion">{{ rolInfo.descripcion }}</p>



                <!-- Lista de beneficios del rol seleccionado -->
                <ul class="info-beneficios">
                    <li v-for="beneficio in rolInfo.beneficios" :key="beneficio" class="beneficio-item">
                        <span class="beneficio-check"><img src="@/assets/icons/check.png" alt="descripcion"
                                width="20" /></span>
                        {{ beneficio }}
                    </li>
                </ul>
            </div>

            <p class="registro-footer">TraineeIQ © 2024 • Todos los derechos reservados</p>

        </div>

        <!-- ===== COLUMNA DERECHA — Formulario ===== -->
        <div class="registro-form-container">

            <h2>Crear <span>cuenta</span></h2>
            <p class="registro-subtitle">Únete a TraineeIQ hoy</p>

            <!-- Selector de rol -->
            <p class="rol-label">Registrarse como:</p>
            <div class="rol-selector">
                <button v-for="rol in roles" :key="rol.valor"
                    :class="['rol-btn', { activo: rolSeleccionado === rol.valor }]" @click="rolSeleccionado = rol.valor"
                    type="button">
                    <component :is="rol.icono" :size="16" />
                    {{ rol.etiqueta }}
                </button>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="registrarse">

                <!-- Campo nombre completo -->
                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <div class="input-wrapper">
                        <User class="input-icon" :size="18" />
                        <input id="nombre" v-model="form.nombre" type="text" placeholder="Ingresa tu nombre completo"
                            required />
                    </div>
                </div>

                <!-- Campo correo -->
                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <div class="input-wrapper">
                        <Mail class="input-icon" :size="18" />
                        <input id="correo" v-model="form.correo" type="email"
                            placeholder="Ingresa tu correo electrónico" required />
                    </div>
                </div>

                <!-- Campo contraseña -->
                <div class="campo">
                    <label for="contrasena">Contraseña</label>
                    <div class="input-wrapper">
                        <Lock class="input-icon" :size="18" />
                        <input id="contrasena" v-model="form.contrasena" :type="mostrarContrasena ? 'text' : 'password'"
                            placeholder="Crea una contraseña" required />
                        <button type="button" class="btn-toggle-password"
                            @click="mostrarContrasena = !mostrarContrasena">
                            <EyeOff v-if="mostrarContrasena" :size="18" />
                            <Eye v-else :size="18" />
                        </button>
                    </div>
                </div>

                <!-- Campo confirmar contraseña -->
                <div class="campo">
                    <label for="confirmar">Confirmar contraseña</label>
                    <div class="input-wrapper">
                        <Lock class="input-icon" :size="18" />
                        <input id="confirmar" v-model="form.confirmar" :type="mostrarConfirmar ? 'text' : 'password'"
                            placeholder="Repite tu contraseña" required />
                        <button type="button" class="btn-toggle-password" @click="mostrarConfirmar = !mostrarConfirmar">
                            <EyeOff v-if="mostrarConfirmar" :size="18" />
                            <Eye v-else :size="18" />
                        </button>
                    </div>
                    <!-- Aviso si las contraseñas no coinciden -->
                    <p v-if="contrasenasNoCoinciden" class="campo-error">
                        Las contraseñas no coinciden
                    </p>
                </div>

                <!-- Mensaje de error general -->
                <p v-if="error" class="mensaje-error">{{ error }}</p>

                <!-- Mensaje de éxito -->
                <p v-if="exito" class="mensaje-exito">{{ exito }}</p>

                <!-- Botón registrarse -->
                <button type="submit" class="btn-registro" :disabled="cargando || contrasenasNoCoinciden">
                    {{ cargando ? 'Creando cuenta...' : 'Registrarse' }} →
                </button>

            </form>

            <!-- Link para ir al registro -->
            <p class="link-registro">
                ¿Ya tienes cuenta?
                <RouterLink to="/login">Inicia sesión aquí</RouterLink>
            </p>

        </div>
    </div>
</template>

<script>
import api from '../../services/api'
import {
    Mail,
    Lock,
    Eye,
    EyeOff,
    User,
    GraduationCap,
    Users,
    Shield
} from 'lucide-vue-next'

export default {
    name: 'Registro',

    components: {
        Mail, Lock, Eye, EyeOff, User, GraduationCap, Users, Shield
    },

    data() {
        return {
            // Roles disponibles
            roles: [
                { valor: 'estudiante', etiqueta: 'Aprendiz', icono: 'GraduationCap' },
                { valor: 'instructor', etiqueta: 'Instructor', icono: 'Users' },
                { valor: 'administrador', etiqueta: 'Admin', icono: 'Shield' }
            ],

            // Rol seleccionado por defecto
            rolSeleccionado: 'estudiante',

            // Información que cambia según el rol — esto es lo que se ve en la columna izquierda
            infoRoles: {
                estudiante: {
                    titulo: 'Aprende más allá de las guías.',
                    descripcion: 'TraineeIQ te acompaña semana a semana con recomendaciones inteligentes.',
                    beneficios: [
                        'Recomendaciones inteligentes',
                        'Mini proyectos personalizados',
                        'Seguimiento de progreso'
                    ],
                    logo: new URL('@/assets/icons/aprendiz.png', import.meta.url)
                },
                instructor: {
                    titulo: 'Potencia el aprendizaje de tus grupos.',
                    descripcion: 'Gestiona tus clases y guía a tus aprendices con herramientas inteligentes.',
                    beneficios: [
                        'Gestión de clases',
                        'Publicación de guías',
                        'Seguimiento de aprendices'
                    ],
                    logo: new URL('@/assets/icons/instructor.png', import.meta.url)
                },
                administrador: {
                    titulo: 'Control total de la plataforma.',
                    descripcion: 'Supervisa y gestiona todos los recursos de TraineeIQ.',
                    beneficios: [
                        'Gestión de usuarios',
                        'Supervisión general',
                        'Estadísticas del sistema'
                    ],
                    logo: new URL('@/assets/icons/administracion.png', import.meta.url)
                }
            },

            // Datos del formulario
            form: {
                nombre: '',
                correo: '',
                contrasena: '',
                confirmar: ''
            },

            // Estados de visibilidad de contraseñas
            mostrarContrasena: false,
            mostrarConfirmar: false,

            // Estados de la operación
            cargando: false,
            error: '',
            exito: ''
        }
    },

    computed: {
        // Devuelve la info del rol actualmente seleccionado
        rolInfo() {
            return this.infoRoles[this.rolSeleccionado]
        },

        // Verifica en tiempo real si las contraseñas coinciden
        contrasenasNoCoinciden() {
            return this.form.confirmar !== '' && this.form.contrasena !== this.form.confirmar
        }
    },

    methods: {
        async registrarse() {
            this.error = ''
            this.exito = ''

            // Validacion extra antes de enviar al backend
            if (this.form.contrasena !== this.form.confirmar) {
                this.error = 'Las contraseñas no coinciden.'
                return
            }

            this.cargando = true

            try {
                // Enviamos los datos al backend
                await api.post('/registro', {
                    name: this.form.nombre,
                    email: this.form.correo,
                    password: this.form.contrasena,
                    rol: this.rolSeleccionado
                })

                // Si el registro fue exitoso, mostramos mensaje y redirigimos al registro
                this.exito = '¡Cuenta creada exitosamente! Redirigiendo...'
                setTimeout(() => {
                    this.$router.push('/registro')
                }, 2000)

            } catch (err) {
                if (err.response?.status === 422) {
                    this.error = 'El correo ya está registrado o los datos son inválidos.'
                } else {
                    this.error = 'Ocurrió un error. Intenta de nuevo.'
                }
            } finally {
                this.cargando = false
            }
        }
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Puritan:ital,wght@0,400;0,700;1,400;1,700&display=swap');

/* ===== LAYOUT PRINCIPAL — invertido respecto al Login ===== */

.registro-page {
    font-family: 'Puritan', sans-serif;
    display: flex;
    min-height: 100vh;
    background-color: #0f1117;
    color: #ffffff;
}


/* ===== COLUMNA IZQUIERDA ===== */
.registro-info {
    flex: 1;
    padding: 48px;
    background-color: #161a24;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.registro-logo {
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
}

.registro-logo img{
    /* background-color: rebeccapurple; */
    position: relative;
    top: 15px;
    
}

.registro-slogan {
    font-size: 0.9rem;
    color: #8b8fa8;
    margin-top: 4px;
}

.info-contenido {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 40px 0;
}

.info-titulo {
    font-size: 2rem;
    font-weight: 800;
    line-height: 1.3;
    color: #ffffff;
    margin-bottom: 16px;
}

.info-descripcion {
    font-size: 0.95rem;
    color: #8b8fa8;
    line-height: 1.6;
    margin-bottom: 32px;
}

.info-beneficios {
    list-style: none;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.beneficio-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
    color: #ffffff;
}

.beneficio-check {
    color: #7c3aed;
    font-weight: 700;
    font-size: 1rem;
}

.registro-footer {
    font-size: 0.8rem;
    color: #4a4f6a;
}

/* ===== COLUMNA DERECHA ===== */
.registro-form-container {
    width: 45%;
    background-color: #0f1117;
    padding: 48px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.registro-form-container h2 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 4px;
    color: #ffffff;
}

.registro-form-container h2 span {
    color: #7c3aed;
}

.registro-subtitle {
    color: #8b8fa8;
    font-size: 0.95rem;
    margin-bottom: 32px;
}

/* ===== SELECTOR DE ROL ===== */
.rol-label {
    font-size: 0.9rem;
    color: #8b8fa8;
    margin-bottom: 10px;
}

.rol-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 28px;
}

.rol-btn {
    flex: 1;
    padding: 12px 8px;
    border-radius: 10px;
    border: 1px solid #1e2235;
    background-color: #161a24;
    color: #8b8fa8;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    font-family: 'Puritan', sans-serif;
}

.rol-btn:hover {
    border-color: #7c3aed;
    color: #ffffff;
}

.rol-btn.activo {
    background-color: #7c3aed;
    border-color: #7c3aed;
    color: #ffffff;
    font-weight: 600;
}

/* ===== CAMPOS ===== */
.campo {
    margin-bottom: 18px;
}

.campo label {
    display: block;
    font-size: 0.9rem;
    color: #ffffff;
    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    color: #8b8fa8;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 14px 14px 42px;
    background-color: #161a24;
    border: 1px solid #1e2235;
    border-radius: 10px;
    color: #ffffff;
    font-size: 0.95rem;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s ease;
    font-family: 'Puritan', sans-serif;
}

.input-wrapper input:focus {
    border-color: #7c3aed;
}

.input-wrapper input::placeholder {
    color: #4a4f6a;
}

.btn-toggle-password {
    position: absolute;
    right: 14px;
    background: none;
    border: none;
    color: #8b8fa8;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
}

.campo-error {
    font-size: 0.8rem;
    color: #f87171;
    margin-top: 6px;
}

/* ===== MENSAJES ===== */
.mensaje-error {
    background-color: rgba(220, 38, 38, 0.1);
    border: 1px solid rgba(220, 38, 38, 0.3);
    color: #f87171;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

.mensaje-exito {
    background-color: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #4ade80;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

/* ===== BOTÓN REGISTRO ===== */
.btn-registro {
    width: 100%;
    padding: 16px;
    background-color: #7c3aed;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: background-color 0.2s ease;
    margin-bottom: 24px;
    font-family: 'Puritan', sans-serif;
}

.btn-registro:hover {
    background-color: #6d28d9;
}

.btn-registro:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ===== LINK LOGIN ===== */
.link-registro {
    text-align: center;
    font-size: 0.9rem;
    color: #8b8fa8;
}

.link-registro a {
    color: #7c3aed;
    text-decoration: none;
    font-weight: 600;
}

.link-registro a:hover {
    text-decoration: underline;
}

.logo-rol {
    /* background-color: red; */
    width: 150px;
}

.logo-rol img {
    filter: invert(1);
}


@keyframes glowContinuous {

    0%,
    100% {
        filter: drop-shadow(0 0 2px rgba(115, 0, 181, 0.2));
    }

    50% {
        filter: drop-shadow(0 0 10px rgba(211, 1, 218, 0.8));
    }
}

.logo-rol {
    color: #670bb3;
    /* Color base del icono */
    animation: glowContinuous 2s ease-in-out infinite;
}
</style>