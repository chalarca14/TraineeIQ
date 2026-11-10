<template>
    <PaginaLegal titulo="Términos y condiciones" actualizado="10 de noviembre de 2026">
        <p>
            Al crear una cuenta o usar TraineeIQ aceptas estos términos. TraineeIQ es un proyecto de grado de
            aprendices del SENA, gratuito y con fines educativos.
        </p>

        <h2>1. Qué ofrece TraineeIQ</h2>
        <p>
            Una plataforma donde los instructores comparten guías de aprendizaje por grupos, y donde cada aprendiz
            también puede estudiar con sus propias guías. Con inteligencia artificial, TraineeIQ divide las guías
            por semanas, genera recomendaciones y mini proyectos, responde preguntas en un chat y evalúa el código
            de los repositorios.
        </p>

        <h2>2. Tu cuenta</h2>
        <ul>
            <li>Usa datos reales y elige el rol que te corresponde (aprendiz o instructor).</li>
            <li>Cuida tu contraseña: eres responsable de lo que se haga con tu cuenta.</li>
            <li>Si entras con Google, tu cuenta queda vinculada a ese correo.</li>
        </ul>

        <h2>3. Uso permitido</h2>
        <p>Te comprometes a no:</p>
        <ul>
            <li>Subir guías o código que no tengas derecho a compartir.</li>
            <li>Entregar como propio el repositorio de otra persona.</li>
            <li>Intentar acceder a cuentas, grupos o guías que no son tuyos, o afectar el funcionamiento del servicio.</li>
            <li>Escribir instrucciones en guías, repositorios o el chat para engañar a la inteligencia artificial.</li>
        </ul>

        <h2>4. Tu contenido</h2>
        <p>
            Las guías, el código y los mensajes que compartes siguen siendo tuyos. Nos permites procesarlos solo
            para prestarte el servicio, como se explica en la
            <RouterLink to="/privacidad">Política de privacidad</RouterLink>.
        </p>

        <h2>5. La inteligencia artificial es orientativa</h2>
        <p>
            Las recomendaciones, los mini proyectos, las respuestas del chat y las evaluaciones los genera la
            inteligencia artificial y <strong>pueden equivocarse</strong>. La evaluación lee tu código pero no lo
            ejecuta, y el mismo código puede recibir puntajes distintos. Úsala como retroalimentación: no reemplaza
            la evaluación de tu instructor.
        </p>

        <h2>6. Límites del servicio</h2>
        <ul>
            <li>Cada mini proyecto se puede evaluar hasta 3 veces al día, y solo con repositorios públicos de GitHub.</li>
            <li>El servicio es gratuito y no garantizamos que esté siempre disponible. Después de un rato sin uso,
                la primera carga puede tardar cerca de un minuto.</li>
            <li>TraineeIQ guarda el texto de las guías, no el PDF original: conserva tu copia del archivo.</li>
        </ul>

        <h2>7. Suspensión y cierre de la cuenta</h2>
        <p>
            Podemos suspender cuentas que incumplan estos términos. Puedes pedir que borremos tu cuenta en cualquier
            momento escribiendo a <strong>traineeiq.app@gmail.com</strong>.
        </p>

        <h2>8. Cambios</h2>
        <p>
            Si cambiamos estos términos, actualizaremos la fecha de arriba. Seguir usando TraineeIQ después del
            cambio significa que aceptas la nueva versión.
        </p>
    </PaginaLegal>
</template>

<script setup>
import PaginaLegal from '@/components/legal/PaginaLegal.vue'
</script>
