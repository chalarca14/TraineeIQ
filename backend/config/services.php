<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    // Clave de la API de Brevo (no la SMTP). Solo en el .env y en Render
    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
    'key'            => env('GEMINI_API_KEY'),
    'model'          => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    'fallback_model' => env('GEMINI_FALLBACK_MODEL', 'gemini-3.5-flash-lite'),
    // Una guia mas larga se rechaza con un mensaje claro, en vez de recortarla en silencio
    'max_caracteres_guia' => (int) env('GEMINI_MAX_CARACTERES_GUIA', 300000),
],

    'github' => [
        // Token del servidor: solo sube el limite de consultas, no da acceso a nada privado
        'token' => env('GITHUB_TOKEN'),
    ],

    'evaluacion' => [
        'por_dia'       => (int) env('EVALUACIONES_POR_DIA', 3),
        'max_archivos'  => (int) env('EVALUACION_MAX_ARCHIVOS', 40),
        'max_kb_archivo' => (int) env('EVALUACION_MAX_KB_ARCHIVO', 100),
        'max_caracteres' => (int) env('EVALUACION_MAX_CARACTERES', 200000),
        // El tope es diario para el estudiante, no para el reloj UTC del servidor
        'zona_horaria'  => env('EVALUACION_ZONA_HORARIA', 'America/Bogota'),
    ],

    // Modo Personal: porcentaje del XP de una semana que abre la siguiente
    'experiencia' => [
        'porcentaje_desbloqueo' => (int) env('XP_PORCENTAJE_DESBLOQUEO', 80),
    ],

    // "Continuar con Google": solo se necesita el Client ID (es público); no hay client secret
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        // Minutos que tiene un usuario nuevo para completar su registro después de elegir su cuenta
        'minutos_registro' => (int) env('GOOGLE_MINUTOS_REGISTRO', 10),
    ],

];