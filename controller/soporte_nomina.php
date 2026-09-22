<?php

// Evita que mensajes inesperados contaminen la respuesta JSON.
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_once ('../config/conexion.php');
require_once ('../models/SoporteNomina.php');

$soporteNomina = new SoporteNomina();

// Devuelve siempre una respuesta JSON limpia.
function responderJson($respuesta, $codigo = 200)
{
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($codigo);

    echo json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

// Normaliza el encabezado del Excel.
function normalizarEncabezado($valor)
{
    $valor = trim((string) $valor);
    $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor);
    $valor = mb_strtolower($valor, 'UTF-8');
    $valor = preg_replace('/\s+/', '_', $valor);

    return $valor;
}

// Normaliza la cédula para compararla con empleados.cedu_empl.
function normalizarCedula($valor)
{
    if ($valor === null || $valor === '') {
        return '';
    }

    // Evita notación científica cuando Excel almacena la cédula como número.
    if (is_numeric($valor)) {
        $valor = number_format((float) $valor, 0, '', '');
    }

    // La cédula debe compararse únicamente por sus dígitos.
    return preg_replace('/[^0-9]/', '', (string) $valor);
}

// Convierte el valor monetario proveniente del Excel a número.
function normalizarValor($valor)
{
    if ($valor === null || $valor === '') {
        return null;
    }

    if (is_numeric($valor)) {
        return (float) $valor;
    }

    $valor = trim((string) $valor);
    $valor = str_replace(['$', ' ', "\u{00A0}"], '', $valor);

    // Formato colombiano: 651.406,09
    if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } elseif (strpos($valor, ',') !== false) {
        $valor = str_replace(',', '.', $valor);
    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $valor)) {
        // Formato como 651.406
        $valor = str_replace('.', '', $valor);
    }

    if (!is_numeric($valor)) {
        return null;
    }

    return (float) $valor;
}

$op = isset($_GET['op']) ? $_GET['op'] : '';

switch ($op) {
    case 'validar_archivo':
        try {
            // Valida el periodo seleccionado.
            $mes = isset($_POST['periodo_mes'])
                ? (int) $_POST['periodo_mes']
                : 0;

            $anio = isset($_POST['periodo_anio'])
                ? trim($_POST['periodo_anio'])
                : '';

            if ($mes < 1 || $mes > 12 || $anio === '') {
                responderJson([
                    'success' => false,
                    'mensaje' => 'Debe seleccionar un periodo válido.'
                ], 400);
            }

            // Valida que el archivo haya sido enviado correctamente.
            if (
                !isset($_FILES['archivo_auxilio']) ||
                $_FILES['archivo_auxilio']['error'] !== UPLOAD_ERR_OK
            ) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'No se recibió correctamente el archivo de auxilios.'
                ], 400);
            }

            $archivo = $_FILES['archivo_auxilio'];

            $extension = strtolower(
                pathinfo($archivo['name'], PATHINFO_EXTENSION)
            );

            if (!in_array($extension, ['xlsx', 'xls'])) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'El archivo debe estar en formato Excel (.xlsx o .xls).'
                ], 400);
            }

            /*
             * La lectura del Excel utilizará PhpSpreadsheet.
             * Se verifica primero que Composer y la librería estén disponibles.
             */
            $autoload = dirname(__DIR__) . '/vendor/autoload.php';

            if (!file_exists($autoload)) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'No se encontró el autoload de Composer para procesar archivos Excel.'
                ], 500);
            }

            require_once ($autoload);

            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'La librería PhpSpreadsheet no está disponible en el proyecto.'
                ], 500);
            }

            // Carga la primera hoja activa del archivo.
            $libro = \PhpOffice\PhpSpreadsheet\IOFactory::load(
                $archivo['tmp_name']
            );

            $hoja = $libro->getActiveSheet();

            $ultimaColumna = $hoja->getHighestDataColumn();

            $numeroUltimaColumna =
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
                    $ultimaColumna
                );

            // Obtiene los encabezados de la primera fila.
            $columnas = [];

            for ($columna = 1; $columna <= $numeroUltimaColumna; $columna++) {
                $encabezado = normalizarEncabezado(
                    $hoja->getCell([$columna, 1])->getValue()
                );

                if ($encabezado !== '') {
                    $columnas[$encabezado] = $columna;
                }
            }

            // El archivo debe contener estos dos encabezados.
            if (
                !isset($columnas['cedu_empl']) ||
                !isset($columnas['valor'])
            ) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'El archivo debe contener las columnas cedu_empl y valor.'
                ], 400);
            }

            $ultimaFila = $hoja->getHighestDataRow();

            $filas = [];
            $cedulasArchivo = [];

            // Lee los registros desde la segunda fila.
            for ($fila = 2; $fila <= $ultimaFila; $fila++) {
                $cedula = normalizarCedula(
                    $hoja
                        ->getCell([
                            $columnas['cedu_empl'],
                            $fila
                        ])
                        ->getCalculatedValue()
                );

                $valor = normalizarValor(
                    $hoja
                        ->getCell([
                            $columnas['valor'],
                            $fila
                        ])
                        ->getCalculatedValue()
                );

                // Ignora filas completamente vacías.
                if ($cedula === '' && ($valor === null || (float) $valor == 0)) {
                    continue;
                }

                // Una fila sin cédula no puede ser validada.
                if ($cedula === '') {
                    responderJson([
                        'success' => false,
                        'mensaje' => "La fila {$fila} no contiene cedu_empl."
                    ], 400);
                }

                // El valor debe ser numérico y mayor o igual a cero.
                if ($valor === null || $valor < 0) {
                    responderJson([
                        'success' => false,
                        'mensaje' => "La fila {$fila} contiene un valor de auxilio inválido."
                    ], 400);
                }

                // Si el valor es 0, no se muestra ni se procesa.
                if ((float) $valor == 0) {
                    continue;
                }
                // Evita que el mismo empleado sea cargado dos veces en el archivo.
                if (isset($cedulasArchivo[$cedula])) {
                    responderJson([
                        'success' => false,
                        'mensaje' => "La cédula {$cedula} está repetida en el archivo."
                    ], 400);
                }

                $cedulasArchivo[$cedula] = true;

                $filas[] = [
                    'fila_excel' => $fila,
                    'cedu_empl' => $cedula,
                    'valor' => $valor
                ];
            }

            if (empty($filas)) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'El archivo no contiene registros para procesar.'
                ], 400);
            }

            /*
             * El Controller no realiza consultas SQL.
             * Envía los registros al modelo para validar las cédulas.
             */
            $resultado = $soporteNomina->validarEmpleadosArchivo($filas);

            if (
                empty($resultado['registros']) &&
                empty($resultado['inconsistencias'])
            ) {
                responderJson([
                    'success' => false,
                    'mensaje' => 'El archivo no contiene registros con valor de auxilio mayor a cero.'
                ], 400);
            }

            responderJson([
                'success' => true,
                'mensaje' => 'Archivo validado correctamente.',
                'periodo' => [
                    'mes' => $mes,
                    'anio' => $anio
                ],
                'registros' => $resultado['registros'],
                'inconsistencias' => $resultado['inconsistencias']
            ]);
        } catch (Throwable $e) {
            responderJson([
                'success' => false,
                'mensaje' => 'Ocurrió un error al procesar el archivo.',
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ], 500);
        }

        break;

    case 'procesar':
        /*
         * Esta operación todavía no se desarrolla.
         * Falta definir la tabla que almacenará el soporte y su estado.
         */
        responderJson([
            'success' => false,
            'mensaje' => 'El procesamiento contable todavía no se encuentra habilitado.'
        ], 409);

        break;

    default:
        responderJson([
            'success' => false,
            'mensaje' => 'Operación no válida.'
        ], 400);

        break;
}
