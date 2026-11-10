<template>
    <PaginaLegal titulo="Política de privacidad" actualizado="10 de noviembre de 2026">
        <p>
            TraineeIQ es una plataforma de acompañamiento académico para aprendices de programación del SENA
            (Análisis y Desarrollo de Software), creada como proyecto de grado. Esta política explica qué datos
            guardamos, para qué los usamos y cómo puedes pedir que los cambiemos o los borremos.
        </p>

        <h2>1. Quién es el responsable</h2>
        <p>
            El equipo de TraineeIQ. Puedes contactarnos en <strong>traineeiq.app@gmail.com</strong>.
        </p>

        <h2>2. Qué datos guardamos</h2>
        <ul>
            <li><strong>Datos de tu cuenta:</strong> nombre, correo, rol (aprendiz o instructor) y tu contraseña,
                que se guarda cifrada y nadie puede leer.</li>
            <li><strong>Si entras con Google:</strong> el identificador de tu cuenta de Google, tu nombre y tu
                correo. No recibimos tu contraseña de Google ni accedemos a tu correo, tus contactos o tus archivos.</li>
            <li><strong>Lo que creas en TraineeIQ:</strong> el texto de las guías que subes (el archivo PDF no se
                conserva), los grupos a los que perteneces, los enlaces a tus repositorios, las evaluaciones de tus
                mini proyectos, tu experiencia (XP) y tus conversaciones con el chat de IA.</li>
        </ul>

        <h2>3. Para qué los usamos</h2>
        <ul>
            <li>Crear tu cuenta y permitirte iniciar sesión.</li>
            <li>Dividir tus guías por semanas y generar recomendaciones y mini proyectos.</li>
            <li>Evaluar el código de tus repositorios y mostrarte el resultado.</li>
            <li>En los grupos, mostrarle a tu instructor tu avance.</li>
        </ul>
        <p>No vendemos tus datos, no los usamos para publicidad y no te enviamos correos de marketing.</p>

        <h2>4. Quién puede ver tu información</h2>
        <ul>
            <li><strong>Tu instructor</strong> ve, de los estudiantes de sus grupos, el nombre, el correo, los
                enlaces a los repositorios y los puntajes de las evaluaciones.</li>
            <li><strong>Tus guías personales</strong> (Modo Personal) solo las ves tú, ni siquiera el instructor.</li>
            <li><strong>Tus compañeros de grupo</strong> no ven tus repositorios ni tus evaluaciones.</li>
        </ul>

        <h2>5. Servicios externos que usamos</h2>
        <ul>
            <li><strong>Google Gemini:</strong> procesa el texto de las guías, tus preguntas al chat y el código
                de tus repositorios para generar el contenido y las evaluaciones.</li>
            <li><strong>GitHub:</strong> leemos solo repositorios públicos cuyo enlace guardaste para evaluarlos.</li>
            <li><strong>Google (inicio de sesión):</strong> solo si eliges "Continuar con Google".</li>
            <li><strong>Alojamiento:</strong> Vercel (la página), Render (el servidor) y TiDB Cloud (la base de
                datos, en Estados Unidos).</li>
        </ul>

        <h2>6. Cómo protegemos tus datos</h2>
        <p>
            Toda la comunicación viaja cifrada (HTTPS), las contraseñas se guardan cifradas y cada persona solo
            puede ver lo que le corresponde según su rol.
        </p>

        <h2>7. Tus derechos</h2>
        <p>
            De acuerdo con la Ley 1581 de 2012 de Colombia, puedes conocer, actualizar, corregir y pedir que
            borremos tus datos, y revocar tu autorización. Escríbenos a <strong>traineeiq.app@gmail.com</strong>
            desde el correo de tu cuenta y lo atenderemos. Al borrar tu cuenta se eliminan también tus guías
            personales, tus entregas y tus conversaciones.
        </p>

        <h2>8. Cambios a esta política</h2>
        <p>
            Si cambiamos esta política, actualizaremos la fecha de arriba. Si el cambio es importante, lo
            avisaremos dentro de la plataforma.
        </p>
    </PaginaLegal>
</template>

<script setup>
import PaginaLegal from '@/components/legal/PaginaLegal.vue'
</script>
