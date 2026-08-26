<template>
    <main class="login-page">
        <LoginInfo />
        <LoginForm />
    </main>
</template>

<script setup >
import LoginInfo from '@/components/auth/LoginInfo.vue';
import LoginForm from '@/components/ui/LoginForm.vue';

</script>

<style scoped> 

.login-page {
    display: grid;
    grid-template-columns: 55% 45%;
    min-height: 100vh;
    width: 100%;
    background-color: var(--color-background);
    overflow-x: hidden;
}

/* TABLET */
@media(max-width: 900px){
    .login-page{
        grid-template-columns: 45% 55%;
    }
}

/* CELULAR */
@media(max-width: 700px){
    .login-page{
        grid-template-columns: 1fr;
        min-height: 100vh;

    }
}

</style>