<?php
// Formato GH-F-19 compartido por horas extras y consultas personales BioTime.
require_once __DIR__ . '/../../public/assets/tcpdf/tcpdf.php';
class JornadaReportePDF extends TCPDF
{
    private $datosEncabezado = [];

    // Coordenada inicial y anchos tomados de la distribución del GH-F-19.
    private $xTabla = 10.5;

    private $anchos = [
        6.8,  // Día
        15.1,  // Fecha
        9.3,  // Entrada
        9.3,  // Salida
        13.0,  // Ordinarias 100
        8.7,  // RN 101
        8.8,  // HED 107
        9.9,  // HEN 108
        10.0,  // HEDF 109
        9.9,  // HENF 110
        6.8,  // RF 115
        30.5,  // Frente
        47.4,  // Actividad
        27.7,  // Empleado
        23.8,  // Persona autoriza
        21.4  // VoBo
    ];

    public function configurarEmpleado(array $snapshot)
    {
        $this->datosEncabezado = $snapshot;
    }

    /**
     * Encabezado institucional repetido en todas las páginas.
     */
    public function Header()
    {
        $logo = __DIR__ . '/../../public/img/logo asf.png';

        if (is_file($logo)) {
            $this->Image($logo, 12, 5, 23, 0, '', '', '', false, 200);
        }

        // Título principal.
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('dejavusans', 'B', 4.6);
        $this->SetXY(43, 8);

        $this->Cell(
            175,
            4,
            'PLANILLA MENSUAL PARA CONTROL DE TIEMPO APROBACIÓN DE HORAS EXTRAS '
                . 'Y REPORTE DE NOVEDADES DE PERSONAL',
            0,
            0,
            'C',
            false,
            '',
            1
        );

        // Control documental.
        $this->SetFont('dejavusans', '', 3.6);
        $this->SetXY(230, 4);

        $this->Cell(39, 2.6, 'Versión: 3', 0, 2, 'L');
        $this->Cell(39, 2.6, 'Fecha: 1 Octubre 2023', 0, 2, 'L');
        $this->Cell(39, 2.6, 'Código: GH-F-19', 0, 2, 'L');
        $this->Cell(39, 2.6, 'Tipo de Documento: Formato', 0, 2, 'L');

        $this->Cell(
            39,
            2.6,
            'Página ' . $this->getPageNumGroupAlias()
                . ' de ' . $this->getPageGroupAlias(),
            0,
            0,
            'L'
        );

        // Línea institucional inferior del encabezado.
        $this->SetLineWidth(0.15);
        $this->Line(
            $this->xTabla,
            20,
            $this->xTabla + array_sum($this->anchos),
            20
        );
    }

    /**
     * Obtiene la coordenada X inicial de una columna.
     */
    private function xColumna($indice)
    {
        $x = $this->xTabla;

        for ($i = 0; $i < $indice; $i++) {
            $x += $this->anchos[$i];
        }

        return $x;
    }

    /**
     * Calcula el ancho comprendido entre varias columnas.
     */
    private function anchoColumnas($desde, $hasta)
    {
        $ancho = 0;

        for ($i = $desde; $i <= $hasta; $i++) {
            $ancho += $this->anchos[$i];
        }

        return $ancho;
    }

    /**
     * Dibuja una celda estándar.
     */
    private function celda(
        $x,
        $y,
        $w,
        $h,
        $texto = '',
        $alineacion = 'C',
        $negrita = false,
        $tamano = 4,
        $borde = 1
    ) {
        $this->SetFont(
            'dejavusans',
            $negrita ? 'B' : '',
            $tamano
        );

        $this->SetXY($x, $y);

        $this->MultiCell(
            $w,
            $h,
            (string) $texto,
            $borde,
            $alineacion,
            false,
            0,
            $x,
            $y,
            true,
            0,
            false,
            true,
            $h,
            'M',
            true
        );
    }

    /**
     * Dibuja texto vertical para columnas angostas.
     */
    private function textoVertical(
        $x,
        $y,
        $w,
        $h,
        $texto,
        $tamano = 3.2
    ) {
        // La celda se dibuja primero.
        $this->Rect($x, $y, $w, $h);

        $cx = $x + ($w / 2);
        $cy = $y + ($h / 2);

        $this->StartTransform();

        // Rota el contenido alrededor del centro de la celda.
        $this->Rotate(90, $cx, $cy);

        $this->SetFont('dejavusans', 'B', $tamano);

        $this->SetXY(
            $cx - ($h / 2),
            $cy - ($w / 2)
        );

        $this->MultiCell(
            $h,
            $w,
            $texto,
            0,
            'C',
            false,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $w,
            'M',
            true
        );

        $this->StopTransform();
    }

    /**
     * Convierte una fecha del sistema al formato visual del reporte.
     */
    private function fechaVisual($fecha, $formato = 'd/m/Y')
    {
        if (empty($fecha)) {
            return '';
        }

        try {
            return (new DateTimeImmutable($fecha))->format($formato);
        } catch (Throwable $e) {
            return (string) $fecha;
        }
    }

    /**
     * Dibuja la información del empleado únicamente en la primera página.
     */
    public function dibujarDatosEmpleado(array $datos, $y = 21)
    {
        $edad = '';

        if (
            !empty($datos['fecha_nacimiento']) &&
            !empty($datos['hasta'])
        ) {
            try {
                $nacimiento = new DateTimeImmutable(
                    $datos['fecha_nacimiento']
                );

                $fechaPeriodo = new DateTimeImmutable(
                    $datos['hasta']
                );

                $edad = (string) $nacimiento
                    ->diff($fechaPeriodo)
                    ->y;
            } catch (Throwable $e) {
                $edad = '';
            }
        }

        $periodo = $this->fechaVisual(
            $datos['desde'] ?? ''
        ) . ' al ' . $this->fechaVisual(
            $datos['hasta'] ?? ''
        );

        $campos = [
            ['Nombre:', 6.8, $datos['empleado'] ?? '', 55.3],
            ['Cédula:', 8.7, $datos['documento'] ?? '', 29.9],
            ['Cargo:', 12.3, $datos['cargo'] ?? '', 46.7],
            ['Periodo del:', 10.0, $periodo, 43.2],
            ['Edad:', 11.4, $edad, 12.4],
            ['Sexo:', 8.2, $datos['sexo'] ?? '', 13.2]
        ];

        $x = $this->xTabla;
        $alto = 5.5;

        foreach ($campos as $campo) {
            // Etiqueta.
            $this->SetFont('dejavusans', 'B', 3.9);
            $this->SetXY($x, $y);

            $this->Cell(
                $campo[1],
                $alto,
                $campo[0],
                0,
                0,
                'L'
            );

            $x += $campo[1];

            // Valor.
            $this->celda(
                $x,
                $y,
                $campo[3],
                $alto,
                $campo[2],
                'L',
                false,
                4.1
            );

            $x += $campo[3];
        }
    }

    /**
     * Dibuja las tres secciones del encabezado de la tabla.
     */
    public function dibujarEncabezadoTabla($y)
    {
        $altoGrupo = 2.5;
        $altoCabecera = 13.5;
        $altoCodigo = 2.6;

        $this->SetLineWidth(0.15);

        // Leyenda superior del formato.
        $this->SetFont('dejavusans', 'BI', 3.4);
        $this->SetXY($this->xTabla, $y - 2.3);

        $this->Cell(
            array_sum($this->anchos),
            2,
            'Importante: El diligenciamiento debe ser en letra legible y sin tachones',
            0,
            0,
            'C'
        );

        // Primera fila: grupos.
        $this->celda(
            $this->xColumna(0),
            $y,
            $this->anchoColumnas(0, 4),
            $altoGrupo,
            ''
        );

        $this->celda(
            $this->xColumna(5),
            $y,
            $this->anchoColumnas(5, 10),
            $altoGrupo,
            'HORAS EXTRAS AUTORIZADAS',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(11),
            $y,
            $this->anchos[11],
            $altoGrupo,
            'FRENTE',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(12),
            $y,
            $this->anchos[12],
            $altoGrupo,
            'ACTIVIDAD',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(13),
            $y,
            $this->anchoColumnas(13, 15),
            $altoGrupo,
            'FIRMAS',
            'C',
            true,
            3.5
        );

        $yCab = $y + $altoGrupo;

        // Día de la semana.
        $this->textoVertical(
            $this->xColumna(0),
            $yCab,
            $this->anchos[0],
            $altoCabecera,
            "DIA DE LA SEMANA\nL,Ma,Mi,J,V,S,D",
            2.8
        );

        $cabeceras = [
            1 => 'FECHA DD/MM',
            2 => "HORA DE\nENTRADA",
            3 => "HORA DE\nSALIDA",
            4 => !empty($this->datosEncabezado['biotime']) ? "TIEMPO\nBRUTO" : "HORAS\nORDINARIAS"
        ];

        foreach ($cabeceras as $indice => $titulo) {
            $this->celda(
                $this->xColumna($indice),
                $yCab,
                $this->anchos[$indice],
                $altoCabecera,
                $titulo,
                'C',
                true,
                3.6
            );
        }

        // Código 101.
        $this->textoVertical(
            $this->xColumna(5),
            $yCab,
            $this->anchos[5],
            $altoCabecera,
            "Recargo Nocturno\n35% 9 PM A 6 AM\nTurno Ordinario",
            2.6
        );

        // Códigos 107 a 110.
        $extras = [
            6 => "Diurnas\n25%\nHASTA 9\nPM",
            7 => "Nocturnas\n75% 9 PM A\n6 AM",
            8 => "Diurnas\nFestivas\n2.00% > 8\nHoras Hasta\nlas 9 PM",
            9 => "Nocturnas\nFestivas\n2.50%\nFestivos\nDespués 9\nPM"
        ];

        foreach ($extras as $indice => $titulo) {
            $this->celda(
                $this->xColumna($indice),
                $yCab,
                $this->anchos[$indice],
                $altoCabecera,
                $titulo,
                'C',
                true,
                3.2
            );
        }

        // Código 115.
        $this->textoVertical(
            $this->xColumna(10),
            $yCab,
            $this->anchos[10],
            $altoCabecera,
            "Recargo Festivas\n1.75% Festivo <= 8 Horas",
            2.5
        );

        // Frente.
        $this->celda(
            $this->xColumna(11),
            $yCab,
            $this->anchos[11],
            $altoCabecera,
            "(INDICAR LA OBRA EN LA QUE ESTA ESE\nDIA)",
            'C',
            true,
            3.4
        );

        // Actividad.
        $this->celda(
            $this->xColumna(12),
            $yCab,
            $this->anchos[12],
            $altoCabecera,
            !empty($this->datosEncabezado['biotime']) ? 'OBSERVACIONES DE MARCACIONES BIOTIME' : 'RESUMA LA ACTIVIDAD EJECUTADA CUANDO HAY EXTRAS',
            'C',
            true,
            3.4
        );

        // Firmas.
        $this->celda(
            $this->xColumna(13),
            $yCab,
            $this->anchos[13],
            $altoCabecera,
            'EMPLEADO',
            'C',
            true,
            3.5
        );

        $this->celda(
            $this->xColumna(14),
            $yCab,
            $this->anchos[14],
            $altoCabecera,
            'Nombre Persona que Autoriza',
            'C',
            true,
            3.2
        );

        $this->celda(
            $this->xColumna(15),
            $yCab,
            $this->anchos[15],
            $altoCabecera,
            'VoBo Persona que Autoriza',
            'C',
            true,
            3.1
        );

        $yCodigos = $yCab + $altoCabecera;

        // Texto CÓDIGOS.
        $this->celda(
            $this->xColumna(0),
            $yCodigos,
            $this->anchoColumnas(0, 3),
            $altoCodigo,
            'CODIGOS',
            'C',
            true,
            3.4
        );

        $codigos = [
            4 => '100',
            5 => '101',
            6 => '107',
            7 => '108',
            8 => '109',
            9 => '110',
            10 => '115'
        ];

        foreach ($codigos as $indice => $codigo) {
            if (!empty($this->datosEncabezado['biotime'])) $codigo = '';
            $this->celda(
                $this->xColumna($indice),
                $yCodigos,
                $this->anchos[$indice],
                $altoCodigo,
                $codigo,
                'C',
                true,
                3.4
            );
        }

        // Zona derecha vacía de la fila de códigos.
        $this->celda(
            $this->xColumna(11),
            $yCodigos,
            $this->anchoColumnas(11, 15),
            $altoCodigo,
            ''
        );

        return $yCodigos + $altoCodigo;
    }

    /**
     * Dibuja una jornada o una fila vacía.
     */
    public function dibujarFilaJornada(
        $y,
        $jornada = null,
        $alto = 7.8
    ) {
        $valores = array_fill(0, 16, '');

        if (!empty($jornada)) {
            $inicio = new DateTimeImmutable(
                $jornada['jornada_inicio']
            );

            $fin = !empty($jornada['jornada_fin']) ? new DateTimeImmutable($jornada['jornada_fin']) : null;

            $borrador = !empty($this->datosEncabezado['borrador']);
            $totales = $borrador
                ? ['ORD' => (int) $jornada['jornada_minutos_ordinarios']]
                : je_totales_jornada($jornada);

            $dias = [
                1 => 'L',
                2 => 'Ma',
                3 => 'Mi',
                4 => 'J',
                5 => 'V',
                6 => 'S',
                7 => 'D'
            ];

            $valores[0] = $dias[(int) $inicio->format('N')];
            $valores[1] = $inicio->format('d/m');
            $valores[2] = $inicio->format('h:i a');
            $valores[3] = $fin ? $fin->format('h:i a') : '—';

            $valores[4] = isset($totales['ORD'])
                ? je_horas($totales['ORD'])
                : '';

            $valores[5] = isset($totales['RN'])
                ? je_horas($totales['RN'])
                : '';

            $valores[6] = isset($totales['HED'])
                ? je_horas($totales['HED'])
                : '';

            $valores[7] = isset($totales['HEN'])
                ? je_horas($totales['HEN'])
                : '';

            $valores[8] = isset($totales['HEDF'])
                ? je_horas($totales['HEDF'])
                : '';

            $valores[9] = isset($totales['HENF'])
                ? je_horas($totales['HENF'])
                : '';

            $valores[10] = isset($totales['RF'])
                ? je_horas($totales['RF'])
                : '';

            $valores[11] = $jornada['jornada_ubicacion'] ?? '';
            $valores[12] = $jornada['jornada_actividad'] ?? '';

            // Esta columna corresponde a la firma del empleado.
            $valores[13] = '';

            // Se conserva el responsable que actualmente trae el snapshot.
            $valores[14] = $jornada['aprobado_por'] ?? '';

            // El VoBo se deja disponible para la siguiente etapa.
            $valores[15] = '';
            if ($borrador) {
                // Solo se omiten conceptos pendientes de liquidación.
                for ($i = 5; $i <= 10; $i++) {
                    $valores[$i] = '';
                }
                $valores[14] = $this->datosEncabezado['jefe_nombre'] ?? '';
                $valores[15] = $this->datosEncabezado['jefe_nombre'] ?? '';
            }
            if (!empty($this->datosEncabezado['biotime'])) {
                $valores[4] = $jornada['tiempo_bruto_biotime'] ?? '—';
            }
        }

        foreach ($valores as $indice => $valor) {
            $alineacion = in_array($indice, [11, 12, 14, 15], true)
                ? 'L'
                : 'C';

            $tamano = in_array($indice, [11, 12, 14, 15], true)
                ? 3.7
                : 4.0;

            $this->celda(
                $this->xColumna($indice),
                $y,
                $this->anchos[$indice],
                $alto,
                $valor,
                $alineacion,
                false,
                $tamano
            );
        }

        return $y + $alto;
    }

    /**
     * Dibuja totales y zona de notas al final del reporte.
     */
    public function dibujarTotalesNotas(
        $y,
        array $totales
    ) {
        $alto = 19;

        // Celda TOTALES.
        $this->celda(
            $this->xColumna(0),
            $y,
            $this->anchoColumnas(0, 3),
            $alto,
            'TOTALES',
            'C',
            true,
            3.8
        );

        $codigos = [
            4 => 'ORD',
            5 => 'RN',
            6 => 'HED',
            7 => 'HEN',
            8 => 'HEDF',
            9 => 'HENF',
            10 => 'RF'
        ];

        foreach ($codigos as $indice => $codigo) {
            $valor = isset($totales[$codigo])
                ? je_horas($totales[$codigo])
                : '00:00';
            if (!empty($this->datosEncabezado['borrador']) && $indice > 4) {
                $valor = '';
            }
            if (!empty($this->datosEncabezado['biotime']) && $indice === 4) {
                $segundos = (int) ($totales['BRUTO_SEGUNDOS'] ?? 0);
                $valor = sprintf('%02d:%02d:%02d', floor($segundos / 3600), floor(($segundos % 3600) / 60), $segundos % 60);
            }

            $this->celda(
                $this->xColumna($indice),
                $y,
                $this->anchos[$indice],
                $alto,
                $valor,
                'C',
                true,
                4
            );
        }

        // Frente queda libre en la sección inferior.
        $this->celda(
            $this->xColumna(11),
            $y,
            $this->anchos[11],
            $alto,
            ''
        );

        // Bloque de notas.
        $xNotas = $this->xColumna(12);
        $anchoNotas = $this->anchoColumnas(12, 15);
        $altoNota = $alto / 3;

        $this->celda(
            $xNotas,
            $y,
            $anchoNotas,
            $altoNota,
            !empty($this->datosEncabezado['biotime']) ? 'NOTAS: Fuente BioTime. Tiempo bruto sin descuentos ni liquidación de horas extras.' : 'NOTAS:',
            'L',
            true,
            3.6
        );

        $this->celda(
            $xNotas,
            $y + $altoNota,
            $anchoNotas,
            $altoNota,
            ''
        );

        $this->celda(
            $xNotas,
            $y + ($altoNota * 2),
            $anchoNotas,
            $altoNota,
            ''
        );

        return $y + $alto;
    }

    /** Dibuja una única firma de cada persona al pie de la segunda hoja del borrador. */
    public function dibujarFirmasBorrador($y)
    {
        $ancho = array_sum($this->anchos) / 2;
        $bloques = [
            ['firma_empleado', 'TRABAJADOR', $this->datosEncabezado['empleado'] ?? ''],
            ['firma_jefe', 'JEFE INMEDIATO', $this->datosEncabezado['jefe_nombre'] ?? '']
        ];
        foreach ($bloques as $indice => $bloque) {
            $x = $this->xTabla + $indice * $ancho;
            $firma = $this->datosEncabezado[$bloque[0]] ?? '';
            $imagen = $this->imagenFirma($firma);
            if ($imagen !== null) {
                [$contenido, $dimensiones] = $imagen;
                $alto = min(11, 65 * $dimensiones[1] / $dimensiones[0]);
                $w = $alto * $dimensiones[0] / $dimensiones[1];
                $this->Image('@' . $contenido, $x + ($ancho - $w) / 2, $y + (11 - $alto) / 2,
                    $w, $alto, $dimensiones[2] === IMAGETYPE_JPEG ? 'JPEG' : 'PNG');
            } else {
                $pendiente = !empty($this->datosEncabezado['biotime']) ? 'Espacio para firma' : ($indice === 0 ? 'Pendiente de firma' : 'Firma no configurada');
                $this->celda($x, $y + 4, $ancho, 4, $pendiente, 'C', false, 5, 0);
            }
            $this->Line($x + 18, $y + 12, $x + $ancho - 18, $y + 12);
            $this->celda($x, $y + 12.5, $ancho, 3, $bloque[1], 'C', true, 6, 0);
            $this->celda($x, $y + 15.5, $ancho, 3, $bloque[2], 'C', false, 6, 0);
        }
        $total = count($this->datosEncabezado['jornadas'] ?? []);
        $firmadas = (int) ($this->datosEncabezado['jornadas_firmadas'] ?? 0);
        if ($firmadas > 0 && $firmadas < $total) {
            $this->celda($this->xTabla, $y + 19, $ancho * 2, 2.5,
                'Firma del trabajador: ' . $firmadas . ' de ' . $total . ' jornadas con confirmación vigente.', 'C', false, 4.5, 0);
        }
        return $y + 22;
    }

    /** Acepta imágenes almacenadas, nunca rutas ni URLs recibidas como firma. */
    private function imagenFirma($firma)
    {
        if (!is_string($firma) || $firma === '') {
            return null;
        }
        $base64 = preg_replace('#^data:image/(?:png|jpe?g);base64,#i', '', trim($firma));
        $contenido = base64_decode(str_replace(' ', '+', $base64), true);
        $dimensiones = $contenido === false ? false : @getimagesizefromstring($contenido);
        if (!$dimensiones || !in_array($dimensiones[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return null;
        }
        return [$contenido, $dimensiones];
    }

    /**
     * Pie institucional ubicado debajo de la cuadrícula.
     */
    public function dibujarLema($y)
    {
        $this->SetFont('dejavusans', 'BI', 3.5);
        $this->SetXY($this->xTabla, $y);

        $this->Cell(
            array_sum($this->anchos),
            2.5,
            'El espíritu de las grandes obras',
            0,
            0,
            'C'
        );
    }
}

function je_horas($minutos)
{
    $minutos = max(0, (int) $minutos);
    return str_pad((string) floor($minutos / 60), 2, '0', STR_PAD_LEFT)
        . ':'
        . str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT);
}

function je_totales_jornada($jornada)
{
    $totales = [];
    foreach ($jornada['segmentos'] ?? [] as $segmento) {
        $codigo = $segmento['codigo'];
        if ($codigo === 'NO_LIQ') {
            continue;
        }
        $totales[$codigo] = ($totales[$codigo] ?? 0)
            + (int) $segmento['minutos'];
    }
    return $totales;
}

function je_pdf_empleado(
    JornadaReportePDF $pdf,
    $snapshot,
    $lote
) {
    $pdf->configurarEmpleado($snapshot);
    $pdf->startPageGroup();

    $jornadas = array_values(
        $snapshot['jornadas'] ?? []
    );

    // La primera página del GH-F-19 dispone de 17 filas.
    $paginas = [
        array_slice($jornadas, 0, 17)
    ];

    // Las páginas siguientes tienen capacidad para 16 filas.
    $restantes = array_slice($jornadas, 17);

    while (!empty($restantes)) {
        $paginas[] = array_slice($restantes, 0, 16);
        $restantes = array_slice($restantes, 16);
    }

    // El formato institucional siempre posee como mínimo dos páginas.
    if (count($paginas) === 1) {
        $paginas[] = [];
    }

    foreach ($paginas as $numeroPagina => $jornadasPagina) {
        $pdf->AddPage('L', 'LETTER');

        $esPrimera = $numeroPagina === 0;
        $esUltima = $numeroPagina === count($paginas) - 1;

        if ($esPrimera) {
            // Datos del empleado solamente en la primera página.
            $pdf->dibujarDatosEmpleado(
                $snapshot,
                21
            );

            $y = $pdf->dibujarEncabezadoTabla(
                29
            );

            $capacidad = 17;
        } else {
            // Desde la página 2 la tabla inicia más arriba.
            $y = $pdf->dibujarEncabezadoTabla(
                23
            );

            $capacidad = 16;
        }

        // Mantiene siempre la cuadrícula completa.
        for ($i = 0; $i < $capacidad; $i++) {
            $jornada = $jornadasPagina[$i] ?? null;

            $y = $pdf->dibujarFilaJornada(
                $y,
                $jornada,
                7.8
            );
        }

        // Los totales solamente aparecen en la última página.
        if ($esUltima) {
            $y = $pdf->dibujarTotalesNotas(
                $y,
                $snapshot['totales'] ?? []
            );
        }

        if (!empty($snapshot['borrador']) && $numeroPagina === 1) {
            $y = $pdf->dibujarFirmasBorrador($pdf->getPageHeight() - 28.5);
        }

        $pdf->dibujarLema(
            $y + 0.5
        );
    }
}

function je_crear_pdf($snapshots, $lote)
{
    $pdf = new JornadaReportePDF(
        'L',
        'mm',
        'LETTER',
        true,
        'UTF-8',
        false
    );
    $pdf->SetCreator('EVDS - ASFALTART');
    $pdf->SetAuthor('ASFALTART S.A.S.');
    $pdf->SetTitle($lote['jlot_nombre']);
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(false);
    $pdf->SetHeaderMargin(0);

    // El diseño del GH-F-19 se controla mediante coordenadas absolutas.
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false, 0);

    // Padding reducido para conservar la proporción del formato original.
    $pdf->setCellPaddings(0.4, 0.3, 0.4, 0.3);
    foreach ($snapshots as $snapshot) {
        je_pdf_empleado($pdf, $snapshot, $lote);
    }
    return $pdf;
}
