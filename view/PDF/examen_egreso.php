<?php
ob_start();

require_once __DIR__ . '/../../public/tcpfd/tcpdf.php';

// ══════════════════════════════════════════════════════
// Configuración TCPDF — UTF-8 directo sin conversión
// ══════════════════════════════════════════════════════
$pdf = new TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);

$pdf->SetCreator('ASFALTART S.A.S.');
$pdf->SetAuthor('Gestión Humana');
$pdf->SetTitle('Examen de Egreso - ' . $pdf_nombre);
$pdf->SetSubject('Orden Examenes Medicos de Egreso');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(25, 20, 25);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// ══════════════════════════════════════════════════════
// LOGOS
// ══════════════════════════════════════════════════════
$logo_izq = __DIR__ . '/../../public/img/logo asft vertical@3x.png';
if (file_exists($logo_izq)) {
    $pdf->Image($logo_izq, 15, 8, 30, 0, 'PNG');
}

$logo_der = __DIR__ . '/../../public/img/proforma_header.jpg';
if (file_exists($logo_der)) {
    $pdf->Image($logo_der, 185, 2, 20, 0, 'JPG');
}

$pdf->SetY(48);

// ══════════════════════════════════════════════════════
// FECHA Y CIUDAD
// ══════════════════════════════════════════════════════
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, $pdf_ciudad . ', ' . $pdf_fecha_carta, 0, 1, 'L');
$pdf->Ln(6);

// ══════════════════════════════════════════════════════
// RADICADO
// ══════════════════════════════════════════════════════
$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, $pdf_radicado . '.', 0, 1, 'R');
$pdf->Ln(6);

// ══════════════════════════════════════════════════════
// DESTINATARIO
// ══════════════════════════════════════════════════════
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Señor(a)', 0, 1, 'L');

$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, strtoupper($pdf_nombre), 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 6, 'C.C. No. ' . number_format($pdf_cedula, 0, ',', '.') . ' de ' . strtoupper($pdf_lugar_exp), 0, 1, 'L');

$pdf->SetX(25);
$pdf->Cell(0, 6, 'E.     S.     M.', 0, 1, 'L');
$pdf->Ln(4);

// ══════════════════════════════════════════════════════
// REFERENCIA
// ══════════════════════════════════════════════════════
$pdf->SetX(25);
$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->Write(6, 'REFERENCIA: ');

$pdf->SetFont('dejavuserif', '', 11);
$pdf->Write(6, 'Orden para exámenes médicos de egreso.');
$pdf->Ln(8);

// Línea horizontal
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.4);
$pdf->Line(25, $pdf->GetY(), 185, $pdf->GetY());
$pdf->Ln(6);

// ══════════════════════════════════════════════════════
// SALUDO
// ══════════════════════════════════════════════════════
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Cordial saludo.', 0, 1, 'L');
$pdf->Ln(4);

// ══════════════════════════════════════════════════════
// PÁRRAFO 1
// ══════════════════════════════════════════════════════
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->MultiCell(160, 6,
    'Por medio de la presente me permito informar que a partir de la fecha usted cuenta con un ' .
    'término de cinco (05) días hábiles para realizarse los exámenes médicos de egreso. Estos ' .
    'exámenes son un requisito indispensable para tramitar la paz y salvo respectivo en el ' .
    'Departamento de Gestión Humana, el cual se requiere para el trámite interno de liquidación y ' .
    'posterior pago de las prestaciones sociales derivadas del contrato de trabajo.',
0, 'J');
$pdf->Ln(4);

// ══════════════════════════════════════════════════════
// PÁRRAFO 2
// ══════════════════════════════════════════════════════
$pdf->SetX(25);
$pdf->SetFont('dejavuserif', '', 11);
$pdf->Write(6, 'Los exámenes médicos se le realizarán en la ');

$pdf->SetFont('dejavuserif', 'B', 11);
$pdf->Write(6, 'IPS PROSYNERGO ');

$pdf->SetFont('dejavuserif', '', 11);
$pdf->Write(6,
    'ubicada en la cra 31 # 49 - 67, en el municipio de Bucaramanga como requisito ' .
    'indispensable se solicita presentar este documento.'
);
$pdf->Ln(12);

// ══════════════════════════════════════════════════════
// DESPEDIDA
// ══════════════════════════════════════════════════════
$pdf->SetFont('dejavuserif', '', 11);
$pdf->SetX(25);
$pdf->Cell(0, 6, 'Atentamente,', 0, 1, 'L');
$pdf->Ln(20);

// ══════════════════════════════════════════════════════
// FIRMANTE
// ══════════════════════════════════════════════════════
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

// ══════════════════════════════════════════════════════
// FOOTER — posición fija
// ══════════════════════════════════════════════════════
$y_footer = 250;

$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, $y_footer, 195, $y_footer);

$pdf->SetFont('dejavusans', '', 7.5);
$pdf->SetTextColor(80, 80, 80);
$pdf->SetXY(15, $y_footer + 3);
$pdf->MultiCell(90, 4,
    "Km. 5 Anillo Vial, 100m. Adelante de\n" .
    "la hacienda Transilvania Vanguardia Liberal.\n" .
    "Sentido Girón - Floridablanca\n" .
    "PBX: (57) (7) 605 91 70\n" .
    "Celular: 320 853 9870 / 320 453 0501\n" .
    "servicioalcliente@asfaltart.com\n" .
    "Santander / Colombia",
0, 'L');

$logos = [
    __DIR__ . '/../../public/img/iso 9001.png',
    __DIR__ . '/../../public/img/iso 14001.png',
    __DIR__ . '/../../public/img/iso 45001.png',
];

$x_logo = 112;
foreach ($logos as $logo) {
    if (file_exists($logo)) {
        $pdf->Image($logo, $x_logo, $y_footer + 3, 16, 0, 'PNG');
        $x_logo += 18;
    }
}

// ══════════════════════════════════════════════════════
// OUTPUT
// ══════════════════════════════════════════════════════
ob_end_clean();
$nombre_archivo = 'Examen_Egreso_' . str_replace(' ', '_', $pdf_nombre) . '.pdf';
$pdf->Output($nombre_archivo, 'D');
exit;