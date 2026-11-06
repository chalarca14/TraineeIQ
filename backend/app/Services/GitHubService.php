<?php

namespace App\Services;

use App\Exceptions\RepositorioNoDisponibleException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Lee repositorios publicos con la API de GitHub. El token del servidor solo
 * sube el limite de consultas: no da acceso a repositorios privados.
 */
class GitHubService
{
    private const URL_API = 'https://api.github.com';

    private const SEGUNDOS_POR_CONSULTA = 15;

    // Carpetas con dependencias, compilados o configuracion del editor
    private const CARPETAS_IGNORADAS = [
        'node_modules', 'vendor', 'dist', 'build', 'coverage', 'target', 'bin', 'obj',
        '.git', '.idea', '.vscode', '__pycache__', 'venv',
    ];

    // Archivos de bloqueo: son largos y no los escribe el estudiante
    private const ARCHIVOS_IGNORADOS = [
        'package-lock.json', 'composer.lock', 'yarn.lock', 'pnpm-lock.yaml', 'poetry.lock', 'gemfile.lock',
    ];

    private const EXTENSIONES_IGNORADAS = [
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'ico', 'svg', 'pdf', 'zip', 'rar', 'gz', 'tar', '7z',
        'exe', 'dll', 'so', 'jar', 'class', 'mp3', 'mp4', 'wav', 'avi', 'mov', 'woff', 'woff2', 'ttf', 'eot',
        'map', 'db', 'sqlite', 'lock',
    ];

    /**
     * Devuelve el ultimo commit de la rama principal y el arbol de archivos que le
     * corresponde. Un repositorio privado responde igual que uno que no existe.
     *
     * @return array{sha: string, arbol: string}
     *
     * @throws RepositorioNoDisponibleException
     */
    public function ultimoCommit(string $repoUrl): array
    {
        $ruta = $this->rutaRepositorio($repoUrl);
        $repositorio = $this->consultar("{$ruta}")->json();

        // Un token con mas permisos podria ver privados: la regla es solo repositorios publicos
        if (($repositorio['private'] ?? true) === true) {
            throw $this->repositorioNoEncontrado();
        }

        $rama = rawurlencode((string) ($repositorio['default_branch'] ?? ''));
        $commit = $this->consultar("{$ruta}/commits/{$rama}")->json();

        return ['sha' => $commit['sha'], 'arbol' => $commit['commit']['tree']['sha']];
    }

    /**
     * Lee los archivos de codigo del repositorio, sin pasar de los limites configurados.
     * recortado indica que quedaron archivos sin leer.
     *
     * @return array{archivos: array<int, array{ruta: string, contenido: string}>, recortado: bool}
     *
     * @throws RepositorioNoDisponibleException
     */
    public function leerArchivos(string $repoUrl, string $arbolSha): array
    {
        $ruta = $this->rutaRepositorio($repoUrl);
        $arbol = $this->consultar("{$ruta}/git/trees/{$arbolSha}?recursive=1")->json();

        $maxArchivos = (int) config('services.evaluacion.max_archivos');
        $maxCaracteres = (int) config('services.evaluacion.max_caracteres');
        $maxBytes = (int) config('services.evaluacion.max_kb_archivo') * 1024;

        $elegibles = array_values(array_filter(
            $arbol['tree'] ?? [],
            fn (array $item) => $this->esCodigo($item, $maxBytes)
        ));

        // Primero lo mas cercano a la raiz: ahi suelen estar el README y el codigo principal
        usort($elegibles, fn (array $a, array $b) => [substr_count($a['path'], '/'), $a['path']]
            <=> [substr_count($b['path'], '/'), $b['path']]);

        $recortado = ($arbol['truncated'] ?? false) === true || count($elegibles) > $maxArchivos;
        $archivos = [];
        $caracteres = 0;

        foreach (array_slice($elegibles, 0, $maxArchivos) as $item) {
            $contenido = $this->leerArchivo($ruta, $item['sha']);

            if ($contenido === null) {
                continue; // Era binario aunque su extension no lo dijera
            }

            if ($caracteres + mb_strlen($contenido) > $maxCaracteres) {
                $recortado = true;
                break;
            }

            $caracteres += mb_strlen($contenido);
            $archivos[] = ['ruta' => $item['path'], 'contenido' => $contenido];
        }

        if ($archivos === []) {
            throw new RepositorioNoDisponibleException('No encontramos archivos de código en tu repositorio.');
        }

        return ['archivos' => $archivos, 'recortado' => $recortado];
    }

    /**
     * Saca dueño y repositorio de un link ya validado por GuardarEntregaRequest.
     */
    private function rutaRepositorio(string $repoUrl): string
    {
        return '/repos/' . substr($repoUrl, strlen('https://github.com/'));
    }

    /**
     * Decide si un elemento del arbol vale la pena leerlo. Los .env se excluyen aunque
     * sean texto: podrian tener claves del estudiante, y esto se le envia a Gemini.
     */
    private function esCodigo(array $item, int $maxBytes): bool
    {
        if (($item['type'] ?? '') !== 'blob' || ($item['size'] ?? 0) > $maxBytes) {
            return false;
        }

        $partes = explode('/', strtolower($item['path']));
        $nombre = array_pop($partes);

        return array_intersect($partes, self::CARPETAS_IGNORADAS) === []
            && ! in_array($nombre, self::ARCHIVOS_IGNORADOS, true)
            && ! in_array(pathinfo($nombre, PATHINFO_EXTENSION), self::EXTENSIONES_IGNORADAS, true)
            && ! str_starts_with($nombre, '.env')
            && ! str_contains($nombre, '.min.');
    }

    /**
     * Descarga un archivo. Retorna null si no es texto.
     */
    private function leerArchivo(string $ruta, string $sha): ?string
    {
        $contenido = $this->consultar("{$ruta}/git/blobs/{$sha}", 'application/vnd.github.raw+json')->body();

        if (str_contains($contenido, "\0") || ! mb_check_encoding($contenido, 'UTF-8')) {
            return null;
        }

        return $contenido;
    }

    /**
     * Hace una consulta y traduce los errores de GitHub a mensajes para el estudiante.
     *
     * @throws RepositorioNoDisponibleException
     */
    private function consultar(string $ruta, string $formato = 'application/vnd.github+json'): Response
    {
        try {
            $respuesta = $this->cliente($formato)->get($ruta);
        } catch (ConnectionException $e) {
            throw $this->githubNoDisponible();
        }

        if ($respuesta->successful()) {
            return $respuesta;
        }

        throw match ($respuesta->status()) {
            404     => $this->repositorioNoEncontrado(),
            409     => new RepositorioNoDisponibleException('Tu repositorio está vacío. Sube tu código primero.'),
            default => $this->githubNoDisponible(),
        };
    }

    /**
     * Prepara la peticion. Sin token tambien funciona, pero con un limite muy bajo.
     */
    private function cliente(string $formato): PendingRequest
    {
        $cliente = Http::baseUrl(self::URL_API)
            ->accept($formato)
            ->timeout(self::SEGUNDOS_POR_CONSULTA);

        $token = config('services.github.token');

        return $token ? $cliente->withToken($token) : $cliente;
    }

    /**
     * Error para un repositorio privado o que no existe: GitHub no los distingue.
     */
    private function repositorioNoEncontrado(): RepositorioNoDisponibleException
    {
        return new RepositorioNoDisponibleException(
            'No encontramos tu repositorio. Revisa el link y que sea público.'
        );
    }

    /**
     * Error temporal: GitHub no respondio o se alcanzo su limite de consultas.
     */
    private function githubNoDisponible(): RepositorioNoDisponibleException
    {
        return new RepositorioNoDisponibleException(
            'No pudimos conectarnos con GitHub. Intenta de nuevo en un momento.',
            503
        );
    }
}
