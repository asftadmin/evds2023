<?php
require('public/assets/fpdf/fpdf.php');
require('config/conexion.php');
require('models/Evaluacion.php');


class PDF extends FPDF
{
    // Cabecera de página
    function Header()
    {

        //Logo
        $this->SetY(15);
        $this->Image('public/img/logo asf.png', 10, 8, 35);
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
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'El espiritu de las Grandes Obras ', 'T', 0, 'C');
    }
}

$evaluacionClass = new Evaluacion();
$evaluacion= $evaluacionClass->listar_evaluacion();

$nombEmpleado = isset($evaluacion[0]['nombre_empleado']) ? $evaluacion[0]['nombre_empleado'] : 'N/A';
$cargEmpleado = isset($evaluacion[0]['cargo']) ? $evaluacion[0]['cargo'] : 'N/A';
$periodEvaluacion = isset($evaluacion[0]['periodo']) ? $evaluacion[0]['periodo'] : 'N/A';
$mesEvaluacion = isset($evaluacion[0]['nombre_mes']) ? $evaluacion[0]['nombre_mes'] : 'N/A';
$productividad1 = isset($evaluacion[0]['productividad_pregunta_1']) ? $evaluacion[0]['productividad_pregunta_1'] : 'N/A';
$productividad2 = isset($evaluacion[0]['productividad_pregunta_2']) ? $evaluacion[0]['productividad_pregunta_2'] : 'N/A';
$productividad3 = isset($evaluacion[0]['productividad_pregunta_3']) ? $evaluacion[0]['productividad_pregunta_3'] : 'N/A';
$productividad4 = isset($evaluacion[0]['productividad_pregunta_4']) ? $evaluacion[0]['productividad_pregunta_4'] : 'N/A';
$productividad5 = isset($evaluacion[0]['productividad_pregunta_5']) ? $evaluacion[0]['productividad_pregunta_5'] : 'N/A';
$productividad6 = isset($evaluacion[0]['productividad_pregunta_6']) ? $evaluacion[0]['productividad_pregunta_6'] : 'N/A';
$subtotalProduvct = isset($evaluacion[0]['productividad']) ? $evaluacion[0]['productividad'] : 'N/A';
$conducta1 = isset($evaluacion[0]['liderazgo_pregunta_1']) ? $evaluacion[0]['liderazgo_pregunta_1'] : 'N/A';
$conducta2 = isset($evaluacion[0]['liderazgo_pregunta_2']) ? $evaluacion[0]['liderazgo_pregunta_2'] : 'N/A';
$conducta3 = isset($evaluacion[0]['liderazgo_pregunta_3']) ? $evaluacion[0]['liderazgo_pregunta_3'] : 'N/A';
$conducta4 = isset($evaluacion[0]['liderazgo_pregunta_4']) ? $evaluacion[0]['liderazgo_pregunta_4'] : 'N/A';
$conducta5 = isset($evaluacion[0]['liderazgo_pregunta_5']) ? $evaluacion[0]['liderazgo_pregunta_5'] : 'N/A';
$conducta6 = isset($evaluacion[0]['liderazgo_pregunta_6']) ? $evaluacion[0]['liderazgo_pregunta_6'] : 'N/A';
$subtotalCond = isset($evaluacion[0]['liderazgo']) ? $evaluacion[0]['liderazgo'] : 'N/A';
$actitud1 = isset($evaluacion[0]['actitud_pregunta_1']) ? $evaluacion[0]['actitud_pregunta_1'] : 'N/A';
$actitud2 = isset($evaluacion[0]['actitud_pregunta_2']) ? $evaluacion[0]['actitud_pregunta_2'] : 'N/A';
$actitud3 = isset($evaluacion[0]['actitud_pregunta_3']) ? $evaluacion[0]['actitud_pregunta_3'] : 'N/A';
$actitud4 = isset($evaluacion[0]['actitud_pregunta_4']) ? $evaluacion[0]['actitud_pregunta_4'] : 'N/A'; 
$subtotalActitud = isset($evaluacion[0]['actitud']) ? $evaluacion[0]['actitud'] : 'N/A';
$liderazgo1 = isset($evaluacion[0]['desarrollo_pregunta_1']) ? $evaluacion[0]['desarrollo_pregunta_1'] : 'N/A';
$liderazgo2 = isset($evaluacion[0]['desarrollo_pregunta_2']) ? $evaluacion[0]['desarrollo_pregunta_2'] : 'N/A';
$liderazgo3 = isset($evaluacion[0]['desarrollo_pregunta_3']) ? $evaluacion[0]['desarrollo_pregunta_3'] : 'N/A';
$liderazgo4 = isset($evaluacion[0]['desarrollo_pregunta_4']) ? $evaluacion[0]['desarrollo_pregunta_4'] : 'N/A';
$liderazgo5 = isset($evaluacion[0]['desarrollo_pregunta_5']) ? $evaluacion[0]['desarrollo_pregunta_5'] : 'N/A';
$subtotalLiderazgo = isset($evaluacion[0]['desarrollo']) ? $evaluacion[0]['desarrollo'] : 'N/A';
$observacionesE = isset($evaluacion[0]['observaciones']) ? $evaluacion[0]['observaciones'] : 'N/A';
$totalEvaluacion = isset($evaluacion[0]['total']) ? $evaluacion[0]['total'] : 'N/A';

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('p','Letter', '0');
$pdf->SetFont('Times','B',14);

// Obtener la fecha y hora actual
$date = date('d/m/Y H:i:s');


// Texto Preguntas
$pregunta1 = 'Conocimiento: conocimiento y habilidades para el desempeño, cumpliendo con las actividades del cargo y calidad del trabajo.';
$pregunta2 = 'Planeación y dirección: determinación de metas y prioridades institucionales, cumplimiento con las metas, objetivos y planes establecidos relacionadas con su cargo. Determinación de planes y objetivos claros coherentes con las metas. ';
$pregunta3 = 'Planeación y organización: determina eficazmente las metas, objetivos y prioridades, estipulando la acción, los plazos y los recursos requeridos para su labor. determina mecanismos y seguimiento para su labor, los cuales le permiten obtener los resultados esperados.';
$pregunta4 = 'Capacidad de organización del trabajo: la disposición y habilidad para crear las condiciones adecuadas de utilización de todos los recursos para el desarrollo y cumplimiento de las actividades de su cargo, dando atención y ejecución de solicitudes verbales.';
$pregunta5 = 'Orientación al servicio: poseen un trato cordial y amable, se interesan por el cliente como persona, se preocupan por entender las necesidades de los clientes internos y externos y dar soluciones a sus problemas, colabora con los clientes internos en la realización de las labores.';
$pregunta6 = 'Capacidad de iniciativa y/o innovación: disposición para tomar decisiones y encaminarlas en propuestas o acciones, que permitan mejorar las labores desempeñadas. aplicación y asimilación de nueva información y/o tecnología.';
$pregunta7= 'Confiablidad y lealtad: frente al manejo de la información de la entidad, cumplimiento de las funciones.';
$pregunta8= 'Dirección y supervisión: motiva al personal a su cargo para el desempeño de su labor. fomenta la comunicación clara y correcta, fomenta el cumplimiento de las directrices organizacionales en materia de calidad, seguridad y salud en el trabajo y ambiente, fomenta integración para trabajar en equipo opinión y aceptación de otros puntos de vista valoración y respeto por el trabajo de otros.';
$pregunta9= 'Sentido de pertenencia y adaptación: se aprecia gran sentido de identificación con los objetivos de la empresa, aceptan y se adaptan fácilmente a los cambios con flexibilidad.';
$pregunta10= 'Proactividad en evitar y/o resolver conflictos: habilidad para enfrentarse y dar respuesta a una situación determinada mediante la aplicación de una estrategia que promueva soluciones eficaces, se expresa adecuadamente y con respeto a sus jefes, compañeros, clientes, proveedores, comunidad, entre otros actores relacionados con la labor que desempeña.';
$pregunta11= 'Responsabilidad en el cumplimiento de sus obligaciones: cumple correctamente con las funciones de su cargo y vela continuamente por mejorar su desempeño, cumple con todo lo relacionado con la seguridad y salud en el trabajo.';
$pregunta12= 'Puntualidad con sentido de pertenencia: cumplimiento del horario de trabajo establecido.';
$pregunta13 = 'Capacidad para aprender: realiza algún tipo de estudio regularmente, tiene una permanente actitud de aprendizaje y de espíritu investigativo el conocimiento que posee agrega valor al trabajo.';
$pregunta14 = 'Autoevaluación: capacidad de discernir en el desempeño de sus labores, el cómo lo realiza; y a la vez medir su grado de entrega y compromiso; propiciando estrategias de mejoramiento continuo para el optimo desempeño de su labor.';
$pregunta15 = 'Disposición personal: es la actitud dispuesta y de entrega a las labores de la organización; ofrece colaboración y apoyo en determinado proceso organizacional.';
$pregunta16 = 'Ética profesional: capacidad de aplicar conocimientos de forma responsable con honestidad y objetividad en las labores que desempeña';
$pregunta17 = 'Relacionamiento: empatía/ecpatía: capacidad de establecer una relación sana y afectiva hacia una persona con una realidad ajena a la nuestra.';
$pregunta18 = 'Escucha atenta activa analítica: capacidad de comprender lo que se está escuchando y así mismo entender la relación entre las ideas';
$pregunta19 = 'Comunicador asertivo efectivo y constructivo: capacidad de respetar a otros y hacernos respetar por otros a partir del lenguaje verbal, no verbal y la actitud, trasmitiendo de manera eficaz un mensaje';
$pregunta20 = 'Actitud optimista resiliente: capacidad de ser persistentes y determinados, teniendo expectativas y metas realistas y alcanzables, aprendiendo de la experiencia anterior para no repetir errores.';
$pregunta21 = 'Delegar para empoderar: capacidad de manifestar empatía y confianza al momento de asignar tareas a otra persona';



// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times','B',10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ').$pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

//Seccion Informacion General
$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('INFORMACIÓN GENERAL '), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
$pdf->Cell(35, 15, utf8_decode('Evaluado: '), 1, 0, 'L');
$pdf->SetX(45);
$pdf->Cell(35, 5, utf8_decode('Período: '), 1, 0, 'L');
$pdf->Cell(0, 5, utf8_decode($periodEvaluacion.' - '.$mesEvaluacion), 1, 1, 'L');
$pdf->SetX(45);
$pdf->Cell(35, 5, utf8_decode('Nombre: '), 1, 0, 'L');
$pdf->Cell(0, 5, utf8_decode($nombEmpleado), 1, 1, 'L');
$pdf->SetX(45);
$pdf->Cell(35, 5, utf8_decode('Cargo: '), 1, 0, 'L');
$pdf->Cell(0, 5, utf8_decode($cargEmpleado), 1, 1, 'L');
$pdf->Ln(5);

//Tabla Productividad
$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
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
$productividad = [$productividad1, $productividad2, $productividad3, $productividad4, $productividad5, $productividad6, $subtotalProduvct];

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

$pdf->AddPage('p','Letter', '0');

// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times','B',10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ').$pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

//Tabla Conducta Laboral

$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
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
$laboral = [$conducta1,$conducta2,$conducta3,$conducta4,$conducta5,$conducta6,$subtotalCond];

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

$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
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
$actitud = [$actitud1,$actitud2,$actitud3,$actitud4,$subtotalActitud];

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

$pdf->AddPage('p','Letter', '0');

// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times','B',10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ').$pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

//Tabla Liderazgo

$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('FACTOR DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
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
$liderazgo = [$liderazgo1,$liderazgo2,$liderazgo3,$liderazgo4,$liderazgo5,$subtotalLiderazgo];

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

$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('OBSERVACIONES'), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
$pdf->MultiCell(0,45,$observacionesE,1,'J');

$pdf->Ln(7);
$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('INTERPRETACIÓN DE LA EVALUACIÓN DE DESEMPEÑO'), 1, 1, 'C');
$pdf->SetFont('Times','',12);
$pdf->MultiCell(0,7,utf8_decode('Para efectos de las decisiones que se deriven de la evaluación del desempeño, se tienen en cuenta los siguientes grados:'),1,'J');
$pdf->Cell(96, 8, utf8_decode('Excelente:'), 1, 0, 'C');
$pdf->Cell(100, 8, utf8_decode('50 A 63'), 1, 1, 'C');
$pdf->Cell(96, 8, utf8_decode('Bueno:'), 1, 0, 'C');
$pdf->Cell(100, 8, utf8_decode('22 A 49'), 1, 1, 'C');
$pdf->Cell(96, 8, utf8_decode('Deficiente:'), 1, 0, 'C');
$pdf->Cell(100, 8, utf8_decode('1 A 21'), 1, 1, 'C');

$pdf->AddPage('p','Letter', '0');

// Imprimir la fecha y hora en el PDF
$pdf->SetFont('Times','B',10);
$pdf->Cell(55, 8, utf8_decode('Impresión: ') . $date, 1, 0, 'L');
$pdf->Cell(106, 8, utf8_decode($nombEmpleado), 1, 0, 'L');
$pdf->Cell(35, 8, utf8_decode('Página: ').$pdf->PageNo() . ' de {nb}', 1, 1, 'R');
$pdf->Ln(5);

$pdf->SetFont('Times','B',14);
$pdf->Cell(0, 8, utf8_decode('CALIFICACION FINAL'), 1, 1, 'C');
$pdf->SetFont('Times','',12);
$pdf->Cell(20, 8, utf8_decode('A'), 1, 0, 'C');
$pdf->Cell(80, 8, utf8_decode('EVALUACION DE PRODUCTIVIDAD'), 1, 0, 'C');
$pdf->Cell(50, 8, utf8_decode('40%'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalProduvct), 1, 1, 'C');
$pdf->Cell(20, 8, utf8_decode('B'), 1, 0, 'C');
$pdf->Cell(80, 8, utf8_decode('EVALUACION CONDUCTA LABORAL'), 1, 0, 'C');
$pdf->Cell(50, 8, utf8_decode('20%'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalCond), 1, 1, 'C');
$pdf->Cell(20, 8, utf8_decode('C'), 1, 0, 'C');
$pdf->Cell(80, 8, utf8_decode('EVALUACION ACTITUD'), 1, 0, 'C');
$pdf->Cell(50, 8, utf8_decode('20%'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalActitud), 1, 1, 'C');
$pdf->Cell(20, 8, utf8_decode('D'), 1, 0, 'C');
$pdf->Cell(80, 8, utf8_decode('EVALUACION LIDERAZGO'), 1, 0, 'C');
$pdf->Cell(50, 8, utf8_decode('20%'), 1, 0, 'C');
$pdf->Cell(46, 8, utf8_decode($subtotalLiderazgo), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
$pdf->Cell(150, 8, utf8_decode('CALIFICACION (A+B+C+D) ='), 1, 0, 'C');
$pdf->Cell(46, 8, $totalEvaluacion, 1, 1, 'C');
$pdf->Cell(0, 8, utf8_decode('RESULTADO DE LA EVALUACIÓN'), 1, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('Times','B',12);
$pdf->Cell(0, 8, utf8_decode('RECOMENDACIONES'), 1, 1, 'C');
$pdf->SetFont('Times','',12);
$pdf->Cell(46, 8, utf8_decode('1.'), 1, 0, 'L');
$pdf->Cell(150, 8, utf8_decode(''), 1, 1, 'C');
$pdf->Cell(46, 8, utf8_decode('2.'), 1, 0, 'L');
$pdf->Cell(150, 8, utf8_decode(''), 1, 1, 'C');
$pdf->Cell(46, 8, utf8_decode('3.'), 1, 0, 'L');
$pdf->Cell(150, 8, utf8_decode(''), 1, 1, 'C');
$pdf->Cell(46, 8, utf8_decode('4.'), 1, 0, 'L');
$pdf->Cell(150, 8, utf8_decode(''), 1, 1, 'C');
$pdf->SetFont('Times','B',12);
$pdf->Cell(0, 25, utf8_decode('Evaluado: '), 'LTR', 1, 'L');
$pdf->Cell(0, 15, utf8_decode('Superior Evaluado: '), 'LBR', 1, 'L');

$pdf->Output();


?>