<template>
    <div class="role-selector" role="radiogroup" aria-label="Selecciona tu rol">
        <label v-for="rol in roles" :key="rol.valor" class="role-card" :class="{ activo: modelValue === rol.valor }">
            <input type="radio" name="rol" :value="rol.valor" :checked="modelValue === rol.valor"
                @change="$emit('update:modelValue', rol.valor)" class="sr-only" />
            <component :is="rol.icono" :size="28" class="role-icon" />
            <h3>{{ rol.titulo }}</h3>
            <p>{{ rol.descripcion }}</p>
        </label>
    </div>
</template>

<script setup>
import { GraduationCap, Users } from 'lucide-vue-next'

// v-model personalizado: modelValue entra, update:modelValue sale.
// Así RegistroForm puede hacer <RoleCardSelector v-model="rolSeleccionado" />
defineProps({ modelValue: String })
defineEmits(['update:modelValue'])

const roles = [
    {
        valor: 'estudiante',
        titulo: 'Estudiante',
        icono: GraduationCap,
        descripcion: 'Accede a guías, recibe recomendaciones y realiza mini proyectos.'
    },
    {
        valor: 'instructor',
        titulo: 'Instructor',
        icono: Users,
        descripcion: 'Crea grupos, comparte guías y realiza seguimiento al progreso de estudiantes.'
    }
    // Nunca agregar 'administrador' aquí — ese rol está eliminado del sistema (Documento Maestro v3)
]
</script>

<style scoped>
.role-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .8rem;
}

.role-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: .4rem;
    padding: clamp(.8rem, 2vh, 1.2rem);
    border: 1px solid var(--color-card);
    border-radius: 14px;
    cursor: pointer;
    transition: .2s;
    background: var(--color-background);
}

.role-card:hover {
    border-color: var(--color-primary);
}

.role-card.activo {
    border-color: var(--color-primary);
    background: rgba(124, 58, 237, .08);
    box-shadow: 0 0 0 1px var(--color-primary);
}

.role-icon {
    color: var(--color-primary);
}

.role-card h3 {
    margin: 0;
    font-size: .95rem;
    color: var(--color-text);
}

.role-card p {
    margin: 0;
    font-size: .75rem;
    color: var(--color-text-secondary);
    line-height: 1.4;
}

/* Oculta visualmente el radio, pero sigue siendo funcional
   para teclado (Tab + flechas) y lectores de pantalla */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* @media (max-width: 480px) {
    .role-selector {
        grid-template-columns: 1fr;
    }
} */

@media (max-width: 700px) {
    .role-selector {
        grid-template-columns: 1fr;
    }
}
</style>