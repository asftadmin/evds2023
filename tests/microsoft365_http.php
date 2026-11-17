<?php
// Prueba el controlador real con modelos/Graph sintéticos, sin sesiones o datos productivos.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') === '--caso') {
    $caso = $argv[2];
    $_SERVER['REQUEST_METHOD'] = $caso === 'metodo' ? 'GET' : 'POST';
    $_SESSION = $caso === 'sin_sesion' ? [] : ['user_id' => 90, 'microsoft365_csrf' => 'csrf-prueba'];
    $_GET['op'] = in_array($caso, ['guardar', 'editar', 'sin_cambios'], true) ? ($caso === 'sin_cambios' ? 'editar' : $caso) : 'consultar';
    if ($caso === 'op_invalida') { $_GET['op'] = 'borrar'; }
    $_POST = ['csrf_token' => $caso === 'csrf' ? 'incorrecto' : 'csrf-prueba'];
    // Conserva solamente estado HTTP; no mezcla el diagnóstico con el cuerpo JSON.
    register_shutdown_function(static function () { fwrite(STDERR, '\nHTTP=' . http_response_code()); });
    class Usuario {
        /** Simula el permiso existente, incluida su denegación explícita. */
        public function autorizado_microsoft365($id) { return $GLOBALS['caso'] !== 'sin_permiso'; }
        /** Simula un esquema válido sin consultar PostgreSQL. */
        public function verificar_esquema_microsoft365() {}
        /** La validación de valores tiene su cobertura en microsoft365.php. */
        public static function validar_vinculacion_microsoft365($datos) { return $datos; }
        /** Devuelve una comparación sintética, nunca registros reales. */
        public function consultar_microsoft365($cuentas) { return ['registros' => [], 'indicadores' => ['total' => 0]]; }
        /** Verifica que escribir no pueda prescindir de la consulta Graph del controlador. */
        public function vincular_microsoft365($actor, $op, $entrada, $cuentas) {
            if (empty($GLOBALS['graph_consultado'])) { throw new RuntimeException('Faltó verificar Graph.'); }
            return $this->consultar_microsoft365($cuentas) + ['sin_cambios' => $GLOBALS['caso'] === 'sin_cambios'];
        }
    }
    class CurlController {
        /** Comprueba recurso/método y simula fallos remotos sin filtrar su mensaje. */
        public static function requestMicrosoftGraph($url, $method) {
            if ($url !== 'users?$select=id,displayName,mail,userPrincipalName' || $method !== 'GET') { throw new RuntimeException('Recurso incorrecto.'); }
            if ($GLOBALS['caso'] === 'graph_error') { throw new RuntimeException('secreto-que-no-debe-salir'); }
            $GLOBALS['graph_consultado'] = true;
            return $GLOBALS['caso'] === 'graph_incompleto' ? (object) ['value' => [], '@odata.nextLink' => 'pendiente'] : (object) ['value' => []];
        }
    }
    $codigo = file_get_contents(__DIR__ . '/../controller/microsoft365.php');
    $codigo = preg_replace('/^require_once .*;\r?$/m', '', $codigo);
    // Los errores se capturan fuera de eval para conservar el comportamiento PHP de excepciones.
    eval(substr($codigo, 5));
    exit;
}
$casos = ['sin_sesion' => 401, 'sin_permiso' => 403, 'metodo' => 405, 'csrf' => 403,
    'op_invalida' => 400, 'graph_error' => 502, 'graph_incompleto' => 502,
    'consultar' => 200, 'guardar' => 200, 'editar' => 200, 'sin_cambios' => 200];
foreach ($casos as $caso => $esperado) {
    $proceso = proc_open([PHP_BINARY, __FILE__, '--caso', $caso], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
    $salida = stream_get_contents($pipes[1]); $errores = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); proc_close($proceso);
    $json = json_decode($salida, true, 512, JSON_THROW_ON_ERROR);
    if (!str_contains($errores, 'HTTP=' . $esperado) || $json['status'] !== ($esperado === 200 ? 'success' : 'error')
        || str_contains($salida, 'secreto-que-no-debe-salir')) { throw new RuntimeException('Falló HTTP/JSON: ' . $caso); }
}
echo 'OK: ' . count($casos) . " escenarios HTTP/JSON del controlador real.\n";
