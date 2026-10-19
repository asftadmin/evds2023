<?php
/** Genera PDFs sintéticos para revisión. No carga conexion.php ni consulta datos reales. */
require_once __DIR__ . '/../public/assets/tcpdf/tcpdf.php';
$codigo = file_get_contents(__DIR__ . '/../controller/jornada_exportar.php');
$inicio = strpos($codigo, 'class JornadaReportePDF');
$fin = strpos($codigo, 'function je_fallar');
eval(substr($codigo, $inicio, $fin - $inicio));
$inicio = strpos($codigo, 'function je_horas');
$fin = strpos($codigo, 'function je_pdf($snapshots');
eval(substr($codigo, $inicio, $fin - $inicio));
class PdfBorradorPrueba extends JornadaReportePDF {
    public $imagenesFirmas = [];
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
$jornada = ['jornada_id' => 1, 'jornada_inicio' => '2026-10-19 08:00:00', 'jornada_fin' => '2026-10-19 17:00:00',
    'jornada_minutos_ordinarios' => 480, 'jornada_ubicacion' => 'LA TOSCANA',
    'jornada_actividad' => 'Instalacion y mantenimiento de equipos', 'aprobado_por' => '', 'segmentos' => []];
$snapshot = ['empleado' => 'TRABAJADOR DE PRUEBA', 'documento' => '123456', 'cargo' => 'TECNICO',
    'fecha_nacimiento' => '1990-01-01', 'sexo' => 'Masculino', 'desde' => '2026-10-01', 'hasta' => '2026-10-31',
    'borrador' => true, 'jefe_nombre' => 'JEFE DE PRUEBA', 'firma_jefe' => $jefe,
    'firma_empleado' => 'data:image/png;base64,' . $trabajador, 'jornadas_firmadas' => 1,
    'jornadas' => [$jornada], 'totales' => ['ORD' => 480]];
foreach (['firmado' => 1, 'sin_firma' => 1, 'tres_hojas' => 34, 'contable' => 1] as $nombre => $cantidad) {
    $datos = $snapshot;
    $datos['jornadas'] = array_fill(0, $cantidad, $jornada);
    $datos['jornadas_firmadas'] = $cantidad;
    if ($nombre === 'sin_firma') { $datos['firma_empleado'] = null; $datos['jornadas_firmadas'] = 0; }
    if ($nombre === 'contable') { $datos['borrador'] = false; }
    $pdf = new PdfBorradorPrueba('L', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);
    $pdf->setCellPaddings(0.4, 0.3, 0.4, 0.3);
    je_pdf_empleado($pdf, $datos, ['jlot_nombre' => 'PRUEBA']);
    $esperadas = $nombre === 'contable' ? 0 : ($nombre === 'sin_firma' ? 1 : 2);
    if (count($pdf->imagenesFirmas) !== $esperadas) throw new RuntimeException('Número de firmas incorrecto: ' . $nombre);
    foreach ($pdf->imagenesFirmas as $imagen) {
        if ($imagen['pagina'] !== 2) throw new RuntimeException('La firma debe aparecer solo en la segunda hoja.');
    }
    $paginas = $nombre === 'tres_hojas' ? 3 : 2;
    if ($pdf->getNumPages() !== $paginas) throw new RuntimeException('Paginación incorrecta.');
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
