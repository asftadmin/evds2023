<?php

ob_start();
ini_set('display_errors', '0');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Jornada.php';
require_once __DIR__ . '/../models/JornadaReporteContable.php';
require_once __DIR__ . '/../view/PDF/jornadas_formato.php';

// La construcción visual se comparte desde view/PDF/jornadas_formato.php.
function je_fallar($mensaje, $estado = 400)
{
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($estado);
    header('Content-Type: text/plain; charset=utf-8');
    echo $mensaje;
    exit;
}

function je_entero($nombre)
{
    $valor = filter_var(
        $_GET[$nombre] ?? null,
        FILTER_VALIDATE_INT
    );
    return $valor && $valor > 0 ? (int) $valor : null;
}

function je_html($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function je_xml($valor)
{
    return htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function je_nombre_archivo($texto)
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    $texto = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $texto);
    return trim($texto, '_') ?: 'reporte_jornadas';
}

function je_validar_acceso()
{
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $rol_id = (int) ($_SESSION['user_rol'] ?? 0);
    if ($user_id <= 0 || $rol_id <= 0) {
        je_fallar('Debe iniciar sesión.', 401);
    }
    $jornada = new Jornada();
    $empleado = $jornada->obtener_empleado_por_usuario($user_id);
    if (
        !$empleado ||
        (int) $empleado['esta_empl'] !== 1 ||
        $empleado['rol_nomb'] !== 'Contabilidad' ||
        !$jornada->tiene_permiso_menu(
            $rol_id,
            'reporte_contable',
            false
        )
    ) {
        je_fallar('No tiene permiso para exportar este reporte.', 403);
    }
}

function je_pdf($snapshots, $lote, $nombre)
{
    $pdf = je_crear_pdf($snapshots, $lote);
    if (ob_get_length()) ob_clean();
    $pdf->Output($nombre . '.pdf', 'I');
    exit;
}
function je_celda_xml($valor, $estilo = '')
{
    $atributo = $estilo !== '' ? ' ss:StyleID="' . $estilo . '"' : '';
    return '<Cell' . $atributo . '><Data ss:Type="String">'
        . je_xml($valor) . '</Data></Cell>';
}

/** Identifica cada hoja anulada sin modificar las filas del snapshot original. */
function je_hoja_xml($nombre, $encabezados, $filas, $anulado = false)
{
    $xml = '<Worksheet ss:Name="' . je_xml($nombre) . '"><Table>';
    if ($anulado) {
        $xml .= '<Row><Cell ss:StyleID="Anulado" ss:MergeAcross="' . (count($encabezados) - 1)
            . '"><Data ss:Type="String">ANULADO - SOLO CONSULTA HISTÓRICA</Data></Cell></Row>';
    }
    $xml .= '<Row>';
    foreach ($encabezados as $encabezado) {
        $xml .= je_celda_xml($encabezado, 'Header');
    }
    $xml .= '</Row>';
    foreach ($filas as $fila) {
        $xml .= '<Row>';
        foreach ($fila as $valor) {
            $xml .= je_celda_xml($valor);
        }
        $xml .= '</Row>';
    }
    $filaEncabezado = $anulado ? 2 : 1;
    return $xml . '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">'
        . '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>' . $filaEncabezado . '</SplitHorizontal>'
        . '<TopRowBottomPane>' . $filaEncabezado . '</TopRowBottomPane><AutoFilter x:Range="R' . $filaEncabezado . 'C1:R' . $filaEncabezado . 'C'
        . count($encabezados) . '" xmlns="urn:schemas-microsoft-com:office:excel"/>'
        . '</WorksheetOptions></Worksheet>';
}

/** Exporta el histórico e identifica los anulados en todas las hojas y el nombre del archivo. */
function je_excel($snapshots, $lote)
{
    $anulado = ($lote['jlot_estado'] ?? '') === 'ANULADO';
    $control = [];
    $detalle = [];
    $totales = [];
    $novedades = [];
    foreach ($snapshots as $snapshot) {
        $total = array_sum(array_map('intval', $snapshot['totales']));
        $control[] = [
            $lote['jlot_nombre'],
            'v' . (int) ($lote['jlot_version'] ?? 1),
            $lote['jlot_tipo'] ?? 'NORMAL',
            $lote['jlot_lote_origen_id'] ?? '',
            $lote['jlot_estado'],
            $lote['jlot_fecha_corte'],
            $snapshot['empleado'],
            $snapshot['documento'],
            $snapshot['cargo'],
            $snapshot['desde'],
            $snapshot['hasta'],
            $snapshot['estado'],
            count($snapshot['jornadas']),
            je_horas($total)
        ];
        foreach ($snapshot['jornadas'] as $jornada) {
            foreach ($jornada['segmentos'] as $segmento) {
                if ($segmento['codigo'] === 'NO_LIQ') {
                    continue;
                }
                $detalle[] = [
                    $snapshot['empleado'],
                    $snapshot['documento'],
                    $jornada['jornada_id'],
                    $jornada['jornada_inicio'],
                    $jornada['jornada_fin'],
                    $segmento['codigo'],
                    $segmento['concepto'],
                    $segmento['codigo_contable'],
                    $segmento['inicio'],
                    $segmento['fin'],
                    je_horas($segmento['minutos']),
                    $jornada['aprobado_por'] ?: 'Automática'
                ];
            }
            $novedades[] = [
                $snapshot['empleado'],
                $snapshot['documento'],
                $jornada['jornada_inicio'],
                $jornada['jornada_fin'],
                $jornada['jornada_ubicacion'],
                $jornada['jornada_actividad'],
                $jornada['jornada_observaciones'],
                $jornada['jornada_origen']
            ];
        }
        foreach ($snapshot['totales'] as $codigo => $minutos) {
            $totales[] = [
                $snapshot['empleado'],
                $snapshot['documento'],
                $snapshot['desde'],
                $snapshot['hasta'],
                $codigo,
                je_horas($minutos)
            ];
        }
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
        . '
<?mso-application progid="Excel.Sheet"?>'
        . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
        . ' xmlns:o="urn:schemas-microsoft-com:office:office" '
        . ' xmlns:x="urn:schemas-microsoft-com:office:excel" '
        . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
        . '<Styles>
        <Style ss:ID="Default">
        <Alignment ss:Vertical="Center"/>'
        . '
        </Style>
        <Style ss:ID="Header">
        <Font ss:Bold="1" ss:Color="#FFFFFF"/>'
        . '<Interior ss:Color="#087F8C" ss:Pattern="Solid"/>
        </Style>
        <Style ss:ID="Anulado">
        <Font ss:Bold="1" ss:Color="#B40000"/>
        <Interior ss:Color="#FCE4E4" ss:Pattern="Solid"/>
        </Style>
    </Styles>';
    $xml .= je_hoja_xml(
        'Control',
        ['Lote', 'Versión lote', 'Tipo', 'Lote reemplazado', 'Estado lote',
            'Corte', 'Empleado', 'Documento', 'Cargo', 'Desde', 'Hasta',
            'Estado empleado', 'Jornadas', 'Horas reportables'],
        $control,
        $anulado
    );
    $xml .= je_hoja_xml(
        'Detalle',
        ['Empleado', 'Documento', 'Jornada', 'Entrada', 'Salida', 'Concepto',
            'Nombre concepto', 'Código contable', 'Inicio segmento',
            'Fin segmento', 'Horas', 'Aprobó'],
        $detalle,
        $anulado
    );
    $xml .= je_hoja_xml(
        'Totales',
        ['Empleado', 'Documento', 'Desde', 'Hasta', 'Concepto', 'Total horas'],
        $totales,
        $anulado
    );
    $xml .= je_hoja_xml(
        'Novedades',
        ['Empleado', 'Documento', 'Entrada', 'Salida', 'Lugar / frente',
            'Actividad', 'Observaciones', 'Origen'],
        $novedades,
        $anulado
    );
    $xml .= '</Workbook>';

    if (ob_get_length()) {
        ob_clean();
    }
    $archivo = ($anulado ? 'ANULADO_' : '') . je_nombre_archivo($lote['jlot_nombre']) . '_consolidado.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Content-Length: ' . strlen($xml));
    echo $xml;
    exit;
}

try {
    if (in_array($_GET['tipo'] ?? '', ['pdf_borrador_equipo', 'pdf_borrador_equipo_lote'], true)) {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $rolId = (int) ($_SESSION['user_rol'] ?? 0);
        if ($userId <= 0 || $rolId <= 0) {
            je_fallar('Debe iniciar sesión.', 401);
        }
        $jornada = new Jornada();
        $jefe = $jornada->obtener_empleado_por_usuario($userId);
        if (!$jefe || (int) $jefe['esta_empl'] !== 1
            || !$jornada->es_jefe_activo((int) $jefe['id_empl'])
            || !$jornada->tiene_permiso_menu($rolId, 'equipo', true)) {
            je_fallar('No tiene permiso para exportar jornadas del equipo.', 403);
        }
        $esLote = ($_GET['tipo'] ?? '') === 'pdf_borrador_equipo_lote';
        $empleadoId = $esLote ? null : je_entero('empleado_id');
        if (!$esLote && !$empleadoId) {
            throw new InvalidArgumentException('Seleccione un empleado válido.');
        }
        $fechas = [];
        foreach (['fecha_desde', 'fecha_hasta'] as $campo) {
            $valor = $_GET[$campo] ?? '';
            $fecha = is_string($valor)
                ? DateTimeImmutable::createFromFormat('!Y-m-d', $valor) : false;
            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                throw new InvalidArgumentException('El periodo no es válido.');
            }
            $fechas[$campo] = $valor;
        }
        if ($fechas['fecha_desde'] > $fechas['fecha_hasta']) {
            throw new InvalidArgumentException('La fecha inicial no puede superar la fecha final.');
        }
        if ($esLote) {
            $todos = ($_GET['todos'] ?? '') === '1';
            $ids = $todos ? [] : json_decode(is_string($_GET['empleados_ids'] ?? null) ? $_GET['empleados_ids'] : '', true);
            if (!is_array($ids)) {
                throw new InvalidArgumentException('Seleccione empleados válidos para el PDF lote.');
            }
            $snapshots = $jornada->obtener_borradores_equipo_lote(
                (int) $jefe['id_empl'], $ids, $fechas['fecha_desde'], $fechas['fecha_hasta'], $todos
            );
            je_pdf($snapshots, ['jlot_nombre' => 'Borrador de jornadas del equipo'],
                je_nombre_archivo('GH-F-19_BORRADOR_LOTE_' . $fechas['fecha_desde'] . '_' . $fechas['fecha_hasta']));
        }
        $snapshot = $jornada->obtener_borrador_equipo(
            (int) $jefe['id_empl'], $empleadoId,
            $fechas['fecha_desde'], $fechas['fecha_hasta']
        );
        je_pdf([$snapshot], ['jlot_nombre' => 'Borrador de jornadas'],
            je_nombre_archivo('GH-F-19_BORRADOR_' . $snapshot['documento'] . '_' . $snapshot['hasta']));
    }
    je_validar_acceso();
    $tipo = trim((string) ($_GET['tipo'] ?? ''));
    $modelo = new JornadaReporteContable();
    if ($tipo === 'pdf_empleado') {
        $fila_id = je_entero('fila_id');
        if (!$fila_id) {
            throw new InvalidArgumentException('El empleado del lote no es válido.');
        }
        $datos = $modelo->obtener_snapshot_empleado($fila_id);
        je_pdf(
            [$datos['snapshot']],
            $datos['lote'],
            je_nombre_archivo(
                ($datos['lote']['jlot_estado'] === 'ANULADO' ? 'ANULADO_' : '')
                . 'GH-F-19_' . $datos['snapshot']['documento']
                . '_' . $datos['snapshot']['hasta']
            )
        );
    }
    $lote_id = je_entero('lote_id');
    if (!$lote_id) {
        throw new InvalidArgumentException('El lote no es válido.');
    }
    $datos = $modelo->obtener_snapshots_lote($lote_id);
    if ($tipo === 'pdf_lote') {
        je_pdf(
            $datos['snapshots'],
            $datos['lote'],
            je_nombre_archivo(($datos['lote']['jlot_estado'] === 'ANULADO' ? 'ANULADO_' : '')
                . 'GH-F-19_' . $datos['lote']['jlot_nombre'])
        );
    }
    if ($tipo === 'excel') {
        je_excel($datos['snapshots'], $datos['lote']);
    }
    throw new InvalidArgumentException('El tipo de exportación no es válido.');
} catch (InvalidArgumentException $e) {
    je_fallar($e->getMessage(), 422);
} catch (RuntimeException $e) {
    je_fallar($e->getMessage(), 409);
} catch (Throwable $e) {
    error_log('Exportación jornadas: ' . $e->getMessage());
    je_fallar('No fue posible generar el archivo solicitado.', 500);
}

?>
