<?php
ob_start();

require_once __DIR__ . '/../../public/tcpfd/tcpdf.php';

function htmlPdf($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function escribirParrafoTerminacion($pdf, $html, $espacio = 3) {
    $pdf->SetX(25);
    $pdf->writeHTML(
        '<p style="text-align:justify; font-size:10.5pt; font-family:dejavuserif;">' . $html . '</p>',
        true,
        false,
        true,
        false,
        ''
    );
    $pdf->Ln($espacio);
}

function dibujarEncabezadoTerminacion($pdf) {
    $logo_izq = __DIR__ . '/../../public/img/logo asft vertical@3x.png';
    if (file_exists($logo_izq)) {
        $pdf->Image($logo_izq, 15, 8, 30, 0, 'PNG');
    }

    $logo_der = __DIR__ . '/../../public/img/proforma_header.jpg';
    if (file_exists($logo_der)) {
        $pdf->Image($logo_der, 185, 2, 20, 0, 'JPG');
    }
}

function dibujarPieTerminacion($pdf) {
    $y_footer = 250;

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);
    $pdf->Line(15, $y_footer, 195, $y_footer);

    $y_footer_txt = $y_footer + 3;
    $pdf->SetFont('dejavusans', '', 7.5);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetXY(15, $y_footer_txt);
    $pdf->MultiCell(
        90,
        4,
        "Km. 5 Anillo Vial, 100m. Adelante de\n" .
            "la hacienda Transilvania Vanguardia Liberal.\n" .
            "Sentido Girón - Floridablanca\n" .
            "PBX: (57) (7) 605 91 70\n" .
            "Celular: 320 853 9870 / 320 453 0501\n" .
            "servicioalcliente@asfaltart.com\n" .
            "Santander / Colombia",
        0,
        'L'
    );

    $logos = [
        __DIR__ . '/../../public/img/iso 9001.png',
        __DIR__ . '/../../public/img/iso 14001.png',
        __DIR__ . '/../../public/img/iso 45001.png',
    ];

    $x_logo = 112;
    foreach ($logos as $logo) {
        if (file_exists($logo)) {
            $pdf->Image($logo, $x_logo, $y_footer_txt + 3, 16, 0, 'PNG');
            $x_logo += 18;
        }
    }

    $pdf->SetTextColor(0, 0, 0);
}

function agregarPaginaTerminacion($pdf) {
    $pdf->AddPage();
    dibujarEncabezadoTerminacion($pdf);
    $pdf->SetY(48);
}

function dibujarFirmasTerminacion($pdf, $nombre_firmante, $cargo_firmante) {
    $y_footer = 250;
    $x_tabla = 25;
    $y_nueva_pagina = 44;
    $ancho_tabla = 160;
    $ancho_columna = $ancho_tabla / 2;
    $alto_firmas = 50;
    $padding = 2;
    $ancho_texto = $ancho_columna - ($padding * 2);
    $y_firma = max($pdf->GetY() + 4, 188);

    if (($y_firma + $alto_firmas) > ($y_footer - 2)) {
        dibujarPieTerminacion($pdf);
        agregarPaginaTerminacion($pdf);
        $y_firma = $y_nueva_pagina;
    }

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.25);
    $pdf->Rect($x_tabla, $y_firma, $ancho_tabla, $alto_firmas);
    $pdf->Line($x_tabla + $ancho_columna, $y_firma, $x_tabla + $ancho_columna, $y_firma + $alto_firmas);

    $pdf->SetFont('dejavuserif', '', 11);
    $pdf->SetXY($x_tabla + $padding, $y_firma + $padding);
    $pdf->Cell($ancho_texto, 6, 'Notifica', 0, 1, 'L');

    $pdf->SetXY($x_tabla + $ancho_columna + $padding, $y_firma + $padding);
    $pdf->Cell($ancho_texto, 6, 'Notificado', 0, 1, 'L');

    $y_detalle = $y_firma + 18;

    $x_izq = $x_tabla + $padding;
    $y_linea = $y_detalle;

    $pdf->SetFont('dejavuserif', 'B', 9.8);
    $pdf->SetXY($x_izq, $y_linea);
    $pdf->MultiCell($ancho_texto, 4.2, strtoupper($nombre_firmante), 0, 'L');
    $y_linea = $pdf->GetY() + 0.4;

    $pdf->SetFont('dejavuserif', 'B', 8.8);
    $pdf->SetXY($x_izq, $y_linea);
    $pdf->MultiCell($ancho_texto, 4, $cargo_firmante, 0, 'L');
    $y_linea = $pdf->GetY() + 0.4;

    $pdf->SetXY($x_izq, $y_linea);
    $pdf->MultiCell($ancho_texto, 4, 'ASFALTART S.A.S. EN REORGANIZACIÓN.', 0, 'L');
    $y_linea = $pdf->GetY() + 0.4;

    $pdf->SetXY($x_izq, $y_linea);
    $pdf->MultiCell($ancho_texto, 4, 'NIT No. 800.164.580-6.', 0, 'L');

    $pdf->SetFont('dejavuserif', '', 10.5);
    $pdf->SetXY($x_tabla + $ancho_columna + $padding, $y_detalle);
    $pdf->Cell($ancho_texto, 5, 'Firma:', 0, 1, 'L');

    $pdf->SetXY($x_tabla + $ancho_columna + $padding, $y_detalle + 7);
    $pdf->Cell($ancho_texto, 5, 'Nombre:', 0, 1, 'L');

    $pdf->SetXY($x_tabla + $ancho_columna + $padding, $y_detalle + 14);
    $pdf->Cell($ancho_texto, 5, 'Cédula:', 0, 1, 'L');

    dibujarPieTerminacion($pdf);
}

$pdf_empresa = $pdf_empresa ?? 'ASFALTART S.A.S. EN REORGANIZACIÓN';
$pdf_tipo_terminacion = $pdf_tipo_terminacion ?? 'obra_labor';
$pdf_radicado = $pdf_radicado ?? '';

$empresa_html = htmlPdf($pdf_empresa);
$fecha_ingreso_html = htmlPdf($pdf_fecha_ingreso ?? '');
$fecha_retiro_html = htmlPdf($pdf_fecha_retiro);
$fecha_renuncia_html = htmlPdf($pdf_fecha_renuncia ?? '');

$parrafo_prestaciones =
    'Las Prestaciones Sociales serán canceladas a la mayor brevedad posible e igualmente se le hará entrega ' .
    'de la orden para practicarse el examen médico de egreso dentro los cinco (5) días hábiles siguientes ' .
    'al momento de recibir este comunicado. La práctica de los exámenes médicos de retiro son un requisito ' .
    'indispensable para tramitar el paz y salvo y continuar con el procedimiento interno de liquidación y ' .
    'posterior pago de las prestaciones sociales derivadas del contrato de trabajo.';

$contenido = [];

switch ($pdf_tipo_terminacion) {
    case 'preaviso_terminacion':
        $contenido[] = 'En nombre de la sociedad <b>' . $empresa_html . '</b> mediante la presente comunicación respetuosamente le notificamos nuestra decisión de no prorrogar su contrato de trabajo a la fecha de vencimiento del mismo, es decir, el día <b>' . $fecha_retiro_html . '</b>, fecha de vencimiento de la respectiva prórroga.';
        $contenido[] = 'Igualmente, le manifestamos que la relación laboral finaliza el día de hoy; en consecuencia, Usted prestará sus servicios hasta el momento de recibo de esta notificación.';
        $contenido[] = 'La empresa le cancelará la indemnización correspondiente y la liquidación de prestaciones sociales de acuerdo con la Ley.';
        $contenido[] = 'Agradecemos sus servicios y le deseamos éxitos en sus futuras actividades.';
        break;

    case 'aceptacion_renuncia':
        $contenido[] = 'Ha recibido esta administración el comunicado de fecha <b>' . $fecha_renuncia_html . '</b>, radicado de su parte ante las instalaciones de la sociedad <b>' . $empresa_html . '</b>, y en donde en términos generales presenta renuncia voluntaria e irrevocable al puesto de trabajo desempeñado por Usted; atendiendo lo estipulado al respecto en el Código Sustantivo del Trabajo, la mentada persona jurídica de derecho privado a través de su Departamento de Gestión Humana, <b>ACEPTA</b> la renuncia libre y voluntaria presentada de su parte y tiene como última fecha de servicios el día <b>' . $fecha_retiro_html . '</b>.';
        $contenido[] = $parrafo_prestaciones;
        $contenido[] = 'Agradecemos los servicios prestados y le deseamos muchos éxitos en sus nuevas actividades profesionales.';
        break;

    case 'periodo_prueba':
        $contenido[] = 'Me permito comunicarle que la sociedad <b>' . $empresa_html . '</b>, ha decidido dar por terminado el contrato de trabajo de duración determinada por la obra o labor, que inició el día <b>' . $fecha_ingreso_html . '</b>. Esta determinación se hace efectiva al finalizar la jornada laboral del día <b>' . $fecha_retiro_html . '</b> y se funda en el artículo ochenta (80), numeral 1 del Código Sustantivo de Trabajo, modificado por el Decreto 617 de 1954, artículo Tercero (3°).';
        $contenido[] = 'Igualmente, el Artículo 78 del Código Sustantivo del Trabajo, modificado por el artículo 7 de la Ley 50 de 1990, regula la estipulación escrita del período de prueba.';
        $contenido[] = 'Lo anterior se pactó en la cláusula 3 del contrato de trabajo, donde se dispuso que el trabajador tendrá período de prueba y su duración será de dos (2) meses.';
        break;

    case 'obra_labor':
    default:
        $contenido[] = 'Por medio de la presente me permito comunicarle que la sociedad <b>' . $empresa_html . '</b>, ha decidido dar por terminado el contrato de trabajo por la duración de una obra o labor contratada.';
        $contenido[] = 'Lo anterior, en virtud del numeral primero literal (d) del Artículo 61 del Código Sustantivo del Trabajo. Relación laboral que inició el <b>' . $fecha_ingreso_html . '</b>.';
        $contenido[] = 'Esta determinación se hace efectiva al finalizar la jornada laboral del día <b>' . $fecha_retiro_html . '</b>.';
        $contenido[] = $parrafo_prestaciones;
        $contenido[] = 'Es propia la ocasión para agradecer los servicios prestados a nuestra empresa durante el tiempo de su vinculación y desearle éxitos en sus nuevas actividades.';
        break;
}

$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);

$pdf->SetCreator('ASFALTART S.A.S.');
$pdf->SetAuthor('Gestión Humana');
$pdf->SetTitle('Terminación de Contrato - ' . $pdf_nombre);
$pdf->SetSubject('Carta de Terminación de Contrato Laboral');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(25, 20, 25);
$pdf->SetAutoPageBreak(false);
agregarPaginaTerminacion($pdf);

$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, $pdf_ciudad . ', ' . $pdf_fecha_carta, 0, 1, 'L');
$pdf->Ln(6);

$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, $pdf_radicado . '.', 0, 1, 'R');
$pdf->Ln(6);

$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Señor', 0, 1, 'L');

$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, strtoupper($pdf_nombre), 0, 1, 'L');

$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'C.C. No. ' . number_format($pdf_cedula, 0, ',', '.') . ' de ' . $pdf_lugar_exp, 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 6, 'ASFALTART S.A.S. EN REORGANIZACIÓN.', 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 6, 'E.     S.     M.', 0, 1, 'L');
$pdf->Ln(4);

$pdf->SetX(25);
$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->Write(6, 'REFERENCIA: ');

$pdf->SetFont('dejavuserif', '', 11);
$pdf->Write(6, $pdf_referencia);
$pdf->Ln(8);

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.4);
$pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
$pdf->Ln(6);

$pdf->SetFont('dejavuserif', '', 11);
foreach ($contenido as $parrafo) {
    escribirParrafoTerminacion($pdf, $parrafo);
}

dibujarFirmasTerminacion($pdf, $pdf_nombre_firmante, $pdf_cargo_firmante);

ob_end_clean();
$nombre_archivo = 'Terminacion_Contrato_' . str_replace(' ', '_', $pdf_nombre) . '.pdf';
$pdf->Output($nombre_archivo, 'I');
exit;
