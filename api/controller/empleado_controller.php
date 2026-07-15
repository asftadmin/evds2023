<?php

require_once dirname(__DIR__) . '/config/api_config.php';
require_once dirname(__DIR__) . '/model/EmpleadoApi.php';

class EmpleadoController
{
    private $modelo;

    public function __construct($modelo = null)
    {
        $this->modelo = $modelo === null ? new EmpleadoApi() : $modelo;
    }

    public function consultar($servidor, $parametros)
    {
        $metodo = isset($servidor['REQUEST_METHOD']) ? strtoupper($servidor['REQUEST_METHOD']) : '';

        if ($metodo !== 'GET') {
            return $this->respuesta(405, 'Método no permitido. Utilice GET.');
        }

        $clave = isset($servidor['HTTP_X_API_KEY']) ? trim($servidor['HTTP_X_API_KEY']) : '';

        if ($clave === '') {
            return $this->respuesta(401, 'Debe enviar la clave de acceso X-API-KEY.');
        }

        if (!hash_equals(EVDS_API_KEY, $clave)) {
            return $this->respuesta(401, 'La clave de acceso no es válida.');
        }

        $documento = isset($parametros['documento']) && !is_array($parametros['documento'])
            ? trim((string) $parametros['documento'])
            : '';

        $nombre = isset($parametros['nombre']) && !is_array($parametros['nombre'])
            ? trim((string) $parametros['nombre'])
            : '';

        if ($documento === '' && $nombre === '') {
            return $this->respuesta(400, 'Debe enviar el número de documento o el nombre.');
        }

        if ($documento !== '' && $nombre !== '') {
            return $this->respuesta(400, 'Envíe solamente documento o nombre, no ambos.');
        }

        if ($documento !== '' && !preg_match('/^[0-9]+$/D', $documento)) {
            return $this->respuesta(400, 'El documento solamente debe contener números.');
        }

        if ($nombre !== '' && strlen($nombre) > 200) {
            return $this->respuesta(400, 'El nombre no debe superar los 200 caracteres.');
        }

        try {
            $empleado = $documento !== ''
                ? $this->modelo->buscarPorDocumento($documento)
                : $this->modelo->buscarPorNombre($nombre);

            if ($empleado === null) {
                return $this->respuesta(404, 'El empleado no fue encontrado.');
            }

            if ((int) $empleado['estado'] !== 1) {
                return $this->respuesta(403, 'El empleado se encuentra inactivo.');
            }

            return array(
                'status' => 200,
                'body' => array(
                    'success' => true,
                    'message' => 'Empleado encontrado.',
                    'data' => array(
                        'documento' => (string) $empleado['documento'],
                        'nombre' => $this->texto($empleado['nombre']),
                        'correo' => $this->texto($empleado['correo']),
                        'cargo' => $this->texto($empleado['cargo']),
                        'area' => $this->texto($empleado['area']),
                        'activo' => true
                    )
                )
            );
        } catch (Throwable $error) {
            error_log('API empleados: ' . $error->getMessage());
            return $this->respuesta(500, 'Se presentó un error interno en la API.');
        }
    }

    private function respuesta($status, $mensaje)
    {
        return array(
            'status' => $status,
            'body' => array(
                'success' => false,
                'message' => $mensaje
            )
        );
    }

    private function texto($valor)
    {
        return $valor === null ? '' : (string) $valor;
    }
}
