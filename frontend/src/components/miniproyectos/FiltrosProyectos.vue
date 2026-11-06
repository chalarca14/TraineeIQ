<template>
    <div>
        <Pestanas v-model="pestana" :pestanas="PESTANAS" compacto class="pestanas" />

        <div class="herramientas">
            <label class="buscador">
                <Search :size="16" />
                <input v-model="busqueda" type="search" placeholder="Buscar proyecto..." />
            </label>
            <select v-model="orden" aria-label="Ordenar proyectos"
                title="Por tema: agrupados por tema, de básico a avanzado">
                <option value="tema">Por tema</option>
                <option value="recientes">Más recientes</option>
                <option value="antiguos">Más antiguos</option>
            </select>
        </div>
    </div>
</template>

<script setup>
import { Search } from 'lucide-vue-next'
import Pestanas from '@/components/ui/Pestanas.vue'

// defineModel permite usar v-model:pestana, v-model:busqueda y v-model:orden desde la vista.
// Así el estado vive en la vista (que es quien filtra la lista) y este componente solo lo edita.
const pestana = defineModel('pestana', { type: String, required: true })
const busqueda = defineModel('busqueda', { type: String, default: '' })
const orden = defineModel('orden', { type: String, default: 'tema' })

const PESTANAS = [
    { clave: 'todos', etiqueta: 'Todos' },
    { clave: 'en_progreso', etiqueta: 'En progreso' },
    { clave: 'completado', etiqueta: 'Completados' },
    { clave: 'por_iniciar', etiqueta: 'Por iniciar' }
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

/* min-width: 0 es la clave: sin él, un elemento flex no se encoge por debajo de su contenido
   y el selector se salía de la tarjeta en pantallas de 360 px */
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

.buscador input,
select {
    border: none;
    outline: none;
    background: transparent;
    color: var(--color-text);
}

.buscador input {
    flex: 1;
    min-width: 0;
    padding: .6rem 0;
}

select {
    flex-shrink: 0;
    min-width: 0;
    max-width: 45%;
    padding: 0 .8rem;
    border: 1px solid var(--color-border);
    border-radius: 10px;
    background: var(--color-surface);
}
</style>
