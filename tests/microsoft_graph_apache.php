<?php

// Permitir la prueba únicamente desde el equipo local.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Verificar las variables sin mostrar sus valores.
$variables = [
    'MS_GRAPH_TENANT_ID',
    'MS_GRAPH_CLIENT_ID',
    'MS_GRAPH_CLIENT_SECRET'
];

$resultado = [];

foreach ($variables as $variable) {
    $valor = getenv($variable);
    $resultado[$variable] = $valor !== false && $valor !== '';
}

echo json_encode([
    'estado' => 'verificacion_apache',
    'variables' => $resultado
]);