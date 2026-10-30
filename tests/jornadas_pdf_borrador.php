<?php
/** Genera PDFs sintéticos para revisión. No carga conexion.php ni consulta datos reales. */
require_once __DIR__ . '/../view/PDF/jornadas_formato.php';
class PdfBorradorPrueba extends JornadaReportePDF {
    public $imagenesFirmas = [];
    public $textos = [];
    public function MultiCell($w, $h, $txt, ...$args) {
        $this->textos[] = (string)$txt;
        return parent::MultiCell($w, $h, $txt, ...$args);
    }
    public function Image($file, ...$args) {
        if (is_string($file) && substr($file, 0, 1) === '@') {
            $this->imagenesFirmas[] = ['pagina' => $this->getPage(), 'hash' => hash('sha256', substr($file, 1))];
        }
        return parent::Image($file, ...$args);
    }
}
function imagenPrueba($azul) {
    $imagen = imagecreatetruecolor(300, 65);
    imagefill($imagen, 0, 0, imagecolorallocate($imagen, 255, 255, 255));
    $tinta = imagecolorallocate($imagen, $azul ? 30 : 0, $azul ? 60 : 0, $azul ? 180 : 0);
    imagesetthickness($imagen, 3);
    for ($i = 0; $i < 5; $i++) {
        imageline($imagen, 20 + $i * 45, 48, 40 + $i * 45, 12 + $i * 3, $tinta);
        imageline($imagen, 40 + $i * 45, 12 + $i * 3, 60 + $i * 45, 43, $tinta);
    }
    if ($azul) imageline($imagen, 15, 53, 280, 47, $tinta);
    ob_start();
    imagepng($imagen);
    $binario = ob_get_clean();
    imagedestroy($imagen);
    return base64_encode($binario);
}
$destino = $argv[1] ?? sys_get_temp_dir();
if (!is_dir($destino)) throw new RuntimeException('El directorio de salida no existe.');
$trabajador = imagenPrueba(false);
$jefe = imagenPrueba(true);
$jornadaNeta = ['jornada_inicio' => '2026-09-16 03:53:00', 'jornada_fin' => '2026-09-16 18:27:00',
    'segmentos' => [['codigo' => 'ORD', 'minutos' => 353], ['codigo' => 'RN', 'minutos' => 127],
        ['codigo' => 'HED', 'minutos' => 394]]];
if (je_minutos_netos_jornada($jornadaNeta) !== 874 || je_totales_jornada($jornadaNeta)['ORD'] !== 353) {
    throw new RuntimeException('El neto del documento no debe confundirse con el concepto liquidado ORD.');
}
$conDescuento = $jornadaNeta;
$conDescuento['segmentos'][] = ['codigo' => 'NO_LIQ', 'minutos' => 60];
if (je_minutos_netos_jornada($conDescuento) !== 814) throw new RuntimeException('El neto antiguo descuenta NO_LIQ.');
$conDescuento['jornada_minutos_ordinarios'] = 800;
if (je_minutos_netos_jornada($conDescuento) !== 800) throw new RuntimeException('Debe respetar el neto guardado cuando existe.');
$pdfNeto = new PdfBorradorPrueba('L', 'mm', 'LETTER', true, 'UTF-8', false);
$pdfNeto->setPrintHeader(false);
$pdfNeto->AddPage();
$pdfNeto->configurarEmpleado(['jornadas' => [$jornadaNeta]]);
$pdfNeto->dibujarFilaJornada(30, $jornadaNeta);
if ($pdfNeto->textos[4] !== '14:34') throw new RuntimeException('La columna del PDF debe mostrar 14:34 netas, no 05:53 ORD.');
$pdfNeto->textos = [];
$pdfNeto->dibujarTotalesNotas(50, ['ORD' => 353]);
if ($pdfNeto->textos[1] !== '14:34') throw new RuntimeException('El total de la columna debe sumar las horas netas mostradas.');
echo "OK: columna y total netos 14:34; conceptos liquidados y descuentos conservados.\n";
$jornada = ['jornada_id' => 1, 'jornada_inicio' => '2026-10-19 08:00:00', 'jornada_fin' => '2026-10-19 17:00:00',
    'jornada_minutos_ordinarios' => 480, 'jornada_ubicacion' => 'LA TOSCANA',
    'jornada_actividad' => 'Instalacion y mantenimiento de equipos', 'aprobado_por' => '', 'segmentos' => []];
$snapshot = ['empleado' => 'TRABAJADOR DE PRUEBA', 'documento' => '123456', 'cargo' => 'TECNICO',
    'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Masculino', 'desde' => '2026-10-01', 'hasta' => '2026-10-31',
    'borrador' => true, 'jefe_nombre' => 'JEFE DE PRUEBA', 'firma_jefe' => $jefe,
    'firma_empleado' => 'data:image/png;base64,' . $trabajador, 'jornadas_firmadas' => 1,
    'jornadas' => [$jornada], 'totales' => ['ORD' => 480]];
foreach (['firmado' => 1, 'sin_firma' => 1, 'tres_hojas' => 34, 'contable' => 1, 'contable_firmado' => 1,
    'contable_tres_hojas' => 34, 'contable_varios_jefes' => 1] as $nombre => $cantidad) {
    $datos = $snapshot;
    $datos['jornadas'] = array_fill(0, $cantidad, $jornada);
    $datos['jornadas_firmadas'] = $cantidad;
    if ($nombre === 'sin_firma') { $datos['firma_empleado'] = null; $datos['jornadas_firmadas'] = 0; }
    if ($nombre === 'contable') { $datos['borrador'] = false; }
    if (strpos($nombre, 'contable_') === 0) { $datos['borrador'] = false; $datos['mostrar_firmas'] = true; }
    if ($nombre === 'contable_varios_jefes') {
        $datos['firmas_jefes'] = [['jefe_nombre' => 'APROBADOR UNO', 'firma_jefe' => $jefe],
            ['jefe_nombre' => 'APROBADOR DOS', 'firma_jefe' => $trabajador]];
    }
    $pdf = new PdfBorradorPrueba('L', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->setCellPaddings(0.4, 0.3, 0.4, 0.3);
    je_pdf_empleado($pdf, $datos, ['jlot_nombre' => 'PRUEBA']);
    $esperadas = $nombre === 'contable' ? 0 : ($nombre === 'sin_firma' ? 1 : 2);
    if ($nombre === 'contable_varios_jefes') $esperadas = 3;
    if (count($pdf->imagenesFirmas) !== $esperadas) throw new RuntimeException('Número de firmas incorrecto: ' . $nombre);
    foreach ($pdf->imagenesFirmas as $imagen) {
        if ($imagen['pagina'] !== 2) throw new RuntimeException('La firma debe aparecer solo en la segunda hoja.');
    }
    $paginas = in_array($nombre, ['tres_hojas', 'contable_tres_hojas'], true) ? 3 : 2;
    if ($pdf->getNumPages() !== $paginas) throw new RuntimeException('Paginación incorrecta.');
    if ($nombre === 'contable_varios_jefes' && (!in_array('APROBADOR UNO', $pdf->textos, true)
        || !in_array('APROBADOR DOS', $pdf->textos, true))) throw new RuntimeException('Deben identificarse ambos aprobadores.');
    $pdf->Output($destino . '/borrador_' . $nombre . '.pdf', 'F');
    echo "OK: $nombre, $paginas hojas y $esperadas firmas únicamente en la segunda hoja.\n";
}
$pdf = new PdfBorradorPrueba('L', 'mm', 'LETTER', true, 'UTF-8', false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->setCellPaddings(0.4, 0.3, 0.4, 0.3);
je_pdf_empleado($pdf, $snapshot, []);
$segundo = $snapshot;
$segundo['empleado'] = 'SEGUNDO TRABAJADOR';
$segundo['documento'] = '654321';
je_pdf_empleado($pdf, $segundo, []);
if ($pdf->getNumPages() !== 4 || array_column($pdf->imagenesFirmas, 'pagina') !== [2, 2, 4, 4]) {
    throw new RuntimeException('El lote debe separar las planillas y firmas de cada empleado.');
}
$pdf->Output($destino . '/borrador_lote.pdf', 'F');
echo "OK: lote con dos empleados, cuatro hojas y firmas separadas.\n";
$biotime = $snapshot;
$biotime['biotime'] = true;
$biotime['firma_empleado'] = null;
$biotime['firma_jefe'] = null;
$biotime['jefe_nombre'] = '';
$biotime['jornadas_firmadas'] = 0;
$biotime['jornadas'] = array(
    array_merge($jornada, array('tiempo_bruto_biotime' => '09:00:00', 'jornada_ubicacion' => '', 'jornada_actividad' => '')),
    array_merge($jornada, array('jornada_inicio' => '2026-10-20 08:00:00', 'jornada_fin' => null,
        'tiempo_bruto_biotime' => null, 'jornada_ubicacion' => '', 'jornada_actividad' => 'Sin marcación de salida.'))
);
$biotime['totales'] = array('BRUTO_SEGUNDOS' => 32400);
$pdf = je_crear_pdf(array($biotime), array('jlot_nombre' => 'Marcaciones BioTime'));
if ($pdf->getNumPages() !== 2) throw new RuntimeException('El formato BioTime debe conservar las dos hojas.');
$pdf->Output($destino . '/marcaciones_biotime.pdf', 'F');
echo "OK: consulta BioTime con tiempo bruto, día incompleto y formato de dos hojas.\n";
