<script setup>
import { ref, computed } from 'vue'
import { User, Settings, LogOut } from 'lucide-vue-next'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import Pestanas from '@/components/ui/Pestanas.vue'
import PerfilCabecera from '@/components/perfil/PerfilCabecera.vue'
import DatosPersonales from '@/components/perfil/DatosPersonales.vue'
import CambiarPasswordForm from '@/components/perfil/CambiarPasswordForm.vue'
import { useAuthStore } from '@/stores/auth'
import { useCerrarSesion } from '@/composables/useCerrarSesion'
import { cambiarPassword, actualizarNombre } from '@/services/usuarioService'
import { mensajeDeErrorPassword, mensajeDeErrorCuenta } from '@/utils/errores'

const authStore = useAuthStore()
const { cerrarSesion } = useCerrarSesion()

// Los datos de la cuenta ya están en el store desde el login: esta vista no llama al backend para
// leerlos. Por eso no hay estado "cargando"; sí "vacío" por si el store llegara sin usuario.
const usuario = computed(() => authStore.usuario)

const PESTANAS = [
  { clave: 'info', etiqueta: 'Información personal', icono: User },
  { clave: 'config', etiqueta: 'Configuración', icono: Settings }
]
const pestana = ref('info')

// --- Cambio de nombre ---
const guardandoNombre = ref(false)
const errorNombre = ref('')
const exitoNombre = ref('')

async function alGuardarNombre(nombre) {
  guardandoNombre.value = true
  errorNombre.value = ''
  exitoNombre.value = ''
  try {
    const actualizado = await actualizarNombre(nombre)
    // Se guarda en el store: la cabecera del perfil y la NavBar (nombre e iniciales) cambian solas
    authStore.actualizarUsuario({ name: actualizado?.name ?? nombre })
    exitoNombre.value = 'Tu nombre se actualizó correctamente.'
  } catch (err) {
    errorNombre.value = mensajeDeErrorCuenta(err)
  } finally {
    guardandoNombre.value = false
  }
}

// --- Cambio de contraseña ---
const formulario = ref(null)
const enviando = ref(false)
const errorCambio = ref('')
const exito = ref('')

async function alCambiar({ actual, nueva }) {
  enviando.value = true
  errorCambio.value = ''
  exito.value = ''
  try {
    await cambiarPassword(actual, nueva)
    exito.value = 'Tu contraseña se actualizó correctamente.'
    formulario.value?.limpiar() // no dejar las contraseñas escritas en pantalla
  } catch (err) {
    errorCambio.value = mensajeDeErrorPassword(err)
  } finally {
    enviando.value = false
  }
}
</script>

<template>
  <main class="perfil">
    <EstadoVista :vacio="!usuario" :icono="User" texto-vacio="No pudimos cargar tu perfil."
      descripcion-vacio="Cierra sesión y vuelve a entrar.">
      <PerfilCabecera :nombre="usuario?.name" :correo="usuario?.email" :rol="usuario?.rol"
        :miembro-desde="usuario?.created_at" />

      <Pestanas v-model="pestana" :pestanas="PESTANAS" />

      <DatosPersonales v-if="pestana === 'info'" :nombre="usuario?.name" :correo="usuario?.email"
        :rol="usuario?.rol" :guardando="guardandoNombre" :error="errorNombre" :exito="exitoNombre"
        @guardar="alGuardarNombre" />

      <template v-else>
        <CambiarPasswordForm ref="formulario" :enviando="enviando" :error-externo="errorCambio" :exito="exito"
          @cambiar="alCambiar" />

        <section class="sesion">
          <h2><LogOut :size="20" /> Sesión</h2>
          <p>Cierra tu sesión en este dispositivo.</p>
          <button type="button" class="btn btn-secundario" @click="cerrarSesion">Cerrar sesión</button>
        </section>
      </template>
    </EstadoVista>
  </main>
</template>

<style scoped>
.perfil {
  display: flex;
  flex-direction: column;
  gap: 1.2rem;
  max-width: 900px;
  margin: 0 auto;
  padding: clamp(1rem, 4vw, 2.5rem);
}

.sesion {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: .6rem;
  padding: 1.2rem;
  border: 1px solid var(--color-border);
  border-radius: 16px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-card) 55%, var(--color-surface)), var(--color-surface));
}

h2 {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: 1.05rem;
}

h2 svg {
  color: var(--color-primary-light);
}

.sesion p {
  color: var(--color-text-secondary);
  font-size: .9rem;
}
</style>
