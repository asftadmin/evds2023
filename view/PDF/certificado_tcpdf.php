<?php

require_once(__DIR__ . '/../../public/tcpfd/tcpdf.php');

class PDF extends TCPDF
{

    public function Header()
    {

        // Logo izquierdo (proporcional)
        $this->Image(
            __DIR__ . '/../../public/img/logo asf.png',
            15,
            12,
            35,
            0,
            '',
            '',
            '',
            true,
            300
        );

        // Imagen decorativa derecha proporcional
        $this->Image(
            __DIR__ . '/../../public/img/proforma_header.jpg',
            190,
            0,
            20,
            0,
            '',
            '',
            '',
            true,
            300
        );

        // Línea institucional
        //$this->SetDrawColor(180,180,180);
        //$this->Line(15, 35, 195, 35);
    }

    public function Footer()
    {
        $margins = $this->getMargins();
        $left   = $margins['left'];
        $width  = $this->getPageWidth();

        $footerTop = $this->getPageHeight() - 30;
        $this->SetY($footerTop);

        $bloqueAncho = 110;



        $textY = $footerTop + 1;

        $this->SetFont('dejavusans', '', 8);

        $this->setCellHeightRatio(1.6);

        $texto_footer = "
    Km. 5 Anillo Vial, 100m. Adelante de la Hacienda Transilvania Vanguardia Liberal.
    Sentido Girón - Floridblanca.
    PBX: (57) (7) 605 91 70
    Celular: 320 853 9870 - 320 453 0501
    servicioalcliente@asfaltart.com
    Santander / Colombia
    ";

        $this->writeHTMLCell(
            $bloqueAncho,
            0,
            $left,
            $textY,
            $texto_footer,
            0,
            0,
            false,
            true,
            'L',
            true
        );
        // 🔹 ICONOS alineados EXACTAMENTE con el bloque
        $iconY = $textY;
        $iconStart = $left + $bloqueAncho + 10;

        $this->Image(__DIR__ . '/../../public/img/iso 9001.png',  $iconStart,       $iconY, 18);
        $this->Image(__DIR__ . '/../../public/img/iso 14001.png', $iconStart + 20,  $iconY, 18);
        $this->Image(__DIR__ . '/../../public/img/iso 45001.png', $iconStart + 40,  $iconY, 18);
    }
}

$pdf = new PDF('P', 'mm', 'Letter', true, 'UTF-8', false);

$pdf->SetMargins(20, 45, 20);
$pdf->SetAutoPageBreak(true, 40);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 11);
$pdf->setCellHeightRatio(1.4);

$pdf->Ln(3);

// ==========================
// CÓDIGO A LA DERECHA
// ==========================
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(0, 4, 'ASF-GH-2.6-0083-26', 0, 1, 'R');
$pdf->Ln(8);


// ==========================
// ENCABEZADO CENTRADO
// ==========================
$pdf->SetFont('dejavusans', 'B', 12);

$titulo = "
EL SUSCRITO DIRECTOR DEL DEPARTAMENTO DE GESTIÓN HUMANA Y JURÍDICA DE LA SOCIEDAD ASFALTART S.A.S. EN REORGANIZACIÓN IDENTIFICADA CON NIT No.
800.164.580-6.
";

$pdf->MultiCell(0, 7, $titulo, 0, 'C');
$pdf->Ln(10);


// ==========================
// CERTIFICA CENTRADO
// ==========================
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 6, 'CERTIFICA:', 0, 1, 'C');
$pdf->Ln(10);


// ==========================
// CUERPO JUSTIFICADO
// ==========================
$pdf->SetFont('dejavusans', '', 11);

$cuerpo = "
Que el señor <b>CRISTIAN GIOVANNY ARCINIEGAS PORTILLA</b>, identificado con cédula de ciudadanía número 1.095.934.409 de Girón, labora a través de un contrato de trabajo a término fijo desde el veinte (20) de septiembre de 2021 y hasta la fecha, desempeñando el cargo de <b>COORDINADOR DE SISTEMAS</b> devengando un salario básico mensual de <b>DOS MILLONES SETECIENTOS MIL PESOS M/CTE. ($2.700.000), más AUXILIO DE TRANSPORTE POR DOSCIENTOS CUARENTA Y NUEVE MIL NOVENTA Y CINCO PESOS M/TE ($249.095)</b>.
";

$pdf->writeHTMLCell(0, 0, '', '', $cuerpo, 0, 1, 0, true, 'J', true);

$pdf->Ln(8);


// ==========================
// FECHA JUSTIFICADA
// ==========================
$fecha = "
Se expide a solicitud del interesado, en San Juan de Girón (Santander), el día veintiséis (26) de febrero de dos mil veintiséis (2026).
";

$pdf->MultiCell(0, 6, $fecha, 0, 'J');

$pdf->Ln(15);


// ==========================
// FIRMA
// ==========================
$pdf->Cell(0, 6, 'Atentamente,', 0, 1);
$pdf->Ln(20);

$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(0, 6, 'JORGE CARDENAS', 0, 1);

$pdf->SetFont('dejavusans', '', 11);
$pdf->MultiCell(
    0,
    6,
    "Director de Gestión Humana y Jurídico.
ASFALTART S.A.S. EN REORGANIZACIÓN.
NIT No. 800.164.580-6.",
    0,
    'L'
);

$pdf->Ln(5);

$pdf->SetFont('dejavusans', '', 9);
$pdf->MultiCell(
    0,
    5,
    "Para confirmar esta información comunicarse al 3134008506 o 3175424050
Correos: rhumano@asfaltart.com | dirhumano@asfaltart.com |",
    0,
    'L'
);

$pdf->Output('Certificado_Laboral.pdf', 'I'); 
