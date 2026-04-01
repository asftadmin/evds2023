<?php
ob_start();
require_once __DIR__ . '/../../public/tcpfd/tcpdf.php';

$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);

$pdf->SetCreator('ASFALTART S.A.S.');
$pdf->SetAuthor('Gestión Humana');
$pdf->SetTitle('Cesantías - ' . $pdf_nombre);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(25, 20, 25);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// ── Logos ──────────────────────────────────────────────
$logo_izq = __DIR__ . '/../../public/img/logo asft vertical@3x.png';
if (file_exists($logo_izq)) {
    $pdf->Image($logo_izq, 15, 8, 30, 0, 'PNG');
}
$logo_der = __DIR__ . '/../../public/img/proforma_header.jpg';
if (file_exists($logo_der)) {
    $pdf->Image($logo_der, 185, 2, 20, 0, 'JPG');
}

$pdf->SetY(48);

// ── Fecha y ciudad ─────────────────────────────────────
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, $pdf_ciudad . ', ' . $pdf_fecha_carta, 0, 1, 'L');
$pdf->Ln(6);

// ── Radicado ───────────────────────────────────────────
$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, $pdf_radicado . '.', 0, 1, 'R');
$pdf->Ln(6);

// ── Destinatario ───────────────────────────────────────
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Señores', 0, 1, 'L');

$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, strtoupper($pdf_fondo), 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 6, 'E.     S.     M.', 0, 1, 'L');
$pdf->Ln(4);

// ── Referencia ─────────────────────────────────────────
$pdf->SetX(25);
$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->Write(6, 'REFERENCIA: ');
$pdf->SetFont('dejavuserif', '', 11);
$pdf->Write(6, 'Certificación de terminación de contrato de trabajo para retiro de cesantías.');
$pdf->Ln(8);

// Línea
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.4);
$pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
$pdf->Ln(6);

// ── Saludo ─────────────────────────────────────────────
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Cordial saludo.', 0, 1, 'L');
$pdf->Ln(4);

// ── Párrafo principal ──────────────────────────────────

// ── Párrafo principal justificado con negrilla ─────────
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->writeHTML(
    '<p style="text-align:justify; font-size:11pt; font-family:dejavuserif;">De manera atenta me permito certificar que el señor&nbsp;<b> ' . strtoupper($pdf_nombre) . '</b>, identificado con CC. No. <b>' . number_format($pdf_cedula, 0, ',', '.') . ' de ' . strtoupper($pdf_lugar_exp) . '</b>, laboró con la sociedad <b>ASFALTART S.A.S. EN REORGANIZACIÓN</b> hasta el día ' . $pdf_fecha_retiro . ', fecha en la cual se terminó su contrato laboral.</p>',
    true,
    false,
    true,
    false,
    ''
);
$pdf->Ln(4);

// ── Párrafo expedición justificado ────────────────────
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->writeHTML(
    '<p style="text-align:justify; font-size:11pt; font-family:dejavuserif;">La presente se expide a solicitud del interesado para realizar el respectivo retiro de cesantías por terminación del contrato de trabajo.</p>',
    true,
    false,
    true,
    false,
    ''
);
$pdf->Ln(4);

/* $pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->MultiCell(
    160,
    6,
    'De manera atenta me permito certificar que el señor ' .
        strtoupper($pdf_nombre) . ', identificado con CC. No. ' .
        number_format($pdf_cedula, 0, ',', '.') . ' de ' .
        strtoupper($pdf_lugar_exp) . ', laboró con la sociedad ' .
        'ASFALTART S.A.S. EN REORGANIZACIÓN hasta el día ' .
        $pdf_fecha_retiro . ', fecha en la cual se terminó su contrato laboral.',
    0,
    'J'
);
$pdf->Ln(4); */


// ── Despedida ──────────────────────────────────────────
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Atentamente,', 0, 1, 'L');
$pdf->Ln(20);

// ── Firmante ───────────────────────────────────────────
$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 5, strtoupper($pdf_nombre_firmante), 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 5, $pdf_cargo_firmante, 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 5, 'ASFALTART S.A.S. EN REORGANIZACIÓN.', 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 5, 'NIT No. 800.164.580-6.', 0, 1, 'L');
$pdf->Ln(30);

// ── Footer ─────────────────────────────────────────────
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

// ── Output ─────────────────────────────────────────────
ob_end_clean();
$nombre_archivo = 'Cesantias_' . str_replace(' ', '_', $pdf_nombre) . '.pdf';
$pdf->Output($nombre_archivo, 'D');
exit;
