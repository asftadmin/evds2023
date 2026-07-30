<?php

ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . "/../config/conexion.php";
require_once __DIR__ . "/../models/Jornada.php";
require_once __DIR__ . "/../models/JornadaReporteContable.php";
require_once __DIR__ . '/../public/assets/tcpdf/tcpdf.php';

/**
 * Encabezado institucional repetido en cada página del GH-F-19.
 */
class JornadaReportePDF extends TCPDF {
    private $datosEncabezado = [];

    public function configurarEmpleado(array $snapshot) {
        $this->datosEncabezado = $snapshot;
    }

    public function Header() {
        $datos = $this->datosEncabezado;
        $logo = __DIR__ . '/../public/img/logo asf.png';
        if (is_file($logo)) {
            $this->Image($logo, 10, 4, 27, 0, '', '', '', false, 200);
        }

        $this->SetTextColor(0, 0, 0);
        $this->SetXY(40, 9);
        $this->SetFont('dejavusans', 'B', 6.2);
        $this->Cell(
            178,
            5,
            'PLANILLA MENSUAL PARA CONTROL DE TIEMPO '
                . 'APROBACIÓN DE HORAS EXTRAS Y REPORTE DE NOVEDADES DE PERSONAL',
            0,
            0,
            'C',
            false,
            '',
            1
        );

        $this->SetFont('dejavusans', '', 4.7);
        $this->SetXY(225, 4);
        $this->Cell(46, 3, 'Versión: 3', 0, 2, 'L');
        $this->Cell(46, 3, 'Fecha: 1 Octubre 2023', 0, 2, 'L');
        $this->Cell(46, 3, 'Código: GH-F-19', 0, 2, 'L');
        $this->Cell(46, 3, 'Tipo de Documento: Formato', 0, 2, 'L');
        $this->Cell(
            46,
            3,
            'Página ' . $this->getPageNumGroupAlias()
                . ' de ' . $this->getPageGroupAlias(),
            0,
            0,
            'L'
        );

        $edad = '';
        if (
            !empty($datos['fecha_nacimiento'])
            && !empty($datos['hasta'])
        ) {
            try {
                $nacimiento = new DateTimeImmutable(
                    $datos['fecha_nacimiento']
                );
                $fechaPeriodo = new DateTimeImmutable($datos['hasta']);
                $edad = (string)$nacimiento->diff($fechaPeriodo)->y;
            } catch (Throwable $e) {
                $edad = '';
            }
        }

        $this->SetXY(8, 21);
        $campos = [
            ['Nombre:', 9, $datos['empleado'] ?? '', 58],
            ['Cédula:', 10, $datos['documento'] ?? '', 32],
            ['Cargo:', 9, $datos['cargo'] ?? '', 51],
            [
                'Periodo del:',
                12,
                ($datos['desde'] ?? '') . ' al ' . ($datos['hasta'] ?? ''),
                42
            ],
            ['Edad:', 7, $edad, 9],
            ['Sexo:', 7, $datos['sexo'] ?? '', 17]
        ];
        foreach ($campos as $campo) {
            $this->SetFont('dejavusans', 'B', 4.8);
            $this->Cell($campo[1], 5, $campo[0], 0, 0, 'L');
            $this->SetFont('dejavusans', '', 5);
            $this->Cell(
                $campo[3],
                5,
                (string)$campo[2],
                1,
                0,
                'L',
                false,
                '',
                1
            );
        }
    }
}

function je_fallar($mensaje, $estado = 400) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($estado);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

function je_entero($nombre) {
    $valor = filter_var(
        $_GET[$nombre] ?? null,
        FILTER_VALIDATE_INT
    );
    return $valor && $valor > 0 ? (int)$valor : null;
}

function je_html($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function je_xml($valor) {
    return htmlspecialchars((string)$valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function je_horas($minutos) {
    $minutos = max(0, (int)$minutos);
    return str_pad((string)floor($minutos / 60), 2, '0', STR_PAD_LEFT)
        . ':'
        . str_pad((string)($minutos % 60), 2, '0', STR_PAD_LEFT);
}

function je_nombre_archivo($texto) {
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$texto);
    return trim($texto, '_') ?: 'reporte_jornadas';
}

function je_validar_acceso() {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $rol_id = (int)($_SESSION['user_rol'] ?? 0);
    if ($user_id <= 0 || $rol_id <= 0) {
        je_fallar('Debe iniciar sesión.', 401);
    }
    $jornada = new Jornada();
    $empleado = $jornada->obtener_empleado_por_usuario($user_id);
    if (
        !$empleado
        || (int)$empleado['esta_empl'] !== 1
        || $empleado['rol_nomb'] !== 'Contabilidad'
        || !$jornada->tiene_permiso_menu(
            $rol_id,
            'reporte_contable',
            false
        )
    ) {
        je_fallar('No tiene permiso para exportar este reporte.', 403);
    }
}

function je_totales_jornada($jornada) {
    $totales = [];
    foreach ($jornada['segmentos'] ?? [] as $segmento) {
        $codigo = $segmento['codigo'];
        if ($codigo === 'NO_LIQ') {
            continue;
        }
        $totales[$codigo] = ($totales[$codigo] ?? 0)
            + (int)$segmento['minutos'];
    }
    return $totales;
}

function je_pdf_empleado(JornadaReportePDF $pdf, $snapshot, $lote) {
    $pdf->configurarEmpleado($snapshot);
    $pdf->startPageGroup();
    $pdf->AddPage('L', 'LETTER');
    $pdf->SetFont('dejavusans', '', 7);

    $columnas = [
        'ORD' => '100<br>ORD',
        'RN' => '101<br>RN',
        'HED' => '107<br>HED',
        'HEN' => '108<br>HEN',
        'HEDF' => '109<br>HEDF',
        'HENF' => '110<br>HENF',
        'RF' => '115<br>RF'
    ];
    $html = '<table border="1" cellpadding="2">
        <thead>
        <tr style="background-color:#d9eaf7;font-weight:bold;text-align:center;">
            <th width="7%">Fecha</th>
            <th width="5%">Día</th>
            <th width="6%">Entrada</th>
            <th width="6%">Salida</th>
            <th width="13%">Lugar / frente</th>
            <th width="20%">Actividad o novedad</th>';
    foreach ($columnas as $titulo) {
        $html .= '<th width="5%">' . $titulo . '</th>';
    }
    $html .= '<th width="8%">Aprobó</th></tr></thead><tbody>';

    $dias = [
        1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue',
        5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'
    ];
    foreach ($snapshot['jornadas'] as $jornada) {
        $inicio = new DateTimeImmutable($jornada['jornada_inicio']);
        $fin = new DateTimeImmutable($jornada['jornada_fin']);
        $totales = je_totales_jornada($jornada);
        $html .= '<tr>
            <td width="7%">' . $inicio->format('Y-m-d') . '</td>
            <td width="5%" style="text-align:center;">'
                . $dias[(int)$inicio->format('N')] . '</td>
            <td width="6%" style="text-align:center;">'
                . $inicio->format('H:i') . '</td>
            <td width="6%" style="text-align:center;">'
                . $fin->format('m-d H:i') . '</td>
            <td width="13%">' . je_html($jornada['jornada_ubicacion']) . '</td>
            <td width="20%">' . je_html($jornada['jornada_actividad']) . '</td>';
        foreach (array_keys($columnas) as $codigo) {
            $html .= '<td width="5%" style="text-align:center;">'
                . (isset($totales[$codigo])
                    ? je_horas($totales[$codigo])
                    : '')
                . '</td>';
        }
        $html .= '<td width="8%">'
            . je_html($jornada['aprobado_por'] ?: 'Automática')
            . '</td></tr>';
    }
    if (!$snapshot['jornadas']) {
        $html .= '<tr><td width="100%" colspan="14" style="text-align:center;">'
            . 'Sin novedades de jornada en el periodo</td></tr>';
    }
    $html .= '<tr style="font-weight:bold;background-color:#eeeeee;">
        <td width="57%" colspan="6" style="text-align:right;">TOTALES</td>';
    foreach (array_keys($columnas) as $codigo) {
        $html .= '<td width="5%" style="text-align:center;">'
            . (isset($snapshot['totales'][$codigo])
                ? je_horas($snapshot['totales'][$codigo])
                : '00:00')
            . '</td>';
    }
    $html .= '<td width="8%"></td></tr></tbody></table>';
    $pdf->writeHTML($html, true, false, false, false, '');

    $aprobadores = [];
    foreach ($snapshot['jornadas'] as $jornada) {
        if (!empty($jornada['aprobado_por'])) {
            $aprobadores[$jornada['aprobado_por']] = true;
        }
    }
    $texto_aprobador = $aprobadores
        ? implode(', ', array_keys($aprobadores))
        : 'Aprobación automática / sin novedades';
    $firmas = '
        <br><br>
        <table cellpadding="3">
            <tr>
                <td width="45%" align="center">______________________________<br>
                    <b>Responsable / jefe</b><br>'
                    . je_html($texto_aprobador) . '</td>
                <td width="10%"></td>
                <td width="45%" align="center">______________________________<br>
                    <b>Revisión Contabilidad</b></td>
            </tr>
        </table>';
    $pdf->writeHTML($firmas, true, false, false, false, '');
}

function je_pdf($snapshots, $lote, $nombre) {
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
    $pdf->SetHeaderMargin(3);
    $pdf->SetMargins(8, 29, 8);
    $pdf->SetAutoPageBreak(true, 8);
    $pdf->setCellPaddings(1, 1, 1, 1);
    foreach ($snapshots as $snapshot) {
        je_pdf_empleado($pdf, $snapshot, $lote);
    }
    if (ob_get_length()) {
        ob_clean();
    }
    $pdf->Output($nombre . '.pdf', 'I');
    exit;
}

function je_celda_xml($valor, $estilo = '') {
    $atributo = $estilo !== '' ? ' ss:StyleID="' . $estilo . '"' : '';
    return '<Cell' . $atributo . '><Data ss:Type="String">'
        . je_xml($valor) . '</Data></Cell>';
}

function je_hoja_xml($nombre, $encabezados, $filas) {
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

function je_excel($snapshots, $lote) {
    $control = [];
    $detalle = [];
    $totales = [];
    $novedades = [];
    foreach ($snapshots as $snapshot) {
        $total = array_sum(array_map('intval', $snapshot['totales']));
        $control[] = [
            $lote['jlot_nombre'],
            'v' . (int)($lote['jlot_version'] ?? 1),
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
        . '<?mso-application progid="Excel.Sheet"?>'
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
        . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
        . 'xmlns:x="urn:schemas-microsoft-com:office:excel" '
        . 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
        . '<Styles><Style ss:ID="Default"><Alignment ss:Vertical="Center"/>'
        . '</Style><Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/>'
        . '<Interior ss:Color="#087F8C" ss:Pattern="Solid"/></Style></Styles>';
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
    je_validar_acceso();
    $tipo = trim((string)($_GET['tipo'] ?? ''));
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
