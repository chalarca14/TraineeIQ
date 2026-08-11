<template>

    <button class="social-button">

        <slot name="icon"></slot>

        <span>

            <slot></slot>

        </span>

    </button>

</template>

<style scoped>

.social-button{

    width:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    gap:.8rem;

    padding:1rem;

    background:transparent;

    border:1px solid var(--color-border);

    border-radius:12px;

    color:var(--color-text);

    cursor:pointer;

    transition:.25s;

}

.social-button:hover{

    border-color:var(--color-primary);

    background:rgba(124,58,237,.08);

}

</style>