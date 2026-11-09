import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'


// Definimos todas las rutas de la aplicacion
const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue')
  },
  {
    path: '/registro',
    name: 'Registro',
    component: () => import('../views/auth/Registro.vue')
  },

  // Ruta dashboard para el rol de estudiante
  {
    path: '/estudiante',
    component: () => import('../layouts/AppLayout.vue'),
    redirect: '/estudiante/dashboard',
    meta: {
      requiereAuth: true,
      rol: 'estudiante'
    },
    children: [
      {
        path: 'dashboard',
        name: 'EstudianteDashboard',
        component: () => import('../views/estudiante/Dashboard.vue')
      },
      // Aquí se irán sumando mi-guia, mini-proyectos, perfil...

      // Chat IA: libre por defecto, guiado si llega ?guiaId=N
      {
        path: 'ia',
        name: 'ChatIA',
        component: () => import('../views/estudiante/ChatIA.vue')
      },
      // Reabrir una conversación guardada (la lista del panel lateral enlaza aquí)
      {
        path: 'ia/:conversacionId',
        name: 'ChatIAConversacion',
        component: () => import('../views/estudiante/ChatIA.vue')
      },


      // Mis grupos: lista de grupos del aprendiz y unirse con código (el detalle llega en la Tarea 8)
      {
        path: 'grupos',
        name: 'MisGrupos',
        component: () => import('../views/estudiante/MisGrupos.vue')
      },

      {
        path: 'guias',
        name: 'MisGuias',
        component: () => import('../views/personal/MisGuias.vue')
      },
      {
        path: 'guias/:id',
        name: 'GuiaDetalle', component: () => import('../views/personal/GuiaDetalle.vue')
      },

      // Lista de mini proyectos y su detalle (Descripción + Evaluación)
      {
        path: 'mini-proyectos',
        name: 'MiniProyectos',
        component: () => import('../views/estudiante/MiniProyectos.vue')
      },
      {
        path: 'mini-proyectos/:id',
        name: 'MiniProyectoDetalle',
        component: () => import('../views/estudiante/MiniProyectoDetalle.vue')
      },

      // Historial de actividad (conversaciones con la IA y recomendaciones)
      {
        path: 'historial',
        name: 'Historial',
        component: () => import('../views/estudiante/Historial.vue')
      },

      // Perfil: datos de la cuenta, cambio de contraseña y cerrar sesión
      {
        path: 'perfil',
        name: 'Perfil',
        component: () => import('../views/estudiante/Perfil.vue')
      }

    ]
  },

  {
    path: '/instructor',
    component: () => import('../layouts/AppLayout.vue'),
    redirect: '/instructor/dashboard',
    meta: {
      requiereAuth: true,
      rol: 'instructor'
    },
    children: [
      {
        path: 'dashboard',
        name: 'InstructorDashboard',
        component: () => import('../views/instructor/Dashboard.vue')
      }
    ]
  },

  
  // Ruta para paginas no encontradas
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  },
]

// Creamos el router con el historial del navegador
const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guard de navegacion — se ejecuta antes de entrar a cualquier ruta
// Su trabajo es verificar autenticacion y rol del usuario
router.beforeEach((to, from, next) => {
  // Obtenemos el store de autenticacion
  const authStore = useAuthStore()

  // Si la ruta requiere autenticacion y no hay token, vamos al login
  if (to.meta.requiereAuth && !authStore.estaAutenticado) {
    next('/login')
    return
  }

  // Si la ruta requiere un rol especifico y el usuario no lo tiene
  if (to.meta.rol && authStore.rolUsuario !== to.meta.rol) {
    next('/login')
    return
  }

  // Todo bien, dejamos pasar
  next()
})

export default router