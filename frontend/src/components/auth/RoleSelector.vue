<template>

    <div class="role-selector">

        <button v-for="role in roles" :key="role.name" type="button" :class="{ active: selectedRole === role.name }"
            @click="selectedRole = role.name">

            <component :is="role.icon" :size="18" :stroke-width="1.8" />

            <span>{{ role.name }}</span>

        </button>

    </div>

</template>

<script setup>

import { ref } from 'vue'
import { GraduationCap, Users } from 'lucide-vue-next'

const selectedRole = ref('Aprendiz')

const roles = [
    {
        name: 'Aprendiz',
        icon: GraduationCap
    },
    {
        name: 'Instructor',
        icon: Users
    }
]

</script>

<style scoped>
.role-selector {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: .8rem;
}

.role-selector button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .75rem .8rem;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    color: var(--color-text-secondary);
    font-family: inherit;
    font-size: .9rem;
    cursor: pointer;
    transition: .25s;
}

.role-selector button:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.role-selector button.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}

@media (min-width: 413px) {
    .role-selector{
        margin-bottom: 12px;
    }
}

@media (max-width: 700px){
    .role-selector{
        margin-bottom: 17px;
        margin-right: 3px;
    }
}
</style>