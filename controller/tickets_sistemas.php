<?php

require_once '../config/conexion.php';
require_once '../models/TicketsSistemas.php';
require_once 'curl.php';

header('Content-Type: application/json; charset=utf-8');

$modelo = new TicketsSistemas();

function responderTicketsSistemas($success, $message, $data = null, $codigoHttp = 200)
{
    http_response_code($codigoHttp);

    echo json_encode(array(
        'success' => $success,
        'message' => $message,
        'data' => $data
    ), JSON_UNESCAPED_UNICODE);

    exit;
}

function exigirSesionTicketsSistemas()
{
    if (empty($_SESSION['user_id'])) {
        responderTicketsSistemas(
            false,
            'La sesión ha expirado.',
            null,
            401
        );
    }
}

function validarMetodoTicketsSistemas($metodoPermitido)
{
    $metodoActual = isset($_SERVER['REQUEST_METHOD'])
        ? strtoupper($_SERVER['REQUEST_METHOD'])
        : '';

    if ($metodoActual !== strtoupper($metodoPermitido)) {
        responderTicketsSistemas(
            false,
            'Método HTTP no permitido.',
            null,
            405
        );
    }
}

function textoPostTicketsSistemas($campo, $maximo, $obligatorio = false)
{
    $valor = isset($_POST[$campo]) && !is_array($_POST[$campo])
        ? trim((string) $_POST[$campo])
        : '';

    if ($obligatorio && $valor === '') {
        responderTicketsSistemas(
            false,
            'El campo ' . $campo . ' es obligatorio.',
            null,
            422
        );
    }

    if (mb_strlen($valor, 'UTF-8') > $maximo) {
        responderTicketsSistemas(
            false,
            'El campo ' . $campo . ' supera la longitud permitida.',
            null,
            422
        );
    }

    return $valor;
}

function valorPermitidoTicketsSistemas($valor, $permitidos, $campo)
{
    if (!in_array($valor, $permitidos, true)) {
        responderTicketsSistemas(
            false,
            'El valor de ' . $campo . ' no es válido.',
            null,
            422
        );
    }

    return $valor;
}

function decodificarRespuestaTicketsApi($respuesta)
{
    if ($respuesta === false || trim((string) $respuesta) === '') {
        throw new RuntimeException(
            'La API de Control de Equipos no respondió.'
        );
    }

    $resultado = json_decode($respuesta, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($resultado)) {
        throw new RuntimeException(
            'La API de Control de Equipos devolvió una respuesta no válida.'
        );
    }

    return $resultado;
}

// Comprueba que el lote temporal fue generado en la sesión autenticada.
function validarTokenSoportesTickets($token)
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)
        || empty($_SESSION['tickets_soportes_tokens'][$token])
        || $_SESSION['tickets_soportes_tokens'][$token] < time() - 86400) {
        responderTicketsSistemas(false, 'El formulario expiró. Cierre y abra Nuevo ticket.', null, 422);
    }
}

exigirSesionTicketsSistemas();

$op = isset($_GET['op'])
    ? trim((string) $_GET['op'])
    : '';

try {
    switch ($op) {
        // Genera un lote independiente para cada formulario, incluso en varias pestañas.
        case 'prepararSoportes':
            validarMetodoTicketsSistemas('POST');
            $tokens = $_SESSION['tickets_soportes_tokens'] ?? array();
            foreach ($tokens as $clave => $fecha) {
                if ($fecha < time() - 86400) { unset($tokens[$clave]); }
            }
            if (count($tokens) >= 100) { array_shift($tokens); }
            $token = bin2hex(random_bytes(32));
            $tokens[$token] = time();
            $_SESSION['tickets_soportes_tokens'] = $tokens;
            responderTicketsSistemas(true, 'Formulario preparado.', array('soporte_token' => $token));
            break;

        // Recibe archivos y transporta JSON por la API existente, sin SQL en el controlador.
        case 'subirSoporte':
        case 'eliminarSoporte':
        case 'listarSoportes':
        case 'descargarSoporte':
            validarMetodoTicketsSistemas('POST');
            $empleado = $modelo->obtenerEmpleadoSesion((int) $_SESSION['user_id']);
            if ($empleado === null) {
                responderTicketsSistemas(false, 'No se encontró el empleado autenticado.', null, 422);
            }
            $datosSoporte = array('empleado_documento' => trim((string) $empleado['cedu_empl']));
            if ($op === 'subirSoporte' || $op === 'eliminarSoporte') {
                $token = textoPostTicketsSistemas('soporte_token', 64, true);
                validarTokenSoportesTickets($token);
                $datosSoporte['soporte_token'] = $token;
            }
            if ($op === 'subirSoporte') {
                $archivo = $_FILES['file'] ?? null;
                if (!$archivo || !isset($archivo['error'], $archivo['size'], $archivo['tmp_name'], $archivo['name'])
                    || is_array($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK
                    || $archivo['size'] <= 0 || $archivo['size'] > 10485760
                    || !is_uploaded_file($archivo['tmp_name'])) {
                    responderTicketsSistemas(false, 'Adjunte un archivo válido de hasta 10 MB.', null, 422);
                }
                $datosSoporte['nombre'] = basename(str_replace('\\', '/', $archivo['name']));
                $datosSoporte['contenido'] = base64_encode(file_get_contents($archivo['tmp_name']));
            } else {
                $campo = $op === 'listarSoportes' ? 'ticket_id' : 'soporte_id';
                $id = filter_var($_POST[$campo] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id < 1) { responderTicketsSistemas(false, 'Identificador no válido.', null, 422); }
                $datosSoporte[$campo] = $id;
            }
            $resultado = decodificarRespuestaTicketsApi(CurlController::requestPreoperacional('op=' . $op, 'POST', $datosSoporte));
            responderTicketsSistemas(!empty($resultado['success']), $resultado['message'] ?? 'Error procesando soporte.',
                $resultado['data'] ?? null, !empty($resultado['success']) ? 200 : 422);
            break;

        /*
         * =====================================================
         * EMPLEADO AUTENTICADO
         * =====================================================
         */
        case 'empleado':
            validarMetodoTicketsSistemas('GET');

            $empleado = $modelo->obtenerEmpleadoSesion(
                (int) $_SESSION['user_id']
            );

            if ($empleado === null) {
                responderTicketsSistemas(
                    false,
                    'No se encontró un empleado asociado al usuario.',
                    null,
                    404
                );
            }

            $data = array(
                'documento' => $empleado['cedu_empl'],
                'nombre' => $empleado['nomb_empl'],
                'correo' => $empleado['email_empl'] ?? '',
                'cargo' => $empleado['nomb_carg'] ?? ''
            );

            responderTicketsSistemas(
                true,
                'Información del empleado consultada correctamente.',
                $data
            );

            break;

        /*
         * =====================================================
         * CATEGORÍAS
         * =====================================================
         */
        case 'categorias':
            validarMetodoTicketsSistemas('GET');

            $respuestaApi = CurlController::requestPreoperacional(
                'op=categorias',
                'GET'
            );

            $resultado = decodificarRespuestaTicketsApi(
                $respuestaApi
            );

            if (empty($resultado['success'])) {
                responderTicketsSistemas(
                    false,
                    $resultado['message'] ?? 'No fue posible consultar las categorías.',
                    null,
                    502
                );
            }

            responderTicketsSistemas(
                true,
                $resultado['message'] ?? 'Categorías consultadas correctamente.',
                $resultado['data'] ?? array()
            );

            break;

        /*
         * =====================================================
         * CREAR TICKET
         * =====================================================
         */
        case 'crear':
            validarMetodoTicketsSistemas('POST');

            $empleado = $modelo->obtenerEmpleadoSesion(
                (int) $_SESSION['user_id']
            );

            if ($empleado === null) {
                responderTicketsSistemas(
                    false,
                    'No se encontró un empleado asociado al usuario.',
                    null,
                    422
                );
            }

            $tipo = valorPermitidoTicketsSistemas(
                strtoupper(
                    textoPostTicketsSistemas(
                        'tipo',
                        20,
                        true
                    )
                ),
                array(
                    'SOLICITUD',
                    'INCIDENTE',
                    'REQUERIMIENTO'
                ),
                'tipo'
            );

            $categoriaId = isset($_POST['categoria_id'])
                ? filter_var(
                    $_POST['categoria_id'],
                    FILTER_VALIDATE_INT
                )
                : false;

            if ($categoriaId === false || $categoriaId <= 0) {
                responderTicketsSistemas(
                    false,
                    'Debe seleccionar una categoría válida.',
                    null,
                    422
                );
            }

            $prioridad = valorPermitidoTicketsSistemas(
                strtoupper(
                    textoPostTicketsSistemas(
                        'prioridad',
                        10,
                        true
                    )
                ),
                array(
                    'BAJA',
                    'MEDIA',
                    'ALTA',
                    'CRITICA'
                ),
                'prioridad'
            );

            // El token opcional conserva compatibilidad con consumidores anteriores.
            $soporteToken = textoPostTicketsSistemas('soporte_token', 64);
            if ($soporteToken !== '') { validarTokenSoportesTickets($soporteToken); }
            $datosTicket = array(
                'soporte_token' => $soporteToken,
                'empleado_documento' => trim((string) $empleado['cedu_empl']),
                'empleado_nombre' => trim((string) $empleado['nomb_empl']),
                'empleado_correo' => trim((string) ($empleado['email_empl'] ?? '')),
                'empleado_cargo' => trim((string) ($empleado['nomb_carg'] ?? '')),
                'empleado_area' => trim((string) ($empleado['desc_depen'] ?? '')),
                'tipo' => $tipo,
                'categoria_id' => (int) $categoriaId,
                'asunto' => textoPostTicketsSistemas('asunto', 150, true),
                'descripcion' => textoPostTicketsSistemas('descripcion', 4000, true),
                'prioridad' => $prioridad,
                'ubicacion' => textoPostTicketsSistemas('ubicacion', 150),
                'equipo' => textoPostTicketsSistemas('equipo', 150)
            );

            $respuestaApi = CurlController::requestPreoperacional(
                'op=crear',
                'POST',
                $datosTicket
            );

            $resultado = decodificarRespuestaTicketsApi(
                $respuestaApi
            );

            if (empty($resultado['success'])) {
                responderTicketsSistemas(
                    false,
                    $resultado['message'] ?? 'No fue posible crear el ticket.',
                    $resultado['data'] ?? null,
                    422
                );
            }

            responderTicketsSistemas(
                true,
                $resultado['message'] ?? 'Ticket creado correctamente.',
                $resultado['data'] ?? null,
                201
            );

            break;

        /*
         * =====================================================
         * MIS TICKETS
         * =====================================================
         */
        case 'misTickets':
            validarMetodoTicketsSistemas('GET');

            /*
             * Obtener empleado asociado a la sesión.
             */
            $empleado = $modelo->obtenerEmpleadoSesion(
                (int) $_SESSION['user_id']
            );

            if ($empleado === null) {
                responderTicketsSistemas(
                    false,
                    'No se encontró un empleado asociado al usuario.',
                    null,
                    404
                );
            }

            /*
             * La cédula se obtiene en el servidor.
             * No se recibe desde JavaScript.
             */
            $documento = trim(
                (string) $empleado['cedu_empl']
            );

            if ($documento === '') {
                responderTicketsSistemas(
                    false,
                    'El empleado no tiene documento registrado.',
                    null,
                    422
                );
            }

            /*
             * Consultar API de Control de Equipos.
             */
            $respuestaApi = CurlController::requestPreoperacional(
                'op=misTickets&documento=' . rawurlencode($documento),
                'GET'
            );

            $resultado = decodificarRespuestaTicketsApi(
                $respuestaApi
            );

            if (empty($resultado['success'])) {
                responderTicketsSistemas(
                    false,
                    $resultado['message'] ?? 'No fue posible consultar los tickets.',
                    null,
                    502
                );
            }

            responderTicketsSistemas(
                true,
                $resultado['message'] ?? 'Tickets consultados correctamente.',
                $resultado['data'] ?? array()
            );

            break;

        /*
 * =====================================================
 * DETALLE DEL TICKET
 * =====================================================
 */
        case "detalle":

            validarMetodoTicketsSistemas("GET");

            $ticketId = isset($_GET["id"])
                ? filter_var($_GET["id"], FILTER_VALIDATE_INT)
                : false;

            if (!$ticketId) {
                responderTicketsSistemas(
                    false,
                    "El ticket indicado no es válido.",
                    null,
                    422
                );
            }

            $empleado = $modelo->obtenerEmpleadoSesion(
                (int) $_SESSION["user_id"]
            );

            if ($empleado === null) {
                responderTicketsSistemas(
                    false,
                    "No se encontró un empleado asociado al usuario.",
                    null,
                    404
                );
            }

            $documento = trim(
                (string) $empleado["cedu_empl"]
            );

            if ($documento === "") {
                responderTicketsSistemas(
                    false,
                    "El empleado no tiene documento registrado.",
                    null,
                    422
                );
            }

            $respuestaApi = CurlController::requestPreoperacional(
                "op=detalle&id="
                    . (int) $ticketId
                    . "&documento="
                    . rawurlencode($documento),
                "GET"
            );

            $resultado = decodificarRespuestaTicketsApi(
                $respuestaApi
            );

            if (empty($resultado["success"])) {
                responderTicketsSistemas(
                    false,
                    $resultado["message"] ?? "No fue posible consultar el ticket.",
                    null,
                    404
                );
            }

            responderTicketsSistemas(
                true,
                $resultado["message"] ?? "Detalle consultado correctamente.",
                $resultado["data"] ?? null
            );

            break;

        default:
            responderTicketsSistemas(
                false,
                'La operación solicitada no existe.',
                null,
                404
            );

            break;
    }
} catch (Throwable $error) {
    error_log(
        'Tickets Sistemas Control Personal: '
            . $error->getMessage()
    );

    responderTicketsSistemas(
        false,
        'Se presentó un error procesando la solicitud.',
        null,
        500
    );
}
