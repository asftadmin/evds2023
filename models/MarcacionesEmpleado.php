<?php

class MarcacionesEmpleado extends Conectar
{
    private $consultar;

    public function __construct(callable $consultar = null)
    {
        $this->consultar = $consultar ?: array('CurlController', 'requestBiotime');
    }

    public function empleado_por_usuario($usuario_id)
    {
        $db = parent::Conexion();
        $stmt = $db->prepare('SELECT e.id_empl, e.cedu_empl, e.nomb_empl,
                c.nomb_carg AS cargo, g.nomb_grem AS grupo
            FROM empleados e
            LEFT JOIN cargo c ON c.codi_carg = e.carg_empl
            LEFT JOIN grupoempleados g ON g.codi_grem = c.grem_carg
            WHERE e.user_empl = ? AND e.esta_empl = 1');
        $stmt->execute(array($usuario_id));
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($empleados) !== 1) {
            throw new DomainException('El usuario debe tener un único empleado activo asociado.');
        }
        return $empleados[0];
    }

    public function consultar_propias($usuario_id, $desde, $hasta)
    {
        $empleado = $this->empleado_por_usuario($usuario_id);
        $documento = self::documento($empleado['cedu_empl']);
        if ($documento === '') throw new DomainException('El empleado no tiene un documento válido para consultar BioTime.');
        $marcaciones = array();
        $esperadas = null;
        $recibidas = 0;
        $ids = array();
        $pagina = 1;
        do {
            $respuesta = call_user_func($this->consultar, http_build_query(array(
                'start_date' => $desde, 'end_date' => $hasta, 'emp_code' => $documento,
                'departments' => 1, 'areas' => 2, 'page' => $pagina, 'page_size' => 1000
            )), 'GET');
            if (!is_object($respuesta) || !isset($respuesta->data, $respuesta->count) ||
                !is_array($respuesta->data) || !ctype_digit((string) $respuesta->count)) {
                throw new RuntimeException('BioTime no entregó una respuesta válida. Intente nuevamente.');
            }
            $total = (int) $respuesta->count;
            if ($total > 20000) throw new RuntimeException('La consulta BioTime es demasiado extensa. Seleccione un periodo más corto.');
            if (($esperadas !== null && $esperadas !== $total) ||
                (empty($respuesta->data) && $recibidas < $total)) {
                throw new RuntimeException('Las marcaciones cambiaron o la consulta quedó incompleta. Consulte nuevamente.');
            }
            $esperadas = $total;
            $recibidas += count($respuesta->data);
            if ($recibidas > $total) throw new RuntimeException('La cantidad de marcaciones BioTime no es consistente.');
            foreach ($respuesta->data as $fila) {
                if (!is_object($fila) || !isset($fila->emp_code)) {
                    throw new RuntimeException('BioTime devolvió una marcación incompleta.');
                }
                // BioTime puede ignorar emp_code; filtrar antes de transformar o devolver datos.
                if (self::documento($fila->emp_code) !== $documento) continue;
                if (isset($fila->id)) {
                    $id = (string) $fila->id;
                    if (isset($ids[$id])) throw new RuntimeException('BioTime repitió registros entre páginas. Consulte nuevamente.');
                    $ids[$id] = true;
                }
                $instante = self::instante($fila);
                $fecha = $instante->format('Y-m-d');
                if ($fecha < $desde || $fecha > $hasta) continue;
                $marcaciones[] = array('fecha' => $fecha, 'hora' => $instante->format('H:i:s'),
                    'segundos' => $instante->getTimestamp());
            }
            ++$pagina;
        } while ($recibidas < $esperadas);
        $datos = self::organizar($marcaciones);
        $datos['empleado'] = array('nombre' => $empleado['nomb_empl'], 'cargo' => $empleado['cargo'], 'grupo' => $empleado['grupo']);
        usort($marcaciones, function ($a, $b) { return $b['segundos'] <=> $a['segundos']; });
        $datos['detalle'] = array();
        foreach ($marcaciones as $fila) {
            $datos['detalle'][] = array('fecha' => $fila['fecha'], 'hora' => $fila['hora'],
                'empleado' => $empleado['nomb_empl'], 'cargo' => $empleado['cargo'], 'grupo' => $empleado['grupo']);
        }
        $datos['periodo'] = array('desde' => $desde, 'hasta' => $hasta);
        return $datos;
    }

    private static function documento($valor)
    {
        return preg_replace('/\D+/', '', trim((string) $valor));
    }

    private static function instante($fila)
    {
        $fecha = isset($fila->att_date) ? trim((string) $fila->att_date) : '';
        $hora = isset($fila->punch_time) ? trim((string) $fila->punch_time) : '';
        if (preg_match('/^\d{2}:\d{2}$/', $hora)) $hora .= ':00';
        if (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $hora)) {
            $texto = str_replace('T', ' ', $hora);
        } else {
            $texto = $fecha . ' ' . $hora;
        }
        $instante = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $texto, new DateTimeZone('America/Bogota'));
        if (!$instante || $instante->format('Y-m-d H:i:s') !== $texto) {
            throw new RuntimeException('BioTime devolvió una fecha u hora no válida para sus marcaciones.');
        }
        return $instante;
    }

    private static function duracion($segundos)
    {
        return sprintf('%02d:%02d:%02d', floor($segundos / 3600), floor(($segundos % 3600) / 60), $segundos % 60);
    }

    private static function organizar(array $marcaciones)
    {
        usort($marcaciones, function ($a, $b) { return $a['segundos'] <=> $b['segundos']; });
        $dias = array();
        foreach ($marcaciones as $fila) $dias[$fila['fecha']][] = $fila;
        $resumen = array();
        $pares = 0;
        $incompletos = 0;
        foreach ($dias as $fecha => $filas) {
            $cantidad = count($filas);
            $primera = $filas[0];
            $ultima = $filas[$cantidad - 1];
            $resumen[] = array('fecha' => $fecha, 'entrada' => $primera['hora'],
                'salida' => $cantidad > 1 ? $ultima['hora'] : null,
                'tiempo_bruto' => $cantidad > 1 ? self::duracion($ultima['segundos'] - $primera['segundos']) : null,
                'marcaciones' => $cantidad, 'observacion' => $cantidad % 2 ? 'Cantidad impar de marcaciones; revisar.' : '');
            $pares += intdiv($cantidad, 2);
            $incompletos += $cantidad % 2;
        }
        return array('resumen' => $resumen,
            'totales' => array('marcaciones' => count($marcaciones), 'dias' => count($dias),
                'pares' => $pares, 'incompletos' => $incompletos));
    }
}
