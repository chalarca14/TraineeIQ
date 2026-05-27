import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Definimos todas las rutas de la aplicacion
const routes = [
  // Ruta raiz — redirige al login
  {
    path: '/',
    redirect: '/login'
  },

  // Rutas de autenticacion — accesibles sin token
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

  // Rutas del administrador — solo rol administrador
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: () => import('../views/admin/Dashboard.vue'),
    meta: { requiereAuth: true, rol: 'administrador' }
  },
  {
    path: '/admin/usuarios',
    name: 'GestionUsuarios',
    component: () => import('../views/admin/GestionUsuarios.vue'),
    meta: { requiereAuth: true, rol: 'administrador' }
  },

  // Ruta para paginas no encontradas
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login'
  }
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