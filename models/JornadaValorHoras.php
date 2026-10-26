<?php

/** Valoración del reporte; conserva las cantidades y la clasificación de jornadas. */
class JornadaValorHoras
{
    const DIAS_MES = 30;
    const HORAS_DIA = 7.3333;
    const FACTORES = ['hed' => 1.25, 'hen' => 1.75, 'hedf' => 2.0, 'henf' => 2.5];

    public function consultar_salario($cedula)
    {
        $cedula = trim((string) $cedula);
        if ($cedula === '') {
            return null;
        }
        require_once __DIR__ . '/../controller/curl.php';
        $url = 'idCompania=6026&descripcion=asfaltart_salarioxempleado'
            . '&paginacion=' . urlencode('numPag=1|tamPag=100')
            . '&parametros=' . urlencode("cedula={$cedula}");
        try {
            $respuesta = CurlController::requestEstandar($url, 'GET');
            if (is_string($respuesta)) {
                $respuesta = json_decode($respuesta);
            }
            if (!isset($respuesta->codigo) || $respuesta->codigo != 0) {
                return null;
            }
            $filas = $respuesta->detalle->Datos ?? $respuesta->detalle->Table ?? [];
            $valor = str_replace([',', ' '], '', (string) ($filas[0]->c0550_salario ?? ''));
            return is_numeric($valor) && (float) $valor > 0 ? (float) $valor : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function valorar(array $filas)
    {
        $salarios = [];
        foreach ($filas as &$fila) {
            $cedula = (string) $fila['documento'];
            if (!array_key_exists($cedula, $salarios)) {
                $salarios[$cedula] = $this->consultar_salario($cedula);
            }
            $fila['ibc'] = $salarios[$cedula];
            $hora = $fila['ibc'] === null ? null : $fila['ibc'] / self::DIAS_MES / self::HORAS_DIA;
            $fila['valor_hora'] = $hora === null ? null : round($hora, 2);
            $fila['valor_rn'] = null;
            $fila['total'] = $hora === null ? null : 0;
            foreach (self::FACTORES as $codigo => $factor) {
                $valor = $hora === null ? null : round((float) $fila[$codigo] * $hora * $factor, 2);
                $fila['valor_' . $codigo] = $valor;
                if ($valor !== null) {
                    $fila['total'] += $valor;
                }
            }
            if ($fila['total'] !== null) {
                $fila['total'] = round($fila['total'], 2);
            }
            $fila['estado_ibc'] = $hora === null ? 'Salario no disponible en Siesa' : 'Consultado en Siesa';
        }
        unset($fila);
        return $filas;
    }
}
