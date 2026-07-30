<?php

ob_start();
ini_set('display_errors', '0');

require_once("../config/conexion.php");
require_once("../models/Jornada.php");

$jornada = new Jornada();

/**
 * Finaliza la petición con una única respuesta JSON limpia.
 */
function jornada_responder($payload, $status_code = 200) {
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Lee un valor de entrada y lo normaliza como texto.
 */
function jornada_entrada($nombre, $predeterminado = '') {
    $valor = $_POST[$nombre] ?? $_GET[$nombre] ?? $predeterminado;
    return is_string($valor) ? trim($valor) : $predeterminado;
}

/**
 * Valida el token usado por las operaciones que modifican información.
 */
function jornada_validar_csrf() {
    $recibido = jornada_entrada('csrf_token');
    $esperado = $_SESSION['csrf_jornadas'] ?? '';

    if (
        $recibido === ''
        || $esperado === ''
        || !hash_equals($esperado, $recibido)
    ) {
        jornada_responder([
            'success' => false,
            'message' => 'La sesión del formulario venció. Recargue la página.'
        ], 419);
    }
}

/**
 * Convierte minutos al formato operativo HH:MM.
 */
function jornada_minutos_a_horas($minutos) {
    $minutos = max(0, (int)$minutos);
    return str_pad((string)floor($minutos / 60), 2, '0', STR_PAD_LEFT)
        . ':'
        . str_pad((string)($minutos % 60), 2, '0', STR_PAD_LEFT);
}

/**
 * Valida una fecha estricta en formato ISO.
 */
function jornada_fecha_valida($fecha) {
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $objeto && $objeto->format('Y-m-d') === $fecha;
}

/**
 * Valida una hora estricta en formato de 24 horas.
 */
function jornada_hora_valida($hora) {
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) === 1;
}

/**
 * Resuelve sesión, empleado y permiso al menú Mis Jornadas.
 */
function jornada_contexto_autorizado(
    Jornada $modelo,
    $menu_ident = 'mis_jornadas',
    $requiere_jefe = false
) {
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    $rol_id = (int)($_SESSION['user_rol'] ?? 0);

    if ($user_id <= 0 || $rol_id <= 0) {
        jornada_responder([
            'success' => false,
            'message' => 'Debe iniciar sesión.'
        ], 401);
    }

    $empleado = $modelo->obtener_empleado_por_usuario($user_id);
    if (!$empleado || (int)$empleado['esta_empl'] !== 1) {
        jornada_responder([
            'success' => false,
            'message' => 'El usuario no tiene un empleado activo asociado.'
        ], 403);
    }

    $es_jefe = $modelo->es_jefe_activo((int)$empleado['id_empl']);
    $_SESSION['es_jefe'] = $es_jefe ? 1 : 0;

    if ($requiere_jefe && !$es_jefe) {
        jornada_responder([
            'success' => false,
            'message' => 'El usuario no tiene empleados relacionados como jefe.'
        ], 403);
    }

    if (!$modelo->tiene_permiso_menu($rol_id, $menu_ident, $es_jefe)) {
        jornada_responder([
            'success' => false,
            'message' => 'No tiene permiso para acceder a esta función de jornadas.'
        ], 403);
    }

    return [
        'user_id' => $user_id,
        'rol_id' => $rol_id,
        'es_jefe' => $es_jefe,
        'empleado' => $empleado
    ];
}

/**
 * Calcula la duración neta. El almuerzo se descuenta una sola vez cuando la
 * fecha inicial es de lunes a viernes.
 */
function jornada_calcular_intervalo(
    Jornada $modelo,
    $fecha,
    $hora_entrada,
    $hora_salida,
    $cruza_medianoche
) {
    if (!jornada_fecha_valida($fecha)) {
        throw new InvalidArgumentException('La fecha no es válida.');
    }

    if (
        !jornada_hora_valida($hora_entrada)
        || !jornada_hora_valida($hora_salida)
    ) {
        throw new InvalidArgumentException('La entrada o salida no es válida.');
    }

    $inicio = new DateTimeImmutable($fecha . ' ' . $hora_entrada . ':00');
    $fin = new DateTimeImmutable($fecha . ' ' . $hora_salida . ':00');

    if ($cruza_medianoche) {
        $fin = $fin->modify('+1 day');
    } elseif ($fin <= $inicio) {
        throw new InvalidArgumentException(
            'Confirme que la salida corresponde al día siguiente.'
        );
    }

    $duracion_minutos = (int)(($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
    if ($duracion_minutos <= 0 || $duracion_minutos > 2880) {
        throw new InvalidArgumentException(
            'La duración debe ser mayor a cero y no superar 48 horas.'
        );
    }

    $regla = $modelo->obtener_regla_vigente($fecha);
    if (!$regla) {
        throw new RuntimeException(
            'No existe una regla laboral vigente para la fecha seleccionada.'
        );
    }

    $dia_semana = (int)$inicio->format('N');
    $es_festivo = $modelo->fecha_es_festiva($fecha);
    $descuento_almuerzo = (
        $dia_semana >= 1
        && $dia_semana <= 5
    ) ? (int)$regla['jreg_almuerzo_min'] : 0;

    // Nunca permite que el descuento produzca una duración negativa.
    $minutos_ordinarios = max(
        0,
        $duracion_minutos - $descuento_almuerzo
    );

    return [
        'inicio' => $inicio->format('Y-m-d H:i:s'),
        'fin' => $fin->format('Y-m-d H:i:s'),
        'minutos_ordinarios' => $minutos_ordinarios,
        'duracion_minutos' => $duracion_minutos,
        'descuento_almuerzo' => $descuento_almuerzo,
        'es_festivo' => $es_festivo
    ];
}

/**
 * Valida los textos del formulario y añade el intervalo calculado.
 */
function jornada_validar_formulario(Jornada $modelo, $empleado) {
    $fecha = jornada_entrada('fecha');
    $hora_entrada = jornada_entrada('hora_entrada');
    $hora_salida = jornada_entrada('hora_salida');
    $ubicacion = jornada_entrada('ubicacion');
    $actividad = jornada_entrada('actividad');
    $observaciones = jornada_entrada('observaciones');
    $cruza_medianoche = jornada_entrada('cruza_medianoche') === '1';

    $intervalo = jornada_calcular_intervalo(
        $modelo,
        $fecha,
        $hora_entrada,
        $hora_salida,
        $cruza_medianoche
    );

    if ($ubicacion === '' || mb_strlen($ubicacion) > 250) {
        throw new InvalidArgumentException(
            'La ubicación es obligatoria y admite máximo 250 caracteres.'
        );
    }

    if ($actividad === '' || mb_strlen($actividad) > 4000) {
        throw new InvalidArgumentException(
            'La actividad es obligatoria y admite máximo 4000 caracteres.'
        );
    }

    if (mb_strlen($observaciones) > 4000) {
        throw new InvalidArgumentException(
            'Las observaciones admiten máximo 4000 caracteres.'
        );
    }

    $intervalo['ubicacion'] = $ubicacion;
    $intervalo['actividad'] = $actividad;
    $intervalo['observaciones'] = $observaciones;
    return $intervalo;
}

try {
    $op = jornada_entrada('op', $_GET['op'] ?? '');
    $operaciones_jefe = [
        'contextoAprobador',
        'listarPendientesJefe',
        'decidirJornadaJefe',
        'contextoEquipo',
        'listarSubordinadosJefe',
        'calcularHorasEquipo',
        'guardarJornadaEquipo',
        'listarJornadasEquipo'
    ];
    $operaciones_equipo = [
        'contextoEquipo',
        'listarSubordinadosJefe',
        'calcularHorasEquipo',
        'guardarJornadaEquipo',
        'listarJornadasEquipo'
    ];
    $es_operacion_jefe = in_array($op, $operaciones_jefe, true);
    $es_operacion_equipo = in_array($op, $operaciones_equipo, true);
    $contexto = jornada_contexto_autorizado(
        $jornada,
        $es_operacion_equipo
            ? 'equipo'
            : ($es_operacion_jefe ? 'aprobaciones' : 'mis_jornadas'),
        $es_operacion_jefe
    );
    $empleado = $contexto['empleado'];

    switch ($op) {
        case 'contextoUsuario':
            jornada_responder([
                'success' => true,
                'data' => [
                    'empleado' => $empleado['nomb_empl'],
                    'documento' => $empleado['cedu_empl'],
                    'rol' => $empleado['rol_nomb'],
                    'es_jefe' => $contexto['es_jefe']
                ]
            ]);
            break;

        case 'contextoAprobador':
            jornada_responder([
                'success' => true,
                'data' => [
                    'empleado' => $empleado['nomb_empl'],
                    'documento' => $empleado['cedu_empl'],
                    'rol' => $empleado['rol_nomb'],
                    'es_jefe' => true
                ]
            ]);
            break;

        case 'contextoEquipo':
            jornada_responder([
                'success' => true,
                'data' => [
                    'empleado' => $empleado['nomb_empl'],
                    'documento' => $empleado['cedu_empl'],
                    'rol' => $empleado['rol_nomb'],
                    'es_jefe' => true
                ]
            ]);
            break;

        case 'listarSubordinadosJefe':
            $subordinados = $jornada->listar_subordinados_jefe(
                (int)$empleado['id_empl']
            );
            jornada_responder([
                'success' => true,
                'data' => $subordinados
            ]);
            break;

        case 'listarJornadasEquipo':
            $fecha_desde = jornada_entrada('fecha_desde');
            $fecha_hasta = jornada_entrada('fecha_hasta');

            if ($fecha_desde !== '' && !jornada_fecha_valida($fecha_desde)) {
                throw new InvalidArgumentException(
                    'La fecha inicial no es válida.'
                );
            }

            if ($fecha_hasta !== '' && !jornada_fecha_valida($fecha_hasta)) {
                throw new InvalidArgumentException(
                    'La fecha final no es válida.'
                );
            }

            if (
                $fecha_desde !== ''
                && $fecha_hasta !== ''
                && $fecha_desde > $fecha_hasta
            ) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la fecha final.'
                );
            }

            $filas = $jornada->listar_jornadas_equipo(
                (int)$empleado['id_empl'],
                $fecha_desde === '' ? null : $fecha_desde,
                $fecha_hasta === '' ? null : $fecha_hasta
            );
            $dias = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo'
            ];
            $data = [];

            foreach ($filas as $fila) {
                $inicio = new DateTimeImmutable($fila['jornada_inicio']);
                $fin = new DateTimeImmutable($fila['jornada_fin']);
                $data[] = [
                    'jornada_id' => (int)$fila['jornada_id'],
                    'empleado_id' => (int)$fila['empleado_id'],
                    'empleado' => $fila['empleado_nombre'],
                    'documento' => $fila['empleado_documento'],
                    'dia' => $dias[(int)$inicio->format('N')],
                    'fecha' => $inicio->format('Y-m-d'),
                    'hora_entrada' => $inicio->format('H:i'),
                    'fecha_salida' => $fin->format('Y-m-d'),
                    'hora_salida' => $fin->format('H:i'),
                    'horas_ordinarias' => jornada_minutos_a_horas(
                        $fila['jornada_minutos_ordinarios']
                    ),
                    'ubicacion' => $fila['jornada_ubicacion'],
                    'actividad' => $fila['jornada_actividad'],
                    'observaciones' => $fila['jornada_observaciones'],
                    'origen' => $fila['jornada_origen'],
                    'estado_codigo' => $fila['estado_codigo'],
                    'estado_nombre' => $fila['estado_nombre']
                ];
            }

            jornada_responder([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'guardarJornadaEquipo':
            jornada_validar_csrf();
            $empleado_objetivo_id = filter_var(
                jornada_entrada('empleado_id'),
                FILTER_VALIDATE_INT
            );

            if (!$empleado_objetivo_id || $empleado_objetivo_id <= 0) {
                throw new InvalidArgumentException(
                    'Seleccione un empleado válido.'
                );
            }

            $datos = jornada_validar_formulario($jornada, $empleado);
            $jornada_id = $jornada->guardar_jornada_equipo_aprobada(
                $empleado_objetivo_id,
                (int)$empleado['id_empl'],
                $contexto['user_id'],
                $datos['inicio'],
                $datos['fin'],
                $datos['minutos_ordinarios'],
                $datos['ubicacion'],
                $datos['actividad'],
                $datos['observaciones']
            );

            jornada_responder([
                'success' => true,
                'jornada_id' => $jornada_id,
                'estado' => 'APROBADO',
                'message' => 'La jornada fue registrada y aprobada automáticamente.'
            ]);
            break;

        case 'listarPendientesJefe':
            $fecha_desde = jornada_entrada('fecha_desde');
            $fecha_hasta = jornada_entrada('fecha_hasta');

            if ($fecha_desde !== '' && !jornada_fecha_valida($fecha_desde)) {
                throw new InvalidArgumentException(
                    'La fecha inicial no es válida.'
                );
            }

            if ($fecha_hasta !== '' && !jornada_fecha_valida($fecha_hasta)) {
                throw new InvalidArgumentException(
                    'La fecha final no es válida.'
                );
            }

            if (
                $fecha_desde !== ''
                && $fecha_hasta !== ''
                && $fecha_desde > $fecha_hasta
            ) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la fecha final.'
                );
            }

            $filas = $jornada->listar_pendientes_jefe(
                (int)$empleado['id_empl'],
                $fecha_desde === '' ? null : $fecha_desde,
                $fecha_hasta === '' ? null : $fecha_hasta
            );

            $dias = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo'
            ];
            $data = [];

            foreach ($filas as $fila) {
                $inicio = new DateTimeImmutable($fila['jornada_inicio']);
                $fin = new DateTimeImmutable($fila['jornada_fin']);
                $data[] = [
                    'jornada_id' => (int)$fila['jornada_id'],
                    'empleado_id' => (int)$fila['empleado_id'],
                    'empleado' => $fila['empleado_nombre'],
                    'documento' => $fila['empleado_documento'],
                    'dia' => $dias[(int)$inicio->format('N')],
                    'fecha' => $inicio->format('Y-m-d'),
                    'hora_entrada' => $inicio->format('H:i'),
                    'fecha_salida' => $fin->format('Y-m-d'),
                    'hora_salida' => $fin->format('H:i'),
                    'horas_ordinarias' => jornada_minutos_a_horas(
                        $fila['jornada_minutos_ordinarios']
                    ),
                    'ubicacion' => $fila['jornada_ubicacion'],
                    'actividad' => $fila['jornada_actividad'],
                    'observaciones' => $fila['jornada_observaciones'],
                    'estado_codigo' => $fila['estado_codigo'],
                    'estado_nombre' => $fila['estado_nombre']
                ];
            }

            jornada_responder([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'decidirJornadaJefe':
            jornada_validar_csrf();
            $jornada_id = filter_var(
                jornada_entrada('jornada_id'),
                FILTER_VALIDATE_INT
            );

            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }

            $estado_nuevo = $jornada->decidir_jornada_jefe(
                $jornada_id,
                (int)$empleado['id_empl'],
                $contexto['user_id'],
                jornada_entrada('decision'),
                jornada_entrada('motivo')
            );

            jornada_responder([
                'success' => true,
                'estado' => $estado_nuevo,
                'message' => $estado_nuevo === 'APROBADO'
                    ? 'La jornada fue aprobada correctamente.'
                    : 'La jornada fue rechazada correctamente.'
            ]);
            break;

        case 'calcularHoras':
        case 'calcularHorasEquipo':
            $fecha = jornada_entrada('fecha');
            $hora_entrada = jornada_entrada('hora_entrada');
            $hora_salida = jornada_entrada('hora_salida');
            $cruza_medianoche = jornada_entrada('cruza_medianoche') === '1' || (
                $hora_entrada !== ''
                && $hora_salida !== ''
                && $hora_salida <= $hora_entrada
            );

            $calculo = jornada_calcular_intervalo(
                $jornada,
                $fecha,
                $hora_entrada,
                $hora_salida,
                $cruza_medianoche
            );

            jornada_responder([
                'success' => true,
                'data' => [
                    'horas_ordinarias' => jornada_minutos_a_horas(
                        $calculo['minutos_ordinarios']
                    ),
                    'duracion_total' => jornada_minutos_a_horas(
                        $calculo['duracion_minutos']
                    ),
                    'descuento_almuerzo' => jornada_minutos_a_horas(
                        $calculo['descuento_almuerzo']
                    ),
                    'cruza_medianoche' => $cruza_medianoche,
                    'es_festivo' => $calculo['es_festivo']
                ]
            ]);
            break;

        case 'guardarBorrador':
            jornada_validar_csrf();
            $datos = jornada_validar_formulario($jornada, $empleado);
            $jornada_id_texto = jornada_entrada('jornada_id');
            $jornada_id = $jornada_id_texto === ''
                ? null
                : filter_var($jornada_id_texto, FILTER_VALIDATE_INT);

            if ($jornada_id_texto !== '' && (!$jornada_id || $jornada_id <= 0)) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }

            if ($jornada->existe_superposicion(
                (int)$empleado['id_empl'],
                $datos['inicio'],
                $datos['fin'],
                $jornada_id
            )) {
                jornada_responder([
                    'success' => false,
                    'inconsistente' => true,
                    'message' => 'El intervalo se superpone con otra jornada registrada.'
                ], 409);
            }

            $guardada_id = $jornada->guardar_borrador_propio(
                $jornada_id,
                (int)$empleado['id_empl'],
                $contexto['user_id'],
                $datos['inicio'],
                $datos['fin'],
                $datos['minutos_ordinarios'],
                $datos['ubicacion'],
                $datos['actividad'],
                $datos['observaciones']
            );

            jornada_responder([
                'success' => true,
                'jornada_id' => $guardada_id,
                'message' => 'El borrador fue guardado correctamente.'
            ]);
            break;

        case 'enviarAprobacion':
            jornada_validar_csrf();
            $jornada_id = filter_var(
                jornada_entrada('jornada_id'),
                FILTER_VALIDATE_INT
            );

            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }

            $detalle = $jornada->obtener_mi_jornada(
                $jornada_id,
                (int)$empleado['id_empl']
            );

            if (!$detalle) {
                jornada_responder([
                    'success' => false,
                    'message' => 'No se encontró la jornada.'
                ], 404);
            }

            if ($jornada->existe_superposicion(
                (int)$empleado['id_empl'],
                $detalle['jornada_inicio'],
                $detalle['jornada_fin'],
                $jornada_id
            )) {
                jornada_responder([
                    'success' => false,
                    'inconsistente' => true,
                    'message' => 'La jornada se superpone con otro registro.'
                ], 409);
            }

            $jornada->enviar_aprobacion_propia(
                $jornada_id,
                (int)$empleado['id_empl'],
                $contexto['user_id']
            );

            jornada_responder([
                'success' => true,
                'message' => 'La jornada fue enviada a aprobación.'
            ]);
            break;

        case 'listarMisJornadas':
            $fecha_desde = jornada_entrada('fecha_desde');
            $fecha_hasta = jornada_entrada('fecha_hasta');

            if ($fecha_desde !== '' && !jornada_fecha_valida($fecha_desde)) {
                throw new InvalidArgumentException('La fecha inicial no es válida.');
            }

            if ($fecha_hasta !== '' && !jornada_fecha_valida($fecha_hasta)) {
                throw new InvalidArgumentException('La fecha final no es válida.');
            }

            if (
                $fecha_desde !== ''
                && $fecha_hasta !== ''
                && $fecha_desde > $fecha_hasta
            ) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la fecha final.'
                );
            }

            $filas = $jornada->listar_mis_jornadas(
                (int)$empleado['id_empl'],
                $fecha_desde === '' ? null : $fecha_desde,
                $fecha_hasta === '' ? null : $fecha_hasta
            );

            $dias = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miércoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sábado',
                7 => 'Domingo'
            ];

            $data = [];
            foreach ($filas as $fila) {
                $inicio = new DateTimeImmutable($fila['jornada_inicio']);
                $fin = new DateTimeImmutable($fila['jornada_fin']);
                $data[] = [
                    'jornada_id' => (int)$fila['jornada_id'],
                    'dia' => $dias[(int)$inicio->format('N')],
                    'fecha' => $inicio->format('Y-m-d'),
                    'hora_entrada' => $inicio->format('H:i'),
                    'fecha_salida' => $fin->format('Y-m-d'),
                    'hora_salida' => $fin->format('H:i'),
                    'horas_ordinarias' => jornada_minutos_a_horas(
                        $fila['jornada_minutos_ordinarios']
                    ),
                    'ubicacion' => $fila['jornada_ubicacion'],
                    'actividad' => $fila['jornada_actividad'],
                    'observaciones' => $fila['jornada_observaciones'],
                    'estado_codigo' => $fila['estado_codigo'],
                    'estado_nombre' => $fila['estado_nombre'],
                    'inconsistente' => (int)$fila['jornada_inconsistente'] === 1,
                    'inconsistencia' => $fila['jornada_inconsistencia_detalle']
                ];
            }

            jornada_responder([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'obtenerMiJornada':
            $jornada_id = filter_var(
                jornada_entrada('jornada_id'),
                FILTER_VALIDATE_INT
            );

            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }

            $fila = $jornada->obtener_mi_jornada(
                $jornada_id,
                (int)$empleado['id_empl']
            );

            if (!$fila) {
                jornada_responder([
                    'success' => false,
                    'message' => 'No se encontró la jornada.'
                ], 404);
            }

            $inicio = new DateTimeImmutable($fila['jornada_inicio']);
            $fin = new DateTimeImmutable($fila['jornada_fin']);
            jornada_responder([
                'success' => true,
                'data' => [
                    'jornada_id' => (int)$fila['jornada_id'],
                    'fecha' => $inicio->format('Y-m-d'),
                    'hora_entrada' => $inicio->format('H:i'),
                    'hora_salida' => $fin->format('H:i'),
                    'cruza_medianoche' => $fin->format('Y-m-d') !== $inicio->format('Y-m-d'),
                    'horas_ordinarias' => jornada_minutos_a_horas(
                        $fila['jornada_minutos_ordinarios']
                    ),
                    'ubicacion' => $fila['jornada_ubicacion'],
                    'actividad' => $fila['jornada_actividad'],
                    'observaciones' => $fila['jornada_observaciones'],
                    'estado_codigo' => $fila['estado_codigo'],
                    'estado_nombre' => $fila['estado_nombre']
                ]
            ]);
            break;

        default:
            jornada_responder([
                'success' => false,
                'message' => 'Operación no soportada.'
            ], 404);
    }
} catch (InvalidArgumentException $e) {
    jornada_responder([
        'success' => false,
        'message' => $e->getMessage()
    ], 422);
} catch (RuntimeException $e) {
    jornada_responder([
        'success' => false,
        'message' => $e->getMessage()
    ], 409);
} catch (Throwable $e) {
    error_log('Jornadas: ' . $e->getMessage());
    jornada_responder([
        'success' => false,
        'message' => 'No fue posible procesar la solicitud.'
    ], 500);
}

?>
