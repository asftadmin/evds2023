<?php

// En produccion, defina EVDS_API_KEY en el entorno del servidor web.
// La clave de respaldo es solo para pruebas locales y debe cambiarse.
$apiKeyFromEnvironment = getenv('EVDS_API_KEY');

define(
    'EVDS_API_KEY',
    $apiKeyFromEnvironment !== false && $apiKeyFromEnvironment !== ''
        ? $apiKeyFromEnvironment
        : '_95UfYULG1XcqtYVd1CMynlM5uXx1hqz2PM1vZd18MOywHkFEs'
);
