<?php

// Centraliza respuestas limpias y protección de las operaciones AJAX del módulo.
ob_start();
header('Content-Type: application/json; charset=utf-8');
$soporteRespuestaEnviada = false;

// Descarta salidas accidentales antes de responder JSON.
function responderJson($respuesta, $codigo = 200)
{
    global $soporteRespuestaEnviada;
    $soporteRespuestaEnviada = true;
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($codigo);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

// Conserva JSON incluso si la conexión compartida termina prematuramente con die().
register_shutdown_function(function () {
    global $soporteRespuestaEnviada;
    if (!$soporteRespuestaEnviada) {
        responderJson(['success' => false, 'mensaje' => 'No fue posible completar la operación.'], 500);
    }
});

// Traduce validaciones y conflictos; los detalles técnicos permanecen en el log.
set_exception_handler(function ($error) {
    if ($error instanceof InvalidArgumentException || $error instanceof DomainException) {
        responderJson(['success' => false, 'mensaje' => $error->getMessage()], 400);
    }
    if ($error instanceof PDOException && (string) $error->getCode() === '23505') {
        responderJson(['success' => false, 'mensaje' => 'Ya existe una configuración para este periodo.'], 409);
    }
    error_log('Soporte Nómina: ' . $error->getMessage());
    responderJson(['success' => false, 'mensaje' => 'No fue posible completar la operación.'], 500);
});

require_once __DIR__ . '/../config/conexion.php';

// Mantiene la autenticación de las vistas y verifica CSRF antes de cualquier escritura.
if (empty($_SESSION['user_id'])) {
    responderJson(['success' => false, 'mensaje' => 'Debe iniciar sesión.'], 401);
}

// Exige POST y el token emitido para las dos vistas del módulo.
function validarEscrituraSoporte()
{
    $token = $_POST['csrf_token'] ?? '';
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responderJson(['success' => false, 'mensaje' => 'Debe utilizar POST.'], 405);
    }
    if (!is_string($token) || empty($_SESSION['soporte_nomina_csrf'])
        || !hash_equals($_SESSION['soporte_nomina_csrf'], $token)) {
        responderJson(['success' => false, 'mensaje' => 'La sesión del formulario venció. Recargue la página.'], 403);
    }
}
