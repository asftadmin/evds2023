<?php

require_once(__DIR__ . "/../../public/docs/fpdf.php");


class PDF extends FPDF {
    function Header() {

        //$altoPagina = $this->GetPageHeight();

        // Imagen decorativa derecha ocupando todo el alto
        $this->Image(
            __DIR__ . '/../../public/img/proforma_header.jpg',
            $this->GetPageWidth() - 30, // pegado derecha
            0,
            30,   // ancho
            60  // alto completo
        );

        $this->Image(
            __DIR__ . '/../../public/img/logo asf.png',
            15,
            10,
            40
        );
    }

    function Footer() {

        // Posición 20 mm desde abajo
        $this->SetY(-35);

        // Línea superior
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, $this->GetY(), 195, $this->GetY());

        $this->Ln(5);

        // Fuente pequeña
        $this->SetFont('Arial', '', 8);

        // INFORMACIÓN IZQUIERDA
        $this->MultiCell(
            120,
            4,
            "Km. 5 Anillo Vial, 100m. Adelante de la Hacienda Transilvania Vanguardia Liberal.\n" .
                "PBX: (57) (7) 605 91 70\n" .
                "Celular: 320 853 9870 / 320 453 0501\n" .
                "servicioalcliente@asfaltart.com\n" .
                "Santander / Colombia",
            0,
            'L'
        );

        // ICONOS CERTIFICACIONES (DERECHA)
        //$y = $this->GetY() - 20;
        $this->Image(__DIR__ . '/../../public/img/iso 9001.png', 130, 248, 18);
        $this->Image(__DIR__ . '/../../public/img/iso 14001.png', 150, 248, 18);
        $this->Image(__DIR__ . '/../../public/img/iso 45001.png', 170, 248, 18);
        //this->Image(__DIR__ . '/../../public/img/norsok.png', 190, $y, 18);
    }

    function WriteHTML($html)
{
    $html = str_replace("\n",' ',$html);
    $a = preg_split('/<(.*)>/U',$html,-1,PREG_SPLIT_DELIM_CAPTURE);
    foreach($a as $i=>$e)
    {
        if($i%2==0)
        {
            if($e!='')
                $this->MultiCell(0,6,utf8_decode($e),0,'J');
        }
        else
        {
            if($e=='b')
                $this->SetFont('','B');
            elseif($e=='/b')
                $this->SetFont('','');
        }
    }
}
}

$pdf = new PDF('P', 'mm', 'Letter');
$pdf->SetMargins(15, 40, 15);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);
$pdf->Ln(20);

// =============================
// TÍTULO SUPERIOR
// =============================

$pdf->SetFont('Arial', 'B', 12);
$pdf->MultiCell(
    0,
    7,
    utf8_decode("EL SUSCRITO DIRECTOR DEL DEPARTAMENTO DE GESTIÓN HUMANA Y JURÍDICA DE
LA SOCIEDAD ASFALTART S.A.S. EN REORGANIZACIÓN IDENTIFICADA CON NIT No.
800.164.580-6."),
    0,
    'C'
);

$pdf->Ln(8);

// =============================
// CERTIFICA
// =============================

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, "CERTIFICA:", 0, 1, 'C');

$pdf->Ln(8);

// =======================================
// CUERPO DEL CERTIFICADO
// =======================================

$pdf->SetFont('Arial','',11);

$html = "
Que el señor <b>CRISTIAN GIOVANNY ARCINIEGAS PORTILLA</b>, identificado con cedula de ciudadanía número 1.095.934.409 de Girón, labora a través de un contrato de trabajo a término fijo desde el veinte (20) de septiembre de 2021 y hasta la fecha, desempeñando el cargo de <b>COORDINADOR DE SISTEMAS</b> devengando un salario básico mensual de <b>DOS MILLONES SETECIENTOS MIL PESOS M/CTE. ($2.700.000), más AUXILIO DE TRANSPORTE POR DOSCIENTOS CUARENTA Y NUEVE MIL NOVENTA Y CINCO PESOS M/TE ($249.095)</b>.
";

$pdf->WriteHTML($html);

$pdf->Ln(12);

$pdf->MultiCell(
    0,
    6,
    utf8_decode("Se expide a solicitud del interesado, en San Juan de Girón (Santander) el día veintiséis (26) de febrero de dos mil veintiséis (2026)."),
    0,
    'J'
);
/* $lineHeight = 8;
$pdf->SetFont('Arial', '', 11);

// Primera parte
$pdf->Write($lineHeight, utf8_decode("Que el señor "));

// Nombre en negrilla
$pdf->SetFont('Arial', 'B', 11);
$pdf->Write($lineHeight, utf8_decode("CRISTIAN GIOVANNY ARCINIEGAS PORTILLA, "));

// Volver a normal
$pdf->SetFont('Arial', '', 11);
$pdf->Write($lineHeight, utf8_decode("identificado con cedula de ciudadanía número 1.095.934.409 de Girón, labora a través de un contrato de trabajo a término fijo desde el veinte (20) de septiembre de 2021 y hasta la fecha, desempeñando el cargo de "));

// Cargo en negrilla
$pdf->SetFont('Arial', 'B', 11);
$pdf->Write($lineHeight, utf8_decode("COORDINADOR DE SISTEMAS "));

// Normal
$pdf->SetFont('Arial', '', 11);
$pdf->Write($lineHeight, utf8_decode("devengando un salario básico mensual de "));

// Salario en negrilla
$pdf->SetFont('Arial', 'B', 11);
$pdf->Write($lineHeight, utf8_decode("DOS MILLONES SETECIENTOS MIL PESOS M/CTE. ($2.700.000), más AUXILIO DE TRANSPORTE POR DOSCIENTOS CUARENTA Y NUEVE MIL NOVENTA Y CINCO PESOS M/TE ($249.095)."));

$pdf->Ln(12); */

/* $lineHeight = 8;

// Guardar posición inicial
$y = $pdf->GetY();

// Escribir el texto con Cell y MultiCell combinados
$pdf->SetFont('Arial', '', 11);
$pdf->Cell($pdf->GetStringWidth("Que el señor "), $lineHeight, utf8_decode("Que el señor "), 0, 0, 'L');

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell($pdf->GetStringWidth("CRISTIAN GIOVANNY ARCINIEGAS PORTILLA, "), $lineHeight, utf8_decode("CRISTIAN GIOVANNY ARCINIEGAS PORTILLA, "), 0, 0, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Cell($pdf->GetStringWidth("identificado con cedula de ciudadanía"), $lineHeight, utf8_decode("identificado con cedula de ciudadanía"), 0, 0, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->Ln($lineHeight);
$pdf->SetX(15);
$remainingText = utf8_decode("número 1.095.934.409 de Girón, labora a través de un contrato de trabajo a término fijo desde el veinte (20) de septiembre de 2021 y hasta la fecha, desempeñando el cargo de COORDINADOR DE SISTEMAS devengando un salario básico mensual de DOS MILLONES SETECIENTOS MIL PESOS M/CTE. ($2.700.000), más AUXILIO DE TRANSPORTE POR DOSCIENTOS CUARENTA Y NUEVE MIL NOVENTA Y CINCO PESOS M/TE ($249.095).");

$pdf->MultiCell(0, $lineHeight, $remainingText, 0, 'J');

$pdf->Ln(4); */


$pdf->Cell(0, 6, 'Atentamente,', 0, 1);
$pdf->Ln(18);


// =============================
// FIRMA (IMAGEN)
// =============================

/* $pdf->Image(
    __DIR__ . '/../../public/img/firma_rrhh.png',
    30,
    $pdf->GetY() - 10,
    60
); */

// =============================
// NOMBRE Y CARGO
// =============================

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, "JORGE CARDENAS", 0, 1, 'L');

$pdf->SetFont('Arial', '', 11);
$pdf->MultiCell(
    0,
    6,
    utf8_decode("Director de Gestión Humana y Jurídico.
ASFALTART S.A.S. EN REORGANIZACIÓN.
NIT No. 800.164.580-6."),
    0,
    'L'
);

$pdf->Ln(3);

$pdf->SetFont('Arial', '', 9);
$pdf->MultiCell(
    0,
    5,
    utf8_decode("Para confirmar esta información comunicarse al 3134008506 o 3175424050
Correos: rhumano@asfaltart.com o dirhumano@asfaltart.com"),
    0,
    'L'
);

$pdf->Output();
