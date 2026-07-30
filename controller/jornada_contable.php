<?php

ob_start();
ini_set('display_errors', '0');

require_once "../config/conexion.php";
require_once "../models/Jornada.php";
require_once "../models/JornadaContable.php";
require_once "../models/JornadaReporteContable.php";

$jornada = new Jornada();
$contable = new JornadaContable();
$reporte = new JornadaReporteContable();

function jc_responder($payload, $status = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function jc_entrada($nombre, $predeterminado = '') {
    $valor = $_POST[$nombre] ?? $_GET[$nombre] ?? $predeterminado;
    return is_string($valor) ? trim($valor) : $predeterminado;
}

function jc_fecha_valida($fecha) {
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $objeto && $objeto->format('Y-m-d') === $fecha;
}

function jc_minutos_horas($minutos) {
    $minutos = max(0, (int)$minutos);
    return str_pad((string)floor($minutos / 60), 2, '0', STR_PAD_LEFT)
        . ':'
        . str_pad((string)($minutos % 60), 2, '0', STR_PAD_LEFT);
}

function jc_validar_csrf() {
    $recibido = jc_entrada('csrf_token');
    $esperado = $_SESSION['csrf_jornadas'] ?? '';
    if (
        $recibido === ''
        || $esperado === ''
        || !hash_equals($esperado, $recibido)
    ) {
        jc_responder([
            'success' => false,
            'message' => 'La sesión del formulario venció. Recargue la página.'
        ], 419);
    }
}

/**
 * Resuelve el usuario contable y valida el permiso del menú solicitado.
 */
function jc_contexto(Jornada $modelo, $menu_ident) {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $rol_id = (int)($_SESSION['user_rol'] ?? 0);

    if ($user_id <= 0 || $rol_id <= 0) {
        jc_responder([
            'success' => false,
            'message' => 'Debe iniciar sesión.'
        ], 401);
    }

    $empleado = $modelo->obtener_empleado_por_usuario($user_id);
    if (!$empleado || (int)$empleado['esta_empl'] !== 1) {
        jc_responder([
            'success' => false,
            'message' => 'El usuario no tiene un empleado activo asociado.'
        ], 403);
    }

    if ($empleado['rol_nomb'] !== 'Contabilidad') {
        jc_responder([
            'success' => false,
            'message' => 'Esta función es exclusiva de Contabilidad.'
        ], 403);
    }

    if (!$modelo->tiene_permiso_menu($rol_id, $menu_ident, false)) {
        jc_responder([
            'success' => false,
            'message' => 'No tiene permiso para acceder a esta función.'
        ], 403);
    }

    return [
        'user_id' => $user_id,
        'empleado' => $empleado
    ];
}

/**
 * Valida y normaliza los filtros compartidos por las vistas contables.
 */
function jc_filtros() {
    $fecha_desde = jc_entrada('fecha_desde');
    $fecha_hasta = jc_entrada('fecha_hasta');
    $empleado_texto = jc_entrada('empleado_id');

    if (!jc_fecha_valida($fecha_desde) || !jc_fecha_valida($fecha_hasta)) {
        throw new InvalidArgumentException('El periodo no es válido.');
    }
    if ($fecha_desde > $fecha_hasta) {
        throw new InvalidArgumentException(
            'La fecha inicial no puede superar la fecha final.'
        );
    }

    $empleado_id = null;
    if ($empleado_texto !== '') {
        $empleado_id = filter_var($empleado_texto, FILTER_VALIDATE_INT);
        if (!$empleado_id || $empleado_id <= 0) {
            throw new InvalidArgumentException('El empleado no es válido.');
        }
    }

    return [$fecha_desde, $fecha_hasta, $empleado_id];
}

try {
    $op = jc_entrada('op');
    $menus = [
        'contextoLiquidacion' => 'liquidacion',
        'listarEmpleados' => 'liquidacion',
        'listarLiquidacion' => 'liquidacion',
        'listarSegmentos' => 'liquidacion',
        'clasificarJornada' => 'liquidacion',
        'obtenerParametrizacion' => 'liquidacion',
        'contextoInconsistencias' => 'inconsistencias',
        'listarInconsistencias' => 'inconsistencias',
        'contextoReporte' => 'reporte_contable',
        'reporteContable' => 'reporte_contable',
        'listarLotesReporte' => 'reporte_contable',
        'crearLoteReporte' => 'reporte_contable',
        'crearCorreccionLote' => 'reporte_contable',
        'listarEmpleadosLote' => 'reporte_contable',
        'ajustarPeriodoLote' => 'reporte_contable',
        'refrescarLoteReporte' => 'reporte_contable',
        'cerrarLoteReporte' => 'reporte_contable',
        'contextoConfiguracion' => 'configuracion',
        'listarFestivos' => 'configuracion',
        'guardarFestivo' => 'configuracion',
        'cambiarEstadoFestivo' => 'configuracion'
    ];

    if (!isset($menus[$op])) {
        jc_responder([
            'success' => false,
            'message' => 'Operación no soportada.'
        ], 404);
    }

    $contexto = jc_contexto($jornada, $menus[$op]);

    switch ($op) {
        case 'contextoLiquidacion':
        case 'contextoInconsistencias':
        case 'contextoReporte':
        case 'contextoConfiguracion':
            jc_responder([
                'success' => true,
                'data' => [
                    'empleado' => $contexto['empleado']['nomb_empl'],
                    'documento' => $contexto['empleado']['cedu_empl'],
                    'rol' => $contexto['empleado']['rol_nomb']
                ]
            ]);
            break;

        case 'listarFestivos':
            $anio = filter_var(jc_entrada('anio'), FILTER_VALIDATE_INT);
            if (!$anio || $anio < 2000 || $anio > 2100) {
                throw new InvalidArgumentException('El año no es válido.');
            }
            jc_responder([
                'success' => true,
                'data' => $contable->listar_festivos($anio)
            ]);
            break;

        case 'guardarFestivo':
            jc_validar_csrf();
            $fecha = jc_entrada('fecha');
            $descripcion = jc_entrada('descripcion');
            if (!jc_fecha_valida($fecha)) {
                throw new InvalidArgumentException('La fecha no es válida.');
            }
            if (mb_strlen($descripcion) > 160) {
                throw new InvalidArgumentException(
                    'La descripción admite máximo 160 caracteres.'
                );
            }
            $resultado = $contable->guardar_festivo(
                $fecha,
                $descripcion,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'data' => $resultado,
                'message' => 'La fecha especial fue guardada correctamente.'
            ]);
            break;

        case 'cambiarEstadoFestivo':
            jc_validar_csrf();
            $festivo_id = filter_var(
                jc_entrada('festivo_id'),
                FILTER_VALIDATE_INT
            );
            $estado_texto = jc_entrada('estado');
            if (!$festivo_id || $festivo_id <= 0) {
                throw new InvalidArgumentException(
                    'La fecha especial no es válida.'
                );
            }
            if (!in_array($estado_texto, ['0', '1'], true)) {
                throw new InvalidArgumentException('El estado no es válido.');
            }
            $resultado = $contable->cambiar_estado_festivo(
                $festivo_id,
                (int)$estado_texto,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'data' => $resultado,
                'message' => (int)$estado_texto === 1
                    ? 'La fecha especial fue activada.'
                    : 'La fecha especial fue inactivada.'
            ]);
            break;

        case 'listarEmpleados':
            [$desde, $hasta] = jc_filtros();
            jc_responder([
                'success' => true,
                'data' => $contable->listar_empleados_periodo($desde, $hasta)
            ]);
            break;

        case 'listarLiquidacion':
            [$desde, $hasta, $empleado_id] = jc_filtros();
            $filas = $contable->listar_liquidacion(
                $desde,
                $hasta,
                $empleado_id
            );
            $data = [];
            foreach ($filas as $fila) {
                $inicio = new DateTimeImmutable($fila['jornada_inicio']);
                $fin = new DateTimeImmutable($fila['jornada_fin']);
                $resumen_conceptos = json_decode(
                    $fila['resumen_conceptos'] ?? '[]',
                    true
                );
                if (!is_array($resumen_conceptos)) {
                    $resumen_conceptos = [];
                }
                foreach ($resumen_conceptos as &$concepto_resumen) {
                    $minutos = (int)($concepto_resumen['minutos'] ?? 0);
                    $concepto_resumen['horas'] = $minutos % 60 === 0
                        ? (string)($minutos / 60)
                        : floor($minutos / 60)
                            . ':'
                            . str_pad(
                                (string)($minutos % 60),
                                2,
                                '0',
                                STR_PAD_LEFT
                            );
                    unset($concepto_resumen['minutos']);
                }
                unset($concepto_resumen);
                $data[] = [
                    'jornada_id' => (int)$fila['jornada_id'],
                    'empleado' => $fila['empleado'],
                    'documento' => $fila['documento'],
                    'fecha' => $inicio->format('Y-m-d'),
                    'entrada' => $inicio->format('H:i'),
                    'fecha_salida' => $fin->format('Y-m-d'),
                    'salida' => $fin->format('H:i'),
                    'horas_ordinarias' => jc_minutos_horas(
                        $fila['jornada_minutos_ordinarios']
                    ),
                    'ubicacion' => $fila['jornada_ubicacion'],
                    'actividad' => $fila['jornada_actividad'],
                    'origen' => $fila['jornada_origen'],
                    'segmentos' => (int)$fila['cantidad_segmentos'],
                    'horas_clasificadas' => jc_minutos_horas(
                        $fila['minutos_liquidables']
                    ),
                    'resumen_conceptos' => $resumen_conceptos,
                    'clasificacion_completa' => (
                        (int)$fila['cantidad_segmentos'] > 0
                        && (int)$fila['minutos_clasificados']
                            === (int)$fila['minutos_intervalo']
                    )
                ];
            }
            jc_responder(['success' => true, 'data' => $data]);
            break;

        case 'clasificarJornada':
            jc_validar_csrf();
            $jornada_id = filter_var(
                jc_entrada('jornada_id'),
                FILTER_VALIDATE_INT
            );
            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }

            $resultado = $contable->clasificar_jornada(
                $jornada_id,
                $contexto['user_id']
            );
            if (!empty($resultado['inconsistente'])) {
                jc_responder([
                    'success' => false,
                    'inconsistente' => true,
                    'message' => $resultado['message']
                ], 409);
            }
            jc_responder([
                'success' => true,
                'data' => $resultado,
                'message' => 'La jornada fue clasificada correctamente.'
            ]);
            break;

        case 'listarSegmentos':
            $jornada_id = filter_var(
                jc_entrada('jornada_id'),
                FILTER_VALIDATE_INT
            );
            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }

            $filas = $contable->listar_segmentos_jornada($jornada_id);
            $data = [];
            foreach ($filas as $fila) {
                $inicio = new DateTimeImmutable($fila['jcla_inicio']);
                $fin = new DateTimeImmutable($fila['jcla_fin']);
                $data[] = [
                    'segmento_id' => (int)$fila['jcla_id'],
                    'inicio' => $inicio->format('Y-m-d H:i'),
                    'fin' => $fin->format('Y-m-d H:i'),
                    'concepto_codigo' => $fila['jcon_codigo'],
                    'concepto' => $fila['jcon_nombre'],
                    'codigo_contable' => $fila['jcon_codigo_contable'],
                    'horas' => jc_minutos_horas($fila['jcla_minutos'])
                ];
            }
            jc_responder(['success' => true, 'data' => $data]);
            break;

        case 'obtenerParametrizacion':
            jc_responder([
                'success' => true,
                'data' => $contable->obtener_parametrizacion()
            ]);
            break;

        case 'listarInconsistencias':
            [$desde, $hasta, $empleado_id] = jc_filtros();
            $filas = $contable->listar_inconsistencias(
                $desde,
                $hasta,
                $empleado_id
            );
            $data = [];
            foreach ($filas as $fila) {
                $inicio = new DateTimeImmutable($fila['jornada_inicio']);
                $fin = new DateTimeImmutable($fila['jornada_fin']);
                $data[] = [
                    'jornada_id' => (int)$fila['jornada_id'],
                    'empleado' => $fila['empleado'],
                    'documento' => $fila['documento'],
                    'fecha' => $inicio->format('Y-m-d'),
                    'entrada' => $inicio->format('H:i'),
                    'salida' => $fin->format('Y-m-d H:i'),
                    'horas' => jc_minutos_horas(
                        $fila['jornada_minutos_ordinarios']
                    ),
                    'ubicacion' => $fila['jornada_ubicacion'],
                    'actividad' => $fila['jornada_actividad'],
                    'detalle' => $fila['jornada_inconsistencia_detalle'],
                    'estado' => $fila['estado_nombre']
                ];
            }
            jc_responder(['success' => true, 'data' => $data]);
            break;

        case 'reporteContable':
            [$desde, $hasta, $empleado_id] = jc_filtros();
            $filas = $contable->reporte_contable(
                $desde,
                $hasta,
                $empleado_id
            );
            $data = [];
            foreach ($filas as $fila) {
                $data[] = [
                    'empleado' => $fila['empleado'],
                    'documento' => $fila['documento'],
                    'concepto_codigo' => $fila['concepto_codigo'],
                    'concepto' => $fila['concepto'],
                    'codigo_contable' => $fila['codigo_contable'],
                    'horas' => jc_minutos_horas($fila['minutos'])
                ];
            }
            jc_responder(['success' => true, 'data' => $data]);
            break;

        case 'listarLotesReporte':
            jc_responder([
                'success' => true,
                'data' => $reporte->listar_lotes()
            ]);
            break;

        case 'crearLoteReporte':
            jc_validar_csrf();
            $nombre = jc_entrada('nombre');
            $fecha_corte = jc_entrada('fecha_corte');
            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
                throw new InvalidArgumentException(
                    'El nombre del lote debe tener entre 3 y 120 caracteres.'
                );
            }
            if (!jc_fecha_valida($fecha_corte)) {
                throw new InvalidArgumentException(
                    'La fecha de corte no es válida.'
                );
            }
            $lote_id = $reporte->crear_lote(
                $nombre,
                $fecha_corte,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'data' => ['lote_id' => $lote_id],
                'message' => 'El lote fue preparado con los periodos sugeridos.'
            ]);
            break;

        case 'crearCorreccionLote':
            jc_validar_csrf();
            $lote_origen_id = filter_var(
                jc_entrada('lote_id'),
                FILTER_VALIDATE_INT
            );
            if (!$lote_origen_id || $lote_origen_id <= 0) {
                throw new InvalidArgumentException(
                    'El lote que desea corregir no es válido.'
                );
            }
            $nuevo_id = $reporte->crear_correccion(
                $lote_origen_id,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'data' => ['lote_id' => $nuevo_id],
                'message' => 'Se creó una nueva versión en borrador.'
            ]);
            break;

        case 'listarEmpleadosLote':
            $lote_id = filter_var(
                jc_entrada('lote_id'),
                FILTER_VALIDATE_INT
            );
            if (!$lote_id || $lote_id <= 0) {
                throw new InvalidArgumentException('El lote no es válido.');
            }
            $lote = $reporte->obtener_lote($lote_id);
            if (!$lote) {
                throw new RuntimeException('No se encontró el lote.');
            }
            $filas = $reporte->listar_empleados_lote($lote_id);
            foreach ($filas as &$fila) {
                $fila['jle_id'] = (int)$fila['jle_id'];
                $fila['empleado_id'] = (int)$fila['empleado_id'];
                $fila['jle_cantidad_jornadas'] =
                    (int)$fila['jle_cantidad_jornadas'];
                $fila['jle_cantidad_pendientes'] =
                    (int)$fila['jle_cantidad_pendientes'];
                $fila['horas_reportables'] = jc_minutos_horas(
                    $fila['jle_minutos_reportables']
                );
                unset($fila['jle_snapshot']);
            }
            unset($fila);
            jc_responder([
                'success' => true,
                'data' => [
                    'lote' => $lote,
                    'empleados' => $filas
                ]
            ]);
            break;

        case 'ajustarPeriodoLote':
            jc_validar_csrf();
            $fila_id = filter_var(
                jc_entrada('fila_id'),
                FILTER_VALIDATE_INT
            );
            $desde = jc_entrada('desde');
            $hasta = jc_entrada('hasta');
            $motivo = jc_entrada('motivo');
            if (!$fila_id || $fila_id <= 0) {
                throw new InvalidArgumentException(
                    'El periodo del empleado no es válido.'
                );
            }
            if (!jc_fecha_valida($desde) || !jc_fecha_valida($hasta)) {
                throw new InvalidArgumentException(
                    'Las fechas del periodo no son válidas.'
                );
            }
            if ($desde > $hasta) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la final.'
                );
            }
            if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500) {
                throw new InvalidArgumentException(
                    'Indique el motivo del ajuste (5 a 500 caracteres).'
                );
            }
            $reporte->actualizar_periodo(
                $fila_id,
                $desde,
                $hasta,
                $motivo,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'message' => 'El periodo fue actualizado y validado.'
            ]);
            break;

        case 'refrescarLoteReporte':
            jc_validar_csrf();
            $lote_id = filter_var(
                jc_entrada('lote_id'),
                FILTER_VALIDATE_INT
            );
            if (!$lote_id || $lote_id <= 0) {
                throw new InvalidArgumentException('El lote no es válido.');
            }
            $reporte->refrescar_lote(
                $lote_id,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'message' => 'El lote fue validado nuevamente.'
            ]);
            break;

        case 'cerrarLoteReporte':
            jc_validar_csrf();
            $lote_id = filter_var(
                jc_entrada('lote_id'),
                FILTER_VALIDATE_INT
            );
            if (!$lote_id || $lote_id <= 0) {
                throw new InvalidArgumentException('El lote no es válido.');
            }
            $reporte->cerrar_lote(
                $lote_id,
                $contexto['user_id']
            );
            jc_responder([
                'success' => true,
                'message' => 'El lote fue cerrado y quedó listo para exportar.'
            ]);
            break;
    }
} catch (InvalidArgumentException $e) {
    jc_responder([
        'success' => false,
        'message' => $e->getMessage()
    ], 422);
} catch (RuntimeException $e) {
    jc_responder([
        'success' => false,
        'message' => $e->getMessage()
    ], 409);
} catch (Throwable $e) {
    error_log('Jornada contable: ' . $e->getMessage());
    jc_responder([
        'success' => false,
        'message' => 'No fue posible procesar la solicitud contable.'
    ], 500);
}

?>
