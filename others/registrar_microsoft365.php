<?php

// Despliegue explícito por CLI; no permite registrar permisos mediante HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!in_array('--aplicar', $argv, true)) {
    fwrite(STDERR, "Uso: php others/registrar_microsoft365.php --aplicar\nRegistra únicamente el menú y permisos de HU-02; no vincula usuarios.\n");
    exit(1);
}
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Menu.php';
try {
    (new Usuario())->verificar_esquema_microsoft365();
    $resultado = (new Menu())->registrar_microsoft365();
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE), PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, "No se registró el módulo. Revise el esquema y la configuración administrativa.\n");
    exit(1);
}
