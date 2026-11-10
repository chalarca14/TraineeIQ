<?php

namespace App\Services\Chat;

use App\Models\ConversacionIa;

/**
 * Contrato comun para las estrategias de chat (patron Strategy).
 * ChatIaController nunca decide como se restringe o no el contenido:
 * eso vive exclusivamente en las clases que implementan esta interfaz,
 * para que sea auditable y facil de testear por separado (ver
 * Especificacion_Chat_IA_TraineeIQ_v2, seccion 5).
 */
interface ChatStrategy
{
    /**
     * Qué es TraineeIQ, para que la IA no lo describa mal.
     */
    public const CONTEXTO_TRAINEEIQ = 'TraineeIQ es una plataforma web del SENA para aprendices de programación (ADSO). '
        . 'Los instructores crean grupos, suben guías de aprendizaje en PDF y liberan sus semanas; '
        . 'la IA divide cada guía por semanas y genera recomendaciones y mini proyectos. '
        . 'El estudiante entrega cada mini proyecto con el link de su repositorio público de GitHub '
        . 'y recibe una evaluación orientativa, que no reemplaza la nota del instructor. '
        . 'En el Modo Personal el estudiante estudia sus propias guías, sin grupo ni instructor. '
        . 'Si te preguntan por la plataforma, explícala solo con esta información y no inventes funciones.';

    /**
     * Genera la respuesta de la IA para un mensaje del estudiante dentro
     * de una conversacion ya existente.
     */
    public function generarRespuesta(ConversacionIa $conversacion, string $mensajeUsuario): string;
}