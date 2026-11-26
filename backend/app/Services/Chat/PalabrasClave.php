<?php

namespace App\Services\Chat;

use Illuminate\Support\Str;

/**
 * Saca las palabras que de verdad dicen de qué trata un texto. La usan los dos chats:
 * el libre, para sugerir un tema de las guías del estudiante, y el guiado, para revisar
 * en el backend que la respuesta hable del contenido de la guía (RNF-17).
 */
class PalabrasClave
{
    /**
     * Palabras frecuentes en las preguntas y en los nombres de los temas que no sirven para
     * saber de qué tema se habla (van sin tildes, porque se comparan ya normalizadas).
     */
    private const IGNORADAS = [
        // Artículos, preposiciones, conectores y pronombres
        'los', 'las', 'del', 'con', 'por', 'para', 'sin', 'una', 'uno', 'unos', 'unas', 'que',
        'cual', 'cuales', 'como', 'cuando', 'donde', 'entre', 'sobre', 'desde', 'hasta', 'hacia',
        'segun', 'pero', 'porque', 'pues', 'tambien', 'ademas', 'mas', 'muy', 'solo', 'esto',
        'esta', 'este', 'estos', 'estas', 'eso', 'esa', 'ese', 'esos', 'esas', 'aqui', 'algo',
        'todo', 'toda', 'todos', 'todas', 'cada', 'otro', 'otra', 'otros', 'otras', 'mis', 'tus',
        'sus', 'nos', 'les', 'ellos', 'ellas', 'usted', 'ustedes', 'mismo', 'misma', 'bien', 'mal',
        // Verbos y palabras típicas de una pregunta
        'son', 'hay', 'ser', 'estar', 'tiene', 'tienen', 'tengo', 'hace', 'hacer', 'hago', 'puede',
        'pueden', 'puedo', 'quiero', 'necesito', 'sirve', 'sirven', 'servir', 'usar', 'uso',
        'utiliza', 'utilizar', 'funciona', 'funcionan', 'explica', 'explicame', 'explicar', 'dame',
        'dime', 'muestra', 'muestrame', 'ayuda', 'ayudame', 'ejemplo', 'ejemplos', 'diferencia',
        'diferencias', 'significa', 'hola', 'gracias', 'favor', 'porfa', 'sencillo', 'facil',
        // Palabras genéricas de los nombres de temas
        'tema', 'temas', 'guia', 'semana', 'introduccion', 'concepto', 'conceptos', 'basico',
        'basica', 'basicos', 'basicas', 'general', 'generales', 'parte',
    ];

    /**
     * Devuelve las palabras clave de un texto, sin repetir:
     * - pasa a minúsculas y quita las tildes ("básicas" → "basicas"); antes las tildes se
     *   borraban junto con la letra y la palabra quedaba partida ("b" y "sicas");
     * - acepta palabras desde 3 letras, para que cuenten siglas como "git", "php" o "css";
     * - ignora las palabras comunes (IGNORADAS), que no dicen nada del tema: antes
     *   "¿Qué es Git y para qué sirve?" se relacionaba con "Estructuras básicas para datos"
     *   solo por la palabra "para".
     */
    public static function extraer(string $texto): array
    {
        $texto = Str::lower(Str::ascii($texto));
        $texto = preg_replace('/[^a-z0-9\s]/', ' ', $texto);

        $palabras = array_filter(
            explode(' ', $texto),
            fn ($p) => strlen($p) >= 3 && ! in_array($p, self::IGNORADAS, true)
        );

        return array_values(array_unique($palabras));
    }
}
