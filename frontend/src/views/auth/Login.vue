<template>
    <div class="login-page">

        <!-- ===== COLUMNA IZQUIERDA ===== -->
        <div class="login-info">
            <div>
                <h1 class="login-logo"><img src="@/assets/icons/cerebro.png" alt="descripcion" width="50" /> TRAINEEIQ</h1>
                <p class="login-slogan">Plataforma inteligente de acompañamiento académico</p>
            </div>

            <div>
                <h2 class="login-hero-title">
                    Aprende más allá<br>de la guía.<br>
                    <span>Tu mentor inteligente<br>del SENA te espera.</span>
                </h2>
                <p class="login-description">
                    TraineeIQ utiliza inteligencia artificial para brindarte
                    recomendaciones personalizadas, mini proyectos y seguimiento
                    de tu progreso en tiempo real.
                </p>
            </div>

            <ul class="login-features">
                <li class="feature-card">
                    <div class="feature-icon"><img src="@/assets/icons/robot.png" alt="descripcion" width="50" /></div>
                    <h4>Recomendaciones IA</h4>
                    <p>Obtén sugerencias personalizadas de temas según tu avance.</p>
                </li>
                <li class="feature-card">
                    <div class="feature-icon"><img src="@/assets/icons/proyecto.png" alt="descripcion" width="50" /></div>
                    <h4>Mini proyectos</h4>
                    <p>Desarrolla mini proyectos prácticos alineados a tu aprendizaje.</p>
                </li>
                <li class="feature-card">
                    <div class="feature-icon"><img src="@/assets/icons/sincronizacion.png" alt="descripcion" width="50" /></div>
                    <h4>Sincronización con guía</h4>
                    <p>Conecta tus guías del SENA y accede a contenido relevante por semana.</p>
                </li>
                <li class="feature-card">
                    <div class="feature-icon"><img src="@/assets/icons/grafico.png" alt="descripcion" width="50" /></div>
                    <h4>Seguimiento del progreso</h4>
                    <p>Monitorea tu avance y alcanza tus metas académicas.</p>
                </li>
            </ul>

            <p class="login-footer">TraineeIQ © 2024 • Todos los derechos reservados</p>
        </div>

        <!-- ===== COLUMNA DERECHA ===== -->
        <div class="login-form-container">
            <h2>¡Bienvenido <span>de vuelta!</span></h2>
            <p class="login-subtitle">Ingresa tu cuenta de TraineeIQ</p>

            <!-- Selector de rol con íconos -->
            <p class="rol-label">Ingresar como:</p>
            <div class="rol-selector">
                <button v-for="rol in roles" :key="rol.valor"
                    :class="['rol-btn', { activo: rolSeleccionado === rol.valor }]" @click="rolSeleccionado = rol.valor"
                    type="button">
                    <!-- Renderizamos el ícono dinámicamente según el rol -->
                    <component :is="rol.icono" :size="16" />
                    {{ rol.etiqueta }}
                </button>
            </div>

            <form @submit.prevent="iniciarSesion">

                <!-- Campo correo con ícono -->
                <div class="campo">
                    <label for="correo">Correo electrónico</label>
                    <div class="input-wrapper">
                        <Mail class="input-icon" :size="18" />
                        <input id="correo" v-model="form.correo" type="email"
                            placeholder="Ingresa tu correo electrónico" required />
                    </div>
                </div>

                <!-- Campo contraseña con ícono -->
                <div class="campo">
                    <label for="contrasena">Contraseña</label>
                    <div class="input-wrapper">
                        <Lock class="input-icon" :size="18" />
                        <input id="contrasena" v-model="form.contrasena" :type="mostrarContrasena ? 'text' : 'password'"
                            placeholder="Ingresa tu contraseña" required />
                        <!-- Ícono Eye/EyeOff según estado -->
                        <button type="button" class="btn-toggle-password"
                            @click="mostrarContrasena = !mostrarContrasena">
                            <EyeOff v-if="mostrarContrasena" :size="18" />
                            <Eye v-else :size="18" />
                        </button>
                    </div>
                </div>

                <a href="#" class="link-olvide">¿Olvidaste tu contraseña?</a>

                <p v-if="error" class="mensaje-error">{{ error }}</p>

                <button type="submit" class="btn-login" :disabled="cargando">
                    {{ cargando ? 'Iniciando sesión...' : 'Iniciar sesión' }} →
                </button>

            </form>

            <div class="separador">o continúa con</div>

            <div class="botones-sociales">
                <button class="btn-social" type="button">G &nbsp; Google</button>
                <button class="btn-social" type="button">⊞ &nbsp; Microsoft</button>
            </div>

            <p class="link-registro">
                ¿No tienes cuenta?
                <RouterLink to="/registro">Regístrate aquí</RouterLink>
            </p>
        </div>

    </div>
</template>

<script>
import { useAuthStore } from '../../stores/auth'
import api from '../../services/api'
import {
    Mail,        // ícono correo
    Lock,        // ícono contraseña
    Eye,         // ícono ver contraseña
    EyeOff,      // ícono ocultar contraseña
    GraduationCap, // ícono aprendiz
    Users,       // ícono instructor
    Shield       // ícono admin
} from 'lucide-vue-next'

export default {
    name: 'Login',

    // Registramos los íconos como componentes
    components: {
        Mail, Lock, Eye, EyeOff, GraduationCap, Users, Shield
    },

    data() {
        return {
            roles: [
                { valor: 'estudiante', etiqueta: 'Aprendiz', icono: 'GraduationCap' },
                { valor: 'instructor', etiqueta: 'Instructor', icono: 'Users' },
                { valor: 'administrador', etiqueta: 'Admin', icono: 'Shield' }
            ],
            rolSeleccionado: 'estudiante',
            form: {
                correo: '',
                contrasena: ''
            },
            cargando: false,
            error: '',
            mostrarContrasena: false
        }
    },

    methods: {
        async iniciarSesion() {
            this.error = ''
            this.cargando = true

            try {
                const respuesta = await api.post('/login', {
                    email: this.form.correo,
                    password: this.form.contrasena,
                    rol: this.rolSeleccionado
                })

                const authStore = useAuthStore()
                authStore.iniciarSesion(respuesta.data.token, respuesta.data.usuario)

                if (this.rolSeleccionado === 'administrador') {
                    this.$router.push('/admin/dashboard')
                } else {
                    this.$router.push('/login')
                }

            } catch (err) {
                if (err.response?.status === 401) {
                    this.error = 'Correo, contraseña o rol incorrecto.'
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
/* Importar fuente Puritan de Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Puritan:ital,wght@0,400;0,700;1,400;1,700&display=swap');

/* Aplicar fuente a toda la vista */

.login-page {
    font-family: 'Puritan', sans-serif;
    display: flex;
    min-height: 100vh;
    background-color: #0f1117;
    color: #ffffff;
}


/* ===== LAYOUT PRINCIPAL ===== */
.login-page {
    display: flex;
    min-height: 100vh;
    background-color: #0f1117;
    color: #ffffff;
}

/* ===== COLUMNA IZQUIERDA ===== */
.login-info {
    flex: 1;
    padding: 48px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background-color: #0f1117;
}

.login-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 2rem;
    font-weight: 700;
    color: #ffffff;
}

.login-slogan {
    font-size: 0.9rem;
    color: #8b8fa8;
    margin-top: 4px;
}

.login-hero-title {
    font-size: 2.2rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 32px 0 16px;
    color: #ffffff;
}

.login-hero-title span {
    color: #7c3aed;
}

.login-description {
    font-size: 0.95rem;
    color: #8b8fa8;
    line-height: 1.6;
    max-width: 340px;
}

/* ===== GRID DE FEATURES ===== */
.login-features {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    list-style: none;
    padding: 0;
    margin: 32px 0;
}

.feature-card {
    background-color: #161a24;
    border: 1px solid #1e2235;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.feature-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    /* background-color: red; */
}

.feature-card h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #ffffff;
    margin: 0;
}

.feature-card p {
    font-size: 0.8rem;
    color: #8b8fa8;
    margin: 0;
    line-height: 1.4;
}

/* ===== COLUMNA DERECHA ===== */
.login-form-container {
    width: 560px;
    background-color: #161a24;
    padding: 48px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.login-form-container h2 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 4px;
    color: #ffffff;
}

.login-form-container h2 span {
    color: #7c3aed;
}

.login-subtitle {
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
    background-color: #0f1117;
    color: #8b8fa8;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
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

/* ===== CAMPOS DEL FORMULARIO ===== */
.campo {
    margin-bottom: 20px;
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
    font-size: 1rem;
    color: #8b8fa8;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 14px 14px 42px;
    background-color: #0f1117;
    border: 1px solid #1e2235;
    border-radius: 10px;
    color: #ffffff;
    font-size: 0.95rem;
    outline: none;
    box-sizing: border-box;
    transition: border-color 0.2s ease;
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
    font-size: 1rem;
    padding: 0;
}

/* ===== LINK OLVIDÉ CONTRASEÑA ===== */
.link-olvide {
    display: block;
    text-align: right;
    color: #7c3aed;
    font-size: 0.85rem;
    text-decoration: none;
    margin-bottom: 24px;
}

.link-olvide:hover {
    text-decoration: underline;
}

/* ===== MENSAJE DE ERROR ===== */
.mensaje-error {
    background-color: rgba(220, 38, 38, 0.1);
    border: 1px solid rgba(220, 38, 38, 0.3);
    color: #f87171;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

/* ===== BOTÓN INICIAR SESIÓN ===== */
.btn-login {
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
}

.btn-login:hover {
    background-color: #6d28d9;
}

.btn-login:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* ===== SEPARADOR ===== */
.separador {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #4a4f6a;
    font-size: 0.85rem;
    margin-bottom: 24px;
}

.separador::before,
.separador::after {
    content: '';
    flex: 1;
    height: 1px;
    background-color: #1e2235;
}

/* ===== BOTONES SOCIALES ===== */
.botones-sociales {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 32px;
}

.btn-social {
    padding: 12px;
    background-color: #0f1117;
    border: 1px solid #1e2235;
    border-radius: 10px;
    color: #ffffff;
    font-size: 0.9rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: border-color 0.2s ease;
}

.btn-social:hover {
    border-color: #7c3aed;
}

/* ===== LINK REGISTRO ===== */
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

/* ===== FOOTER ===== */
.login-footer {
    text-align: center;
    font-size: 0.8rem;
    color: #4a4f6a;
    padding-top: 24px;
    border-top: 1px solid #1e2235;
    margin-top: auto;
}

</style>