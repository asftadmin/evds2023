<?php
require_once(__DIR__ . '/../../public/assets/fpdf/fpdf.php');
require_once(__DIR__ . '/../../config/conexion.php');
require_once(__DIR__ . '/../../models/Evaluacion.php');


class PDF extends FPDF {
    // Cabecera de página
    function Header() {

        //Logo
        $this->SetY(15);
        $this->Image(__DIR__ . '/../../public/img/logo asf.png', 10, 8, 35);
        $this->SetX(200);
        $this->SetFont('Arial', '', 8);

        // Tabla de Version
        $this->SetX(140);
        $this->Cell(35, 5, 'Version:', 0, 0, 'R');
        $this->Cell(35, 5, '7', 0, 1, 'L');

        $this->SetX(140);
        $this->Cell(35, 5, 'Implementacion:', 0, 0, 'R');
        $this->Cell(35, 5, 'Abril 20 del 2023', 0, 1, 'L');

        $this->SetX(140);
        $this->Cell(35, 5, 'Codigo:', 0, 0, 'R');
        $this->Cell(35, 5, 'GH-F-4', 0, 1, 'L');

        $this->SetX(140);
        $this->Cell(35, 5, utf8_decode('Tipo documento: '), 0, 0, 'R');
        $this->Cell(35, 5, 'Formato', 0, 1, 'L');

        //Titulo
        $this->SetFont('Arial', 'B', 15);
        $this->SetY(15);
        $this->SetX(70);
        $this->Cell(60, 10, utf8_decode('EVALUACIÓN DE DESEMPEÑO'), 0, 0, 'C');
        $this->Ln(25);
    }

    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'El espiritu de las Grandes Obras ', 'T', 0, 'C');
    }
}

$id_empl = $_POST['id_empl'];

$evaluacionClass = new Evaluacion();
$evaluacion = $evaluacionClass->listar_subevaluacion_veinticinco($id_empl);
$evaluacion_coe = $evaluacionClass->listar_coevaluacion_veinticinco($id_empl);
$evaluacion_auto = $evaluacionClass->listar_autoevaluacion_veinticinco($id_empl);

$nombEmpleado = isset($evaluacion[0]['nomb_empl']) ? $evaluacion[0]['nomb_empl'] : 'N/A';
$cargEmpleado = isset($evaluacion[0]['nomb_carg']) ? $evaluacion[0]['nomb_carg'] : 'N/A';
$periodEvaluacion = isset($evaluacion[0]['anio_eval']) ? $evaluacion[0]['anio_eval'] : 'N/A';
/**PUNTAJE PREGUNTAS PRODUCTIVIDAD */
$val_p1_auto = isset($evaluacion[0]['val_p1_auto']) ? (float)$evaluacion[0]['val_p1_auto'] : 0;
$val_p2_auto = isset($evaluacion[0]['val_p2_auto']) ? (float)$evaluacion[0]['val_p2_auto'] : 0;
$val_p3_auto = isset($evaluacion[0]['val_p3_auto']) ? (float)$evaluacion[0]['val_p3_auto'] : 0;
$val_p4_auto = isset($evaluacion[0]['val_p4_auto']) ? (float)$evaluacion[0]['val_p4_auto'] : 0;
$val_p5_auto = isset($evaluacion[0]['val_p5_auto']) ? (float)$evaluacion[0]['val_p5_auto'] : 0;
$val_p6_auto = isset($evaluacion[0]['val_p6_auto']) ? (float)$evaluacion[0]['val_p6_auto'] : 0;

$val_p1_coe = isset($evaluacion_coe[0]['val_p1_coe']) ? (float)$evaluacion_coe[0]['val_p1_coe'] : 0;
$val_p2_coe = isset($evaluacion_coe[0]['val_p2_coe']) ? (float)$evaluacion_coe[0]['val_p2_coe'] : 0;
$val_p3_coe = isset($evaluacion_coe[0]['val_p3_coe']) ? (float)$evaluacion_coe[0]['val_p3_coe'] : 0;
$val_p4_coe = isset($evaluacion_coe[0]['val_p4_coe']) ? (float)$evaluacion_coe[0]['val_p4_coe'] : 0;
$val_p5_coe = isset($evaluacion_coe[0]['val_p5_coe']) ? (float)$evaluacion_coe[0]['val_p5_coe'] : 0;
$val_p6_coe = isset($evaluacion_coe[0]['val_p6_coe']) ? (float)$evaluacion_coe[0]['val_p6_coe'] : 0;

$val_p1_eval = isset($evaluacion_auto[0]['val_p1_eval']) ? (float)$evaluacion_auto[0]['val_p1_eval'] : 0;
$val_p2_eval = isset($evaluacion_auto[0]['val_p2_eval']) ? (float)$evaluacion_auto[0]['val_p2_eval'] : 0;
$val_p3_eval = isset($evaluacion_auto[0]['val_p3_eval']) ? (float)$evaluacion_auto[0]['val_p3_eval'] : 0;
$val_p4_eval = isset($evaluacion_auto[0]['val_p4_eval']) ? (float)$evaluacion_auto[0]['val_p4_eval'] : 0;
$val_p5_eval = isset($evaluacion_auto[0]['val_p5_eval']) ? (float)$evaluacion_auto[0]['val_p5_eval'] : 0;
$val_p6_eval = isset($evaluacion_auto[0]['val_p6_eval']) ? (float)$evaluacion_auto[0]['val_p6_eval'] : 0;

$productividad1 = $val_p1_auto + $val_p1_coe + $val_p1_eval;
$productividad2 = $val_p2_auto + $val_p2_coe + $val_p2_eval;
$productividad3 = $val_p3_auto + $val_p3_coe + $val_p3_eval;
$productividad4 = $val_p4_auto + $val_p4_coe + $val_p5_eval;
$productividad5 = $val_p5_auto + $val_p5_coe + $val_p6_eval;
$productividad6 = $val_p6_auto + $val_p6_coe + $val_p6_eval;

$subtotalPrduct = $productividad1 + $productividad2 + $productividad3 + $productividad4 + $productividad5 + $productividad6;

/**PUNTAJE PREGUNTA CONDUCTA */
$val_l1_auto = isset($evaluacion[0]['val_l1_auto']) ? (float)$evaluacion[0]['val_l1_auto'] : 0;
$val_l2_auto = isset($evaluacion[0]['val_l2_auto']) ? (float)$evaluacion[0]['val_l2_auto'] : 0;
$val_l3_auto = isset($evaluacion[0]['val_l3_auto']) ? (float)$evaluacion[0]['val_l3_auto'] : 0;
$val_l4_auto = isset($evaluacion[0]['val_l4_auto']) ? (float)$evaluacion[0]['val_l4_auto'] : 0;
$val_l5_auto = isset($evaluacion[0]['val_l5_auto']) ? (float)$evaluacion[0]['val_l5_auto'] : 0;
$val_l6_auto = isset($evaluacion[0]['val_l6_auto']) ? (float)$evaluacion[0]['val_l6_auto'] : 0;

$val_l1_coe = isset($evaluacion_coe[0]['val_l1_coe']) ? (float)$evaluacion_coe[0]['val_l1_coe'] : 0;
$val_l2_coe = isset($evaluacion_coe[0]['val_l2_coe']) ? (float)$evaluacion_coe[0]['val_l2_coe'] : 0;
$val_l3_coe = isset($evaluacion_coe[0]['val_l3_coe']) ? (float)$evaluacion_coe[0]['val_l3_coe'] : 0;
$val_l4_coe = isset($evaluacion_coe[0]['val_l4_coe']) ? (float)$evaluacion_coe[0]['val_l4_coe'] : 0;
$val_l5_coe = isset($evaluacion_coe[0]['val_l5_coe']) ? (float)$evaluacion_coe[0]['val_l5_coe'] : 0;
$val_l6_coe = isset($evaluacion_coe[0]['val_l6_coe']) ? (float)$evaluacion_coe[0]['val_l6_coe'] : 0;

$val_l1_eval = isset($evaluacion_auto[0]['val_l1_eval']) ? (float)$evaluacion_auto[0]['val_l1_eval'] : 0;
$val_l2_eval = isset($evaluacion_auto[0]['val_l2_eval']) ? (float)$evaluacion_auto[0]['val_l2_eval'] : 0;
$val_l3_eval = isset($evaluacion_auto[0]['val_l3_eval']) ? (float)$evaluacion_auto[0]['val_l3_eval'] : 0;
$val_l4_eval = isset($evaluacion_auto[0]['val_l4_eval']) ? (float)$evaluacion_auto[0]['val_l4_eval'] : 0;
$val_l5_eval = isset($evaluacion_auto[0]['val_l5_eval']) ? (float)$evaluacion_auto[0]['val_l5_eval'] : 0;
$val_l6_eval = isset($evaluacion_auto[0]['val_l6_eval']) ? (float)$evaluacion_auto[0]['val_l6_eval'] : 0;

$conducta1 = $val_l1_auto + $val_l1_coe + $val_l1_eval;
$conducta2 = $val_l2_auto + $val_l2_coe + $val_l2_eval;
$conducta3 = $val_l3_auto + $val_l3_coe + $val_l3_eval;
$conducta4 = $val_l4_auto + $val_l4_coe + $val_l4_eval;
$conducta5 = $val_l5_auto + $val_l5_coe + $val_l5_eval;
$conducta6 = $val_l6_auto + $val_l6_coe + $val_l6_eval;

$subtotalCond = $conducta1 + $conducta2 + $conducta3 + $conducta4 + $conducta5 + $conducta6;


$val_a1_auto = isset($evaluacion[0]['val_a1_auto']) ? $evaluacion[0]['val_a1_auto'] : 0;
$val_a2_auto = isset($evaluacion[0]['val_a2_auto']) ? $evaluacion[0]['val_a2_auto'] : 0;
$val_a3_auto  = isset($evaluacion[0]['val_a3_auto']) ? $evaluacion[0]['val_a3_auto'] : 0;
$val_a4_auto = isset($evaluacion[0]['val_a4_auto']) ? $evaluacion[0]['val_a4_auto'] : 0;

$val_a1_coe = isset($evaluacion_coe[0]['val_a1_coe']) ? $evaluacion_coe[0]['val_a1_coe'] : 0;
$val_a2_coe = isset($evaluacion_coe[0]['val_a2_coe']) ? $evaluacion_coe[0]['val_a2_coe'] : 0;
$val_a3_coe  = isset($evaluacion_coe[0]['val_a3_coe']) ? $evaluacion_coe[0]['val_a3_coe'] : 0;
$val_a4_coe = isset($evaluacion_coe[0]['val_a4_coe']) ? $evaluacion_coe[0]['val_a4_coe'] : 0;

$val_a1_eval = isset($evaluacion_auto[0]['val_a1_eval']) ? $evaluacion_auto[0]['val_a1_eval'] : 0;
$val_a2_eval = isset($evaluacion_auto[0]['val_a2_eval']) ? $evaluacion_auto[0]['val_a2_eval'] : 0;
$val_a3_eval  = isset($evaluacion_auto[0]['val_a3_eval']) ? $evaluacion_auto[0]['val_a3_eval'] : 0;
$val_a4_eval = isset($evaluacion_auto[0]['val_a4_eval']) ? $evaluacion_auto[0]['val_a4_eval'] : 0;

$actitud1 = $val_a1_auto + $val_a1_coe + $val_a1_eval;
$actitud2 = $val_a2_auto + $val_a2_coe + $val_a2_eval;
$actitud3 = $val_a3_auto + $val_a3_coe + $val_a3_eval;
$actitud4 = $val_a2_auto + $val_a4_coe + $val_a4_eval;

$subtotalActitud = $actitud1 + $actitud2 + $actitud3 + $actitud4;

$val_d1_eval = isset($evaluacion[0]['val_d1_auto']) ? $evaluacion[0]['val_d1_auto'] : 0;
$val_d2_eval = isset($evaluacion[0]['val_d2_auto']) ? $evaluacion[0]['val_d2_auto'] : 0;
$val_d3_eval = isset($evaluacion[0]['val_d3_auto']) ? $evaluacion[0]['val_d3_auto'] : 0;
$val_d4_eval = isset($evaluacion[0]['val_d4_auto']) ? $evaluacion[0]['val_d4_auto'] : 0;
$val_d5_eval  = isset($evaluacion[0]['val_d5_auto']) ? $evaluacion[0]['val_d5_auto'] : 0;

$val_d1_coe = isset($evaluacion_coe[0]['val_d1_coe']) ? $evaluacion_coe[0]['val_d1_coe'] : 0;
$val_d2_coe = isset($evaluacion_coe[0]['val_d2_coe']) ? $evaluacion_coe[0]['val_d2_coe'] : 0;
$val_d3_coe = isset($evaluacion_coe[0]['val_d3_coe']) ? $evaluacion_coe[0]['val_d3_coe'] : 0;
$val_d4_coe = isset($evaluacion_coe[0]['val_d4_coe']) ? $evaluacion_coe[0]['val_d4_coe'] : 0;
$val_d5_coe  = isset($evaluacion_coe[0]['val_d5_coe']) ? $evaluacion_coe[0]['val_d5_coe'] : 0;

$val_d1_auto = isset($evaluacion_auto[0]['val_d1_eval']) ? $evaluacion_auto[0]['val_d1_eval'] : 0;
$val_d2_auto = isset($evaluacion_auto[0]['val_d2_eval']) ? $evaluacion_auto[0]['val_d2_eval'] : 0;
$val_d3_auto = isset($evaluacion_auto[0]['val_d3_eval']) ? $evaluacion_auto[0]['val_d3_eval'] : 0;
$val_d4_auto = isset($evaluacion_auto[0]['val_d4_eval']) ? $evaluacion_auto[0]['val_d4_eval'] : 0;
$val_d5_auto  = isset($evaluacion_auto[0]['val_d5_eval']) ? $evaluacion_auto[0]['val_d5_eval'] : 0;

$liderazgo1 = $val_d1_eval + $val_d1_coe + $val_d1_auto;
$liderazgo2 = $val_d2_eval + $val_d2_coe + $val_d2_auto;
$liderazgo3 = $val_d3_eval + $val_d3_coe + $val_d3_auto;
$liderazgo4 = $val_d4_eval + $val_d4_coe + $val_d4_auto;
$liderazgo5 = $val_d5_eval + $val_d5_coe + $val_d5_auto;

$subtotalLiderazgo = $liderazgo1 + $liderazgo2 + $liderazgo3 + $liderazgo4 + $liderazgo5;

//Observaciones
$observacionesE = '';

// Agregar observación autoevaluación
if (isset($evaluacion[0]['observaciones_auto']) && $evaluacion[0]['observaciones_auto'] !== '') {
    $observacionesE .= ', ' . $evaluacion[0]['observaciones_auto'] . ' ,';
}

// Agregar observación coevaluación
if (isset($evaluacion[0]['observaciones_coe']) && $evaluacion[0]['observaciones_coe'] !== '') {
    $observacionesE .= ', ' . $evaluacion[0]['observaciones_coe'] . ' ,';
}

// Agregar observación evaluación
if (isset($evaluacion[0]['observaciones_eval']) && $evaluacion[0]['observaciones_eval'] !== '') {
    $observacionesE .= ', ' . $evaluacion[0]['observaciones_eval'] . ' ,';
}

// Limpiar espacios y comas iniciales/finales
$observacionesE = trim($observacionesE, ', ');

$subtotalAutoevalcion = isset($evaluacion_auto[0]['total_ponderado_coe']) ? $evaluacion_auto[0]['total_ponderado_coe'] : 0;

$subtotalCoevaluacion = isset($evaluacion_coe[0]['total_ponderado_coe']) ? $evaluacion_coe[0]['total_ponderado_coe'] : 0;

$subtotalSubevaluacion = isset($evaluacion[0]['total_ponderado_auto']) ? $evaluacion[0]['total_ponderado_auto'] : 0;

/*


$observacionesE = isset($evaluacion[0]['observaciones']) ? $evaluacion[0]['observaciones'] : 'N/A';
$totalEvaluacion = isset($evaluacion[0]['total']) ? $evaluacion[0]['total'] : 'N/A';*/
// Texto Preguntas
$pregunta1 = 'Conocimiento: conocimiento y habilidades para el desempeño, cumpliendo con las actividades del cargo y calidad del trabajo.';
$pregunta2 = 'Planeación y dirección: determinación de metas y prioridades institucionales, cumplimiento con las metas, objetivos y planes establecidos relacionadas con su cargo. Determinación de planes y objetivos claros coherentes con las metas. ';
$pregunta3 = 'Planeación y organización: determina eficazmente las metas, objetivos y prioridades, estipulando la acción, los plazos y los recursos requeridos para su labor. determina mecanismos y seguimiento para su labor, los cuales le permiten obtener los resultados esperados.';
$pregunta4 = 'Capacidad de organización del trabajo: la disposición y habilidad para crear las condiciones adecuadas de utilización de todos los recursos para el desarrollo y cumplimiento de las actividades de su cargo, dando atención y ejecución de solicitudes verbales.';
$pregunta5 = 'Orientación al servicio: poseen un trato cordial y amable, se interesan por el cliente como persona, se preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus problemas, colabora con los clientes internos en la realización de las labores.';
$pregunta6 = 'Capacidad de iniciativa y/o innovación: disposición para tomar decisiones y encaminarlas en propuestas o acciones, que permitan mejorar las labores desempeñadas. aplicación y asimilación de nueva información y/o tecnología.';
$pregunta7 = 'Confiablidad y lealtad: frente al manejo de la información de la entidad, cumplimiento de las funciones.';
$pregunta8 = 'Dirección y supervisión: motiva al personal a su cargo para el desempeño de su labor. fomenta la comunicación clara y correcta, fomenta el cumplimiento de las directrices organizacionales en materia de calidad, seguridad y salud en el trabajo y ambiente, fomenta integración para trabajar en equipo opinión y aceptación de otros puntos de vista valoración y respeto por el trabajo de otros.';
$pregunta9 = 'Sentido de pertenencia y adaptación: se aprecia gran sentido de identificación con los objetivos de la empresa, aceptan y se adaptan fácilmente a los cambios con flexibilidad.';
$pregunta10 = 'Proactividad en evitar y/o resolver conflictos: habilidad para enfrentarse y dar respuesta a una situación determinada mediante la aplicación de una estrategia que promueva soluciones eficaces, se expresa adecuadamente y con respeto a sus jefes, compañeros, clientes, proveedores, comunidad, entre otros actores relacionados con la labor que desempeña.';
$pregunta11 = 'Responsabilidad en el cumplimiento de sus obligaciones: cumple correctamente con las funciones de su cargo y vela continuamente por mejorar su desempeño, cumple con todo lo relacionado con la seguridad y salud en el trabajo.';
$pregunta12 = 'Puntualidad con sentido de pertenencia: cumplimiento del horario de trabajo establecido.';
$pregunta13 = 'Capacidad para aprender: realiza algún tipo de estudio regularmente, tiene una permanente actitud de aprendizaje y de espíritu investigativo el conocimiento que posee agrega valor al trabajo.';
$pregunta14 = 'Autoevaluación: capacidad de discernir en el desempeño de sus labores, el cómo lo realiza; y a la vez medir su grado de entrega y compromiso; propiciando estrategias de mejoramiento continuo para el optimo desempeño de su labor.';
$pregunta15 = 'Disposición personal: es la actitud dispuesta y de entrega a las labores de la organización; ofrece colaboración y apoyo en determinado proceso organizacional.';
$pregunta16 = 'Ética profesional: capacidad de aplicar conocimientos de forma responsable con honestidad y objetividad en las labores que desempeña';
$pregunta17 = 'Relacionamiento: empatía/ecpatía: capacidad de establecer una relación sana y afectiva hacia una persona con una realidad ajena a la nuestra.';
$pregunta18 = 'Escucha atenta activa analítica: capacidad de comprender lo que se está escuchando y así mismo entender la relación entre las ideas';
$pregunta19 = 'Comunicador asertivo efectivo y constructivo: capacidad de respetar a otros y hacernos respetar por otros a partir del lenguaje verbal, no verbal y la actitud, trasmitiendo de manera eficaz un mensaje';
$pregunta20 = 'Actitud optimista resiliente: capacidad de ser persistentes y determinados, teniendo expectativas y metas realistas y alcanzables, aprendiendo de la experiencia anterior para no repetir errores.';
$pregunta21 = 'Delegar para empoderar: capacidad de manifestar empatía y confianza al momento de asignar tareas a otra persona';



$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('p', 'Letter', '0');
$pdf->SetFont('Times', 'B', 14);

// Obtener la fecha y hora actual
// Establecer zona horaria GMT-5
date_default_timezone_set('America/Bogota');
$date = date('d-m-Y h:i A');



// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ') . $pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

//Seccion Informacion General
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('INFORMACIÓN GENERAL '), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(35, 15, utf8_decode('Evaluado: '), 1, 0, 'L');
$pdf->SetX(45);
$pdf->Cell(35, 5, utf8_decode('Período: '), 1, 0, 'L');
$pdf->Cell(0, 5, utf8_decode($periodEvaluacion), 1, 1, 'L');
$pdf->SetX(45);
$pdf->Cell(35, 5, utf8_decode('Nombre: '), 1, 0, 'L');
$pdf->Cell(0, 5, utf8_decode($nombEmpleado), 1, 1, 'L');
$pdf->SetX(45);
$pdf->Cell(35, 5, utf8_decode('Cargo: '), 1, 0, 'L');
$pdf->Cell(0, 5, utf8_decode($cargEmpleado), 1, 1, 'L');
$pdf->Ln(5);

//Tabla Productividad
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(35, 8, utf8_decode('AREA'), 1, 0, 'C');
$pdf->Cell(126, 8, utf8_decode('DESCRIPCION'), 1, 0, 'C');
$pdf->Cell(35, 8, utf8_decode('CALIFICACION'), 1, 1, 'C');


$pdf->SetFont('Times', '', 12);

$pdf->Cell(35, 105, 'Productividad', 1, 0, 'C');
// Definir los anchos de las celdas
$anchoIzquierda = 126;
$anchoDerecha = 35;

$pdf->SetFont('Times', '', 10);

$productividadArray = [
    $pregunta1,
    $pregunta2,
    $pregunta3,
    $pregunta4,
    $pregunta5,
    $pregunta6,
    'Subtotal: '
];

// Datos de ejemplo
$productividad = [
    number_format($productividad1, 2),
    number_format($productividad2, 2),
    number_format($productividad3, 2),
    number_format($productividad4, 2),
    number_format($productividad5, 2),
    number_format($productividad6, 2),
    number_format($subtotalPrduct, 2)
];

foreach ($productividad as $index => $productividad) {
    // Establecer la posición inicial en X = 50
    $pdf->SetX(45);

    // Definir las posiciones iniciales de las celdas
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Guardar la posición inicial Y
    $initialY = $y;

    // Celda izquierda con MultiCell
    $pdf->MultiCell($anchoIzquierda, 5, utf8_decode($productividadArray[$index]), 1, 'L');

    // Guardar la posición después de la celda izquierda
    $finalY = $pdf->GetY();

    // Calcular la altura ocupada por MultiCell
    $alturaMultiCell = $finalY - $initialY;

    // Volver a la posición inicial y desplazar para la celda derecha
    $pdf->SetXY($x + $anchoIzquierda, $initialY);

    // Celda derecha con altura ajustada
    $pdf->Cell($anchoDerecha, $alturaMultiCell, $productividad, 1, 0, 'C');

    // Mover a la siguiente línea
    $pdf->Ln($alturaMultiCell);
}


$pdf->AddPage('p', 'Letter', '0');

// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ') . $pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

//Tabla Conducta Laboral

$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(35, 8, utf8_decode('AREA'), 1, 0, 'C');
$pdf->Cell(126, 8, utf8_decode('DESCRIPCION'), 1, 0, 'C');
$pdf->Cell(35, 8, utf8_decode('CALIFICACION'), 1, 1, 'C');



$pdf->SetFont('Times', '', 12);

$pdf->Cell(35, 100, 'Conducta Laboral', 1, 0, 'C');
// Definir los anchos de las celdas
$anchoIzquierda = 126;
$anchoDerecha = 35;

$pdf->SetFont('Times', '', 10);

$laboralArray = [
    $pregunta7,
    $pregunta8,
    $pregunta9,
    $pregunta10,
    $pregunta11,
    $pregunta12,
    'Subtotal: '
];

// Datos de ejemplo
$laboral = [
    number_format($conducta1, 2),
    number_format($conducta2, 2),
    number_format($conducta3, 2),
    number_format($conducta4, 2),
    number_format($conducta5, 2),
    number_format($conducta6, 2),
    number_format($subtotalCond, 2)
];

foreach ($laboral as $index => $laboral) {
    // Establecer la posición inicial en X = 50
    $pdf->SetX(45);

    // Definir las posiciones iniciales de las celdas
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Guardar la posición inicial Y
    $initialY = $y;

    // Celda izquierda con MultiCell
    $pdf->MultiCell($anchoIzquierda, 5, utf8_decode($laboralArray[$index]), 1, 'L');

    // Guardar la posición después de la celda izquierda
    $finalY = $pdf->GetY();

    // Calcular la altura ocupada por MultiCell
    $alturaMultiCell = $finalY - $initialY;

    // Volver a la posición inicial y desplazar para la celda derecha
    $pdf->SetXY($x + $anchoIzquierda, $initialY);

    // Celda derecha con altura ajustada
    $pdf->Cell($anchoDerecha, $alturaMultiCell, $laboral, 1, 0, 'C');

    // Mover a la siguiente línea
    $pdf->Ln($alturaMultiCell);
}

$pdf->Ln(5);




//Tabla Actitud

$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(35, 8, utf8_decode('AREA'), 1, 0, 'C');
$pdf->Cell(126, 8, utf8_decode('DESCRIPCION'), 1, 0, 'C');
$pdf->Cell(35, 8, utf8_decode('CALIFICACION'), 1, 1, 'C');



$pdf->SetFont('Times', '', 12);

$pdf->Cell(35, 55, 'Actitud', 1, 0, 'C');
// Definir los anchos de las celdas
$anchoIzquierda = 126;
$anchoDerecha = 35;

$pdf->SetFont('Times', '', 10);

$actitudArray = [
    $pregunta13,
    $pregunta14,
    $pregunta15,
    $pregunta16,
    'Subtotal: '
];

// Datos de ejemplo
$actitud = [
    number_format($actitud1, 2),
    number_format($actitud2, 2),
    number_format($actitud3, 2),
    number_format($actitud4, 2),
    number_format($subtotalActitud, 2)
];

foreach ($actitud as $index => $actitud) {
    // Establecer la posición inicial en X = 50
    $pdf->SetX(45);

    // Definir las posiciones iniciales de las celdas
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Guardar la posición inicial Y
    $initialY = $y;

    // Celda izquierda con MultiCell
    $pdf->MultiCell($anchoIzquierda, 5, utf8_decode($actitudArray[$index]), 1, 'L');

    // Guardar la posición después de la celda izquierda
    $finalY = $pdf->GetY();

    // Calcular la altura ocupada por MultiCell
    $alturaMultiCell = $finalY - $initialY;

    // Volver a la posición inicial y desplazar para la celda derecha
    $pdf->SetXY($x + $anchoIzquierda, $initialY);

    // Celda derecha con altura ajustada
    $pdf->Cell($anchoDerecha, $alturaMultiCell, $actitud, 1, 0, 'C');

    // Mover a la siguiente línea
    $pdf->Ln($alturaMultiCell);
}

$pdf->AddPage('p', 'Letter', '0');

// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ') . $pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);


//Tabla Liderazgo

$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(35, 8, utf8_decode('AREA'), 1, 0, 'C');
$pdf->Cell(126, 8, utf8_decode('DESCRIPCION'), 1, 0, 'C');
$pdf->Cell(35, 8, utf8_decode('CALIFICACION'), 1, 1, 'C');



$pdf->SetFont('Times', '', 12);

$pdf->Cell(35, 65, 'Liderazgo', 1, 0, 'C');
// Definir los anchos de las celdas
$anchoIzquierda = 126;
$anchoDerecha = 35;

$pdf->SetFont('Times', '', 10);

$liderazgoArray = [
    $pregunta17,
    $pregunta18,
    $pregunta19,
    $pregunta20,
    $pregunta21,
    'Subtotal: '
];

// Datos de ejemplo
$liderazgo = [
    number_format($liderazgo1, 2),
    number_format($liderazgo2, 2),
    number_format($liderazgo3, 2),
    number_format($liderazgo4, 2),
    number_format($liderazgo5, 2),
    number_format($subtotalLiderazgo, 2)
];

foreach ($liderazgo as $index => $liderazgo) {
    // Establecer la posición inicial en X = 50
    $pdf->SetX(45);

    // Definir las posiciones iniciales de las celdas
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Guardar la posición inicial Y
    $initialY = $y;

    // Celda izquierda con MultiCell
    $pdf->MultiCell($anchoIzquierda, 5, utf8_decode($liderazgoArray[$index]), 1, 'L');

    // Guardar la posición después de la celda izquierda
    $finalY = $pdf->GetY();

    // Calcular la altura ocupada por MultiCell
    $alturaMultiCell = $finalY - $initialY;

    // Volver a la posición inicial y desplazar para la celda derecha
    $pdf->SetXY($x + $anchoIzquierda, $initialY);

    // Celda derecha con altura ajustada
    $pdf->Cell($anchoDerecha, $alturaMultiCell, $liderazgo, 1, 0, 'C');

    // Mover a la siguiente línea
    $pdf->Ln($alturaMultiCell);
}

$pdf->Ln(7);


$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('OBSERVACIONES'), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 10);
$pdf->MultiCell(0, 10, utf8_decode($observacionesE), 1, 'J');


$pdf->Ln(7);
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('INTERPRETACIÓN DE LA EVALUACIÓN DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 7, utf8_decode('Para efectos de las decisiones que se deriven de la evaluación del desempeño, se tienen en cuenta los siguientes grados:'), 1, 'J');
$pdf->Cell(96, 8, utf8_decode('Bueno:'), 1, 0, 'C');
$pdf->Cell(100, 8, utf8_decode('50 A 63'), 1, 1, 'C');
$pdf->Cell(96, 8, utf8_decode('Regular:'), 1, 0, 'C');
$pdf->Cell(100, 8, utf8_decode('22 A 49'), 1, 1, 'C');
$pdf->Cell(96, 8, utf8_decode('Deficiente:'), 1, 0, 'C');
$pdf->Cell(100, 8, utf8_decode('1 A 21'), 1, 1, 'C');

$pdf->AddPage('p', 'Letter', '0');

// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ') . $pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('CALIFICACION FINAL'), 1, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(20, 8, utf8_decode('A'), 1, 0, 'C');
$pdf->Cell(130, 8, utf8_decode('AUTOEVALUACION'), 1, 0, 'C');
$pdf->Cell(46, 8, number_format($subtotalAutoevalcion, 2), 1, 1, 'C');
$pdf->Cell(20, 8, utf8_decode('B'), 1, 0, 'C');
$pdf->Cell(130, 8, utf8_decode('COEVALUACION'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalCoevaluacion), 1, 1, 'C');
$pdf->Cell(20, 8, utf8_decode('C'), 1, 0, 'C');
$pdf->Cell(130, 8, utf8_decode('SUBEVALUACION'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalSubevaluacion), 1, 1, 'C');

$totalEvaluacion = $subtotalAutoevalcion + $subtotalCoevaluacion + $subtotalSubevaluacion;

$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(150, 8, utf8_decode('CALIFICACION (A+B+C) ='), 1, 0, 'C');
$pdf->Cell(46, 8, number_format($totalEvaluacion, 2), 1, 1, 'C');
$pdf->Cell(0, 8, utf8_decode('RESULTADO DE LA EVALUACIÓN'), 1, 1, 'C');

/**$pdf->Cell(20, 8, utf8_decode('D'), 1, 0, 'C');
$pdf->Cell(80, 8, utf8_decode('EVALUACION LIDERAZGO'), 1, 0, 'C');
$pdf->Cell(50, 8, utf8_decode('20%'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalLiderazgo), 1, 1, 'C'); */

$pdf->Ln(5);

$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 8, utf8_decode('RECOMENDACIONES'), 1, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(23, 8, utf8_decode('1.'), 1, 0, 'L');
$pdf->Cell(173, 8, utf8_decode(''), 1, 1, 'C');
$pdf->Cell(23, 8, utf8_decode('2.'), 1, 0, 'L');
$pdf->Cell(173, 8, utf8_decode(''), 1, 1, 'C');
$pdf->Cell(23, 8, utf8_decode('3.'), 1, 0, 'L');
$pdf->Cell(173, 8, utf8_decode(''), 1, 1, 'C');
$pdf->Cell(23, 8, utf8_decode('4.'), 1, 0, 'L');
$pdf->Cell(173, 8, utf8_decode(''), 1, 1, 'C');
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 25, utf8_decode('Evaluado: '), 'LTR', 1, 'L');
$pdf->Cell(0, 15, utf8_decode('Superior Evaluado: '), 'LBR', 1, 'L');

$nombreArchivo = $nombEmpleado . '.pdf';
$pdf->Output('I', $nombreArchivo);
