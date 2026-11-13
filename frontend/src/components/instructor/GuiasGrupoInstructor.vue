<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { BookOpen, FileText, Plus, Eye } from 'lucide-vue-next'
import PanelTarjeta from '@/components/ui/PanelTarjeta.vue'
import EstadoVista from '@/components/ui/EstadoVista.vue'
import EsqueletoLista from '@/components/ui/EsqueletoLista.vue'
import AsignarGuiaModal from './AsignarGuiaModal.vue'
import FiltrosGuias from './FiltrosGuias.vue'
import { listarGuiasDeGrupo } from '@/services/guiaService'
import { formatearFecha } from '@/utils/fechas'

// Pestaña "Guías" del grupo (instructor, Tarea I-3). Se piden TODAS las páginas de GET /guias?grupo_id=
// (no "Cargar más"): el buscador y el orden deben ver todas las guías, no solo las primeras 15.
// Sin "Descargar" ni tamaño en MB (no se guarda el PDF), ni filtro "Tipo de guía" (no existe ese dato).
const props = defineProps({ grupoId: { type: Number, required: true } })
const router = useRouter()

const guias = ref([])
const cargando = ref(true)
const error = ref('')
const busqueda = ref('')
const orden = ref('recientes')
const asignando = ref(false)
// Guía que ya estaba subida (respuesta 200 ya_existia): se avisa y se ofrece abrirla
const repetida = ref(null)

const ORDENES = {
  recientes: (a, b) => new Date(b.created_at) - new Date(a.created_at),
  nombre: (a, b) => a.nombre.localeCompare(b.nombre, 'es'),
  semanas: (a, b) => (b.semanas_totales ?? 0) - (a.semanas_totales ?? 0)
}

async function cargar() {
  cargando.value = true
  error.value = ''
  try {
    guias.value = await listarGuiasDeGrupo(props.grupoId)
  } catch {
    error.value = 'No se pudieron cargar las guías del grupo. Intenta de nuevo.'
  } finally {
    cargando.value = false
  }
}

const visibles = computed(() => {
  const texto = busqueda.value.trim().toLowerCase()
  const lista = texto ? guias.value.filter((g) => g.nombre.toLowerCase().includes(texto)) : [...guias.value]
  return lista.sort(ORDENES[orden.value])
})

const rutaGuia = (guia) => ({ name: 'InstructorGuiaSemanas', params: { grupoId: props.grupoId, guiaId: guia.id } })

// 201: se abre la guía nueva; allí la cola genera sus semanas una por una y se ve el avance.
// 200 (ya existía): no se duplica, solo se avisa
function alSubir({ guia, yaExistia }) {
  asignando.value = false
  if (yaExistia) {
    repetida.value = guia
    return
  }
  router.push(rutaGuia(guia))
}

onMounted(cargar)
</script>

<template>
  <div class="guias-grupo">
    <PanelTarjeta titulo="Guías de aprendizaje" :icono="BookOpen" class="principal">
      <template #accion>
        <button type="button" class="btn btn-primario btn-sm" @click="asignando = true; repetida = null">
          <Plus :size="16" aria-hidden="true" /> Asignar guía
        </button>
      </template>
      <p class="ayuda">Las guías de este grupo. Ábrelas para ver sus semanas, temas y mini proyectos.</p>

      <div v-if="repetida" class="aviso" role="status">
        <span>Esta guía ya estaba subida en el grupo: «{{ repetida.nombre }}». No se duplicó.</span>
        <RouterLink :to="rutaGuia(repetida)" class="btn btn-secundario btn-sm">Abrir la guía</RouterLink>
      </div>

      <EstadoVista :cargando="cargando" :error="error" :vacio="!guias.length" :icono="BookOpen"
        texto-cargando="Cargando las guías del grupo…" texto-vacio="Este grupo aún no tiene guías"
        descripcion-vacio="Asigna la primera: sube el PDF y la IA preparará sus semanas." @reintentar="cargar">
        <template #cargando><EsqueletoLista :filas="3" /></template>
        <template #vacio>
          <button type="button" class="btn btn-primario btn-sm" @click="asignando = true">
            <Plus :size="16" aria-hidden="true" /> Asignar la primera guía
          </button>
        </template>

        <p v-if="!visibles.length" class="vacio">Ninguna guía coincide con “{{ busqueda }}”.</p>
        <ul v-else class="lista">
          <li v-for="g in visibles" :key="g.id" class="guia">
            <span class="guia__icono" aria-hidden="true"><FileText :size="24" /></span>
            <div class="guia__datos">
              <strong>{{ g.nombre }}</strong>
              <span>
                {{ g.temas?.length ?? 0 }} {{ g.temas?.length === 1 ? 'tema' : 'temas' }} ·
                {{ g.semanas_totales }} {{ g.semanas_totales === 1 ? 'semana' : 'semanas' }}
              </span>
              <span v-if="g.created_at">Asignada el {{ formatearFecha(g.created_at) }}</span>
            </div>
            <RouterLink :to="rutaGuia(g)" class="btn btn-primario btn-sm">
              <Eye :size="16" aria-hidden="true" /> Ver guía
            </RouterLink>
          </li>
        </ul>
      </EstadoVista>
    </PanelTarjeta>

    <FiltrosGuias v-model:busqueda="busqueda" v-model:orden="orden" class="lateral"
      :total="cargando ? null : guias.length" />

    <AsignarGuiaModal v-if="asignando" :grupo-id="grupoId" @cerrar="asignando = false" @subida="alSubir" />
  </div>
</template>

<style scoped>
.guias-grupo {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 280px;
  gap: var(--espacio-md);
  align-items: start;
}

/* Tablet y celular: el buscador y el orden pasan arriba de la lista */
@media (max-width: 900px) {
  .guias-grupo {
    grid-template-columns: minmax(0, 1fr);
  }

  .lateral {
    order: -1;
  }
}

.ayuda,
.vacio {
  color: var(--color-text-secondary);
  font-size: .9rem;
}

.aviso {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: var(--espacio-sm);
  padding: var(--espacio-sm) var(--espacio-md);
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-warning) 12%, transparent);
  color: var(--color-warning);
  font-size: .88rem;
}

.lista {
  display: flex;
  flex-direction: column;
  gap: var(--espacio-sm);
  margin: 0;
  padding: 0;
  list-style: none;
}

.guia {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--espacio-md);
  padding: var(--espacio-md);
  border: 1px solid var(--color-card);
  border-radius: 14px;
  background: var(--color-surface);
}

.guia__icono {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: linear-gradient(145deg, color-mix(in srgb, var(--color-primary) 40%, var(--color-card)), color-mix(in srgb, var(--color-primary) 12%, var(--color-surface)));
  color: var(--color-primary-light);
}

.guia__datos {
  display: flex;
  flex: 1 1 180px;
  flex-direction: column;
  gap: .15rem;
  min-width: 0;
}

.guia__datos strong {
  overflow-wrap: anywhere;
}

.guia__datos span {
  color: var(--color-text-secondary);
  font-size: .82rem;
}

</style>
