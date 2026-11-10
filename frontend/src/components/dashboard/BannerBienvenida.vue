<template>
    <EncabezadoPagina :titulo="`¡Hola, ${nombre}! 👋`" :descripcion="mensaje" :icono="Sparkles" />
</template>

<script setup>
import { Sparkles } from 'lucide-vue-next'
import EncabezadoPagina from '@/components/ui/EncabezadoPagina.vue'

// Recibe el texto desde afuera: así el mismo banner sirve para estudiante e instructor.
// El aspecto (icono con brillo, degradado e ilustración) viene del banner compartido.
defineProps({
    nombre: { type: String, required: true },
    mensaje: { type: String, required: true }
})
</script>
