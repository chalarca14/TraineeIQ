<template>

    <div class="role-selector">

        <button class="role-btn-active">
            Aprendiz
        </button>

        <button class="role-btn">
            Instructor
        </button>

        <button class="role-btn">
            Admin
        </button>
    </div>

</template>

<script setup>
</script>

<style scoped>

    .role-selector{
        display: flex;
        gap: .8rem;
        margin-bottom: 2rem;
    }

    .role-btn{
        flex: 1;
        padding: .9rem;
        border-radius: 12px;
        border: 1px solid var(--color-card);
        background: transparent;
        color: var(--color-text-secondary);
        cursor: pointer;
        transition: all .25s ease;
    }

    .role-btn:hover{
        border-color:var(--color-primary) ;
    }


    .role-btn-active{
        background: var(--color-primary);
        color: white;
        transform: translateY(-2px);
        flex: 1;
        padding: .9rem;
        border-radius: 12px;
        border: 1px solid var(--color-card);
        cursor: pointer;
        transition: all .25s ease;
    }

</style>