<template>
    <aside class="sidebar" :class="{ abierto }">

        <!-- Cabecera del modo: solo para estudiantes, el instructor no tiene modos -->
        <div v-if="esEstudiante" class="modo-header" :title="modoActual.subtitulo">
            <span class="modo-icono">
                <component :is="modoActual.icono" :size="18" />
            </span>
            <strong>{{ modoActual.titulo }}</strong>
            <span class="modo-badge">{{ modoActual.badge }}</span>
        </div>

        <nav aria-label="Navegación principal">
            <ul>
                <li v-for="item in itemsMenu" :key="item.etiqueta">

                    <RouterLink
                        v-if="item.ruta"
                        :to="item.ruta"
                        class="sidebar-link"
                        active-class="activo"
                        :title="item.descripcion"
                        @click="$emit('cerrar')"
                    >
                        <component :is="item.icono" :size="20" />
                        <span>{{ item.etiqueta }}</span>
                    </RouterLink>

                    <span v-else class="sidebar-link deshabilitado" :title="item.descripcion + ' (Próximamente)'">
                        <component :is="item.icono" :size="20" />
                        <span>{{ item.etiqueta }}</span>
                    </span>

                </li>
            </ul>
        </nav>
    </aside>
</template>

<script setup>
import { computed } from 'vue'
import { Home, BookOpen, Rocket, Bot, History, User, Users, Upload } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useModoStore } from '@/stores/modo'

defineProps({ abierto: Boolean })
defineEmits(['cerrar'])

const authStore = useAuthStore()
const modoStore = useModoStore()

const esEstudiante = computed(() => authStore.rolUsuario !== 'instructor')

// Menú del Modo Grupo (según el boceto del equipo)
const menuGrupo = [
    { etiqueta: 'Inicio', icono: Home, ruta: '/estudiante/dashboard', descripcion: 'Resumen del progreso y actividades recientes.' },
    { etiqueta: 'Mis grupos', icono: Users, ruta: null, descripcion: 'Lista de los grupos en los que estás inscrito.' },
    { etiqueta: 'Mini proyectos', icono: Rocket, ruta: null, descripcion: 'Retos prácticos vinculados al aprendizaje, con niveles progresivos.' },
    { etiqueta: 'IA', icono: Bot, ruta: null, descripcion: 'Recomendaciones y chat guiado sobre las guías del grupo.' },
    { etiqueta: 'Historial · Perfil', icono: History, ruta: null, descripcion: 'Actividad anterior y datos de la cuenta.' }
]

// Menú del Modo Personal
const menuPersonal = [
    { etiqueta: 'Inicio', icono: Home, ruta: '/estudiante/dashboard', descripcion: 'Resumen del aprendizaje personal.' },
    { etiqueta: 'Mis guías', icono: BookOpen, ruta: null, descripcion: 'Guías que tú has cargado, organizadas por temas.' },
    { etiqueta: 'Mini proyectos', icono: Rocket, ruta: null, descripcion: 'Proyectos prácticos adecuados a tu nivel.' },
    { etiqueta: 'IA', icono: Bot, ruta: null, descripcion: 'Recomendaciones sobre tus guías y chat libre para resolver dudas.' },
    { etiqueta: 'Historial · Perfil', icono: History, ruta: null, descripcion: 'Actividad personal anterior y datos de la cuenta.' }
]

// El instructor no tiene modos, así que su menú no cambia
const menuInstructor = [
    { etiqueta: 'Inicio', icono: Home, ruta: null, descripcion: 'Resumen de tus grupos.' },
    { etiqueta: 'Mis Grupos', icono: Users, ruta: null, descripcion: 'Grupos que administras.' },
    { etiqueta: 'Subir Guía', icono: Upload, ruta: null, descripcion: 'Carga guías en PDF para tus grupos.' },
    { etiqueta: 'Perfil', icono: User, ruta: null, descripcion: 'Datos de tu cuenta.' }
]

const itemsMenu = computed(() => {
    if (!esEstudiante.value) return menuInstructor
    return modoStore.esModoPersonal ? menuPersonal : menuGrupo
})

const modoActual = computed(() =>
    modoStore.esModoPersonal
        ? { titulo: 'Modo Personal', subtitulo: 'Aprendizaje autónomo', badge: 'Individual', icono: User }
        : { titulo: 'Modo Grupo', subtitulo: 'Aprendizaje guiado por el instructor', badge: 'Grupal', icono: Users }
)
</script>

<style scoped>
.sidebar {
    width: 240px;
    flex-shrink: 0;
    padding: 1.2rem .8rem;
    background: var(--color-surface);
    border-right: 1px solid var(--color-card);
    font-family: Puritan;
    overflow-y: auto;
}

ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: .3rem;
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: .75rem 1rem;
    border-radius: 12px;
    color: var(--color-text-secondary);
    text-decoration: none;
    font-size: .95rem;
    transition: .2s;
}

.sidebar-link:hover:not(.deshabilitado) {
    background: var(--color-background);
    color: var(--color-text);
}

.sidebar-link.activo {
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-light));
    color: white;
}

.sidebar-link.deshabilitado {
    opacity: .45;
    cursor: not-allowed;
}

.modo-header {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .6rem .8rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--color-card);
}

.modo-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: var(--color-primary);
    color: white;
    flex-shrink: 0;
}

.modo-header strong {
    font-size: .9rem;
    color: var(--color-text);
}

.modo-badge {
    margin-left: auto;
    font-size: .65rem;
    padding: .15rem .5rem;
    border-radius: 999px;
    background: rgba(124, 58, 237, .15);
    color: var(--color-primary);
    font-weight: 600;
}

/* CELULAR Y TABLET: el menú sale de la pantalla y se desliza al abrirlo */
@media (max-width: 900px) {
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        z-index: 20;
        transform: translateX(-100%);
        transition: transform .25s ease;
    }

    .sidebar.abierto {
        transform: translateX(0);
    }
}
</style>