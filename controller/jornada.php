<?php

ob_start();
ini_set('display_errors', '0');

require_once('../config/conexion.php');
require_once('../models/Jornada.php');

$jornada = new Jornada();

/**
 * Finaliza la petición con una única respuesta JSON limpia.
 */
function jornada_responder($payload, $status_code = 200)
{
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
function jornada_entrada($nombre, $predeterminado = '')
{
    $valor = $_POST[$nombre] ?? $_GET[$nombre] ?? $predeterminado;
    return is_string($valor) ? trim($valor) : $predeterminado;
}

/**
 * Valida el token usado por las operaciones que modifican información.
 */
function jornada_validar_csrf()
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        jornada_responder(['success' => false, 'message' => 'Utilice POST para esta operación.'], 405);
    }
    $recibido = jornada_entrada('csrf_token');
    $esperado = $_SESSION['csrf_jornadas'] ?? '';

    if (
        $recibido === '' ||
        $esperado === '' ||
        !hash_equals($esperado, $recibido)
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
function jornada_minutos_a_horas($minutos)
{
    $minutos = max(0, (int) $minutos);
    return str_pad((string) floor($minutos / 60), 2, '0', STR_PAD_LEFT)
        . ':'
        . str_pad((string) ($minutos % 60), 2, '0', STR_PAD_LEFT);
}

/**
 * Valida una fecha estricta en formato ISO.
 */
function jornada_fecha_valida($fecha)
{
    $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $objeto && $objeto->format('Y-m-d') === $fecha;
}

/**
 * Valida una hora estricta en formato de 24 horas.
 */
function jornada_hora_valida($hora)
{
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
    $user_id = (int) ($_SESSION['user_id'] ?? 0);
    $rol_id = (int) ($_SESSION['user_rol'] ?? 0);

    if ($user_id <= 0 || $rol_id <= 0) {
        jornada_responder([
            'success' => false,
            'message' => 'Debe iniciar sesión.'
        ], 401);
    }

    $empleado = $modelo->obtener_empleado_por_usuario($user_id);
    if (!$empleado || (int) $empleado['esta_empl'] !== 1) {
        jornada_responder([
            'success' => false,
            'message' => 'El usuario no tiene un empleado activo asociado.'
        ], 403);
    }

    $es_jefe = $modelo->es_jefe_activo((int) $empleado['id_empl']);
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
        !jornada_hora_valida($hora_entrada) ||
        !jornada_hora_valida($hora_salida)
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

    $duracion_minutos = (int) (($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
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

    $dia_semana = (int) $inicio->format('N');
    $es_festivo = $modelo->fecha_es_festiva($fecha);
    $descuento_almuerzo = 0;
    $fin_cumplimiento_base = $inicio->modify(
        '+' . (int) $regla['jreg_max_lunes_viernes_min'] . ' minutes'
    );
    $almuerzo_inicio = new DateTimeImmutable($fecha . ' 12:00:00');
    if (
        $dia_semana >= 1 &&
        $dia_semana <= 5 &&
        $inicio < $almuerzo_inicio &&
        $fin_cumplimiento_base > $almuerzo_inicio
    ) {
        $almuerzo_fin = $almuerzo_inicio->modify(
            '+' . (int) $regla['jreg_almuerzo_min'] . ' minutes'
        );
        $solapamiento_inicio = $inicio > $almuerzo_inicio
            ? $inicio
            : $almuerzo_inicio;
        $solapamiento_fin = $fin < $almuerzo_fin ? $fin : $almuerzo_fin;
        if ($solapamiento_fin > $solapamiento_inicio) {
            $descuento_almuerzo = (int) (
                ($solapamiento_fin->getTimestamp()
                    - $solapamiento_inicio->getTimestamp()) / 60
            );
        }
    }
    $minutos_ordinarios = max(0, $duracion_minutos - $descuento_almuerzo);

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
function jornada_validar_formulario(Jornada $modelo, $empleado)
{
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
        'listarSubordinadosAprobador',
        'decidirJornadaJefe',
        // Operaciones del expediente de jornadas del equipo.
        'contextoEquipo',
        'listarSubordinadosJefe',
        'calcularHorasEquipo',
        'guardarJornadaEquipo',
        'listarJornadasEquipo',
        'guardarBorradoresEquipoMasivo',
        'registrarAprobarEquipoMasivo',
        'listarUbicacionesJornada'
    ];
    $operaciones_equipo = [
        'contextoEquipo',
        'listarSubordinadosJefe',
        'calcularHorasEquipo',
        'guardarJornadaEquipo',
        'listarJornadasEquipo',
        // Nuevas operaciones masivas del expediente.
        'guardarBorradoresEquipoMasivo',
        'registrarAprobarEquipoMasivo',
        'listarUbicacionesJornada'
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

    /**
     * Valida una jornada recibida desde la planilla masiva del jefe.
     * El cálculo de horas se ejecuta nuevamente en el servidor.
     */
    function jornada_validar_fila_equipo(Jornada $modelo, array $fila)
    {
        $fecha = isset($fila['fecha'])
            ? trim((string) $fila['fecha'])
            : '';

        $hora_entrada = isset($fila['hora_entrada'])
            ? trim((string) $fila['hora_entrada'])
            : '';

        $hora_salida = isset($fila['hora_salida'])
            ? trim((string) $fila['hora_salida'])
            : '';

        $ubicacion = isset($fila['ubicacion'])
            ? trim((string) $fila['ubicacion'])
            : '';

        $actividad = isset($fila['actividad'])
            ? trim((string) $fila['actividad'])
            : '';

        $observaciones = isset($fila['observaciones'])
            ? trim((string) $fila['observaciones'])
            : '';

        $cruza_medianoche = !empty($fila['cruza_medianoche']);

        // La fecha se valida nuevamente aunque provenga del DataTable.
        if (!jornada_fecha_valida($fecha)) {
            throw new InvalidArgumentException(
                'Una de las jornadas contiene una fecha no válida.'
            );
        }

        // Entrada y salida son obligatorias para una fila enviada.
        if (
            !jornada_hora_valida($hora_entrada) ||
            !jornada_hora_valida($hora_salida)
        ) {
            throw new InvalidArgumentException(
                'La jornada del ' . $fecha . ' tiene una hora no válida.'
            );
        }

        // Solo se permiten las ubicaciones ya aceptadas por el módulo.
        $ubicaciones_permitidas = $modelo->listar_ubicaciones_jornada();

        if (!in_array($ubicacion, $ubicaciones_permitidas, true)) {
            throw new InvalidArgumentException(
                'Seleccione una ubicación válida para la jornada del '
                    . $fecha . '.'
            );
        }

        if ($actividad === '' || mb_strlen($actividad) > 4000) {
            throw new InvalidArgumentException(
                'La actividad de la jornada del '
                    . $fecha
                    . ' es obligatoria y admite máximo 4000 caracteres.'
            );
        }

        if (mb_strlen($observaciones) > 4000) {
            throw new InvalidArgumentException(
                'Las observaciones de la jornada del '
                    . $fecha
                    . ' admiten máximo 4000 caracteres.'
            );
        }

        // Las horas ordinarias nunca se reciben confiando en JavaScript.
        $intervalo = jornada_calcular_intervalo(
            $modelo,
            $fecha,
            $hora_entrada,
            $hora_salida,
            $cruza_medianoche
        );

        // Jornada existente cuando corresponde a un BORRADOR.
        $jornada_id = null;

        if (
            isset($fila['jornada_id']) &&
            $fila['jornada_id'] !== '' &&
            $fila['jornada_id'] !== null
        ) {
            $jornada_id = filter_var(
                $fila['jornada_id'],
                FILTER_VALIDATE_INT
            );

            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException(
                    'La jornada del ' . $fecha . ' no es válida.'
                );
            }
        }

        return [
            'jornada_id' => $jornada_id,
            'fecha' => $fecha,
            'inicio' => $intervalo['inicio'],
            'fin' => $intervalo['fin'],
            'minutos_ordinarios' => $intervalo['minutos_ordinarios'],
            'ubicacion' => $ubicacion,
            'actividad' => $actividad,
            'observaciones' => $observaciones
        ];
    }

    /**
     * Lee y valida el lote JSON enviado desde el expediente del jefe.
     */
    function jornada_validar_lote_equipo(Jornada $modelo)
    {
        $empleado_id = filter_var(
            jornada_entrada('empleado_id'),
            FILTER_VALIDATE_INT
        );

        if (!$empleado_id || $empleado_id <= 0) {
            throw new InvalidArgumentException(
                'Seleccione un empleado válido.'
            );
        }

        $jornadas_json = jornada_entrada('jornadas');

        if ($jornadas_json === '') {
            throw new InvalidArgumentException(
                'No se recibieron jornadas para procesar.'
            );
        }

        $jornadas = json_decode($jornadas_json, true);

        if (!is_array($jornadas)) {
            throw new InvalidArgumentException(
                'El formato de las jornadas no es válido.'
            );
        }

        if (count($jornadas) === 0) {
            throw new InvalidArgumentException(
                'No existen jornadas para procesar.'
            );
        }

        // Evita envíos excesivamente grandes por error.
        if (count($jornadas) > 62) {
            throw new InvalidArgumentException(
                'El lote no puede superar 62 jornadas.'
            );
        }

        $validadas = [];
        $fechas = [];

        foreach ($jornadas as $fila) {
            if (!is_array($fila)) {
                throw new InvalidArgumentException(
                    'El lote contiene una jornada no válida.'
                );
            }

            $validada = jornada_validar_fila_equipo(
                $modelo,
                $fila
            );

            // Evita recibir dos filas de la misma fecha en el mismo lote.
            if (isset($fechas[$validada['fecha']])) {
                throw new InvalidArgumentException(
                    'La fecha '
                        . $validada['fecha']
                        . ' está repetida dentro del lote.'
                );
            }

            $fechas[$validada['fecha']] = true;
            $validadas[] = $validada;
        }

        return [
            'empleado_id' => (int) $empleado_id,
            'jornadas' => $validadas
        ];
    }

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

        case 'listarSubordinadosAprobador':
        case 'listarSubordinadosJefe':
            $subordinados = $jornada->listar_subordinados_jefe(
                (int) $empleado['id_empl']
            );
            jornada_responder([
                'success' => true,
                'data' => $subordinados
            ]);
            break;

        case 'listarJornadasEquipo':
            $fecha_desde = jornada_entrada('fecha_desde');
            $fecha_hasta = jornada_entrada('fecha_hasta');

            $empleado_objetivo_id = filter_var(
                jornada_entrada('empleado_id'),
                FILTER_VALIDATE_INT
            );

            if (
                !$empleado_objetivo_id ||
                $empleado_objetivo_id <= 0
            ) {
                throw new InvalidArgumentException(
                    'Seleccione un empleado válido.'
                );
            }

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
                $fecha_desde !== '' &&
                $fecha_hasta !== '' &&
                $fecha_desde > $fecha_hasta
            ) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la fecha final.'
                );
            }

            $filas = $jornada->listar_jornadas_equipo_empleado(
                (int) $empleado['id_empl'],
                (int) $empleado_objetivo_id,
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
                    'jornada_id' => (int) $fila['jornada_id'],
                    'empleado_id' => (int) $fila['empleado_id'],
                    'empleado' => $fila['empleado_nombre'],
                    'documento' => $fila['empleado_documento'],
                    'dia' => $dias[(int) $inicio->format('N')],
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
                (int) $empleado['id_empl'],
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

        case 'guardarBorradoresEquipoMasivo':
            // Las operaciones de escritura requieren token CSRF válido.
            jornada_validar_csrf();

            // Valida empleado, cantidad de filas y cada jornada recibida.
            $lote = jornada_validar_lote_equipo($jornada);

            /*
             * El método será implementado en el Paso 4.
             * No existe todavía en models/Jornada.php.
             */
            $resultado = $jornada->guardar_borradores_equipo_masivo(
                $lote['empleado_id'],
                (int) $empleado['id_empl'],
                $contexto['user_id'],
                $lote['jornadas']
            );

            jornada_responder([
                'success' => true,
                'data' => $resultado,
                'message' => 'Los borradores fueron guardados correctamente.'
            ]);

            break;

        case 'registrarAprobarEquipoMasivo':
            // Las operaciones de escritura requieren token CSRF válido.
            jornada_validar_csrf();

            // Valida nuevamente todo el lote antes de enviarlo al modelo.
            $lote = jornada_validar_lote_equipo($jornada);

            /*
             * El modelo realizará una transacción para registrar o actualizar
             * borradores y aprobar las jornadas correspondientes.
             */
            $resultado = $jornada->registrar_aprobar_equipo_masivo(
                $lote['empleado_id'],
                (int) $empleado['id_empl'],
                $contexto['user_id'],
                $lote['jornadas']
            );

            jornada_responder([
                'success' => true,
                'data' => $resultado,
                'message' => 'Las jornadas fueron registradas y aprobadas correctamente.'
            ]);

            break;

        case 'listarPendientesJefe':
            $fecha_desde = jornada_entrada('fecha_desde');
            $fecha_hasta = jornada_entrada('fecha_hasta');
            $empleado_id_texto = jornada_entrada('empleado_id');
            $empleado_id_filtro = $empleado_id_texto === ''
                ? null
                : filter_var($empleado_id_texto, FILTER_VALIDATE_INT);
            if ($empleado_id_texto !== '' && (!$empleado_id_filtro || $empleado_id_filtro <= 0)) {
                throw new InvalidArgumentException('Seleccione un empleado válido.');
            }

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
                $fecha_desde !== '' &&
                $fecha_hasta !== '' &&
                $fecha_desde > $fecha_hasta
            ) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la fecha final.'
                );
            }

            $filas = $jornada->listar_pendientes_jefe(
                (int) $empleado['id_empl'],
                $fecha_desde === '' ? null : $fecha_desde,
                $fecha_hasta === '' ? null : $fecha_hasta,
                $empleado_id_filtro
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
                    'jornada_id' => (int) $fila['jornada_id'],
                    'empleado_id' => (int) $fila['empleado_id'],
                    'empleado' => $fila['empleado_nombre'],
                    'documento' => $fila['empleado_documento'],
                    'dia' => $dias[(int) $inicio->format('N')],
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
                (int) $empleado['id_empl'],
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
                $hora_entrada !== '' &&
                $hora_salida !== '' &&
                $hora_salida <= $hora_entrada
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

        case 'validarFecha':
            $fecha = jornada_entrada('fecha');
            if (!jornada_fecha_valida($fecha)) {
                throw new InvalidArgumentException('La fecha no es válida.');
            }
            $id_texto = jornada_entrada('jornada_id');
            $excluir = $id_texto === '' ? null : filter_var($id_texto, FILTER_VALIDATE_INT);
            if ($id_texto !== '') {
                if (!$excluir || $excluir <= 0) {
                    throw new InvalidArgumentException('La jornada no es válida.');
                }
                $propia = $jornada->obtener_mi_jornada($excluir, (int) $empleado['id_empl']);
                if (!$propia || $propia['estado_codigo'] !== 'BORRADOR') {
                    throw new RuntimeException('La jornada no existe o ya no puede editarse.');
                }
            }
            $existente = $jornada->obtener_jornada_fecha((int) $empleado['id_empl'], $fecha, $excluir);
            jornada_responder([
                'success' => true,
                'data' => ['disponible' => !$existente, 'jornada' => $existente ?: null]
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

            $guardada_id = $jornada->guardar_borrador_propio(
                $jornada_id,
                (int) $empleado['id_empl'],
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

            $jornada->enviar_aprobacion_propia(
                $jornada_id,
                (int) $empleado['id_empl'],
                $contexto['user_id']
            );

            jornada_responder([
                'success' => true,
                'message' => 'La jornada fue enviada a aprobación.'
            ]);
            break;

        case 'enviarAprobacionMasiva':
            jornada_validar_csrf();
            $ids = $_POST['jornada_ids'] ?? [];
            if (!is_array($ids) || count($ids) === 0 || count($ids) > 500) {
                throw new InvalidArgumentException('Seleccione entre 1 y 500 borradores.');
            }
            $validados = [];
            foreach ($ids as $id) {
                if (!is_scalar($id) || !filter_var($id, FILTER_VALIDATE_INT) || (int) $id <= 0) {
                    throw new InvalidArgumentException('La selección contiene una jornada no válida.');
                }
                $validados[] = (int) $id;
            }
            $resultado = $jornada->enviar_aprobacion_masiva(
                array_values(array_unique($validados)),
                (int) $empleado['id_empl'],
                $contexto['user_id']
            );
            jornada_responder(['success' => true, 'data' => $resultado]);
            break;

        case 'anularBorrador':
            jornada_validar_csrf();
            $jornada_id = filter_var(jornada_entrada('jornada_id'), FILTER_VALIDATE_INT);
            if (!$jornada_id || $jornada_id <= 0) {
                throw new InvalidArgumentException('La jornada no es válida.');
            }
            $jornada->anular_borrador_propio(
                $jornada_id,
                (int) $empleado['id_empl'],
                $contexto['user_id'],
                jornada_entrada('motivo')
            );
            jornada_responder(['success' => true, 'message' => 'Borrador anulado. La fecha queda disponible.']);
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
                $fecha_desde !== '' &&
                $fecha_hasta !== '' &&
                $fecha_desde > $fecha_hasta
            ) {
                throw new InvalidArgumentException(
                    'La fecha inicial no puede superar la fecha final.'
                );
            }

            $filas = $jornada->listar_mis_jornadas(
                (int) $empleado['id_empl'],
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
                    'jornada_id' => (int) $fila['jornada_id'],
                    'dia' => $dias[(int) $inicio->format('N')],
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
                    'inconsistente' => (int) $fila['jornada_inconsistente'] === 1,
                    'inconsistencia' => $fila['jornada_inconsistencia_detalle'],
                    'anulacion_motivo' => $fila['anulacion_motivo'],
                    'anulacion_fecha' => $fila['anulacion_fecha']
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
                (int) $empleado['id_empl']
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
                    'jornada_id' => (int) $fila['jornada_id'],
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

        case 'listarUbicacionesJornada':

            jornada_responder([
                'success' => true,
                'data' => $jornada->listar_ubicaciones_jornada()
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
