<?php

// Aísla advertencias/salidas heredadas para garantizar JSON aun si la conexión usa die().
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$m365Respondido = false;

/**
 * Emite una respuesta consistente y descarta cualquier salida accidental.
 */
function responderMicrosoft365($status, $message, $data = null, $codigo = 200)
{
    global $m365Respondido;
    $m365Respondido = true;
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($codigo);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// No registra mensajes remotos, consultas, argumentos de excepciones ni datos personales.
register_shutdown_function(static function () {
    if (!$GLOBALS['m365Respondido']) {
        responderMicrosoft365('error', 'No fue posible completar la operación.', null, 500);
    }
});
set_exception_handler(static function ($e) {
    if ($e instanceof InvalidArgumentException || $e instanceof DomainException) {
        responderMicrosoft365('error', $e->getMessage(), null, $e instanceof DomainException ? 409 : 400);
    }
    error_log('[Microsoft 365] Operación fallida: ' . get_class($e));
    responderMicrosoft365('error', 'No fue posible completar la operación. Intente consultar nuevamente.', null, 500);
});

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/curl.php';

// Cada petición comprueba identidad, rol actual y permiso del menú administrativo.
$actorId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$actorId) {
    responderMicrosoft365('error', 'Debe iniciar sesión.', null, 401);
}
$modelo = new Usuario();
if (!$modelo->autorizado_microsoft365($actorId)) {
    responderMicrosoft365('error', 'No tiene permiso para administrar Microsoft 365.', null, 403);
}
$op = $_GET['op'] ?? '';
if (!is_string($op) || !in_array($op, ['consultar', 'guardar', 'editar'], true)) {
    responderMicrosoft365('error', 'Operación no válida.', null, 400);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderMicrosoft365('error', 'Debe utilizar POST.', null, 405);
}
$csrf = $_POST['csrf_token'] ?? null;
if (!is_string($csrf) || empty($_SESSION['microsoft365_csrf']) || !hash_equals($_SESSION['microsoft365_csrf'], $csrf)) {
    responderMicrosoft365('error', 'La sesión del formulario venció. Recargue la página.', null, 403);
}
$entrada = $op === 'consultar' ? [] : Usuario::validar_vinculacion_microsoft365($_POST);
$modelo->verificar_esquema_microsoft365();

// No bloquear la sesión durante OAuth/paginación; la BD serializa escrituras entre sesiones.
session_write_close();
try {
    $graph = CurlController::requestMicrosoftGraph('users?$select=id,displayName,mail,userPrincipalName', 'GET');
    if (!isset($graph->value) || !is_array($graph->value) || isset($graph->{'@odata.nextLink'})) {
        throw new RuntimeException('Colección incompleta.');
    }
} catch (Throwable $e) {
    error_log('[Microsoft 365] Consulta Graph fallida: ' . get_class($e));
    responderMicrosoft365('error', 'No fue posible consultar Microsoft 365. No se guardaron cambios; intente nuevamente.', null, 502);
}

// La consulta no escribe. Guardar/Editar revalidan en transacción con las cuentas recién obtenidas.
switch ($op) {
    case 'consultar':
        responderMicrosoft365('success', 'Comparación completada.', $modelo->consultar_microsoft365($graph->value));
    case 'guardar':
    case 'editar':
        $resultado = $modelo->vincular_microsoft365($actorId, $op, $entrada, $graph->value);
        responderMicrosoft365('success', $resultado['sin_cambios']
            ? 'La vinculación ya está actualizada; no se realizaron cambios.'
            : 'Vinculación guardada correctamente.', $resultado);
}
