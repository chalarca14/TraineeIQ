<?php

namespace App\Services;

use App\Exceptions\IaNoDisponibleException;
use App\Exceptions\RespuestaIaInvalidaException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado (patron Singleton) para toda comunicacion con la API de Gemini.
 * CAMBIOS v3:
 * - generarRecomendaciones() ahora trabaja por TEMA (guia_temas), no por guia completa.
 * - Los mini proyectos se separan en su propio metodo, porque ahora tienen su propia tabla
 *   (mini_proyectos) con su propio campo de dificultad.
 * - Se agrega generarRespuestaChat(), metodo generico que usaran las estrategias del chat
 *   (GuiadaStrategy y LibreStrategy) para no duplicar la logica HTTP.
 * CAMBIOS v4:
 * - El modelo de Gemini ya no esta escrito en el codigo: se lee de config/services.php (.env).
 * - llamarApi() reintenta si Gemini esta saturado (429/503) y usa un modelo de respaldo.
 * CAMBIOS v5:
 * - Se agrega segmentarGuiaEnTemas(): divide el texto de una guia (grupal o personal)
 *   en temas para guardarlos en guia_temas.
 * CAMBIOS v6:
 * - dividirEnSemanas() reemplaza a segmentarGuiaEnTemas(): usa un esquema JSON y
 *   lanza excepciones para distinguir "IA caida" de "respuesta invalida".
 * - Los prompts nuevos viven en resources/prompts, no dentro del codigo.
 * CAMBIOS v7:
 * - generarContenidoSemana() genera en una sola llamada las recomendaciones y los
 *   mini proyectos de una semana, y valida la respuesta contra el contrato 2.2.
 * CAMBIOS v8:
 * - Se eliminan generarRecomendaciones() y generarMiniProyectos(): el contenido ya no se
 *   genera a pedido, sino una vez por semana con generarContenidoSemana().
 * CAMBIOS v9:
 * - evaluarCodigo() evalua un repositorio contra los criterios del mini proyecto.
 *   Gemini nunca fija el puntaje: solo dice cuanto se cumple cada criterio.
 */
class GeminiService
{
    // Tope de semanas que se aceptan en una guia
    private const MAX_SEMANAS = 52;

    // Cantidad fija de recomendaciones por semana (contrato 2.2 de la Fase 0)
    private const TOTAL_RECOMENDACIONES = 4;

    private const NIVELES = ['basico', 'recomendado', 'importante', 'avanzado'];

    // La posicion define el orden: basico es el 1, intermedio el 2 y avanzado el 3
    private const DIFICULTADES = ['basico', 'intermedio', 'avanzado'];

    // Los pesos de los criterios de un mini proyecto deben sumar exactamente esto
    private const SUMA_PESOS = 100;

    // Esquema que Gemini debe cumplir al generar una semana (contrato de la Fase 0)
    private const ESQUEMA_CONTENIDO = [
        'type'       => 'object',
        'required'   => ['recomendaciones', 'mini_proyectos'],
        'properties' => [
            'recomendaciones' => [
                'type'     => 'array',
                'minItems' => self::TOTAL_RECOMENDACIONES,
                'items'    => [
                    'type'       => 'object',
                    'required'   => ['orden', 'nivel', 'titulo', 'descripcion'],
                    'properties' => [
                        'orden'       => ['type' => 'integer'],
                        'nivel'       => ['type' => 'string', 'enum' => self::NIVELES],
                        'titulo'      => ['type' => 'string'],
                        'descripcion' => ['type' => 'string'],
                    ],
                ],
            ],
            'mini_proyectos' => [
                'type'     => 'array',
                'minItems' => 3,
                'items'    => [
                    'type'     => 'object',
                    'required' => [
                        'orden', 'dificultad', 'titulo', 'descripcion', 'contenido',
                        'requisitos_funcionales', 'buenas_practicas', 'criterios_evaluacion',
                    ],
                    'properties' => [
                        'orden'                  => ['type' => 'integer'],
                        'dificultad'             => ['type' => 'string', 'enum' => self::DIFICULTADES],
                        'titulo'                 => ['type' => 'string'],
                        'descripcion'            => ['type' => 'string'],
                        'contenido'              => ['type' => 'string'],
                        'requisitos_funcionales' => [
                            'type'     => 'array',
                            'minItems' => 1,
                            'items'    => ['type' => 'string'],
                        ],
                        'buenas_practicas' => [
                            'type'     => 'array',
                            'minItems' => 1,
                            'items'    => ['type' => 'string'],
                        ],
                        'criterios_evaluacion' => [
                            'type'     => 'array',
                            'minItems' => 1,
                            'items'    => [
                                'type'       => 'object',
                                'required'   => ['criterio', 'peso'],
                                'properties' => [
                                    'criterio' => ['type' => 'string'],
                                    'peso'     => ['type' => 'integer'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    // Esquema que Gemini debe cumplir al dividir una guia (contrato de la Fase 0)
    private const ESQUEMA_SEMANAS = [
        'type'       => 'object',
        'required'   => ['semanas'],
        'properties' => [
            'semanas' => [
                'type'     => 'array',
                'minItems' => 1,
                // Sin maxItems: la API lo rechaza con 400. El tope se valida en validarSemanas()
                'items'    => [
                    'type'       => 'object',
                    'required'   => ['numero_semana', 'titulo', 'resumen', 'inicio_texto', 'temas'],
                    'properties' => [
                        'numero_semana' => ['type' => 'integer'],
                        'titulo'        => ['type' => 'string'],
                        'resumen'       => ['type' => 'string'],
                        'inicio_texto'  => ['type' => 'string'],
                        'temas'         => [
                            'type'     => 'array',
                            'minItems' => 1,
                            'items'    => [
                                'type'       => 'object',
                                'required'   => ['nombre_tema', 'descripcion'],
                                'properties' => [
                                    'nombre_tema' => ['type' => 'string'],
                                    'descripcion' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    // Esquema que Gemini debe cumplir al evaluar una entrega (contrato de la Fase 0)
    private const ESQUEMA_EVALUACION = [
        'type'       => 'object',
        'required'   => ['resumen', 'criterios', 'requisitos', 'buenas_practicas', 'sugerencias'],
        'properties' => [
            'resumen'   => ['type' => 'string'],
            'criterios' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'required'   => ['criterio', 'cumplimiento', 'comentario'],
                    'properties' => [
                        'criterio'     => ['type' => 'string'],
                        'cumplimiento' => ['type' => 'integer'],
                        'comentario'   => ['type' => 'string'],
                    ],
                ],
            ],
            'requisitos' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'required'   => ['requisito', 'cumple', 'comentario'],
                    'properties' => [
                        'requisito'  => ['type' => 'string'],
                        'cumple'     => ['type' => 'boolean'],
                        'comentario' => ['type' => 'string'],
                    ],
                ],
            ],
            'buenas_practicas' => [
                'type'  => 'array',
                'items' => [
                    'type'       => 'object',
                    'required'   => ['practica', 'cumple', 'comentario'],
                    'properties' => [
                        'practica'   => ['type' => 'string'],
                        'cumple'     => ['type' => 'boolean'],
                        'comentario' => ['type' => 'string'],
                    ],
                ],
            ],
            'sugerencias' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
    ];

    private static ?GeminiService $instancia = null;
    private string $apiKey;
    private string $modelo;
    private string $modeloRespaldo;

    /**
     * Constructor privado para aplicar el patron Singleton.
     * Lee la configuracion desde config/services.php (que a su vez lee el .env).
     */
    private function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key');
        $this->modelo = (string) config('services.gemini.model');
        $this->modeloRespaldo = (string) config('services.gemini.fallback_model');
    }

    /**
     * Retorna la unica instancia de GeminiService (patron Singleton).
     */
    public static function obtenerInstancia(): GeminiService
    {
        if (self::$instancia === null) {
            self::$instancia = new GeminiService();
        }
        return self::$instancia;
    }

    /**
     * Divide el texto de una guia en semanas con sus temas (contrato 2.1 de la Fase 0).
     * No devuelve el texto de cada semana: solo la frase con la que empieza, para que
     * el backend corte el texto real del PDF.
     *
     * @throws IaNoDisponibleException Si Gemini no responde.
     * @throws RespuestaIaInvalidaException Si la respuesta no cumple el contrato.
     */
    public function dividirEnSemanas(string $textoGuia, ?int $semanasSugeridas = null): array
    {
        $sugerencia = $semanasSugeridas
            ? "La guia deberia quedar en {$semanasSugeridas} semanas, salvo que su contenido indique otra cosa."
            : 'Decide el numero de semanas segun el contenido de la guia.';

        $prompt = $this->cargarPrompt('dividir_semanas', [
            '{{SUGERENCIA_SEMANAS}}' => $sugerencia,
            // Evita que el PDF cierre la etiqueta y su texto parezca una instruccion
            '{{GUIA}}' => str_ireplace(['<guia>', '</guia>'], ' ', $textoGuia),
        ]);

        $texto = $this->llamarApi($prompt, self::ESQUEMA_SEMANAS);

        if ($texto === null) {
            throw new IaNoDisponibleException('Gemini no respondio al dividir la guia.');
        }

        return $this->validarSemanas($this->parsearJson($texto, []));
    }

    /**
     * Genera las recomendaciones y los mini proyectos de una semana (contrato 2.2).
     * El titulo y el resumen tambien van delimitados: Gemini los redacto a partir
     * del PDF, asi que se tratan como dato no confiable igual que el texto.
     *
     * @throws IaNoDisponibleException Si Gemini no responde.
     * @throws RespuestaIaInvalidaException Si la respuesta no cumple el contrato.
     */
    public function generarContenidoSemana(string $titulo, string $resumen, string $contenido): array
    {
        // Evita que el PDF cierre la etiqueta. Se cambia por un espacio y no por nada:
        // borrarla dejaria que "</sem</semana>ana>" formara una etiqueta nueva
        $limpiar = fn (string $texto) => str_ireplace(['<semana>', '</semana>'], ' ', $texto);

        $prompt = $this->cargarPrompt('generar_contenido_semana', [
            '{{TITULO}}'    => $limpiar($titulo),
            '{{RESUMEN}}'   => $limpiar($resumen),
            '{{CONTENIDO}}' => $limpiar($contenido),
        ]);

        $texto = $this->llamarApi($prompt, self::ESQUEMA_CONTENIDO);

        if ($texto === null) {
            throw new IaNoDisponibleException('Gemini no respondio al generar la semana.');
        }

        $datos = $this->parsearJson($texto, []);

        return [
            'recomendaciones' => $this->validarRecomendaciones($datos['recomendaciones'] ?? null),
            'mini_proyectos'  => $this->validarMiniProyectos($datos['mini_proyectos'] ?? null),
        ];
    }

    /**
     * Evalua el codigo de una entrega (contrato 2.3). Recibe los textos exactos de los
     * criterios, requisitos y practicas, y exige que la respuesta traiga esos mismos.
     *
     * @throws IaNoDisponibleException Si Gemini no responde.
     * @throws RespuestaIaInvalidaException Si la respuesta no cumple el contrato.
     */
    public function evaluarCodigo(array $criterios, array $requisitos, array $practicas, string $codigo): array
    {
        $comoJson = fn (array $lista) => json_encode($lista, JSON_UNESCAPED_UNICODE);

        $prompt = $this->cargarPrompt('evaluar_codigo', [
            '{{CRITERIOS}}'  => $comoJson($criterios),
            '{{REQUISITOS}}' => $comoJson($requisitos),
            '{{PRACTICAS}}'  => $comoJson($practicas),
            // Evita que el repositorio cierre la etiqueta y su texto parezca una instruccion
            '{{CODIGO}}'     => str_ireplace(['<repositorio>', '</repositorio>'], ' ', $codigo),
        ]);

        $texto = $this->llamarApi($prompt, self::ESQUEMA_EVALUACION);

        if ($texto === null) {
            throw new IaNoDisponibleException('Gemini no respondio al evaluar el codigo.');
        }

        $datos = $this->parsearJson($texto, []);

        return [
            'resumen'          => $this->textoObligatorio($datos, 'resumen'),
            'criterios'        => $this->validarCriteriosEvaluados($datos['criterios'] ?? null, $criterios),
            'requisitos'       => $this->validarCumplimiento($datos['requisitos'] ?? null, 'requisito', $requisitos),
            'buenas_practicas' => $this->validarCumplimiento($datos['buenas_practicas'] ?? null, 'practica', $practicas),
            'sugerencias'      => $this->validarSugerencias($datos['sugerencias'] ?? null),
        ];
    }

    /**
     * Metodo generico de bajo nivel: envia cualquier prompt ya armado a Gemini y
     * retorna texto plano (no JSON). Lo usaran GuiadaStrategy y LibreStrategy,
     * porque cada una arma su propio prompt segun sus propias reglas de negocio.
     */
    public function generarRespuestaChat(string $prompt): string
    {
        $texto = $this->llamarApi($prompt);

        if ($texto === null) {
            return 'No fue posible generar una respuesta en este momento. Intenta nuevamente.';
        }

        return trim($texto);
    }

    /**
     * Llama a la API REST de Gemini con un prompt ya construido.
     * Reintenta si Gemini esta saturado y usa un modelo de respaldo si el principal falla.
     * Centraliza el manejo de errores HTTP para no repetir try-catch en cada metodo publico.
     */
    private function llamarApi(string $prompt, ?array $esquema = null): ?string
    {
        $cuerpo = ['contents' => [['parts' => [['text' => $prompt]]]]];

        // Con esquema, la API obliga a Gemini a responder JSON con esa forma
        if ($esquema !== null) {
            $cuerpo['generationConfig'] = [
                'responseMimeType'   => 'application/json',
                'responseJsonSchema' => $esquema,
            ];
        }

        // Orden de intento: primero el modelo principal, luego el de respaldo
        $modelos = [$this->modelo, $this->modeloRespaldo];

        foreach ($modelos as $modelo) {
            try {
                $respuesta = Http::withHeaders([
                    'x-goog-api-key' => $this->apiKey, // La clave va por cabecera, no en la URL
                ])
                    ->timeout(60)
                    ->retry(
                        2,
                        1000,
                        fn ($excepcion) => $excepcion instanceof RequestException
                            && in_array($excepcion->response->status(), [429, 503]),
                        throw: false
                    )
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent", $cuerpo);

                if ($respuesta->failed()) {
                    Log::warning('Gemini fallo con este modelo', [
                        'modelo' => $modelo,
                        'status' => $respuesta->status(),
                        'body'   => $respuesta->body(),
                    ]);
                    continue; // Pasa al siguiente modelo de la lista
                }

                return $respuesta->json('candidates.0.content.parts.0.text');
            } catch (\Exception $e) {
                Log::error('Excepcion en GeminiService', [
                    'modelo'  => $modelo,
                    'mensaje' => $e->getMessage(),
                ]);
            }
        }

        // Ningun modelo respondio: los metodos publicos ya manejan el null
        return null;
    }

    /**
     * Lee un prompt de resources/prompts y reemplaza sus marcadores.
     * strtr hace una sola pasada: un marcador escrito dentro del PDF no se expande.
     */
    private function cargarPrompt(string $nombre, array $valores): string
    {
        return strtr(file_get_contents(resource_path("prompts/{$nombre}.txt")), $valores);
    }

    /**
     * Valida la division en semanas contra el contrato. La API aplica el esquema,
     * pero aqui se revisa lo que el esquema no garantiza: textos vacios y numeracion.
     */
    private function validarSemanas(array $datos): array
    {
        $semanas = $datos['semanas'] ?? null;

        if (! is_array($semanas) || $semanas === [] || count($semanas) > self::MAX_SEMANAS) {
            throw new RespuestaIaInvalidaException('La respuesta no trae una lista de semanas valida.');
        }

        $validas = [];

        foreach (array_values($semanas) as $indice => $semana) {
            $titulo = $this->textoObligatorio($semana, 'titulo');

            if (($semana['numero_semana'] ?? null) !== $indice + 1 || mb_strlen($titulo) > 255) {
                throw new RespuestaIaInvalidaException('Numero o titulo de semana invalido.');
            }

            $temas = $semana['temas'] ?? null;

            if (! is_array($temas) || $temas === []) {
                throw new RespuestaIaInvalidaException('Una semana no trae temas.');
            }

            $validas[] = [
                'numero_semana' => $indice + 1,
                'titulo'        => $titulo,
                'resumen'       => $this->textoObligatorio($semana, 'resumen'),
                'inicio_texto'  => $this->textoObligatorio($semana, 'inicio_texto'),
                'temas'         => array_map(fn ($tema) => [
                    'nombre_tema' => mb_substr($this->textoObligatorio($tema, 'nombre_tema'), 0, 255),
                    'descripcion' => $this->textoObligatorio($tema, 'descripcion'),
                ], array_values($temas)),
            ];
        }

        return $validas;
    }

    /**
     * Valida las recomendaciones: exactamente 4, en orden y con un nivel permitido.
     * Un valor fuera de la lista invalida la respuesta en vez de reemplazarse por
     * uno por defecto, porque este contenido lo ve todo el grupo.
     */
    private function validarRecomendaciones(mixed $recomendaciones): array
    {
        if (! is_array($recomendaciones) || count($recomendaciones) !== self::TOTAL_RECOMENDACIONES) {
            throw new RespuestaIaInvalidaException('La semana no trae 4 recomendaciones.');
        }

        $validas = [];

        foreach (array_values($recomendaciones) as $indice => $recomendacion) {
            $nivel = is_array($recomendacion) ? ($recomendacion['nivel'] ?? null) : null;

            if (! in_array($nivel, self::NIVELES, true) || ($recomendacion['orden'] ?? null) !== $indice + 1) {
                throw new RespuestaIaInvalidaException('Orden o nivel de recomendacion invalido.');
            }

            $validas[] = [
                'orden'       => $indice + 1,
                'nivel'       => $nivel,
                'titulo'      => $this->textoObligatorio($recomendacion, 'titulo'),
                'descripcion' => $this->textoObligatorio($recomendacion, 'descripcion'),
            ];
        }

        return $validas;
    }

    /**
     * Valida los mini proyectos: uno por dificultad, en orden, con listas no vacias
     * y criterios cuyos pesos sumen 100.
     */
    private function validarMiniProyectos(mixed $miniProyectos): array
    {
        if (! is_array($miniProyectos) || count($miniProyectos) !== count(self::DIFICULTADES)) {
            throw new RespuestaIaInvalidaException('La semana no trae 3 mini proyectos.');
        }

        $validos = [];

        foreach (array_values($miniProyectos) as $indice => $proyecto) {
            $titulo = $this->textoObligatorio($proyecto, 'titulo');

            if (
                ($proyecto['orden'] ?? null) !== $indice + 1
                || ($proyecto['dificultad'] ?? null) !== self::DIFICULTADES[$indice]
                || mb_strlen($titulo) > 255
            ) {
                throw new RespuestaIaInvalidaException('Orden, dificultad o titulo de mini proyecto invalido.');
            }

            $validos[] = [
                'orden'                  => $indice + 1,
                'dificultad'             => self::DIFICULTADES[$indice],
                'titulo'                 => $titulo,
                'descripcion'            => $this->textoObligatorio($proyecto, 'descripcion'),
                'contenido'              => $this->textoObligatorio($proyecto, 'contenido'),
                'requisitos_funcionales' => $this->listaDeTextos($proyecto, 'requisitos_funcionales'),
                'buenas_practicas'       => $this->listaDeTextos($proyecto, 'buenas_practicas'),
                'criterios_evaluacion'   => $this->validarCriterios($proyecto['criterios_evaluacion'] ?? null),
            ];
        }

        return $validos;
    }

    /**
     * Valida los criterios de evaluacion. La suma de pesos debe ser 100 porque de
     * ella sale el puntaje que el backend calcula en la Fase 7.
     */
    private function validarCriterios(mixed $criterios): array
    {
        if (! is_array($criterios) || $criterios === []) {
            throw new RespuestaIaInvalidaException('Un mini proyecto no trae criterios.');
        }

        $validos = [];

        foreach (array_values($criterios) as $criterio) {
            $peso = is_array($criterio) ? ($criterio['peso'] ?? null) : null;

            if (! is_int($peso) || $peso <= 0) {
                throw new RespuestaIaInvalidaException('Un criterio trae un peso invalido.');
            }

            $validos[] = ['criterio' => $this->textoObligatorio($criterio, 'criterio'), 'peso' => $peso];
        }

        if (array_sum(array_column($validos, 'peso')) !== self::SUMA_PESOS) {
            throw new RespuestaIaInvalidaException('Los pesos de los criterios no suman 100.');
        }

        return $validos;
    }

    /**
     * Valida el cumplimiento de cada criterio: debe ser un entero de 0 a 100.
     */
    private function validarCriteriosEvaluados(mixed $evaluados, array $esperados): array
    {
        return array_map(function (array $item) {
            $cumplimiento = $item['cumplimiento'] ?? null;

            if (! is_int($cumplimiento) || $cumplimiento < 0 || $cumplimiento > 100) {
                throw new RespuestaIaInvalidaException('Un criterio trae un cumplimiento invalido.');
            }

            return [
                'criterio'     => $item['criterio'],
                'cumplimiento' => $cumplimiento,
                'comentario'   => $this->textoObligatorio($item, 'comentario'),
            ];
        }, $this->alinearConEsperados($evaluados, 'criterio', $esperados));
    }

    /**
     * Valida los requisitos o las buenas practicas: cada uno dice si se cumple y por que.
     */
    private function validarCumplimiento(mixed $evaluados, string $campo, array $esperados): array
    {
        return array_map(function (array $item) use ($campo) {
            if (! is_bool($item['cumple'] ?? null)) {
                throw new RespuestaIaInvalidaException("Falta cumple en {$campo}.");
            }

            return [
                $campo       => $item[$campo],
                'cumple'     => $item['cumple'],
                'comentario' => $this->textoObligatorio($item, 'comentario'),
            ];
        }, $this->alinearConEsperados($evaluados, $campo, $esperados));
    }

    /**
     * Exige exactamente los textos esperados, sin faltantes ni sobrantes, y los
     * devuelve en el orden del mini proyecto.
     */
    private function alinearConEsperados(mixed $evaluados, string $campo, array $esperados): array
    {
        if (! is_array($evaluados) || count($evaluados) !== count($esperados)) {
            throw new RespuestaIaInvalidaException("La evaluacion no trae todos los elementos de {$campo}.");
        }

        $porTexto = [];

        foreach ($evaluados as $item) {
            $porTexto[$this->textoObligatorio($item, $campo)] = $item;
        }

        return array_map(function (string $esperado) use ($porTexto, $campo) {
            if (! isset($porTexto[$esperado])) {
                throw new RespuestaIaInvalidaException("La evaluacion cambio un texto de {$campo}.");
            }

            return [$campo => $esperado] + $porTexto[$esperado];
        }, array_values($esperados));
    }

    /**
     * Valida las sugerencias: una lista de textos que puede estar vacia.
     */
    private function validarSugerencias(mixed $sugerencias): array
    {
        if ($sugerencias === []) {
            return [];
        }

        return $this->listaDeTextos(['sugerencias' => $sugerencias], 'sugerencias');
    }

    /**
     * Devuelve una lista de textos limpios, o falla si esta vacia o trae otra cosa.
     */
    private function listaDeTextos(array $datos, string $campo): array
    {
        $lista = $datos[$campo] ?? null;

        if (! is_array($lista) || $lista === []) {
            throw new RespuestaIaInvalidaException("Falta la lista {$campo}.");
        }

        return array_map(function ($texto) use ($campo) {
            if (! is_string($texto) || trim($texto) === '') {
                throw new RespuestaIaInvalidaException("La lista {$campo} trae un texto vacio.");
            }

            return trim($texto);
        }, array_values($lista));
    }

    /**
     * Devuelve un campo de texto limpio, o falla si no existe o esta vacio.
     */
    private function textoObligatorio(mixed $datos, string $campo): string
    {
        $valor = is_array($datos) ? ($datos[$campo] ?? null) : null;

        if (! is_string($valor) || trim($valor) === '') {
            throw new RespuestaIaInvalidaException("Falta el campo {$campo}.");
        }

        return trim($valor);
    }

    /**
     * Limpia bloques de codigo markdown que Gemini a veces agrega y decodifica el JSON.
     * Si algo falla, retorna el valor por defecto en vez de romper la aplicacion.
     */
    private function parsearJson(string $texto, array $porDefecto): array
    {
        try {
            $limpio = preg_replace('/```json|```/', '', $texto);
            $decodificado = json_decode(trim($limpio), true);
            return is_array($decodificado) ? $decodificado : $porDefecto;
        } catch (\Exception $e) {
            Log::error('Error parseando JSON de Gemini', ['mensaje' => $e->getMessage()]);
            return $porDefecto;
        }
    }
}