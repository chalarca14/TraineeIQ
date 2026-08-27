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
     * Genera la respuesta de la IA para un mensaje del estudiante dentro
     * de una conversacion ya existente.
     */
    public function generarRespuesta(ConversacionIa $conversacion, string $mensajeUsuario): string;
}