import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useModoStore } from '../stores/modo'


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

  // "¿Olvidaste tu contraseña?": públicas (no hay sesión). /restablecer llega desde el enlace del correo
  {
    path: '/recuperar',
    name: 'Recuperar',
    component: () => import('../views/auth/Recuperar.vue')
  },
  {
    path: '/restablecer',
    name: 'Restablecer',
    component: () => import('../views/auth/Restablecer.vue')
  },

  // Páginas legales públicas: las enlaza el registro y las pide Google para publicar "Continuar con Google"
  {
    path: '/privacidad',
    name: 'Privacidad',
    component: () => import('../views/legal/Privacidad.vue')
  },
  {
    path: '/terminos',
    name: 'Terminos',
    component: () => import('../views/legal/Terminos.vue')
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


      // Mis grupos: lista de grupos del aprendiz y unirse con código
      {
        path: 'grupos',
        name: 'MisGrupos',
        meta: { modo: 'grupo' },
        component: () => import('../views/estudiante/MisGrupos.vue')
      },
      // Detalle de un grupo: cabecera, tarjetas y pestañas Guías / Proyectos / Mi progreso.
      // El sidebar resalta "Mis grupos" solo, porque la ruta empieza por /estudiante/grupos/
      {
        path: 'grupos/:id',
        name: 'GrupoDetalle',
        meta: { modo: 'grupo' },
        component: () => import('../views/estudiante/GrupoDetalle.vue')
      },

      {
        path: 'guias',
        name: 'MisGuias',
        meta: { modo: 'personal' },
        component: () => import('../views/personal/MisGuias.vue')
      },
      // La guía por semanas: UNA sola vista para la guía personal y la de un grupo
      {
        path: 'guias/:id',
        name: 'GuiaDetalle',
        meta: { modo: 'personal' },
        component: () => import('../views/estudiante/GuiaSemanas.vue')
      },
      {
        path: 'grupos/:grupoId/guias/:guiaId',
        name: 'GrupoGuiaSemanas',
        meta: { modo: 'grupo' },
        component: () => import('../views/estudiante/GuiaSemanas.vue')
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

      // Historial: solo las conversaciones con la IA (libres y guiadas)
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
      },
      // Tarea I-1: grupos del instructor (crear, copiar código, eliminar)
      {
        path: 'grupos',
        name: 'InstructorMisGrupos',
        component: () => import('../views/instructor/MisGrupos.vue')
      }
    ]
  },

  // PRUEBA-BACKEND: inicio. Borrar este bloque cuando exista la vista definitiva
  {
    path: '/prueba',
    component: () => import('../layouts/AppLayout.vue'),
    meta: {
      requiereAuth: true
    },
    children: [
      {
        path: 'guias',
        name: 'GuiasPrueba',
        component: () => import('../views/prueba/GuiasPrueba.vue')
      },
      {
        path: 'guias/:id',
        name: 'GuiaSemanasPrueba',
        component: () => import('../views/prueba/GuiaSemanasPrueba.vue')
      },
      {
        path: 'mini-proyectos/:id',
        name: 'MiniProyectoPrueba',
        component: () => import('../views/prueba/MiniProyectoPrueba.vue')
      },
      {
        path: 'grupos/:id/entregas',
        name: 'EntregasGrupoPrueba',
        component: () => import('../views/prueba/EntregasGrupoPrueba.vue')
      },
      {
        path: 'notificaciones',
        name: 'NotificacionesPrueba',
        component: () => import('../views/prueba/NotificacionesPrueba.vue')
      },
      {
        path: 'esqueletos',
        name: 'EsqueletosPrueba',
        component: () => import('../views/prueba/EsqueletosPrueba.vue')
      }
    ]
  },
  // PRUEBA-BACKEND: fin

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

// Algunas pantallas solo existen en un modo (meta.modo): las de grupos son del Modo Grupo y Mis guías,
// del Modo Personal. Si se llega a una por URL directa o por un enlace, el modo guardado puede ser el
// otro y la barra lateral mostraría el menú equivocado. Por eso, al entrar, se pone el modo de la
// pantalla; es lo mismo que haría el aprendiz con el interruptor.
router.afterEach((to) => {
  if (!to.meta.modo) return
  const modoStore = useModoStore()
  if (modoStore.modoActivo !== to.meta.modo) modoStore.cambiarModo(to.meta.modo)
})

export default router