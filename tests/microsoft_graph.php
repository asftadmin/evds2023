<?php
namespace MicrosoftGraphPruebas;

// Solo CLI: no se crea un endpoint de diagnóstico accesible por Apache.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

/** Comprueba contratos observables sin mostrar respuestas, usuarios ni credenciales. */
function comprobar($condicion, $mensaje)
{
    if (!$condicion) { throw new \RuntimeException($mensaje); }
    $GLOBALS['comprobaciones']++;
}
$GLOBALS['comprobaciones'] = 0;

// Prueba real opcional: usa la clase original y el entorno del proceso, solo lectura.
if (in_array('--live', $argv, true)) {
    require_once __DIR__ . '/../controller/curl.php';
    try {
        $recurso = 'users?$select=id,displayName,mail,userPrincipalName';
        $normal = \CurlController::requestMicrosoftGraph($recurso, 'GET');
        $paginada = \CurlController::requestMicrosoftGraph($recurso . '&$top=2', 'GET');
        comprobar(isset($normal->value, $paginada->value), 'No se recibieron colecciones.');
        comprobar(count($paginada->value) > 2, 'No hay suficientes usuarios para confirmar varias páginas.');
        $ids = array();
        foreach ($paginada->value as $usuario) {
            foreach (array('id', 'displayName', 'mail', 'userPrincipalName') as $campo) {
                comprobar(property_exists($usuario, $campo), 'Falta un campo seleccionado.');
            }
            $ids[] = $usuario->id;
        }
        $esperados = array_column($normal->value, 'id');
        sort($ids);
        sort($esperados);
        comprobar($ids === $esperados, 'Los usuarios cambiaron entre consultas o la paginación no coincide.');
        comprobar(count(array_unique($ids)) === count($ids), 'Se recibieron usuarios duplicados.');
        comprobar(!isset($paginada->{'@odata.nextLink'}), 'La colección no terminó.');
        echo 'OK: conexión real, campos y paginación; usuarios=' . count($ids) . PHP_EOL;
        exit(0);
    } catch (\Throwable $e) {
        // Los mensajes de la clase original son controlados; no imprimir trazas ni argumentos.
        fwrite(STDERR, 'Validación real pendiente/fallida: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

// Evalúa el archivo original dentro de un namespace de pruebas para interceptar cURL.
// No se modifica producción ni se permite tráfico de red durante las pruebas aisladas.
$codigo = file_get_contents(__DIR__ . '/../controller/curl.php');
eval('namespace MicrosoftGraphPruebas; use RuntimeException; use InvalidArgumentException; use JsonException; use stdClass; ' . substr($codigo, 5));

/** Prepara respuestas sintéticas y reinicia la observación del transporte. */
function preparar($respuestas)
{
    $GLOBALS['graph'] = array('cola' => $respuestas, 'peticiones' => array(), 'esperas' => array(), 'logs' => array(),
        'entorno' => array('MS_GRAPH_TENANT_ID' => 'tenant.example', 'MS_GRAPH_CLIENT_ID' => 'client-prueba',
            'MS_GRAPH_CLIENT_SECRET' => 'secreto-sintetico+&='));
}
/** Produce una respuesta simulada con encabezados y código HTTP configurables. */
function respuesta($datos, $http = 200, $retry = null)
{
    return array('texto' => is_string($datos) ? $datos : json_encode($datos), 'http' => $http, 'retry' => $retry);
}
/** Simula exclusivamente la lectura de las tres variables de esta integración. */
function getenv($nombre) { return $GLOBALS['graph']['entorno'][$nombre] ?? false; }
/** Conserva mensajes para comprobar que no contienen secretos. */
function error_log($mensaje) { $GLOBALS['graph']['logs'][] = $mensaje; return true; }
/** Evita esperas reales y conserva la política de reintentos observada. */
function sleep($segundos) { $GLOBALS['graph']['esperas'][] = $segundos; return 0; }
/** Crea un identificador aislado, sin sockets. */
function curl_init() { return new \stdClass(); }
/** Guarda opciones reales de producción para verificar el contrato de transporte. */
function curl_setopt_array($curl, $opciones) { $curl->opciones = $opciones; return true; }
/** Entrega la próxima respuesta y sus encabezados; falla si hay solicitudes imprevistas. */
function curl_exec($curl)
{
    comprobar(count($GLOBALS['graph']['cola']) > 0, 'Petición inesperada.');
    $GLOBALS['graph']['peticiones'][] = $curl->opciones;
    $curl->respuesta = array_shift($GLOBALS['graph']['cola']);
    $callback = $curl->opciones[CURLOPT_HEADERFUNCTION];
    $callback($curl, 'HTTP/1.1 ' . $curl->respuesta['http'] . "\r\n");
    if ($curl->respuesta['retry'] !== null) { $callback($curl, 'Retry-After: ' . $curl->respuesta['retry'] . "\r\n"); }
    return $curl->respuesta['texto'];
}
/** Expone el estado HTTP sintético del intento actual. */
function curl_getinfo($curl, $campo) { return $curl->respuesta['http']; }
/** Simula un timeout cuando la respuesta cURL es false. */
function curl_errno($curl) { return $curl->respuesta['texto'] === false ? 28 : 0; }
/** El doble no tiene recursos nativos que liberar. */
function curl_close($curl) {}
/** Ejecuta una consulta y exige un fallo controlado sin filtrar datos sensibles. */
function rechazar($url = 'users', $metodo = 'GET', $datos = null)
{
    try { CurlController::requestMicrosoftGraph($url, $metodo, $datos); }
    catch (\RuntimeException | \InvalidArgumentException $e) {
        comprobar(!str_contains($e->getMessage(), 'secreto-sintetico'), 'Se filtró el cuerpo remoto.');
        return;
    }
    throw new \RuntimeException('Se aceptó una solicitud o respuesta inválida.');
}

$token = respuesta(array('access_token' => 'token-sintetico'));
$usuarios = array((object) array('id' => '1', 'displayName' => 'Prueba', 'mail' => null, 'userPrincipalName' => 'prueba@example.test'));
$vacia = respuesta(array('value' => array()));
$next = 'https://graph.microsoft.com/v1.0/users?$skiptoken=A%2BB%2F%3D&$top=1';
ob_start();

// Autenticación, campos nulos, páginas vacías intermedias y preservación exacta del enlace.
preparar(array($token, respuesta(array('@odata.context' => 'contexto', 'value' => $usuarios, '@odata.nextLink' => $next)),
    respuesta(array('value' => array(), '@odata.nextLink' => $next . '0')), respuesta(array('value' => array((object) array('id' => '2'))))));
$resultado = CurlController::requestMicrosoftGraph('users?$select=id,displayName,mail,userPrincipalName', 'get');
comprobar(count($resultado->value) === 2 && $resultado->value[0]->mail === null, 'Consolidación incorrecta.');
comprobar($resultado->{'@odata.context'} === 'contexto' && !isset($resultado->{'@odata.nextLink'}), 'Metadatos incorrectos.');
$peticiones = $GLOBALS['graph']['peticiones'];
parse_str($peticiones[0][CURLOPT_POSTFIELDS], $formulario);
comprobar($formulario === array('client_id' => 'client-prueba', 'client_secret' => 'secreto-sintetico+&=',
    'scope' => 'https://graph.microsoft.com/.default', 'grant_type' => 'client_credentials'), 'Formulario OAuth incorrecto.');
comprobar($peticiones[0][CURLOPT_URL] === 'https://login.microsoftonline.com/tenant.example/oauth2/v2.0/token'
    && $peticiones[0][CURLOPT_CUSTOMREQUEST] === 'POST', 'Endpoint OAuth incorrecto.');
comprobar($peticiones[2][CURLOPT_URL] === $next && $peticiones[3][CURLOPT_URL] === $next . '0', 'nextLink fue modificado.');
foreach ($peticiones as $i => $opciones) {
    comprobar($opciones[CURLOPT_SSL_VERIFYPEER] && $opciones[CURLOPT_SSL_VERIFYHOST] === 2
        && !$opciones[CURLOPT_FOLLOWLOCATION] && $opciones[CURLOPT_PROTOCOLS] === CURLPROTO_HTTPS, 'Transporte inseguro.');
    comprobar($opciones[CURLOPT_CONNECTTIMEOUT] === 10 && $opciones[CURLOPT_TIMEOUT] === 30, 'Faltan timeouts.');
    if ($i > 0) { comprobar(in_array('Authorization: Bearer token-sintetico', $opciones[CURLOPT_HTTPHEADER], true), 'Token no reutilizado.'); }
}

// Objetos individuales, otros recursos y colecciones vacías mantienen su forma.
foreach (array(array('id' => 'individual'), array('value' => 'propiedad'), array('value' => array())) as $objeto) {
    preparar(array($token, respuesta($objeto)));
    comprobar(json_encode(CurlController::requestMicrosoftGraph('/groups/ejemplo', 'GET')) === json_encode($objeto), 'Cambió una respuesta individual o vacía.');
}

// Destinos externos, esquemas, credenciales en URL, traversal y métodos de escritura.
foreach (array('http://graph.microsoft.com/v1.0/users', 'https://evil.test/v1.0/users', '//evil.test/users',
    'https://graph.microsoft.com.evil.test/v1.0/users', 'https://x@graph.microsoft.com/v1.0/users',
    'https://graph.microsoft.com:444/v1.0/users', 'https://graph.microsoft.com/v1.0/users#fragment',
    '../users', '%2e%2e/users', "users\r\nX:1", 'https://graph.microsoft.com\\@evil.test/users', null) as $url) {
    preparar(array()); rechazar($url); comprobar(!$GLOBALS['graph']['peticiones'], 'URL inválida llegó a la red.');
}
foreach (array('POST', 'PATCH', 'DELETE', 'PUT') as $metodo) { preparar(array()); rechazar('users', $metodo); }
preparar(array()); rechazar('users', 'GET', array());
foreach (array('MS_GRAPH_TENANT_ID', 'MS_GRAPH_CLIENT_ID', 'MS_GRAPH_CLIENT_SECRET') as $nombre) {
    preparar(array()); unset($GLOBALS['graph']['entorno'][$nombre]); rechazar();
}
preparar(array()); $GLOBALS['graph']['entorno']['MS_GRAPH_TENANT_ID'] = '../common'; rechazar();

// Respuestas inválidas y errores HTTP no pueden convertirse en éxito ni filtrar cuerpos.
foreach (array(respuesta('{}'), respuesta('{'), respuesta(array('access_token' => "token\r\ninyectado")),
    respuesta('secreto-sintetico', 400), respuesta('secreto-sintetico', 401), respuesta('secreto-sintetico', 403)) as $fallo) {
    preparar(array($fallo)); rechazar();
}
foreach (array(respuesta('{'), respuesta('null'), respuesta('[]'), respuesta(array('error' => 'secreto-sintetico')),
    respuesta('secreto-sintetico', 400), respuesta('secreto-sintetico', 403), respuesta('redirect', 302),
    array('texto' => false, 'http' => 0, 'retry' => null)) as $fallo) {
    preparar(array($token, $fallo)); rechazar();
}
foreach (array(respuesta('{'), respuesta(array('id' => 'no-coleccion')), respuesta('denegado', 403)) as $fallo) {
    preparar(array($token, respuesta(array('value' => $usuarios, '@odata.nextLink' => $next)), $fallo)); rechazar();
}
foreach (array('https://evil.test/users', 'users?page=2', '', null, 42) as $enlace) {
    preparar(array($token, respuesta(array('value' => $usuarios, '@odata.nextLink' => $enlace)))); rechazar();
}
preparar(array($token, respuesta(array('value' => $usuarios, '@odata.nextLink' => $next)),
    respuesta(array('value' => $usuarios, '@odata.nextLink' => $next)))); rechazar();
comprobar(count($GLOBALS['graph']['peticiones']) === 3, 'Se descargó una página repetida.');

// Renovación por 401 sobre la página pendiente, sin duplicar registros ni bucles de tokens.
preparar(array($token, respuesta(array('value' => $usuarios, '@odata.nextLink' => $next)), respuesta('caducado', 401),
    respuesta(array('access_token' => 'token-renovado')), $vacia));
comprobar(count(CurlController::requestMicrosoftGraph('users', 'GET')->value) === 1, 'Renovación duplicó registros.');
comprobar(in_array('Authorization: Bearer token-renovado', $GLOBALS['graph']['peticiones'][4][CURLOPT_HTTPHEADER], true), 'No usó token renovado.');
preparar(array($token, respuesta('caducado', 401), $token, respuesta('caducado', 401))); rechazar();

// Límites de reintento, fechas HTTP, backoff y aborto sin acortar Retry-After.
foreach (array(429, 500, 503) as $estado) {
    preparar(array($token, respuesta('temporal', $estado, '2'), $vacia));
    CurlController::requestMicrosoftGraph('users', 'GET');
    comprobar($GLOBALS['graph']['esperas'] === array(2), 'No respetó Retry-After.');
}
preparar(array(respuesta('temporal', 429, '0'), $token, $vacia));
CurlController::requestMicrosoftGraph('users', 'GET');
comprobar($GLOBALS['graph']['esperas'] === array(0), 'OAuth no reintentó.');
preparar(array($token, respuesta('temporal', 429, gmdate('D, d M Y H:i:s', time() + 10) . ' GMT'), $vacia));
CurlController::requestMicrosoftGraph('users', 'GET');
comprobar($GLOBALS['graph']['esperas'][0] >= 9 && $GLOBALS['graph']['esperas'][0] <= 10, 'Fecha Retry-After incorrecta.');
preparar(array($token, respuesta('temporal', 429, '61'))); rechazar();
comprobar($GLOBALS['graph']['esperas'] === array(), 'Se adelantó un Retry-After largo.');
preparar(array($token, respuesta('temporal', 503), respuesta('temporal', 503), respuesta('temporal', 503), respuesta('temporal', 503))); rechazar();
comprobar($GLOBALS['graph']['esperas'] === array(1, 2, 4), 'Backoff o límite de reintentos incorrectos.');
preparar(array($token, respuesta('temporal', 429, '40'), respuesta('temporal', 429, '40'))); rechazar();
comprobar($GLOBALS['graph']['esperas'] === array(40), 'Excedió presupuesto de espera.');
comprobar(!str_contains(implode('\n', $GLOBALS['graph']['logs']), 'token-sintetico'), 'Log contiene token.');
comprobar(ob_get_clean() === '', 'El cliente contaminó la salida HTTP.');
echo 'OK: ' . $GLOBALS['comprobaciones'] . ' verificaciones aisladas de Microsoft Graph.' . PHP_EOL;
