<?php
require_once("../../public/docs/fpdf.php");
require_once("../../config/conexion.php");
require_once("../../models/Permiso.php");

// ==========================================
// FUNCIÓN QUE CONVIERTE BASE64 A PNG TEMPORAL
// ==========================================
function base64_to_png_temp($base64) {
    if (empty($base64)) return null;

    // Si NO trae el encabezado, lo agregamos
    if (strpos($base64, "data:image/") === false) {
        $base64 = "data:image/png;base64," . $base64;
    }

    // Quitar encabezado
    $clean = preg_replace('#^data:image/\w+;base64,#i', '', $base64);

    // Corregir espacios dañinos
    $clean = str_replace(' ', '+', $clean);

    // Decodificar
    $img = base64_decode($clean);

    if (!$img || strlen($img) < 300) return null;

    // Crear archivo PNG temporal
    $file = tempnam(sys_get_temp_dir(), "firma_") . ".png";
    file_put_contents($file, $img);

    return $file;
}

class PDF extends FPDF {
    function Header() {
        // Marco general
        $this->SetLineWidth(0.6);
        $this->Rect(10, 10, 260, 190);

        // LOGO
        $this->Image('../../public/img/logo asf.png', 15, 15, 30);

        // TÍTULO
        $this->SetFont('Arial', 'B', 15);
        $this->SetXY(10, 18);
        $this->Cell(260, 12, utf8_decode("AUTORIZACIÓN DE PERMISO Y/O SALIDA"), 0, 0, 'C');

        // TEXTO DERECHO
        $this->SetFont('Arial', '', 9);
        $this->SetXY(200, 15);
        $this->Cell(65, 5, utf8_decode("Versión 2"), 0, 2, 'R');
        $this->Cell(65, 5, utf8_decode("Implementación Septiembre 13 de 2019"), 0, 2, 'R');
        $this->Cell(65, 5, utf8_decode("Código GH-F-12"), 0, 2, 'R');
        $this->Cell(65, 5, utf8_decode("Tipo documento Formato"), 0, 2, 'R');

        // LÍNEA INFERIOR DEL ENCABEZADO
        $this->Line(10, 40, 270, 40);
    }

    function Footer() {
        $this->SetFont('Arial', 'I', 10);
        $this->SetXY(10, 195);
        $this->Cell(280, 3, utf8_decode("El espíritu de las grandes obras"), 0, 0, 'C');
    }
}

if (isset($_GET['id'])) {
    $codigo_permiso = $_GET['id'];
} else {
    die("Error: Parámetros incompletos. Se requieren id1 y id2.");
}

$permisoClass = new Permiso();
$permiso = $permisoClass->get_detalle_PDF($codigo_permiso);

/* echo "<pre>";
print_r($permiso);
echo "</pre>";
exit; */

$nombre_empleado = isset($permiso['empleado_nombre']) ? $permiso['empleado_nombre'] : 'N/A';
$fecha = isset($permiso['permiso_fecha']) ? $permiso['permiso_fecha'] : 'N/A';
$hora_entrada = isset($permiso['permiso_hora_entrada']) ? $permiso['permiso_hora_entrada'] : 'N/A';
$hora_salida = isset($permiso['permiso_hora_salida']) ? $permiso['permiso_hora_salida'] : 'N/A';
$tipo_permiso = isset($permiso['permiso_tipo']) ? $permiso['permiso_tipo'] : 'N/A';
$justificacion = isset($permiso['permiso_detalle']) ? $permiso['permiso_detalle'] : 'N/A';
$firma = isset($permiso['firma_empleado']) ? $permiso['firma_empleado'] : 'N/A';
$jefe = isset($permiso['jefe_nombre']) ? $permiso['jefe_nombre'] : 'N/A';
$cargo = isset($permiso['cargo']) ? $permiso['cargo'] : 'N/A';
$rrhh = isset($permiso['recurso_humano']) ? $permiso['recurso_humano'] : 'N/A';
$firma_jefe = isset($permiso['firma_jefe']) ? $permiso['firma_jefe'] : 'N/A';
$firma_rrhh = isset($permiso['firma_rrhh']) ? $permiso['firma_rrhh'] : 'N/A';
$estado_permiso = isset($permiso['permiso_estado']) ? (int)$permiso['permiso_estado'] : 0;


$temp_empleado = base64_to_png_temp($firma);
$temp_rrhh     = base64_to_png_temp($firma_rrhh);

$hora_salida_ampm  = date("g:i A", strtotime($hora_salida));
$hora_entrada_ampm = date("g:i A", strtotime($hora_entrada));


$temp_jefe     = null;


// Mostrar firma del jefe SOLO si el permiso fue aprobado por él
if (
    !empty($firma_jefe) &&
    in_array($estado_permiso, [2, 4, 5]) // aprobado jefe o posteriores
) {
    $temp_jefe = base64_to_png_temp($firma_jefe);
}


$pdf = new PDF('L', 'mm', 'Letter');
$pdf->AddPage();

// =========================
// DATOS DEL EMPLEADO
// =========================

$y = 48;
$pdf->SetFont('Arial', 'B', 11);

$labels = [
    "NOMBRES Y APELLIDO",
    "CARGO",
    "FECHA"
];

$valores = [
    utf8_decode($nombre_empleado),
    utf8_decode($cargo),
    date('d-m-Y', strtotime($fecha))
];

foreach ($labels as $i => $label) {

    $pdf->SetXY(15, $y);
    $pdf->Cell(60, 6, utf8_decode($label));

    $pdf->SetFont('Arial', '', 11);
    $pdf->SetXY(80, $y - 1);
    $pdf->Cell(180, 6, utf8_decode($valores[$i]));

    $pdf->SetDrawColor(150, 150, 150);
    $pdf->Line(80, $y + 4, 255, $y + 4);

    $pdf->SetFont('Arial', 'B', 11);
    $y += 10;
}

// =========================
// HORAS
// =========================

$pdf->SetXY(15, $y);
$pdf->Cell(45, 6, utf8_decode("HORA DE SALIDA"));

$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(60, $y - 1);
$pdf->Cell(50, 6, utf8_decode($hora_salida_ampm));

$pdf->SetDrawColor(50, 50, 50);
$pdf->Line(60, $y + 4, 120, $y + 4);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetXY(130, $y);
$pdf->Cell(45, 6, utf8_decode("HORA DE ENTRADA"));

$pdf->SetFont('Arial', '', 11);
$pdf->SetXY(178, $y - 1);
$pdf->Cell(40, 6, utf8_decode($hora_entrada_ampm));


// línea SOLO para hora entrada
$pdf->Line(178, $y + 4, 238, $y + 4);

$y += 15;

// =========================
// MOTIVOS DEL PERMISO
// =========================

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(15, $y);
$pdf->Cell(50, 6, utf8_decode("MOTIVOS DEL PERMISO"));

$y += 10;

// Función para marcar X
function check($value, $tipo_permiso) {
    return ($value == $tipo_permiso) ? "X" : " ";
}

// Primera fila
$pdf->SetFont('Arial', '', 11);


// ===============================
// PRIMERA FILA
// ===============================

// ======== CONFIGURACIÓN DE COLUMNAS ===========
$col1 = 50;  // PERMISO PERSONAL
$col2 = 40;  // CITA MÉDICA
$col3 = 55;  // INCAPACIDAD MÉDICA
$col4 = 55;  // ACCIDENTE DE TRABAJO

// ---------------------------------------------
// FILA 1
// ---------------------------------------------
$pdf->SetXY(15, $y);
$pdf->Cell($col1, 6, utf8_decode("PERMISO PERSONAL"));
$pdf->Cell(10, 6, '(' . check(1, $tipo_permiso) . ')', 0, 0, 'L');

$pdf->Cell($col2, 6, utf8_decode("CITA MÉDICA"));
$pdf->Cell(10, 6, '(' . check(2, $tipo_permiso) . ')', 0, 0, 'L');

$pdf->Cell($col3, 6, utf8_decode("INCAPACIDAD MÉDICA"));
$pdf->Cell(10, 6, '(' . check(3, $tipo_permiso) . ')', 0, 0, 'L');

$pdf->Cell($col4, 6, utf8_decode("ACCIDENTE DE TRABAJO"));
$pdf->Cell(10, 6, '(' . check(4, $tipo_permiso) . ')', 0, 0, 'L');

$y += 10;

// ---------------------------------------------
// FILA 2
// ---------------------------------------------
$pdf->SetXY(15, $y);
$pdf->Cell($col1, 6, utf8_decode("CALAMIDAD DOMÉSTICA"));
$pdf->Cell(10, 6, '(' . check(5, $tipo_permiso) . ')', 0, 0, 'L');

$pdf->Cell($col2, 6, utf8_decode("OTRO"));
$pdf->Cell(10, 6, '(' . check(6, $tipo_permiso) . ')', 0, 0, 'L');

$pdf->Cell(35, 6, utf8_decode("¿CUÁL?"));
$pdf->Cell(60, 6, "__________________________", 0, 0);

$pdf->Cell(10, 6, '(' . check(7, $tipo_permiso) . ')', 0, 0, 'L');

$y += 15;



// =========================
// JUSTIFICACIÓN
// =========================

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(10, $y);
$pdf->Cell(260, 8, utf8_decode("JUSTIFICACIÓN"), 1, 1, 'C');

$y += 8;

// ================================
// RECUADRO DE JUSTIFICACIÓN
// ================================
$alto_cuadro = 20; // Ajustable

$pdf->SetDrawColor(0, 0, 0);
$pdf->Rect(10, $y, 260, $alto_cuadro);

// ================================
// TEXTO DE JUSTIFICACIÓN DENTRO
// ================================
$pdf->SetXY(12, $y + 2); // Margen interno
$pdf->SetFont('Arial', '', 11);

// Justificación multilínea
$pdf->MultiCell(256, 6, utf8_decode($justificacion), 0, 'L');

// Avanzar después del recuadro
$y += $alto_cuadro + 15;

// =========================
// FIRMAS (CORREGIDO)
// =========================

$pdf->SetFont('Arial', '', 10);

//=============================
// Convertir firma base64 en imagen
//=============================

if (!empty($firma)) {

    // 1. Limpiar prefijo
    $firma_limpia = preg_replace('#^data:image/\w+;base64,#i', '', $firma);

    // 2. Reemplazar espacios corruptores
    $firma_limpia = str_replace(' ', '+', $firma_limpia);

    // 3. Decodificar
    $firma_decodificada = base64_decode($firma_limpia);

    // Validación
    if ($firma_decodificada && strlen($firma_decodificada) > 100) {

        // 4. Guardar archivo temporal
        $temp_file = tempnam(sys_get_temp_dir(), 'firma_') . ".png";
        file_put_contents($temp_file, $firma_decodificada);
    } else {
        $temp_file = null; // Firma inválida o vacía
    }
}

// Líneas
$pdf->SetXY(20, $y);
$pdf->Cell(70, 6, "______________________________________");
// Firma encima de la línea (si existe)
if ($temp_empleado) {
    $pdf->Image($temp_empleado, 20, $y - 12, 50);
}


$pdf->SetXY(110, $y);
$pdf->Cell(70, 6, "______________________________________");
// Firma encima de la línea (si existe)
if ($temp_jefe) {
    $pdf->Image($temp_jefe, 115, $y - 10, 50);
}



$pdf->SetXY(195, $y);
$pdf->Cell(70, 6, "______________________________________");
if ($temp_rrhh) {
    $pdf->Image($temp_rrhh, 205, $y - 15, 50);
}

$y += 6;

// Etiquetas
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY(20, $y);
$pdf->Cell(60, 6, utf8_decode("SOLICITANTE"));

$pdf->SetXY(110, $y);
$pdf->Cell(60, 6, utf8_decode("AUTORIZADO JEFE DE ÁREA"));

$pdf->SetXY(200, $y);
$pdf->Cell(60, 6, utf8_decode("Vo.Bo Director Gestión Humana"));

$y += 6;

// Sub-etiquetas
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(20, $y);
$pdf->Cell(70, 6, "Nombre / Cargo  " . utf8_decode($nombre_empleado));


$pdf->SetXY(110, $y);
$pdf->Cell(70, 6, "Nombre / Cargo  " . utf8_decode($jefe));

$pdf->SetXY(200, $y);
$pdf->Cell(70, 6, "Nombre / Cargo ");
$pdf->SetXY(200, $y + 5);
$pdf->Cell(70, 6, utf8_decode($rrhh), 0, 0, 'L');


$pdf->Output();
