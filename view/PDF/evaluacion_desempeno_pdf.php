<?php
// Se desactiva la salida visible de errores para proteger el flujo binario del PDF.
ini_set('display_errors', '0');
ob_start();

require_once(__DIR__ . '/../../config/conexion.php');
require_once(__DIR__ . '/../../models/Evaluacion.php');
require_once(__DIR__ . '/../../public/tcpfd/tcpdf.php');

/**
 * Convierte un valor en texto seguro para las tablas HTML de TCPDF.
 * Recibe cualquier valor escalar y devuelve una cadena escapada en UTF-8.
 */
function textoPdfSeguro($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Presenta una calificacion con dos decimales o indica que no fue registrada.
 * Recibe un valor numerico o nulo y devuelve el texto listo para el reporte.
 */
function calificacionPdf($valor) {
    return $valor === null ? 'No registrada' : number_format((float)$valor, 2, ',', '.');
}

/**
 * Calcula la calificacion consolidada de una pregunta usando los tres tipos de evaluacion.
 * Recibe la fila de detalle y devuelve el promedio de autoevaluacion, coevaluacion y subevaluacion, o null si no hay notas.
 */
function promedioCalificacionesPreguntaPdf($pregunta) {
    $campos = array('autoevaluacion', 'coevaluacion', 'subevaluacion');
    $total = 0.0;
    $tieneCalificacion = false;

    // Las evaluaciones ausentes aportan cero y no redistribuyen su participacion en el promedio de la pregunta.
    foreach ($campos as $campo) {
        if (isset($pregunta[$campo]) && $pregunta[$campo] !== null) {
            $total += (float)$pregunta[$campo];
            $tieneCalificacion = true;
        }
    }

    return $tieneCalificacion ? round($total / 3, 2) : null;
}

/**
 * Determina la interpretacion institucional del resultado ponderado final.
 * Recibe el resultado numerico y devuelve la etiqueta definida para su rango.
 */
function interpretarResultadoPdf($resultado) {
    if ($resultado >= 4.60) {
        return 'Excelente';
    }
    if ($resultado >= 4.00) {
        return 'Bueno';
    }
    if ($resultado >= 3.00) {
        return 'Aceptable';
    }
    if ($resultado >= 2.00) {
        return 'Bajo';
    }
    if ($resultado >= 1.00) {
        return 'Crítico';
    }
    return 'Sin calificación suficiente';
}

/**
 * PDF institucional con encabezado y pie repetidos automaticamente en cada pagina.
 */
class EvaluacionDesempenoPDF extends TCPDF {
    private $colaborador = '';
    private $fechaImpresion = '';

    /**
     * Asigna los datos variables que deben aparecer en todas las paginas.
     * Recibe nombre del colaborador y fecha de impresion; no devuelve valor.
     */
    public function configurarEncabezado($colaborador, $fechaImpresion) {
        $this->colaborador = $colaborador;
        $this->fechaImpresion = $fechaImpresion;
    }

    /**
     * Dibuja logo, titulo y control documental en cada pagina.
     * No recibe parametros y no devuelve valor.
     */
    public function Header() {
        $logo = __DIR__ . '/../../public/img/logo asf.png';
        $this->SetDrawColor(35, 35, 35);
        $this->SetLineWidth(0.15);

        if (file_exists($logo)) {
            $this->Image($logo, 12, 7, 34, 0, '', '', '', false, 300);
        }

        // El titulo y el control documental conservan la composicion abierta del formato institucional.
        $this->SetXY(54, 15);
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(89, 8, 'EVALUACIÓN DE DESEMPEÑO', 0, 0, 'C');

        $this->SetFont('helvetica', '', 7.5);
        $this->SetXY(148, 8);
        $this->Cell(27, 4.5, 'Versión: ', 0, 0, 'R');
        $this->Cell(29, 4.5, ' 7', 0, 1, 'L');
        $this->SetX(148);
        $this->Cell(27, 4.5, 'Implementación: ', 0, 0, 'R');
        $this->Cell(29, 4.5, ' Abril 20 del 2023', 0, 1, 'L');
        $this->SetX(148);
        $this->Cell(27, 4.5, 'Código: ', 0, 0, 'R');
        $this->Cell(29, 4.5, ' GH-F-4', 0, 1, 'L');
        $this->SetX(148);
        $this->Cell(27, 4.5, 'Tipo documento: ', 0, 0, 'R');
        $this->Cell(29, 4.5, ' Formato', 0, 1, 'L');

        // La franja de trazabilidad replica impresion, colaborador y pagina del documento de referencia.
        $this->SetXY(10, 40);
        $this->SetFont('times', 'B', 8.5);
        $this->Cell(55, 8, 'Impresión: ' . $this->fechaImpresion, 1, 0, 'L');
        $this->Cell(106, 8, $this->colaborador, 1, 0, 'L');
        $this->Cell(35, 8, 'Página: ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 1, 1, 'C');
    }

    /**
     * Dibuja el pie institucional y la numeracion total en cada pagina.
     * No recibe parametros y no devuelve valor.
     */
    public function Footer() {
        $this->SetY(-17);
        $this->SetFont('helvetica', 'I', 7.5);
        $this->SetDrawColor(35, 35, 35);
        $this->Cell(196, 5, '', 'T', 1, 'C');
        $this->Cell(196, 4, 'El espíritu de las Grandes Obras', 0, 0, 'C');
    }
}

try {
    // Se aceptan solamente un id entero positivo y un periodo de cuatro digitos.
    $empleadoId = filter_input(INPUT_GET, 'empleado_id', FILTER_VALIDATE_INT);
    $periodo = isset($_GET['periodo']) ? trim((string)$_GET['periodo']) : '';

    if (!$empleadoId || !preg_match('/^\d{4}$/', $periodo)) {
        throw new InvalidArgumentException('Los parámetros del reporte no son válidos.');
    }

    $modelo = new Evaluacion();
    $resumen = $modelo->get_resumen_reporte_desempeno($empleadoId, $periodo);

    // La generacion se detiene cuando no hay evaluaciones para la combinacion solicitada.
    if (!$resumen) {
        throw new RuntimeException('No existen evaluaciones para el empleado y periodo seleccionados.');
    }

    $promediosFilas = $modelo->get_promedios_reporte_desempeno($empleadoId, $periodo);
    $detalle = $modelo->get_detalle_reporte_desempeno($empleadoId, $periodo);
    $cierre = $modelo->get_cierre_reporte_desempeno($empleadoId, $periodo);

    // Se indexan los promedios para presentar bloques y aplicar exactamente los pesos definidos.
    $promedios = array();
    foreach ($promediosFilas as $fila) {
        $promedios[$fila['evde_tipo']] = $fila;
    }

    $tipos = array(
        'AUTOEVALUACION' => array('etiqueta' => 'Autoevaluación', 'peso' => 0.05),
        'COEVALUACION' => array('etiqueta' => 'Coevaluación', 'peso' => 0.05),
        'SUBEVALUACION' => array('etiqueta' => 'Subevaluación', 'peso' => 0.90)
    );
    $resultadoFinal = 0.0;
    foreach ($tipos as $codigo => $configuracion) {
        $promedioGeneral = isset($promedios[$codigo]) ? (float)$promedios[$codigo]['prom_general'] : 0.0;
        $tipos[$codigo]['promedio'] = $promedioGeneral;
        $tipos[$codigo]['ponderado'] = $promedioGeneral * $configuracion['peso'];
        $resultadoFinal += $tipos[$codigo]['ponderado'];
    }
    $resultadoFinal = round($resultadoFinal, 2);

    // Las preguntas se agrupan por los tres bloques reales almacenados en el detalle.
    $bloques = array(
        'PRODUCTIVIDAD' => array(),
        'ACTITUD' => array(),
        'CONDUCTA LABORAL' => array()
    );
    foreach ($detalle as $pregunta) {
        if (!isset($bloques[$pregunta['evdd_bloque']])) {
            $bloques[$pregunta['evdd_bloque']] = array();
        }
        $bloques[$pregunta['evdd_bloque']][] = $pregunta;
    }

    date_default_timezone_set('America/Bogota');
    $fechaImpresion = date('d-m-Y h:i A');
    $superior = $cierre && !empty($cierre['superior_evaluador'])
        ? $cierre['superior_evaluador']
        : 'No registrado';

    // Se fuerza 216 x 279 mm para garantizar carta aun si la configuracion global de TCPDF define A4.
    $pdf = new EvaluacionDesempenoPDF('P', 'mm', array(216, 279), true, 'UTF-8', false);
    $pdf->SetCreator('Control de Personal');
    $pdf->SetAuthor('Asfaltart S.A.S.');
    $pdf->SetTitle('Evaluación de desempeño - ' . $resumen['nomb_empl']);
    $pdf->SetMargins(10, 53, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(true, 21);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    // TCPDF administra internamente el alias del total de paginas usado por getAliasNbPages().
    $pdf->configurarEncabezado($resumen['nomb_empl'], $fechaImpresion);
    $pdf->AddPage();
    $pdf->SetFont('times', '', 9.5);

    $css = '<style>
        h2 { color:#000000; font-family:times; font-size:13px; font-weight:bold; text-align:center; margin:0; }
        table { border-collapse:collapse; width:100%; }
        th { color:#000000; font-family:times; font-size:9.5px; font-weight:bold; text-align:center; }
        td { color:#000000; font-family:times; font-size:9px; }
        td, th { border:0.35px solid #222222; padding:3px; }
        .titulo { font-family:times; font-size:13px; font-weight:bold; text-align:center; }
        .label { font-weight:bold; }
        .center { text-align:center; }
        .small { font-size:7.2px; text-align:center; }
        .resultado { font-weight:bold; }
    </style>';

    /**
     * Escribe un bloque conservando los estilos del formato, ya que TCPDF no comparte CSS entre llamadas.
     * Recibe el HTML de la seccion y no devuelve valor.
     */
    $escribirHtml = function ($html) use ($pdf, $css) {
        $pdf->writeHTML($css . $html, true, false, true, false, '');
    };

    // La informacion general usa solamente los valores consultados nuevamente desde el modelo.
    $html = '<table cellpadding="3" nobr="true">
            <tr><td class="titulo" colspan="3">INFORMACIÓN GENERAL</td></tr>
            <tr><td class="label" rowspan="3" width="18%">Evaluado:</td><td class="label" width="18%">Período:</td><td width="64%">' . textoPdfSeguro($resumen['periodo']) . '</td></tr>
            <tr><td class="label">Nombre:</td><td>' . textoPdfSeguro($resumen['nomb_empl']) . '</td></tr>
            <tr><td class="label">Cargo:</td><td>' . textoPdfSeguro($resumen['nomb_carg']) . '</td></tr>
        </table><br><br>';
    $escribirHtml($html);

    // Cada bloque crea su propia tabla con encabezado repetible y filas indivisibles para controlar saltos.
    foreach ($bloques as $nombreBloque => $preguntas) {
        if (empty($preguntas)) {
            continue;
        }

        $cantidadFilasBloque = count($preguntas) + 1;
        $calificacionesBloque = array();
        $html = '<table cellpadding="3" nobr="true">
            <thead>
                <tr><th class="titulo" colspan="3">FACTOR DE DESEMPEÑO</th></tr>
                <tr>
                    <th width="18%">ÁREA</th>
                    <th width="64%">DESCRIPCIÓN</th>
                    <th width="18%">CALIFICACIÓN</th>
                </tr>
            </thead><tbody>';

        foreach ($preguntas as $indicePregunta => $pregunta) {
            $calificacionPregunta = promedioCalificacionesPreguntaPdf($pregunta);
            if ($calificacionPregunta !== null) {
                $calificacionesBloque[] = $calificacionPregunta;
            }

            $html .= '<tr nobr="true">';
            if ($indicePregunta === 0) {
                // El rowspan combina visualmente el area para todas sus preguntas y la fila del promedio.
                $html .= '<td class="center" width="18%" rowspan="' . $cantidadFilasBloque . '">' . textoPdfSeguro(ucwords(strtolower($nombreBloque))) . '</td>';
            }
            $html .= '<td width="64%">' . textoPdfSeguro($pregunta['evdd_pregunta']) . '</td>
                <td class="center" width="18%">' . textoPdfSeguro(calificacionPdf($calificacionPregunta)) . '</td>
                </tr>';
        }

        // El total del bloque es el promedio de las calificaciones consolidadas de sus preguntas.
        $promedioBloque = count($calificacionesBloque) > 0
            ? round(array_sum($calificacionesBloque) / count($calificacionesBloque), 2)
            : null;
        $html .= '<tr nobr="true" class="resultado">
            <td>Promedio del bloque:</td>
            <td class="center">' . textoPdfSeguro(calificacionPdf($promedioBloque)) . '</td>
            </tr></tbody></table><br><br>';
        $escribirHtml($html);
    }

    // El consolidado aplica 5 %, 5 % y 90 %; una coevaluacion ausente aporta cero sin redistribucion.
    $html = '<table cellpadding="4" nobr="true">
        <thead><tr><th class="titulo" colspan="5">CALIFICACIÓN FINAL</th></tr>
        <tr><th width="8%"></th><th width="32%">TIPO DE EVALUACIÓN</th><th width="20%">PROMEDIO</th><th width="20%">PESO</th><th width="20%">RESULTADO</th></tr></thead><tbody>';
    $letrasTipo = array('A', 'B', 'C');
    $indiceTipo = 0;
    foreach ($tipos as $tipo) {
        $html .= '<tr>
            <td class="center" width="8%">' . $letrasTipo[$indiceTipo] . '</td>
            <td class="center" width="32%">' . textoPdfSeguro(strtoupper($tipo['etiqueta'])) . '</td>
            <td class="center" width="20%">' . number_format($tipo['promedio'], 2, ',', '.') . '</td>
            <td class="center" width="20%">' . number_format($tipo['peso'] * 100, 2, ',', '.') . ' %</td>
            <td class="center" width="20%">' . number_format($tipo['ponderado'], 2, ',', '.') . '</td>
        </tr>';
        $indiceTipo++;
    }
    $html .= '<tr class="resultado"><td class="center" colspan="4">CALIFICACIÓN (A+B+C) =</td><td class="center">' . number_format($resultadoFinal, 2, ',', '.') . '</td></tr>
        <tr class="resultado"><td class="center" colspan="4">RESULTADO DE LA EVALUACIÓN</td><td class="small">' . textoPdfSeguro(interpretarResultadoPdf($resultadoFinal)) . '</td></tr>
        </tbody></table><br>';
    $htmlConsolidado = $html;

    // El cierre se presenta aunque falte alguna respuesta, sin incorporar observaciones de otro periodo.
    $html = '<table cellpadding="4">
        <thead><tr><th class="titulo" colspan="2">PREGUNTAS DE CIERRE DE LA SUBEVALUACIÓN</th></tr></thead><tbody>
        <tr nobr="true"><td class="label" width="27%">Fortalezas:</td><td width="73%">' . textoPdfSeguro($cierre && $cierre['fortalezas'] ? $cierre['fortalezas'] : 'No registrada') . '</td></tr>
        <tr nobr="true"><td class="label">Oportunidades de mejora:</td><td>' . textoPdfSeguro($cierre && $cierre['oportunidades_mejora'] ? $cierre['oportunidades_mejora'] : 'No registrada') . '</td></tr>
        <tr nobr="true"><td class="label">Apoyo requerido:</td><td>' . textoPdfSeguro($cierre && $cierre['apoyo_requerido'] ? $cierre['apoyo_requerido'] : 'No registrado') . '</td></tr>
        <tr nobr="true"><td class="label">Fecha de revisión:</td><td>' . textoPdfSeguro($cierre && $cierre['fecha_revision'] ? $cierre['fecha_revision'] : 'No registrada') . '</td></tr>
        </tbody></table><br><br>';
    $escribirHtml($html);

    // La escala nueva reemplaza la interpretacion historica de 1 a 63 sin alterar el estilo del formato.
    $html = '<table cellpadding="4" nobr="true">
        <thead><tr><th class="titulo" colspan="2">INTERPRETACIÓN DE LA EVALUACIÓN DE DESEMPEÑO</th></tr></thead><tbody>
        <tr><td colspan="2">La interpretación se determina con el resultado final ponderado:</td></tr>
        <tr><td class="center" width="50%">Excelente:</td><td class="center" width="50%">4,60 a 5,00</td></tr>
        <tr><td class="center">Bueno:</td><td class="center">4,00 a 4,59</td></tr>
        <tr><td class="center">Aceptable:</td><td class="center">3,00 a 3,99</td></tr>
        <tr><td class="center">Bajo:</td><td class="center">2,00 a 2,99</td></tr>
        <tr><td class="center">Crítico:</td><td class="center">1,00 a 1,99</td></tr>
        <tr><td class="center">Sin calificación suficiente:</td><td class="center">Menor a 1,00</td></tr>
        </tbody></table><br><br>';
    $escribirHtml($html);

    // El consolidado se presenta despues del detalle y la escala, como en el formato institucional.
    $escribirHtml($htmlConsolidado);

    // Las firmas son espacios fisicos; esta version no incorpora imagenes ni firma digital.
    $html = '<table cellpadding="5" nobr="true">
        <tr><td width="47%" height="38"></td><td width="6%"></td><td width="47%"></td></tr>
        <tr><td style="border-top:0.5px solid #333333; text-align:center;">Firma del empleado evaluado<br>' . textoPdfSeguro($resumen['nomb_empl']) . '</td>
            <td></td>
            <td style="border-top:0.5px solid #333333; text-align:center;">Firma del superior evaluador<br>' . textoPdfSeguro($superior) . '</td></tr>
        </table>';
    $escribirHtml($html);

    $nombreArchivo = 'evaluacion_desempeno_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $resumen['nomb_empl']) . '_' . $periodo . '.pdf';
    ob_end_clean();
    $pdf->Output($nombreArchivo, 'I');
} catch (Exception $e) {
    // Se entrega un error de texto limpio y nunca un PDF parcial o contaminado por HTML previo.
    if (ob_get_length()) {
        ob_clean();
    }
    if ($e instanceof InvalidArgumentException) {
        $codigoEstado = 400;
        $mensajeError = $e->getMessage();
    } elseif ($e instanceof RuntimeException) {
        $codigoEstado = 404;
        $mensajeError = $e->getMessage();
    } else {
        $codigoEstado = 500;
        $mensajeError = 'No fue posible generar el reporte de evaluación de desempeño.';
    }
    http_response_code($codigoEstado);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensajeError;
    ob_end_flush();
}
