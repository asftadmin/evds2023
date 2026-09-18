<?php

ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Jornada.php';
require_once __DIR__ . '/../models/JornadaReporteContable.php';
require_once __DIR__ . '/../public/assets/tcpdf/tcpdf.php';

/**
 * Construcción visual del formato institucional GH-F-19.
 */
class JornadaReportePDF extends TCPDF
{
    private $datosEncabezado = [];

    // Coordenada inicial y anchos tomados de la distribución del GH-F-19.
    private $xTabla = 10.5;

    private $anchos = [
        6.8,  // Día
        15.1,  // Fecha
        9.3,  // Entrada
        9.3,  // Salida
        13.0,  // Ordinarias 100
        8.7,  // RN 101
        8.8,  // HED 107
        9.9,  // HEN 108
        10.0,  // HEDF 109
        9.9,  // HENF 110
        6.8,  // RF 115
        30.5,  // Frente
        47.4,  // Actividad
        27.7,  // Empleado
        23.8,  // Persona autoriza
        21.4  // VoBo
    ];

    public function configurarEmpleado(array $snapshot)
    {
        $this->datosEncabezado = $snapshot;
    }

    /**
     * Encabezado institucional repetido en todas las páginas.
     */
    public function Header()
    {
        $logo = __DIR__ . '/../public/img/logo asf.png';

        if (is_file($logo)) {
            $this->Image($logo, 12, 5, 23, 0, '', '', '', false, 200);
        }

        // Título principal.
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('dejavusans', 'B', 4.6);
        $this->SetXY(43, 8);

        $this->Cell(
            175,
            4,
            'PLANILLA MENSUAL PARA CONTROL DE TIEMPO APROBACIÓN DE HORAS EXTRAS '
                . 'Y REPORTE DE NOVEDADES DE PERSONAL',
            0,
            0,
            'C',
            false,
            '',
            1
        );

        // Control documental.
        $this->SetFont('dejavusans', '', 3.6);
        $this->SetXY(230, 4);

        $this->Cell(39, 2.6, 'Versión: 3', 0, 2, 'L');
        $this->Cell(39, 2.6, 'Fecha: 1 Octubre 2023', 0, 2, 'L');
        $this->Cell(39, 2.6, 'Código: GH-F-19', 0, 2, 'L');
        $this->Cell(39, 2.6, 'Tipo de Documento: Formato', 0, 2, 'L');

        $this->Cell(
            39,
            2.6,
            'Página ' . $this->getPageNumGroupAlias()
                . ' de ' . $this->getPageGroupAlias(),
            0,
            0,
            'L'
        );

        // Línea institucional inferior del encabezado.
        $this->SetLineWidth(0.15);
        $this->Line(
            $this->xTabla,
            20,
            $this->xTabla + array_sum($this->anchos),
            20
        );
    }

    /**
     * Obtiene la coordenada X inicial de una columna.
     */
    private function xColumna($indice)
    {
        $x = $this->xTabla;

        for ($i = 0; $i < $indice; $i++) {
            $x += $this->anchos[$i];
        }

        return $x;
    }

    /**
     * Calcula el ancho comprendido entre varias columnas.
     */
    private function anchoColumnas($desde, $hasta)
    {
        $ancho = 0;

        for ($i = $desde; $i <= $hasta; $i++) {
            $ancho += $this->anchos[$i];
        }

        return $ancho;
    }

    /**
     * Dibuja una celda estándar.
     */
    private function celda(
        $x,
        $y,
        $w,
        $h,
        $texto = '',
        $alineacion = 'C',
        $negrita = false,
        $tamano = 4,
        $borde = 1
    ) {
        $this->SetFont(
            'dejavusans',
            $negrita ? 'B' : '',
            $tamano
        );

        $this->SetXY($x, $y);

        $this->MultiCell(
            $w,
            $h,
            (string) $texto,
            $borde,
            $alineacion,
            false,
            0,
            $x,
            $y,
            true,
            0,
            false,
            true,
            $h,
            'M',
            true
        );
    }

    /**
     * Dibuja texto vertical para columnas angostas.
     */
    private function textoVertical(
        $x,
        $y,
        $w,
        $h,
        $texto,
        $tamano = 3.2
    ) {
        // La celda se dibuja primero.
        $this->Rect($x, $y, $w, $h);

        $cx = $x + ($w / 2);
        $cy = $y + ($h / 2);

        $this->StartTransform();

        // Rota el contenido alrededor del centro de la celda.
        $this->Rotate(90, $cx, $cy);

        $this->SetFont('dejavusans', 'B', $tamano);

        $this->SetXY(
            $cx - ($h / 2),
            $cy - ($w / 2)
        );

        $this->MultiCell(
            $h,
            $w,
            $texto,
            0,
            'C',
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $w,
            'M',
            true
        );

        $this->StopTransform();
    }

    /**
     * Convierte una fecha del sistema al formato visual del reporte.
     */
    private function fechaVisual($fecha, $formato = 'd/m/Y')
    {
        if (empty($fecha)) {
            return '';
        }

        try {
            return (new DateTimeImmutable($fecha))->format($formato);
        } catch (Throwable $e) {
            return (string) $fecha;
        }
    }

    /**
     * Dibuja la información del empleado únicamente en la primera página.
     */
    public function dibujarDatosEmpleado(array $datos, $y = 21)
    {
        $edad = '';

        if (
            !empty($datos['fecha_nacimiento']) &&
            !empty($datos['hasta'])
        ) {
            try {
                $nacimiento = new DateTimeImmutable(
                    $datos['fecha_nacimiento']
                );

                $fechaPeriodo = new DateTimeImmutable(
                    $datos['hasta']
                );

                $edad = (string) $nacimiento
                    ->diff($fechaPeriodo)
                    ->y;
            } catch (Throwable $e) {
                $edad = '';
            }
        }

        $periodo = $this->fechaVisual(
            $datos['desde'] ?? ''
        ) . ' al ' . $this->fechaVisual(
            $datos['hasta'] ?? ''
        );

        $campos = [
            ['Nombre:', 6.8, $datos['empleado'] ?? '', 55.3],
            ['Cédula:', 8.7, $datos['documento'] ?? '', 29.9],
            ['Cargo:', 12.3, $datos['cargo'] ?? '', 46.7],
            ['Periodo del:', 10.0, $periodo, 43.2],
            ['Edad:', 11.4, $edad, 12.4],
            ['Sexo:', 8.2, $datos['sexo'] ?? '', 13.2]
        ];

        $x = $this->xTabla;
        $alto = 5.5;

        foreach ($campos as $campo) {
            // Etiqueta.
            $this->SetFont('dejavusans', 'B', 3.9);
            $this->SetXY($x, $y);

            $this->Cell(
                $campo[1],
                $alto,
                $campo[0],
                0,
                0,
                'L'
            );

            $x += $campo[1];

            // Valor.
            $this->celda(
                $x,
                $y,
                $campo[3],
                $alto,
                $campo[2],
                'L',
                false,
                4.1
            );

            $x += $campo[3];
        }
    }

    /**
     * Dibuja las tres secciones del encabezado de la tabla.
     */
    public function dibujarEncabezadoTabla($y)
    {
        $altoGrupo = 2.5;
        $altoCabecera = 13.5;
        $altoCodigo = 2.6;

        $this->SetLineWidth(0.15);

        // Leyenda superior del formato.
        $this->SetFont('dejavusans', 'BI', 3.4);
        $this->SetXY($this->xTabla, $y - 2.3);

        $this->Cell(
            array_sum($this->anchos),
            2,
            'Importante: El diligenciamiento debe ser en letra legible y sin tachones',
            0,
            0,
            'C'
        );

        // Primera fila: grupos.
        $this->celda(
            $this->xColumna(0),
            $y,
            $this->anchoColumnas(0, 4),
            $altoGrupo,
            ''
        );

        $this->celda(
            $this->xColumna(5),
            $y,
            $this->anchoColumnas(5, 10),
            $altoGrupo,
            'HORAS EXTRAS AUTORIZADAS',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(11),
            $y,
            $this->anchos[11],
            $altoGrupo,
            'FRENTE',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(12),
            $y,
            $this->anchos[12],
            $altoGrupo,
            'ACTIVIDAD',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(13),
            $y,
            $this->anchoColumnas(13, 15),
            $altoGrupo,
            'FIRMAS',
            'C',
            true,
            3.5
        );

        $yCab = $y + $altoGrupo;

        // Día de la semana.
        $this->textoVertical(
            $this->xColumna(0),
            $yCab,
            $this->anchos[0],
            $altoCabecera,
            "DIA DE LA SEMANA\nL,Ma,Mi,J,V,S,D",
            2.8
        );

        $cabeceras = [
            1 => 'FECHA DD/MM',
            2 => "HORA DE\nENTRADA",
            3 => "HORA DE\nSALIDA",
            4 => "HORAS\nORDINARIAS"
        ];

        foreach ($cabeceras as $indice => $titulo) {
            $this->celda(
                $this->xColumna($indice),
                $yCab,
                $this->anchos[$indice],
                $altoCabecera,
                $titulo,
                'C',
                true,
                3.6
            );
        }

        // Código 101.
        $this->textoVertical(
            $this->xColumna(5),
            $yCab,
            $this->anchos[5],
            $altoCabecera,
            "Recargo Nocturno\n35% 9 PM A 6 AM\nTurno Ordinario",
            2.6
        );

        // Códigos 107 a 110.
        $extras = [
            6 => "Diurnas\n25%\nHASTA 9\nPM",
            7 => "Nocturnas\n75% 9 PM A\n6 AM",
            8 => "Diurnas\nFestivas\n2.00% > 8\nHoras Hasta\nlas 9 PM",
            9 => "Nocturnas\nFestivas\n2.50%\nFestivos\nDespués 9\nPM"
        ];

        foreach ($extras as $indice => $titulo) {
            $this->celda(
                $this->xColumna($indice),
                $yCab,
                $this->anchos[$indice],
                $altoCabecera,
                $titulo,
                'C',
                true,
                3.2
            );
        }

        // Código 115.
        $this->textoVertical(
            $this->xColumna(10),
            $yCab,
            $this->anchos[10],
            $altoCabecera,
            "Recargo Festivas\n1.75% Festivo <= 8 Horas",
            2.5
        );

        // Frente.
        $this->celda(
            $this->xColumna(11),
            $yCab,
            $this->anchos[11],
            $altoCabecera,
            "(INDICAR LA OBRA EN LA QUE ESTA ESE\nDIA)",
            'C',
            true,
            3.4
        );

        // Actividad.
        $this->celda(
            $this->xColumna(12),
            $yCab,
            $this->anchos[12],
            $altoCabecera,
            'RESUMA LA ACTIVIDAD EJECUTADA CUANDO HAY EXTRAS',
            'C',
            true,
            3.4
        );

        // Firmas.
        $this->celda(
            $this->xColumna(13),
            $yCab,
            $this->anchos[13],
            $altoCabecera,
            'EMPLEADO',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(14),
            $yCab,
            $this->anchos[14],
            $altoCabecera,
            'Nombre Persona que Autoriza',
            'C',
            true,
            3.2
        );

        $this->celda(
            $this->xColumna(15),
            $yCab,
            $this->anchos[15],
            $altoCabecera,
            'VoBo Persona que Autoriza',
            'C',
            true,
            3.1
        );

        $yCodigos = $yCab + $altoCabecera;

        // Texto CÓDIGOS.
        $this->celda(
            $this->xColumna(0),
            $yCodigos,
            $this->anchoColumnas(0, 3),
            $altoCodigo,
            'CODIGOS',
            'C',
            true,
            3.4
        );

        $codigos = [
            4 => '100',
            5 => '101',
            6 => '107',
            7 => '108',
            8 => '109',
            9 => '110',
            10 => '115'
        ];

        foreach ($codigos as $indice => $codigo) {
            $this->celda(
                $this->xColumna($indice),
                $yCodigos,
                $this->anchos[$indice],
                $altoCodigo,
                $codigo,
                'C',
                true,
                3.4
            );
        }

        // Zona derecha vacía de la fila de códigos.
        $this->celda(
            $this->xColumna(11),
            $yCodigos,
            $this->anchoColumnas(11, 15),
            $altoCodigo,
            ''
        );

        return $yCodigos + $altoCodigo;
    }

    /**
     * Dibuja una jornada o una fila vacía.
     */
    public function dibujarFilaJornada(
        $y,
        $jornada = null,
        $alto = 7.8
    ) {
        $valores = array_fill(0, 16, '');

        if (!empty($jornada)) {
            $inicio = new DateTimeImmutable(
                $jornada['jornada_inicio']
            );

            $fin = new DateTimeImmutable(
                $jornada['jornada_fin']
            );

            $borrador = !empty($this->datosEncabezado['borrador']);
            $totales = $borrador
                ? ['ORD' => (int) $jornada['jornada_minutos_ordinarios']]
                : je_totales_jornada($jornada);

            $dias = [
                1 => 'L',
                2 => 'Ma',
                3 => 'Mi',
                4 => 'J',
                5 => 'V',
                6 => 'S',
                7 => 'D'
            ];

            $valores[0] = $dias[(int) $inicio->format('N')];
            $valores[1] = $inicio->format('d/m');
            $valores[2] = $inicio->format('h:i a');
            $valores[3] = $fin->format('h:i a');

            $valores[4] = isset($totales['ORD'])
                ? je_horas($totales['ORD'])
                : '';

            $valores[5] = isset($totales['RN'])
                ? je_horas($totales['RN'])
                : '';

            $valores[6] = isset($totales['HED'])
                ? je_horas($totales['HED'])
                : '';

            $valores[7] = isset($totales['HEN'])
                ? je_horas($totales['HEN'])
                : '';

            $valores[8] = isset($totales['HEDF'])
                ? je_horas($totales['HEDF'])
                : '';

            $valores[9] = isset($totales['HENF'])
                ? je_horas($totales['HENF'])
                : '';

            $valores[10] = isset($totales['RF'])
                ? je_horas($totales['RF'])
                : '';

            $valores[11] = $jornada['jornada_ubicacion'] ?? '';
            $valores[12] = $jornada['jornada_actividad'] ?? '';

            // Esta columna corresponde a la firma del empleado.
            $valores[13] = '';

            // Se conserva el responsable que actualmente trae el snapshot.
            $valores[14] = $jornada['aprobado_por'] ?? '';

            // El VoBo se deja disponible para la siguiente etapa.
            $valores[15] = '';
            if ($borrador) {
                for ($i = 5; $i < 16; $i++) {
                    $valores[$i] = '';
                }
            }
        }

        foreach ($valores as $indice => $valor) {
            $alineacion = in_array($indice, [11, 12, 14], true)
                ? 'L'
                : 'C';

            $tamano = in_array($indice, [11, 12, 14], true)
                ? 3.7
                : 4.0;

            $this->celda(
                $this->xColumna($indice),
                $y,
                $this->anchos[$indice],
                $alto,
                $valor,
                $alineacion,
                false,
                $tamano
            );
        }

        return $y + $alto;
    }

    /**
     * Dibuja totales y zona de notas al final del reporte.
     */
    public function dibujarTotalesNotas(
        $y,
        array $totales
    ) {
        $alto = 19;

        // Celda TOTALES.
        $this->celda(
            $this->xColumna(0),
            $y,
            $this->anchoColumnas(0, 3),
            $alto,
            'TOTALES',
            'C',
            true,
            3.8
        );

        $codigos = [
            4 => 'ORD',
            5 => 'RN',
            6 => 'HED',
            7 => 'HEN',
            8 => 'HEDF',
            9 => 'HENF',
            10 => 'RF'
        ];

        foreach ($codigos as $indice => $codigo) {
            $valor = isset($totales[$codigo])
                ? je_horas($totales[$codigo])
                : '00:00';
            if (!empty($this->datosEncabezado['borrador']) && $indice > 4) {
                $valor = '';
            }

            $this->celda(
                $this->xColumna($indice),
                $y,
                $this->anchos[$indice],
                $alto,
                $valor,
                'C',
                true,
                4
            );
        }

        // Frente queda libre en la sección inferior.
        $this->celda(
            $this->xColumna(11),
            $y,
            $this->anchos[11],
            $alto,
            ''
        );

        // Bloque de notas.
        $xNotas = $this->xColumna(12);
        $anchoNotas = $this->anchoColumnas(12, 15);
        $altoNota = $alto / 3;

        $this->celda(
            $xNotas,
            $y,
            $anchoNotas,
            $altoNota,
            'NOTAS:',
            'L',
            true,
            3.6
        );

        $this->celda(
            $xNotas,
            $y + $altoNota,
            $anchoNotas,
            $altoNota,
            ''
        );

        $this->celda(
            $xNotas,
            $y + ($altoNota * 2),
            $anchoNotas,
            $altoNota,
            ''
        );

        return $y + $alto;
    }

    /**
     * Pie institucional ubicado debajo de la cuadrícula.
     */
    public function dibujarLema($y)
    {
        $this->SetFont('dejavusans', 'BI', 3.5);
        $this->SetXY($this->xTabla, $y);

        $this->Cell(
            array_sum($this->anchos),
            2.5,
            'El espíritu de las grandes obras',
            0,
            0,
            'C'
        );
    }
}

function je_fallar($mensaje, $estado = 400)
{
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($estado);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

function je_entero($nombre)
{
    $valor = filter_var(
        $_GET[$nombre] ?? null,
        FILTER_VALIDATE_INT
    );
    return $valor && $valor > 0 ? (int) $valor : null;
}

function je_html($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function je_xml($valor)
{
    return htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function je_horas($minutos)
{
    $minutos = max(0, (int) $minutos);
    return str_pad((string) floor($minutos / 60), 2, '0', STR_PAD_LEFT)
        . ':'
        . str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT);
}

function je_nombre_archivo($texto)
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $texto);
    return trim($texto, '_') ?: 'reporte_jornadas';
}

function je_validar_acceso()
{
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $rol_id = (int) ($_SESSION['user_rol'] ?? 0);
    if ($user_id <= 0 || $rol_id <= 0) {
        je_fallar('Debe iniciar sesión.', 401);
    }
    $jornada = new Jornada();
    $empleado = $jornada->obtener_empleado_por_usuario($user_id);
    if (
        !$empleado ||
        (int) $empleado['esta_empl'] !== 1 ||
        $empleado['rol_nomb'] !== 'Contabilidad' ||
        !$jornada->tiene_permiso_menu(
            $rol_id,
            'reporte_contable',
            false
        )
    ) {
        je_fallar('No tiene permiso para exportar este reporte.', 403);
    }
}

function je_totales_jornada($jornada)
{
    $totales = [];
    foreach ($jornada['segmentos'] ?? [] as $segmento) {
        $codigo = $segmento['codigo'];
        if ($codigo === 'NO_LIQ') {
            continue;
        }
        $totales[$codigo] = ($totales[$codigo] ?? 0)
            + (int) $segmento['minutos'];
    }
    return $totales;
}

function je_pdf_empleado(
    JornadaReportePDF $pdf,
    $snapshot,
    $lote
) {
    $pdf->configurarEmpleado($snapshot);
    $pdf->startPageGroup();

    $jornadas = array_values(
        $snapshot['jornadas'] ?? []
    );

    // La primera página del GH-F-19 dispone de 17 filas.
    $paginas = [
        array_slice($jornadas, 0, 17)
    ];

    // Las páginas siguientes tienen capacidad para 16 filas.
    $restantes = array_slice($jornadas, 17);

    while (!empty($restantes)) {
        $paginas[] = array_slice($restantes, 0, 16);
        $restantes = array_slice($restantes, 16);
    }

    // El formato institucional siempre posee como mínimo dos páginas.
    if (count($paginas) === 1) {
        $paginas[] = [];
    }

    foreach ($paginas as $numeroPagina => $jornadasPagina) {
        $pdf->AddPage('L', 'LETTER');

        $esPrimera = $numeroPagina === 0;
        $esUltima = $numeroPagina === count($paginas) - 1;

        if ($esPrimera) {
            // Datos del empleado solamente en la primera página.
            $pdf->dibujarDatosEmpleado(
                $snapshot,
                21
            );

            $y = $pdf->dibujarEncabezadoTabla(
                29
            );

            $capacidad = 17;
        } else {
            // Desde la página 2 la tabla inicia más arriba.
            $y = $pdf->dibujarEncabezadoTabla(
                23
            );

            $capacidad = 16;
        }

        // Mantiene siempre la cuadrícula completa.
        for ($i = 0; $i < $capacidad; $i++) {
            $jornada = $jornadasPagina[$i] ?? null;

            $y = $pdf->dibujarFilaJornada(
                $y,
                $jornada,
                7.8
            );
        }

        // Los totales solamente aparecen en la última página.
        if ($esUltima) {
            $y = $pdf->dibujarTotalesNotas(
                $y,
                $snapshot['totales'] ?? []
            );
        }

        $pdf->dibujarLema(
            $y + 0.5
        );
    }
}

function je_pdf($snapshots, $lote, $nombre)
{
    $pdf = new JornadaReportePDF(
        'L',
        'mm',
        'LETTER',
        true,
        'UTF-8',
        false
    );
    $pdf->SetCreator('EVDS - ASFALTART');
    $pdf->SetAuthor('ASFALTART S.A.S.');
    $pdf->SetTitle($lote['jlot_nombre']);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(false);
    $pdf->SetHeaderMargin(0);

    // El diseño del GH-F-19 se controla mediante coordenadas absolutas.
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);

    // Padding reducido para conservar la proporción del formato original.
    $pdf->setCellPaddings(0.4, 0.3, 0.4, 0.3);
    foreach ($snapshots as $snapshot) {
        je_pdf_empleado($pdf, $snapshot, $lote);
    }
    if (ob_get_length()) {
        ob_clean();
    }
    $pdf->Output($nombre . '.pdf', 'I');
    exit;
}

function je_celda_xml($valor, $estilo = '')
{
    $atributo = $estilo !== '' ? ' ss:StyleID="' . $estilo . '"' : '';
    return '<Cell' . $atributo . '><Data ss:Type="String">'
        . je_xml($valor) . '</Data></Cell>';
}

function je_hoja_xml($nombre, $encabezados, $filas)
{
    $xml = '<Worksheet ss:Name="' . je_xml($nombre) . '"><Table>';
    $xml .= '<Row>';
    foreach ($encabezados as $encabezado) {
        $xml .= je_celda_xml($encabezado, 'Header');
    }
    $xml .= '</Row>';
    foreach ($filas as $fila) {
        $xml .= '<Row>';
        foreach ($fila as $valor) {
            $xml .= je_celda_xml($valor);
        }
        $xml .= '</Row>';
    }
    return $xml . '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
        . '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal>'
        . '<TopRowBottomPane>1</TopRowBottomPane><AutoFilter x:Range="R1C1:R1C'
        . count($encabezados) . '" xmlns="urn:schemas-microsoft-com:office:excel"/>'
        . '</WorksheetOptions></Worksheet>';
}

function je_excel($snapshots, $lote)
{
    $control = [];
    $detalle = [];
    $totales = [];
    $novedades = [];
    foreach ($snapshots as $snapshot) {
        $total = array_sum(array_map('intval', $snapshot['totales']));
        $control[] = [
            $lote['jlot_nombre'],
            'v' . (int) ($lote['jlot_version'] ?? 1),
            $lote['jlot_tipo'] ?? 'NORMAL',
            $lote['jlot_lote_origen_id'] ?? '',
            $lote['jlot_estado'],
            $lote['jlot_fecha_corte'],
            $snapshot['empleado'],
            $snapshot['documento'],
            $snapshot['cargo'],
            $snapshot['desde'],
            $snapshot['hasta'],
            $snapshot['estado'],
            count($snapshot['jornadas']),
            je_horas($total)
        ];
        foreach ($snapshot['jornadas'] as $jornada) {
            foreach ($jornada['segmentos'] as $segmento) {
                if ($segmento['codigo'] === 'NO_LIQ') {
                    continue;
                }
                $detalle[] = [
                    $snapshot['empleado'],
                    $snapshot['documento'],
                    $jornada['jornada_id'],
                    $jornada['jornada_inicio'],
                    $jornada['jornada_fin'],
                    $segmento['codigo'],
                    $segmento['concepto'],
                    $segmento['codigo_contable'],
                    $segmento['inicio'],
                    $segmento['fin'],
                    je_horas($segmento['minutos']),
                    $jornada['aprobado_por'] ?: 'Automática'
                ];
            }
            $novedades[] = [
                $snapshot['empleado'],
                $snapshot['documento'],
                $jornada['jornada_inicio'],
                $jornada['jornada_fin'],
                $jornada['jornada_ubicacion'],
                $jornada['jornada_actividad'],
                $jornada['jornada_observaciones'],
                $jornada['jornada_origen']
            ];
        }
        foreach ($snapshot['totales'] as $codigo => $minutos) {
            $totales[] = [
                $snapshot['empleado'],
                $snapshot['documento'],
                $snapshot['desde'],
                $snapshot['hasta'],
                $codigo,
                je_horas($minutos)
            ];
        }
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '
<?mso-application progid="Excel.Sheet"?>'
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
        . ' xmlns:o="urn:schemas-microsoft-com:office:office" '
        . ' xmlns:x="urn:schemas-microsoft-com:office:excel" '
        . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
        . '<Styles>
        <Style ss:ID="Default">
        <Alignment ss:Vertical="Center"/>'
        . '
        </Style>
        <Style ss:ID="Header">
        <Font ss:Bold="1"ss:Color="#FFFFFF"/>'
        . '<Interior ss:Color="#087F8C" ss:Pattern="Solid"/>
        </Style>
    </Styles>';
    $xml .= je_hoja_xml(
        'Control',
        ['Lote', 'Versión lote', 'Tipo', 'Lote reemplazado', 'Estado lote',
            'Corte', 'Empleado', 'Documento', 'Cargo', 'Desde', 'Hasta',
            'Estado empleado', 'Jornadas', 'Horas reportables'],
        $control
    );
    $xml .= je_hoja_xml(
        'Detalle',
        ['Empleado', 'Documento', 'Jornada', 'Entrada', 'Salida', 'Concepto',
            'Nombre concepto', 'Código contable', 'Inicio segmento',
            'Fin segmento', 'Horas', 'Aprobó'],
        $detalle
    );
    $xml .= je_hoja_xml(
        'Totales',
        ['Empleado', 'Documento', 'Desde', 'Hasta', 'Concepto', 'Total horas'],
        $totales
    );
    $xml .= je_hoja_xml(
        'Novedades',
        ['Empleado', 'Documento', 'Entrada', 'Salida', 'Lugar / frente',
            'Actividad', 'Observaciones', 'Origen'],
        $novedades
    );
    $xml .= '</Workbook>';

    if (ob_get_length()) {
        ob_clean();
    }
    $archivo = je_nombre_archivo($lote['jlot_nombre']) . '_consolidado.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Content-Length: ' . strlen($xml));
    echo $xml;
    exit;
}

try {
    if (($_GET['tipo'] ?? '') === 'pdf_borrador_equipo') {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $rolId = (int) ($_SESSION['user_rol'] ?? 0);
        if ($userId <= 0 || $rolId <= 0) {
            je_fallar('Debe iniciar sesión.', 401);
        }
        $jornada = new Jornada();
        $jefe = $jornada->obtener_empleado_por_usuario($userId);
        if (!$jefe || (int) $jefe['esta_empl'] !== 1
            || !$jornada->es_jefe_activo((int) $jefe['id_empl'])
            || !$jornada->tiene_permiso_menu($rolId, 'equipo', true)) {
            je_fallar('No tiene permiso para exportar jornadas del equipo.', 403);
        }
        $empleadoId = je_entero('empleado_id');
        if (!$empleadoId) {
            throw new InvalidArgumentException('Seleccione un empleado válido.');
        }
        $fechas = [];
        foreach (['fecha_desde', 'fecha_hasta'] as $campo) {
            $valor = $_GET[$campo] ?? '';
            $fecha = is_string($valor)
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $valor) : false;
            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                throw new InvalidArgumentException('El periodo no es válido.');
            }
            $fechas[$campo] = $valor;
        }
        if ($fechas['fecha_desde'] > $fechas['fecha_hasta']) {
            throw new InvalidArgumentException('La fecha inicial no puede superar la fecha final.');
        }
        $snapshot = $jornada->obtener_borrador_equipo(
            (int) $jefe['id_empl'], $empleadoId,
            $fechas['fecha_desde'], $fechas['fecha_hasta']
        );
        je_pdf([$snapshot], ['jlot_nombre' => 'Borrador de jornadas'],
            je_nombre_archivo('GH-F-19_BORRADOR_' . $snapshot['documento'] . '_' . $snapshot['hasta']));
    }
    je_validar_acceso();
    $tipo = trim((string) ($_GET['tipo'] ?? ''));
    $modelo = new JornadaReporteContable();
    if ($tipo === 'pdf_empleado') {
        $fila_id = je_entero('fila_id');
        if (!$fila_id) {
            throw new InvalidArgumentException('El empleado del lote no es válido.');
        }
        $datos = $modelo->obtener_snapshot_empleado($fila_id);
        je_pdf(
            [$datos['snapshot']],
            $datos['lote'],
            je_nombre_archivo(
                'GH-F-19_' . $datos['snapshot']['documento']
                . '_' . $datos['snapshot']['hasta']
            )
        );
    }
    $lote_id = je_entero('lote_id');
    if (!$lote_id) {
        throw new InvalidArgumentException('El lote no es válido.');
    }
    $datos = $modelo->obtener_snapshots_lote($lote_id);
    if ($tipo === 'pdf_lote') {
        je_pdf(
            $datos['snapshots'],
            $datos['lote'],
            je_nombre_archivo('GH-F-19_' . $datos['lote']['jlot_nombre'])
        );
    }
    if ($tipo === 'excel') {
        je_excel($datos['snapshots'], $datos['lote']);
    }
    throw new InvalidArgumentException('El tipo de exportación no es válido.');
} catch (InvalidArgumentException $e) {
    je_fallar($e->getMessage(), 422);
} catch (RuntimeException $e) {
    je_fallar($e->getMessage(), 409);
} catch (Throwable $e) {
    error_log('Exportación jornadas: ' . $e->getMessage());
    je_fallar('No fue posible generar el archivo solicitado.', 500);
}

?>
