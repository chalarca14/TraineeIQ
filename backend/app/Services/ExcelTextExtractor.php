<?php

namespace App\Services;

use App\Exceptions\GuiaNoProcesableException;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Convierte una guía en Excel (.xlsx), como el pacto pedagógico o la planeación del trimestre
 * del SENA, en texto ordenado por semana.
 *
 * Por qué se reorganiza: el resto del sistema divide la guía cortando el texto de arriba hacia
 * abajo, como en un PDF. En un Excel la misma semana aparece en varias hojas, así que leerlo
 * hoja por hoja pegaría todo a la última semana. Por eso se agrupan las filas de todas las
 * hojas por su "Semana N" y se escribe una semana detrás de otra.
 */
class ExcelTextExtractor
{
    // "Semana 01", "SEMANA 3", "Semana 1 - Detalle"... Se aceptan hasta 52 semanas
    private const PATRON_SEMANA = '/^\s*semana\s*0*([1-9]\d?)\b/iu';

    // Un texto de este largo o más no se repite dentro de la misma semana. Los más cortos sí,
    // porque son nombres o categorías que tienen sentido en cada fila
    private const LARGO_PARA_NO_REPETIR = 60;

    // Entre los datos de una misma fila: una línea en blanco, para que la pantalla los muestre como
    // párrafos separados y no los junte en uno solo
    private const SEPARADOR_DATOS = "\n\n";

    /**
     * Devuelve el texto del Excel ordenado por semanas, o null si no tiene texto.
     *
     * @throws GuiaNoProcesableException Si el archivo no se puede leer o es demasiado grande.
     */
    public function extraer(string $rutaAbsoluta): ?string
    {
        $lector = new Xlsx();
        // Las celdas vacías no se cargan: ahorra memoria en Render
        $lector->setReadEmptyCells(false);
        $lector->setIncludeCharts(false);

        try {
            $this->verificarTamano($lector, $rutaAbsoluta);
            $libro = $lector->load($rutaAbsoluta);
        } catch (GuiaNoProcesableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('No se pudo leer el Excel', ['mensaje' => $e->getMessage()]);
            return null;
        }

        try {
            $hojas = [];
            foreach ($libro->getAllSheets() as $hoja) {
                if ($this->seIgnora($hoja)) {
                    continue;
                }
                $hojas[] = $this->leerHoja($hoja);
            }
        } finally {
            // PhpSpreadsheet guarda referencias circulares: se liberan a mano
            $libro->disconnectWorksheets();
            unset($libro);
        }

        $texto = $this->armarTexto($hojas);

        return trim($texto) !== '' ? $texto : null;
    }

    /**
     * Rechaza el archivo antes de cargarlo si tiene demasiadas celdas. Excel ocupa mucha más
     * memoria al abrirse que en disco, y Render gratis tiene 512 MB para todo el servidor.
     */
    private function verificarTamano(Xlsx $lector, string $ruta): void
    {
        $celdas = 0;
        foreach ($lector->listWorksheetInfo($ruta) as $info) {
            $celdas += (int) $info['totalRows'] * (int) $info['totalColumns'];
        }

        if ($celdas > (int) config('services.guia_excel.max_celdas')) {
            throw new GuiaNoProcesableException(
                'El Excel es demasiado grande para procesarlo. Quita las hojas que no sean de la planeación o divídelo en dos.'
            );
        }
    }

    /**
     * Las hojas ocultas y las de la lista de .env (por ejemplo PlanEvaluacion, que en la
     * plantilla RP33 suele venir de otro curso) no se leen.
     */
    private function seIgnora(Worksheet $hoja): bool
    {
        if ($hoja->getSheetState() !== Worksheet::SHEETSTATE_VISIBLE) {
            return true;
        }

        $ignoradas = array_map(
            fn ($nombre) => mb_strtolower(trim($nombre)),
            (array) config('services.guia_excel.hojas_ignoradas')
        );

        return in_array(mb_strtolower(trim($hoja->getTitle())), $ignoradas, true);
    }

    /**
     * Lee una hoja y la clasifica: cada fila queda en su semana o en la información general.
     *
     * @return array{nombre: string, porSemana: array<int, list<array<string, string>>>, general: list<array<string, string>>}
     */
    private function leerHoja(Worksheet $hoja): array
    {
        $tabla = $this->leerTabla($hoja);
        $nombre = $hoja->getTitle();
        $resultado = ['nombre' => $nombre, 'porSemana' => [], 'general' => []];

        if ($tabla === []) {
            return $resultado;
        }

        $columnaSemana = $this->buscarColumnaSemana($tabla);

        // Hoja sin columna de semana: si su nombre dice la semana ("Semana 1 - Detalle"), todo
        // va a esa semana; si no, es información general (fuentes, portada, etc.)
        if ($columnaSemana === null) {
            $filas = $this->filasConTitulos($tabla, array_key_first($tabla));
            if (preg_match(self::PATRON_SEMANA, $nombre, $m) === 1) {
                $resultado['porSemana'][(int) $m[1]] = $filas;
            } else {
                $resultado['general'] = $filas;
            }

            return $resultado;
        }

        $numerosFila = array_keys($tabla);
        $primeraSemana = $this->primeraFilaDeSemana($tabla, $columnaSemana);
        $filaTitulos = $this->buscarFilaTitulos($tabla, $primeraSemana);
        $titulos = $filaTitulos !== null ? $tabla[$filaTitulos] : [];

        $semanaActual = null;
        foreach ($numerosFila as $numero) {
            $fila = $tabla[$numero];

            // Lo que está arriba de los títulos (nombre del programa, instructor...) es general.
            // Lo que está entre los títulos y la primera semana ("PROGRAMADA") se descarta
            if ($numero < $primeraSemana) {
                // Sin los textos de los títulos: con celdas combinadas, la fila de arriba los repite
                $sinTitulos = array_diff($fila, $titulos);
                if (($filaTitulos === null || $numero < $filaTitulos) && $sinTitulos !== []) {
                    $resultado['general'][] = $this->conTitulos($sinTitulos, []);
                }
                continue;
            }

            $valorSemana = $fila[$columnaSemana] ?? '';
            if (preg_match(self::PATRON_SEMANA, $valorSemana, $m) === 1) {
                $semanaActual = (int) $m[1];
            }
            unset($fila[$columnaSemana]);

            // Una fila sin semana debajo de otra con semana es la continuación de esa semana
            if ($fila !== [] && $semanaActual !== null) {
                $resultado['porSemana'][$semanaActual][] = $this->conTitulos($fila, $titulos);
            }
        }

        return $resultado;
    }

    /**
     * Pasa la hoja a una tabla [fila => [columna => texto]], solo con celdas que tienen texto.
     * Las celdas combinadas se repiten en cada fila que abarcan, para que cada semana tenga
     * su dato aunque en Excel la celda ocupe varias filas.
     *
     * @return array<int, array<int, string>>
     */
    private function leerTabla(Worksheet $hoja): array
    {
        $tabla = [];
        foreach ($hoja->getCellCollection()->getCoordinates() as $coordenada) {
            $valor = $this->valorDeCelda($hoja, $coordenada);
            if ($valor === '') {
                continue;
            }
            [$columna, $fila] = Coordinate::indexesFromString($coordenada);
            $tabla[$fila][$columna] = $valor;
        }

        foreach ($hoja->getMergeCells() as $rango) {
            [[$colInicio, $filaInicio], [, $filaFin]] = Coordinate::rangeBoundaries($rango);
            $valor = $tabla[$filaInicio][$colInicio] ?? null;
            if ($valor === null) {
                continue;
            }
            // Solo hacia abajo: hacia los lados repetiría el mismo texto en una misma fila
            for ($fila = $filaInicio + 1; $fila <= $filaFin; $fila++) {
                $tabla[$fila][$colInicio] = $valor;
            }
        }

        ksort($tabla);
        foreach ($tabla as &$fila) {
            ksort($fila);
        }

        return $tabla;
    }

    /**
     * El texto que se ve en Excel: resultado de las fórmulas, fechas como fecha y
     * porcentajes como porcentaje (no 45231 ni 0.05). Los espacios repetidos se juntan.
     */
    private function valorDeCelda(Worksheet $hoja, string $coordenada): string
    {
        try {
            $valor = (string) $hoja->getCell($coordenada)->getFormattedValue();
        } catch (\Throwable) {
            // Una fórmula rota no debe impedir leer el resto del archivo
            return '';
        }

        return trim((string) preg_replace('/\s+/u', ' ', $valor));
    }

    /**
     * La columna de las semanas es la que más celdas tiene con "Semana N" (al menos 2).
     */
    private function buscarColumnaSemana(array $tabla): ?int
    {
        $conteo = [];
        foreach ($tabla as $fila) {
            foreach ($fila as $columna => $valor) {
                if (preg_match(self::PATRON_SEMANA, $valor) === 1) {
                    $conteo[$columna] = ($conteo[$columna] ?? 0) + 1;
                }
            }
        }

        if ($conteo === [] || max($conteo) < 2) {
            return null;
        }
        arsort($conteo);

        return array_key_first($conteo);
    }

    /**
     * Número de la primera fila que dice "Semana N" en la columna de semanas.
     */
    private function primeraFilaDeSemana(array $tabla, int $columnaSemana): int
    {
        foreach ($tabla as $numero => $fila) {
            if (preg_match(self::PATRON_SEMANA, $fila[$columnaSemana] ?? '') === 1) {
                return $numero;
            }
        }

        return PHP_INT_MAX;
    }

    /**
     * Fila de títulos: la más cercana por encima de la primera semana que tenga 2 o más celdas.
     */
    private function buscarFilaTitulos(array $tabla, int $primeraSemana): ?int
    {
        $candidata = null;
        foreach ($tabla as $numero => $fila) {
            if ($numero >= $primeraSemana) {
                break;
            }
            if (count($fila) >= 2) {
                $candidata = $numero;
            }
        }

        return $candidata;
    }

    /**
     * Para hojas sin semanas: la primera fila son los títulos si tiene 2 o más celdas.
     *
     * @return list<array<string, string>>
     */
    private function filasConTitulos(array $tabla, int $primeraFila): array
    {
        $titulos = count($tabla[$primeraFila]) >= 2 ? $tabla[$primeraFila] : [];
        $filas = [];
        foreach ($tabla as $numero => $fila) {
            if ($titulos !== [] && $numero === $primeraFila) {
                continue;
            }
            $filas[] = $this->conTitulos($fila, $titulos);
        }

        return $filas;
    }

    /**
     * Une cada valor con el título de su columna ("Actividad" => "Analizar casos..."). Una
     * columna sin título queda con clave numérica y se escribe solo el valor.
     *
     * @return array<string|int, string>
     */
    private function conTitulos(array $fila, array $titulos): array
    {
        $resultado = [];
        foreach ($fila as $columna => $valor) {
            $titulo = $titulos[$columna] ?? null;
            if ($titulo !== null && $titulo !== $valor) {
                $resultado[$titulo] = $valor;
            } else {
                $resultado[] = $valor;
            }
        }

        return $resultado;
    }

    /**
     * Arma el texto final con una forma fácil de leer en la pantalla de la guía (utils/textoGuia.js
     * del frontend la reconoce como la de un PDF): cada hoja es un título en mayúsculas y cada dato
     * va en su propia línea "Título: valor", con una línea en blanco entre filas.
     *
     * Si el Excel tiene semanas: primero la información general (queda en la semana 1, como la
     * portada de un PDF) y luego cada semana con las filas de todas sus hojas. Si no tiene semanas,
     * va hoja por hoja y Gemini las organiza como un PDF.
     */
    private function armarTexto(array $hojas): string
    {
        $semanas = [];
        foreach ($hojas as $hoja) {
            foreach (array_keys($hoja['porSemana']) as $numero) {
                $semanas[$numero] = true;
            }
        }

        if ($semanas === []) {
            $bloques = [];
            foreach ($hojas as $hoja) {
                $vistos = [];
                $seccion = $this->seccion($hoja['nombre'], $hoja['general'], $vistos);
                if ($seccion !== null) {
                    $bloques[] = $seccion;
                }
            }

            return implode("

", $bloques);
        }

        $bloques = [];

        $vistos = [];
        $general = [];
        foreach ($hojas as $hoja) {
            $seccion = $this->seccion($hoja['nombre'], $hoja['general'], $vistos, 1);
            if ($seccion !== null) {
                $general[] = $seccion;
            }
        }
        if ($general !== []) {
            $bloques[] = "INFORMACIÓN GENERAL

" . implode("

", $general);
        }

        ksort($semanas);
        foreach (array_keys($semanas) as $numero) {
            // Cada semana lleva su propia lista de vistos: así una fila que repite lo de otra
            // hoja de la misma semana ("Temas actualizados" repite "Formato") no se duplica
            $vistos = [];
            $secciones = [];
            foreach ($hojas as $hoja) {
                $seccion = $this->seccion($hoja['nombre'], $hoja['porSemana'][$numero] ?? [], $vistos);
                if ($seccion !== null) {
                    $secciones[] = $seccion;
                }
            }
            $bloques[] = "Semana {$numero}

" . implode("

", $secciones);
        }

        return implode("

", $bloques);
    }

    /**
     * Una hoja dentro de un bloque: su nombre en mayúsculas como título y debajo sus filas.
     * Devuelve null si la hoja no aporta nada nuevo a este bloque.
     *
     * @param  array<string, true>  $vistos  Textos largos ya escritos en el bloque (se actualiza)
     * @param  int  $largoMinimo  Desde qué largo un texto no se repite (1: ninguno se repite)
     */
    private function seccion(string $hoja, array $filas, array &$vistos, int $largoMinimo = self::LARGO_PARA_NO_REPETIR): ?string
    {
        $textos = $this->filas($filas, $vistos, $largoMinimo);

        return $textos === [] ? null : mb_strtoupper($hoja) . "

" . implode("

", $textos);
    }

    /**
     * Escribe cada fila con un dato por línea ("Título: valor"). Los textos largos que ya
     * aparecieron en el mismo bloque no se repiten; los cortos sí ("Producto", "10 %"), porque
     * se repiten con sentido en cada fila. Una fila que no aporta nada nuevo se omite.
     *
     * @param  array<string, true>  $vistos  Textos largos ya escritos en el bloque (se actualiza)
     * @return list<string>
     */
    private function filas(array $filas, array &$vistos, int $largoMinimo): array
    {
        $textos = [];
        foreach ($filas as $fila) {
            $partes = [];
            $aportaAlgo = false;
            foreach ($fila as $titulo => $valor) {
                if (mb_strlen($valor) >= $largoMinimo) {
                    $clave = mb_strtolower($valor);
                    if (isset($vistos[$clave])) {
                        continue;
                    }
                    $vistos[$clave] = true;
                    $aportaAlgo = true;
                }
                $partes[] = is_string($titulo) ? $this->etiqueta($titulo) . ": {$valor}" : $valor;
            }

            // Con títulos, un dato por párrafo. Sin títulos (el encabezado del formato: "INSTRUCTOR",
            // "TRIM", "PROGRAMA"…) la fila va en una sola línea: cada celda suelta parecería un título
            $sinTitulos = array_filter(array_keys($fila), 'is_string') === [];
            $texto = implode($sinTitulos ? ' · ' : self::SEPARADOR_DATOS, $partes);

            // Una fila con solo textos cortos se escribe si no es idéntica a otra ya escrita
            if (! $aportaAlgo) {
                $clave = 'fila:' . mb_strtolower($texto);
                if ($texto === '' || isset($vistos[$clave])) {
                    continue;
                }
                $vistos[$clave] = true;
            }
            $textos[] = $texto;
        }

        return $textos;
    }

    /**
     * Un título de columna escrito todo en mayúsculas ("ACCIONES A DESARROLLAR") se pasa a
     * "Acciones a desarrollar": así se lee como etiqueta y no como el título de una sección.
     * Las siglas cortas ("URL", "TRIM") se dejan como están.
     */
    private function etiqueta(string $titulo): string
    {
        if ($titulo !== mb_strtoupper($titulo) || mb_strlen($titulo) <= 4) {
            return $titulo;
        }
        $minusculas = mb_strtolower($titulo);

        return mb_strtoupper(mb_substr($minusculas, 0, 1)) . mb_substr($minusculas, 1);
    }
}
