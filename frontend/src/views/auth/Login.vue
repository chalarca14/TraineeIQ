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
.login-page{
    display: grid;
    grid-template-columns: 55% 45%;
    min-height: 100vh;
    background-color: #0f1117;
}

@media(max-width:1100px){
    .login-page{
        grid-template-columns: 1fr;
    }
}
</style>