<template>
    <div>
        <Pestanas v-model="pestana" :pestanas="PESTANAS" compacto class="pestanas" />

        <div class="herramientas">
            <label class="buscador">
                <Search :size="16" />
                <input v-model="busqueda" type="search" placeholder="Buscar proyecto..." aria-label="Buscar proyecto" />
            </label>
        </div>
    </div>
</template>

<script setup>
import { Search } from 'lucide-vue-next'
import Pestanas from '@/components/ui/Pestanas.vue'

// defineModel permite usar v-model:pestana y v-model:busqueda desde la vista.
// Así el estado vive en la vista (que es quien filtra la lista) y este componente solo lo edita.
// Ya no hay selector de orden: los proyectos salen siempre por semana, que es su orden natural.
const pestana = defineModel('pestana', { type: String, required: true })
const busqueda = defineModel('busqueda', { type: String, default: '' })

// Se calculan con el estado de la ENTREGA (ver utils/estadoEntrega.js)
const PESTANAS = [
    { clave: 'todos', etiqueta: 'Todos' },
    { clave: 'por_entregar', etiqueta: 'Por entregar' },
    { clave: 'evaluando', etiqueta: 'Evaluando' },
    { clave: 'evaluado', etiqueta: 'Evaluados' }
]
</script>

<style scoped>
.pestanas {
    margin-bottom: 1rem;
}

.herramientas {
    display: flex;
    gap: .6rem;
    margin-bottom: 1rem;
}

/* min-width: 0 es la clave: sin él, un elemento flex no se encoge por debajo de su contenido */
.buscador {
    display: flex;
    flex: 1;
    min-width: 0;
    align-items: center;
    gap: .5rem;
    padding: 0 .8rem;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    background: var(--color-surface);
    color: var(--color-text-secondary);
}

.buscador input {
    flex: 1;
    min-width: 0;
    padding: .6rem 0;
    border: none;
    outline: none;
    background: transparent;
    color: var(--color-text);
}
</style>
