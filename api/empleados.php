<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

ob_start();
$respuestaEnviada = false;

register_shutdown_function(function () use (&$respuestaEnviada) {
    if ($respuestaEnviada) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode(
        array(
            'success' => false,
            'message' => 'Se presentó un error interno en la API.'
        ),
        JSON_UNESCAPED_UNICODE
    );
});

require_once __DIR__ . '/controller/empleado_controller.php';

$controller = new EmpleadoController();
$respuesta = $controller->consultar($_SERVER, $_GET);

while (ob_get_level() > 0) {
    ob_end_clean();
}

http_response_code($respuesta['status']);

if ($respuesta['status'] === 405) {
    header('Allow: GET');
}

$json = json_encode($respuesta['body'], JSON_UNESCAPED_UNICODE);

if ($json === false) {
    http_response_code(500);
    $json = '{"success":false,"message":"Se presentó un error interno en la API."}';
}

$respuestaEnviada = true;
echo $json;
