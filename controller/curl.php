<?php

class CurlController
{
    /**
     * Obtiene un token de aplicación; las credenciales solo se leen del entorno PHP/Apache.
     * El token permanece en memoria y nunca se incluye en errores o registros.
     */
    private static function obtenerTokenMicrosoftGraph()
    {
        $credenciales = array();
        foreach (array('MS_GRAPH_TENANT_ID', 'MS_GRAPH_CLIENT_ID', 'MS_GRAPH_CLIENT_SECRET') as $nombre) {
            $valor = getenv($nombre);
            if ($valor === false || trim($valor) === '') {
                throw new RuntimeException('Falta configurar ' . $nombre . ' en el entorno de PHP.');
            }
            $credenciales[$nombre] = $valor;
        }

        // Solo se admite un identificador de tenant o dominio, nunca segmentos de URL.
        $tenant = trim($credenciales['MS_GRAPH_TENANT_ID']);
        if (!preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9.-]*\z/', $tenant) || $tenant === '..') {
            throw new RuntimeException('MS_GRAPH_TENANT_ID tiene un formato inválido.');
        }
        $cuerpo = http_build_query(array(
            'client_id' => trim($credenciales['MS_GRAPH_CLIENT_ID']),
            'client_secret' => $credenciales['MS_GRAPH_CLIENT_SECRET'],
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials'
        ), '', '&', PHP_QUERY_RFC3986);
        $respuesta = self::ejecutarSolicitudMicrosoftGraph(
            'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token',
            'POST',
            array('Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'),
            $cuerpo,
            'autenticación'
        );
        if (!isset($respuesta->access_token) || !is_string($respuesta->access_token)
            || !preg_match('/\A[A-Za-z0-9._~+\/-]+=*\z/', $respuesta->access_token)) {
            throw new RuntimeException('Microsoft Graph no entregó un access_token válido.');
        }
        return $respuesta->access_token;
    }

    /**
     * Consulta un recurso relativo a v1.0 o una URL HTTPS autorizada.
     * $method: solo GET en HU-01. $data: reservado para futuros cuerpos; debe ser null.
     * Devuelve stdClass como las consultas existentes: colecciones en ->value (array de
     * objetos), metadatos de la primera página y sin nextLink al terminar; objetos
     * individuales sin cambios. Ante cualquier fallo lanza excepción, sin salida HTTP.
     */
    public static function requestMicrosoftGraph($url, $method, $data = null)
    {
        try {
            if (!is_string($method) || strtoupper($method) !== 'GET' || $data !== null) {
                throw new InvalidArgumentException('Esta fase de Microsoft Graph admite únicamente GET sin cuerpo.');
            }
            $siguiente = self::validarUrlMicrosoftGraph($url);
            $token = self::obtenerTokenMicrosoftGraph();
            $renovado = false;
            $visitadas = array();
            $resultado = null;

            // No se entrega ningún resultado hasta completar todas las páginas.
            while ($siguiente !== null) {
                $clave = hash('sha256', $siguiente);
                if (isset($visitadas[$clave]) || count($visitadas) >= 10000) {
                    throw new RuntimeException('Paginación de Microsoft Graph repetida o fuera del límite de seguridad.');
                }
                $visitadas[$clave] = true;
                try {
                    $pagina = self::ejecutarSolicitudMicrosoftGraph(
                        $siguiente, 'GET', array('Accept: application/json', 'Authorization: Bearer ' . $token)
                    );
                } catch (RuntimeException $e) {
                    // Un token caducado se renueva una sola vez por consulta, en la misma página.
                    if ($e->getCode() !== 401 || $renovado) {
                        throw $e;
                    }
                    $token = self::obtenerTokenMicrosoftGraph();
                    $renovado = true;
                    $pagina = self::ejecutarSolicitudMicrosoftGraph(
                        $siguiente, 'GET', array('Accept: application/json', 'Authorization: Bearer ' . $token)
                    );
                }

                $esColeccion = isset($pagina->value) && is_array($pagina->value);
                $tieneSiguiente = property_exists($pagina, '@odata.nextLink');
                if (($resultado !== null || $tieneSiguiente) && !$esColeccion) {
                    throw new RuntimeException('Microsoft Graph devolvió una página sin una colección válida.');
                }
                if ($resultado === null) {
                    $resultado = $pagina;
                } else {
                    foreach ($pagina->value as $registro) {
                        $resultado->value[] = $registro;
                    }
                }
                if (!$esColeccion) {
                    return $resultado;
                }

                // nextLink se valida pero se envía literalmente, incluido su skiptoken.
                $siguiente = $tieneSiguiente
                    ? self::validarUrlMicrosoftGraph($pagina->{'@odata.nextLink'}, true) : null;
                unset($resultado->{'@odata.nextLink'});
            }
            return $resultado;
        } catch (RuntimeException | InvalidArgumentException $e) {
            // Solo mensajes locales controlados: nunca cuerpo remoto, URL, token ni curl_error().
            error_log('[Microsoft Graph] ' . $e->getMessage());
            throw $e;
        }
    }

    /** Valida destino HTTPS y v1.0; los enlaces de paginación deben ser absolutos. */
    private static function validarUrlMicrosoftGraph($url, $absoluta = false)
    {
        if (!is_string($url) || $url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new InvalidArgumentException('URL de Microsoft Graph inválida.');
        }
        if (!preg_match('/\Ahttps:\/\//i', $url)) {
            if ($absoluta || str_starts_with($url, '//') || preg_match('/\A[a-z][a-z0-9+.-]*:/i', $url)) {
                throw new InvalidArgumentException('URL de Microsoft Graph no autorizada.');
            }
            $url = 'https://graph.microsoft.com/v1.0/' . ltrim($url, '/');
        }
        $partes = parse_url($url);
        if ($partes === false || strtolower($partes['scheme'] ?? '') !== 'https'
            || strtolower($partes['host'] ?? '') !== 'graph.microsoft.com'
            || isset($partes['user']) || isset($partes['pass']) || isset($partes['fragment'])
            || (isset($partes['port']) && $partes['port'] !== 443)
            || !str_starts_with($partes['path'] ?? '', '/v1.0/')
            || preg_match('~(?:^|/|\\\\)\.{1,2}(?:/|\\\\|$)~', rawurldecode($partes['path'] ?? ''))) {
            throw new InvalidArgumentException('URL de Microsoft Graph no autorizada.');
        }
        return $url;
    }

    /**
     * Transporte privado compartido por OAuth y Graph: HTTPS verificado, sin redirecciones.
     * Hasta tres reintentos para 429/5xx; Retry-After (segundos o fecha HTTP) se respeta
     * completo. Si supera el presupuesto total de espera de 60 s, se aborta sin adelantarlo.
     */
    private static function ejecutarSolicitudMicrosoftGraph($url, $method, $headers, $cuerpo = null, $etapa = 'consulta')
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('Microsoft Graph requiere la extensión cURL de PHP.');
        }
        $esperado = 0;
        for ($intento = 0; $intento <= 3; $intento++) {
            $retryAfter = null;
            $curl = curl_init();
            if ($curl === false) {
                throw new RuntimeException('No fue posible iniciar cURL para Microsoft Graph.');
            }
            try {
                $opciones = array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_HTTPHEADER => $headers,
                    // Solo se captura Retry-After; no se registran otros encabezados.
                    CURLOPT_HEADERFUNCTION => static function ($handle, $linea) use (&$retryAfter) {
                        if (stripos($linea, 'HTTP/') === 0) {
                            $retryAfter = null;
                        } elseif (stripos($linea, 'Retry-After:') === 0) {
                            $retryAfter = trim(substr($linea, 12));
                        }
                        return strlen($linea);
                    }
                );
                if ($cuerpo !== null) {
                    $opciones[CURLOPT_POSTFIELDS] = $cuerpo;
                }
                if (!curl_setopt_array($curl, $opciones)) {
                    throw new RuntimeException('No fue posible configurar cURL para Microsoft Graph.');
                }
                $texto = curl_exec($curl);
                $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $error = curl_errno($curl);
            } finally {
                curl_close($curl);
            }
            if ($texto === false) {
                throw new RuntimeException('Error de conexión Microsoft Graph (' . $etapa . ', cURL ' . $error . ').');
            }

            // Los errores remotos no se propagan: podrían contener secretos o datos personales.
            if ($codigo === 429 || ($codigo >= 500 && $codigo <= 599)) {
                $demora = 2 ** $intento;
                if ($retryAfter !== null) {
                    $fecha = strtotime($retryAfter);
                    if (ctype_digit($retryAfter)) {
                        $demora = (float) $retryAfter;
                    } elseif ($fecha !== false) {
                        $demora = max(0, $fecha - time());
                    }
                }
                if ($intento < 3 && $demora <= 60 - $esperado) {
                    error_log('[Microsoft Graph] Reintento ' . ($intento + 1) . ' (' . $etapa . ', HTTP ' . $codigo . ').');
                    sleep((int) $demora);
                    $esperado += $demora;
                    continue;
                }
                throw new RuntimeException('Microsoft Graph agotó los reintentos o la espera permitida (' . $etapa . ', HTTP ' . $codigo . ').', $codigo);
            }
            if ($codigo < 200 || $codigo >= 300) {
                throw new RuntimeException('Microsoft Graph rechazó la solicitud (' . $etapa . ', HTTP ' . $codigo . ').', $codigo);
            }
            try {
                $respuesta = json_decode($texto, false, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new RuntimeException('Microsoft Graph devolvió JSON inválido (' . $etapa . ').');
            }
            if (!$respuesta instanceof stdClass || property_exists($respuesta, 'error')) {
                throw new RuntimeException('Microsoft Graph devolvió una respuesta inesperada (' . $etapa . ').');
            }
            return $respuesta;
        }
    }

    /* PETICIONES A LA API */

    public static function requestEstandar($url, $method)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://servicios.siesacloud.com/api/connekta/v3/ejecutarconsulta?' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'ConniKey: a3e1f7ae5d9a22640f349486116d7471',
                'ConniToken: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJodHRwOi8vc2NoZW1hcy54bWxzb2FwLm9yZy93cy8yMDA1LzA1L2lkZW50aXR5L2NsYWltcy9uYW1laWRlbnRpZmllciI6IjI1NjRlNjJkLWU0MTctNDJmYS05YjU0LWNhMDFmOTE2MWUyZCIsImh0dHA6Ly9zY2hlbWFzLm1pY3Jvc29mdC5jb20vd3MvMjAwOC8wNi9pZGVudGl0eS9jbGFpbXMvcHJpbWFyeXNpZCI6IjMxMzc0N2Q4LTdhY2EtNGUwZi1iYWZlLTlkZTgyOWJmZjk1OCJ9.3ZWqrum9QPxUizi_qvN5pc0EIlxMPnSRCb5mVCPOzWU'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response);
        return $response;
    }

    public static function requestBiotime($url, $method)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://172.16.1.10/att/api/transactionReport/?' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: token e35e2f80fc6d903a7ba94c55ecc3d7f38c1d5e66'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    public static function requestPreoperacional($url, $method, $data = null)
    {
        $token = trim((string) getenv('TICKETS_API_TOKEN'));

        if ($token === '') {
            throw new RuntimeException(
                'No está configurado TICKETS_API_TOKEN.'
            );
        }

        $curl = curl_init();

        $headers = array(
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        );

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://localhost/preoperacional/api/tickets.php?' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method)
        ));

        if (strtoupper($method) === 'POST' && $data !== null) {
            $json = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE
            );

            if ($json === false) {
                curl_close($curl);

                throw new RuntimeException(
                    'No fue posible preparar la información para la API.'
                );
            }

            $headers[] = 'Content-Type: application/json';

            curl_setopt(
                $curl,
                CURLOPT_POSTFIELDS,
                $json
            );
        }

        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            $headers
        );

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);

            curl_close($curl);

            throw new RuntimeException(
                'Error comunicando con Control de Equipos: '
                . $error
            );
        }

        curl_close($curl);

        return $response;
    }
}

// Pruebas  CURLOPT_URL => 'https://serviciosqa.siesacloud.com/api/connekta/v3/ejecutarconsulta?'.$url,
// Real CURLOPT_URL => 'https://servicios.siesacloud.com/api/connekta/v3/ejecutarconsulta?'.$url,
